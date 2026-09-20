<?php
// app/Http/Controllers/Admin/ProductionOrderController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderMaterial;
use App\Models\Product;
use App\Models\BOM;
use App\Models\PurchaseItem;
use App\Models\Currency;
use App\Models\Sale;
use App\Models\Transaction;
use App\Services\SaleProfitService;
use App\Services\StockDeductionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class ProductionOrderController extends Controller
{
    /**
     * Display a listing of production orders.
     */
    public function index(Request $request)
    {
        $query = ProductionOrder::with(['product', 'bom', 'createdBy']);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->latest()->paginate(15);

        $stats = [
            'total' => ProductionOrder::count(),
            'pending' => ProductionOrder::where('status', 'pending')->count(),
            'in_progress' => ProductionOrder::where('status', 'in_progress')->count(),
            'completed' => ProductionOrder::where('status', 'completed')->count(),
            'cancelled' => ProductionOrder::where('status', 'cancelled')->count(),
        ];

        return view('admin.production-orders.index', compact('orders', 'stats'));
    }

    /**
     * Show the form for creating a new production order.
     */
    public function create()
    {
        $products = Product::finishedGoods()
            ->where('is_active', true)
            ->with('category')
            ->get();

        $boms = BOM::where('status', 'active')
            ->where('is_active', true)
            ->with('product')
            ->get();

        // Get sales that don't have production orders yet
        $availableSales = Sale::whereNull('production_order_id')
            ->where('status', 'confirmed')
            ->where('is_produced', false)
            ->with('customer')
            ->orderBy('created_at', 'desc')
            ->get();

        // ─── GET DEFAULT CURRENCY FOR DISPLAY ───
        $defaultCurrency = Currency::where('is_default', true)->first();
        $currencyCode = $defaultCurrency ? $defaultCurrency->code : 'AFN';
        $currencySymbol = $defaultCurrency ? $defaultCurrency->symbol : '؋';
        $exchangeRate = $defaultCurrency ? $defaultCurrency->exchange_rate : 66;

        return view('admin.production-orders.create', compact(
            'products',
            'boms',
            'availableSales',
            'currencyCode',
            'currencySymbol',
            'exchangeRate'
        ));
    }

    /**
     * Store a newly created production order.
     */

    public function store(Request $request)
    {
        // ─── LOG 1: Request received ───
        \Log::info('Production Order Store - Request Received', [
            'request_data' => $request->all(),
            'request_method' => $request->method(),
            'request_url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_id' => auth()->id(),
        ]);

        try {
            // ─── LOG 2: Validation starting ───
            \Log::info('Production Order Store - Validating request');

            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'bom_id' => 'required|exists:boms,id',
                'sale_id' => 'nullable|exists:sales,id',
                'quantity_ordered' => 'required|numeric|min:1',
                'start_date' => 'required|date|after_or_equal:today',
                'notes' => 'nullable|string',
            ]);

            // ─── LOG 3: Validation passed ───
            \Log::info('Production Order Store - Validation passed', [
                'validated_data' => $validated
            ]);

            DB::beginTransaction();

            // ─── LOG 4: Fetching BOM ───
            \Log::info('Production Order Store - Fetching BOM', [
                'bom_id' => $request->bom_id
            ]);

            $bom = BOM::with('items.material')->findOrFail($request->bom_id);

            \Log::info('Production Order Store - BOM found', [
                'bom_id' => $bom->id,
                'bom_code' => $bom->code,
                'bom_name' => $bom->name,
                'items_count' => $bom->items->count(),
            ]);

            // ─── LOG 5: Checking material availability ───
            \Log::info('Production Order Store - Checking material availability', [
                'bom_id' => $request->bom_id,
                'quantity' => $request->quantity_ordered,
                'sale_id' => $request->sale_id,
            ]);

            if ($request->sale_id) {
                // ─── SALE-LINKED: use the authoritative physical costing rules ───
                // (Same path as the Sale Profit Service and the create-page preview:
                // manual BOM snapshot + calculateStockKgPerUnit + landed USD/kg.)
                $sale = Sale::with([
                    'items', 'items.bom.items', 'items.bom.items.material',
                    'items.product',
                ])->find($request->sale_id);

                $saleProfitService = new SaleProfitService();
                $materialRows = $sale
                    ? $saleProfitService->productionMaterialRequirements($sale, (float) $request->quantity_ordered)
                    : [];

                $requirements = [];
                $shortages = [];
                $hasShortage = false;
                $totalMaterialCost = 0.0;
                $rollWeightBasisMissing = false;

                foreach ($materialRows as $r) {
                    $shortage = (float) $r['shortage'];
                    if ($shortage > 0) {
                        $hasShortage = true;
                        $shortages[] = [
                            'material_id' => $r['material_id'],
                            'material_name' => $r['material_name'],
                            'total_required' => $r['total_required'],
                            'available_stock' => $r['available_stock'],
                            'shortage' => $shortage,
                            'unit' => $r['unit'],
                        ];
                    }
                    if ($r['roll_weight_missing']) {
                        $rollWeightBasisMissing = true;
                    }

                    $requirements[] = [
                        'material_id' => $r['material_id'],
                        'material_name' => $r['material_name'],
                        'required_quantity' => $r['base_kg'],
                        'wastage_quantity' => $r['wastage_kg'],
                        'total_required' => $r['total_required'],
                        'available_stock' => $r['available_stock'],
                        'shortage' => $shortage,
                        'unit' => $r['unit'],
                        'cost_per_unit' => $r['cost_per_unit_usd'],
                        'total_cost' => $r['total_cost_usd'],
                        'roll_weight_missing' => $r['roll_weight_missing'],
                    ];
                    $totalMaterialCost += $r['total_cost_usd'];
                }

                $availability = [
                    'has_shortage' => $hasShortage,
                    'shortages' => $shortages,
                    'requirements' => $requirements,
                    'total_material_cost' => $totalMaterialCost,
                    'roll_weight_basis_missing' => $rollWeightBasisMissing,
                ];
            } else {
                $availability = $this->checkMaterialAvailability($bom, (float) $request->quantity_ordered);
            }

            \Log::info('Production Order Store - Material availability result', [
                'has_shortage' => $availability['has_shortage'],
                'total_material_cost' => $availability['total_material_cost'] ?? 0,
                'requirements_count' => count($availability['requirements'] ?? []),
                'shortages_count' => count($availability['shortages'] ?? []),
            ]);

            if (!empty($availability['roll_weight_basis_missing'])) {
                \Log::warning('Production Order Store - Roll/KG cost basis missing', [
                    'sale_id' => $request->sale_id,
                ]);

                DB::rollBack();
                return back()->with('error', 'Estimated production cost unavailable — a valid roll/KG cost basis is required. Please purchase/confirm a paper roll batch with a kg weight basis first.')->withInput();
            }

            if ($availability['has_shortage']) {
                $shortageMessage = 'The following materials have shortages:<br>';
                foreach ($availability['shortages'] as $shortage) {
                    $shortageMessage .= "- {$shortage['material_name']}: Need {$shortage['total_required']} {$shortage['unit']}, Available: {$shortage['available_stock']} {$shortage['unit']}<br>";
                }

                \Log::warning('Production Order Store - Material shortages detected', [
                    'shortages' => $availability['shortages']
                ]);

                DB::rollBack();
                return back()->with('error', $shortageMessage)->withInput();
            }

            // ─── LOG 6: Calculating costs ───
            $materialCost = $availability['total_material_cost'] ?? 0;
            $laborCost = $bom->labor_cost_per_unit * $request->quantity_ordered;
            $overheadCost = $bom->overhead_cost_per_unit * $request->quantity_ordered;
            $totalCost = $materialCost + $laborCost + $overheadCost;

            \Log::info('Production Order Store - Cost calculation', [
                'material_cost' => $materialCost,
                'labor_cost_per_unit' => $bom->labor_cost_per_unit,
                'labor_cost_total' => $laborCost,
                'overhead_cost_per_unit' => $bom->overhead_cost_per_unit,
                'overhead_cost_total' => $overheadCost,
                'total_cost' => $totalCost,
                'quantity' => $request->quantity_ordered,
            ]);

            // ─── LOG 7: Creating production order ───
            \Log::info('Production Order Store - Creating production order', [
                'order_number' => 'PROD-' . date('Y') . '-' . strtoupper(uniqid()),
                'product_id' => $request->product_id,
                'bom_id' => $request->bom_id,
                'quantity_ordered' => $request->quantity_ordered,
            ]);

            $order = ProductionOrder::create([
                'order_number' => 'PROD-' . date('Y') . '-' . strtoupper(uniqid()),
                'product_id' => $request->product_id,
                'bom_id' => $request->bom_id,
                'quantity_ordered' => $request->quantity_ordered,
                'quantity_produced' => 0,
                'status' => 'pending',
                'start_date' => $request->start_date,
                'created_by' => Auth::id(),
                'notes' => $request->notes ?? null,
                'total_material_cost' => $materialCost,
                'total_labor_cost' => $laborCost,
                'total_overhead_cost' => $overheadCost,
                'total_cost' => $totalCost,
            ]);

            \Log::info('Production Order Store - Production order created', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            // ─── LOG 8: Creating production order materials ───
            \Log::info('Production Order Store - Creating production order materials', [
                'materials_count' => count($availability['requirements'] ?? [])
            ]);

            foreach ($availability['requirements'] as $req) {
                $material = $order->materials()->create([
                    'product_id' => $req['material_id'],
                    'required_quantity' => $req['total_required'],
                    'available_quantity' => $req['available_stock'],
                    'shortage_quantity' => $req['shortage'],
                    'unit' => $req['unit'],
                    'cost_per_unit' => $req['cost_per_unit'],
                    'total_cost' => $req['total_cost'],
                ]);

                \Log::info('Production Order Store - Material created', [
                    'material_id' => $material->id,
                    'product_id' => $req['material_id'],
                    'required_quantity' => $req['total_required'],
                    'total_cost' => $req['total_cost'],
                ]);
            }

            // ─── LOG 9: Linking to sale ───
            if ($request->sale_id) {
                \Log::info('Production Order Store - Linking to sale', [
                    'sale_id' => $request->sale_id
                ]);

                $sale = Sale::find($request->sale_id);
                if ($sale) {
                    $sale->production_order_id = $order->id;
                    $sale->save();

                    \Log::info('Production Order Store - Sale linked successfully', [
                        'sale_id' => $sale->id,
                        'sale_no' => $sale->sale_no,
                        'production_order_id' => $order->id,
                    ]);
                } else {
                    \Log::warning('Production Order Store - Sale not found', [
                        'sale_id' => $request->sale_id
                    ]);
                }
            }

            DB::commit();

            \Log::info('Production Order Store - Success!', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            return redirect()->route('production-orders.show', $order)
                ->with('success', 'Production Order created successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            // ─── LOG 10: Validation error ───
            \Log::error('Production Order Store - Validation Error', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);

            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();

        } catch (\Illuminate\Database\QueryException $e) {
            // ─── LOG 11: Database error ───
            \Log::error('Production Order Store - Database Error', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'sql' => $e->getSql() ?? 'N/A',
                'bindings' => $e->getBindings() ?? [],
                'request_data' => $request->all()
            ]);

            DB::rollBack();
            return back()->withErrors(['error' => 'Database error: ' . $e->getMessage()])->withInput();

        } catch (\Exception $e) {
            // ─── LOG 12: General error ───
            \Log::error('Production Order Store - General Error', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create production order: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Show the form for editing the specified production order.
     */
    public function edit(ProductionOrder $productionOrder)
    {
        if (!in_array($productionOrder->status, [ProductionOrder::STATUS_PENDING, 'in_progress'], true)) {
            return redirect()->route('production-orders.index')
                ->with('warning', 'Only pending or in-progress production orders can be edited.');
        }

        $productionOrder->load(['bom', 'materials.product']);

        $products = Product::finishedGoods()
            ->where('is_active', true)
            ->with('category')
            ->get();

        $boms = BOM::where('status', 'active')
            ->where('is_active', true)
            ->with('product')
            ->get();

        return view('admin.production-orders.edit', compact('productionOrder', 'products', 'boms'));
    }

    /**
     * Update the specified production order details.
     */
    public function update(Request $request, ProductionOrder $productionOrder)
    {
        if (!in_array($productionOrder->status, [ProductionOrder::STATUS_PENDING, 'in_progress'], true)) {
            return back()->with('warning', 'Completed or cancelled production orders cannot be edited.');
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'bom_id' => 'required|exists:boms,id',
            'quantity_ordered' => 'required|numeric|min:1',
            'start_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $productionOrder->fill($validated);
        $productionOrder->save();

        return redirect()->route('production-orders.show', $productionOrder)
            ->with('success', 'Production Order updated successfully!');
    }

    /**
     * Display the specified production order.
     */

    public function show(ProductionOrder $productionOrder)
    {
        // ─── Load relationships ───
        $productionOrder->load([
            'product',
            'bom',
            'bom.items.material',
            'materials.product',
            'createdBy',
            'approvedBy',
        ]);

        // ─── GET LINKED SALE ───
        $sale = Sale::where('production_order_id', $productionOrder->id)
            ->with(['currency', 'items', 'items.bom'])
            ->first();

        // ─── CURRENCY SETUP ───
        if ($sale) {
            $currencyCode = $sale->currency->code ?? 'AFN';
            $currencySymbol = $sale->currency->symbol ?? '؋';
            $exchangeRate = $sale->exchange_rate ?? 66;
            $isUSD = $currencyCode === 'USD';

            // ─── GET DATA FROM SALE ITEMS ───
            $saleItems = $sale->items;
            $totalQuantity = $saleItems->sum('qty');

            // ─── PLANNED COSTS FROM FROZEN production_order_materials SNAPSHOT ───
            // (Create preview == persisted snapshot == show. Never rebuild planned
            // quantities/costs from the current BOM template, Product.unit or
            // legacy BOM item costs — duplicate raw-material rows for the same
            // product_id must stay independent.)
            $materialDetails = $productionOrder->materials
                ->map(function ($mat) {
                    return [
                        'material_name' => $mat->product->name ?? 'Unknown',
                        'required_quantity' => $mat->required_quantity,
                        'unit' => $mat->unit,
                        'cost_per_unit_usd' => $mat->cost_per_unit,
                        'total_cost_usd' => $mat->total_cost,
                    ];
                })
                ->all();

            $totalMaterialCostUsd = (float) $productionOrder->materials->sum('total_cost');

            // ─── EXPLICIT PRODUCTION LABOUR / OVERHEAD ───
            // The client's work_percentage (default 40%) is commercial PROFIT
            // embedded in the Excel Net Rate, NOT a production expense. When no
            // explicit BOM labour/overhead is configured, labour and overhead are
            // zero — the 40% must not be injected as a cost.
            $totalLaborCostUsd = 0;
            $totalOverheadCostUsd = 0;
            $totalCostUsd = 0;
            $workPercentage = (float) ($productionOrder->bom?->work_percentage ?? 40);
            $usesStandardWorkCost = false;

            foreach ($saleItems as $item) {
                $bom = $item->bom;
                if (!$bom) {
                    continue;
                }

                $laborCostPerUnitUsd = ($bom->labor_cost_per_unit ?? 0) / $exchangeRate;
                $overheadCostPerUnitUsd = ($bom->overhead_cost_per_unit ?? 0) / $exchangeRate;
                $explicitLaborUsd = $laborCostPerUnitUsd * $item->qty;
                $explicitOverheadUsd = $overheadCostPerUnitUsd * $item->qty;

                if (abs($explicitLaborUsd) < 0.000001 && abs($explicitOverheadUsd) < 0.000001) {
                    // No genuine labour/overhead configured — labour remains zero.
                    // The 40% is reported separately as Standard Work / Profit.
                    $usesStandardWorkCost = true;
                } else {
                    $totalLaborCostUsd += $explicitLaborUsd;
                    $totalOverheadCostUsd += $explicitOverheadUsd;
                }
            }

            $totalCostUsd = $totalMaterialCostUsd + $totalLaborCostUsd + $totalOverheadCostUsd;

            $hasActualConsumption = DB::table('production_material_consumptions')
                ->where('production_order_id', $productionOrder->id)
                ->exists();

            if ($hasActualConsumption) {
                $totalMaterialCostUsd = (float) DB::table('production_material_consumptions')
                    ->where('production_order_id', $productionOrder->id)
                    ->sum('total_cost_usd');

                $actualLabourAfn = (float) ($productionOrder->actual_labour_cost_afn ?? 0);
                $actualOverheadAfn = (float) ($productionOrder->actual_overhead_cost_afn ?? 0);
                $otherDirectAfn = (float) ($productionOrder->other_direct_cost_afn ?? 0);

                // The standard 40% work/profit is the client's commercial profit
                // embedded in the quotation Net Rate. It must not be injected as an
                // actual production labour expense. Actual cost uses only genuine
                // recorded labour/overhead (zero when none recorded).
                $usesStandardWorkCost = false;
                $totalLaborCostUsd = $actualLabourAfn / max($exchangeRate, 0.000001);
                $totalOverheadCostUsd = $actualOverheadAfn / max($exchangeRate, 0.000001);

                $otherDirectCostUsd = $otherDirectAfn / max($exchangeRate, 0.000001);
                $totalCostUsd = $totalMaterialCostUsd + $totalLaborCostUsd + $totalOverheadCostUsd + $otherDirectCostUsd;
            } else {
                $otherDirectCostUsd = 0;
            }

            // ─── CONVERT TO SALE CURRENCY ───
            $totalMaterialCostInCurrency = $totalMaterialCostUsd * $exchangeRate;
            $totalLaborCostInCurrency = $totalLaborCostUsd * $exchangeRate;
            $totalOverheadCostInCurrency = $totalOverheadCostUsd * $exchangeRate;
            $otherDirectCostInCurrency = ($otherDirectCostUsd ?? 0) * $exchangeRate;
            $totalCostInCurrency = $totalCostUsd * $exchangeRate;

            // ─── PER UNIT COSTS ───
            // Normalize per-unit planned costs against the production order
            // quantity (matches the no-sale path and the Create preview), not the
            // sum of sale-item quantities which may legitimately differ/collide.
            $baseQuantity = $productionOrder->quantity_ordered > 0
                ? (float) $productionOrder->quantity_ordered
                : ($totalQuantity > 0 ? $totalQuantity : 1);
            $materialCostPerUnitUsd = $totalMaterialCostUsd / $baseQuantity;
            $laborCostPerUnitUsd = $totalLaborCostUsd / $baseQuantity;
            $overheadCostPerUnitUsd = $totalOverheadCostUsd / $baseQuantity;

            $materialCostPerUnit = $materialCostPerUnitUsd * $exchangeRate;
            $laborCostPerUnit = $laborCostPerUnitUsd * $exchangeRate;
            $overheadCostPerUnit = $overheadCostPerUnitUsd * $exchangeRate;

            // ─── COST PER UNIT FOR PRODUCED ITEMS ───
            $quantityProduced = (float) $productionOrder->quantity_produced;
            $costPerUnitInCurrency = $quantityProduced > 0
                ? $totalCostInCurrency / $quantityProduced
                : 0;

            // ─── PROGRESS ───
            $progress = $productionOrder->progress_percentage;

            return view('admin.production-orders.show', compact(
                'productionOrder',
                'progress',
                'sale',
                'currencyCode',
                'currencySymbol',
                'exchangeRate',
                'isUSD',
                // Quantities
                'totalQuantity',
                'quantityProduced',
                'baseQuantity',
                // Per-unit values in USD
                'materialCostPerUnitUsd',
                'laborCostPerUnitUsd',
                'overheadCostPerUnitUsd',
                // Per-unit values in sale currency
                'materialCostPerUnit',
                'laborCostPerUnit',
                'overheadCostPerUnit',
                // Total values in USD
                'totalMaterialCostUsd',
                'totalLaborCostUsd',
                'totalOverheadCostUsd',
                'otherDirectCostUsd',
                'totalCostUsd',
                // Total values in sale currency
                'totalMaterialCostInCurrency',
                'totalLaborCostInCurrency',
                'totalOverheadCostInCurrency',
                'otherDirectCostInCurrency',
                'totalCostInCurrency',
                'costPerUnitInCurrency',
                'hasActualConsumption',
                'workPercentage',
                'usesStandardWorkCost',
                // Material details
                'materialDetails'
            ));
        }

        // ─── NO SALE LINKED - Show Production Order Data ───
        $currencyCode = 'AFN';
        $currencySymbol = '؋';
        $exchangeRate = 66;
        $isUSD = false;
        $totalQuantity = $productionOrder->quantity_ordered;
        $quantityProduced = $productionOrder->quantity_produced;
        $baseQuantity = $totalQuantity > 0 ? $totalQuantity : 1;

        // Use production order data
        $totalMaterialCostUsd = $productionOrder->total_material_cost;
        $totalLaborCostUsd = $productionOrder->total_labor_cost;
        $totalOverheadCostUsd = $productionOrder->total_overhead_cost;
        $totalCostUsd = $productionOrder->total_cost;

        $totalMaterialCostInCurrency = $totalMaterialCostUsd * $exchangeRate;
        $totalLaborCostInCurrency = $totalLaborCostUsd * $exchangeRate;
        $totalOverheadCostInCurrency = $totalOverheadCostUsd * $exchangeRate;
        $totalCostInCurrency = $totalCostUsd * $exchangeRate;

        $materialCostPerUnitUsd = $totalMaterialCostUsd / $baseQuantity;
        $laborCostPerUnitUsd = $totalLaborCostUsd / $baseQuantity;
        $overheadCostPerUnitUsd = $totalOverheadCostUsd / $baseQuantity;

        $materialCostPerUnit = $materialCostPerUnitUsd * $exchangeRate;
        $laborCostPerUnit = $laborCostPerUnitUsd * $exchangeRate;
        $overheadCostPerUnit = $overheadCostPerUnitUsd * $exchangeRate;

        $costPerUnitInCurrency = $quantityProduced > 0
            ? $totalCostInCurrency / $quantityProduced
            : 0;

        $progress = $productionOrder->progress_percentage;
        $materialDetails = [];
        $hasActualConsumption = false;

        return view('admin.production-orders.show', compact(
            'productionOrder',
            'progress',
            'sale',
            'currencyCode',
            'currencySymbol',
            'exchangeRate',
            'isUSD',
            'totalQuantity',
            'quantityProduced',
            'baseQuantity',
            'materialCostPerUnitUsd',
            'laborCostPerUnitUsd',
            'overheadCostPerUnitUsd',
            'materialCostPerUnit',
            'laborCostPerUnit',
            'overheadCostPerUnit',
            'totalMaterialCostUsd',
            'totalLaborCostUsd',
            'totalOverheadCostUsd',
            'totalCostUsd',
            'totalMaterialCostInCurrency',
            'totalLaborCostInCurrency',
            'totalOverheadCostInCurrency',
            'totalCostInCurrency',
            'costPerUnitInCurrency',
            'hasActualConsumption',
            'materialDetails'
        ));
    }
    /**
     * Start production (consume materials).
     * ✅ COMPLETE FIX
     */
    public function startProduction(ProductionOrder $productionOrder)
    {
        try {
            $sale = Sale::where('production_order_id', $productionOrder->id)
                ->with('items')
                ->first();

            $result = app(\App\Services\ProductionQuantityService::class)
                ->start($productionOrder, $sale);

            $message = sprintf(
                'Production started. Raw material allocated for %s finished units.',
                number_format($result['allocation_quantity'], 2)
            );

            if ($result['partial_start']) {
                $message .= sprintf(
                    ' Ordered quantity is %s, but current raw material supports only %s. Enter the real quantity produced when production ends.',
                    number_format($result['ordered_quantity'], 2),
                    number_format($result['allocation_quantity'], 2)
                );
            } else {
                $message .= ' Enter the real quantity produced when production ends; it may be lower or higher than the order quantity.';
            }

            return redirect()->route('production-orders.show', $productionOrder)
                ->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Start production failed', [
                'production_order_id' => $productionOrder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('production-orders.show', $productionOrder)
                ->with('error', 'Failed to start production: ' . $e->getMessage());
        }
    }

    private function getAvailableStockForMaterial($materialId)
    {
        return PurchaseItem::where('product_id', $materialId)
            ->where('qty_available', '>', 0)
            ->whereHas('purchase', function($q) {
                $q->where('status', 'arrived');
            })
            ->sum('qty_available') ?? 0;
    }
    /**
     * Complete production (produce finished goods).
     */
    public function completeProduction(
        ProductionOrder $productionOrder,
        ?Request $request = null
    ) {
        if ($productionOrder->status !== ProductionOrder::STATUS_IN_PROGRESS) {
            return back()->with('error', 'Only in-progress orders can be completed.');
        }

        $request ??= request();

        // Direct service/controller calls used by existing regression tests keep
        // the historical default. Normal HTTP completion always submits the
        // actual quantity through the completion form.
        $actualInput = $request->input('quantity_produced');
        $actualQuantity = $actualInput === null
            ? (float) $productionOrder->quantity_ordered
            : (float) $actualInput;

        if ($actualInput !== null) {
            $request->validate([
                'quantity_produced' => 'required|numeric|min:0.01|max:999999999.99',
            ]);
        }

        try {
            $sale = Sale::where('production_order_id', $productionOrder->id)
                ->with(['items', 'currency'])
                ->first();

            $result = app(\App\Services\ProductionQuantityService::class)
                ->complete($productionOrder, $actualQuantity, $sale);

            $variance = (float) $result['variance_quantity'];
            $message = sprintf(
                'Production completed with actual output of %s units.',
                number_format($actualQuantity, 2)
            );

            if ($variance > 0.000001) {
                $message .= sprintf(
                    ' This is %s units above the original order.',
                    number_format($variance, 2)
                );
            } elseif ($variance < -0.000001) {
                $message .= sprintf(
                    ' This is %s units below the original order.',
                    number_format(abs($variance), 2)
                );
            } else {
                $message .= ' Actual output matches the original order.';
            }

            if ($sale) {
                $message .= ' The final invoice quantity and amount were updated to the actual production quantity.';

                $overpayment = (float) data_get($result, 'invoice.overpayment', 0);
                if ($overpayment > 0) {
                    $message .= sprintf(
                        ' Customer payments exceed the revised invoice by %s %s; the excess remains as customer credit.',
                        $sale->currency?->symbol ?? '',
                        number_format($overpayment, 2)
                    );
                }
            }

            return redirect()->route('production-orders.show', $productionOrder)
                ->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Complete production failed', [
                'production_order_id' => $productionOrder->id,
                'actual_quantity' => $actualQuantity,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to complete production: ' . $e->getMessage());
        }
    }

    public function cancelProduction(ProductionOrder $productionOrder)
    {
        if (!in_array($productionOrder->status, ['pending', 'in_progress'])) {
            return back()->with('error', 'Only pending or in-progress orders can be cancelled.');
        }

        try {
            DB::beginTransaction();

            // If in_progress, restore materials
            if ($productionOrder->status === 'in_progress') {
                foreach ($productionOrder->materials as $material) {
                    // Restore consumed materials
                    $purchaseItems = PurchaseItem::where('product_id', $material->product_id)
                        ->where('qty_used', '>', 0)
                        ->orderBy('created_at', 'desc')
                        ->get();

                    $remainingToRestore = $material->required_quantity;

                    foreach ($purchaseItems as $purchaseItem) {
                        if ($remainingToRestore <= 0) break;

                        $used = $purchaseItem->qty_used ?? 0;
                        $toRestore = min($used, $remainingToRestore);

                        $purchaseItem->qty_used = ($purchaseItem->qty_used ?? 0) - $toRestore;
                        $purchaseItem->qty_available = $purchaseItem->qty_available + $toRestore;
                        $purchaseItem->save();

                        $remainingToRestore -= $toRestore;
                    }
                }
            }

            $productionOrder->status = 'cancelled';
            $productionOrder->save();

            // ─── Update the associated sale if exists ───
            $sale = Sale::where('production_order_id', $productionOrder->id)->first();
            if ($sale) {
                $sale->is_produced = false;
                $sale->save();
            }

            DB::commit();

            return redirect()->route('production-orders.show', $productionOrder)
                ->with('success', 'Production order cancelled successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Cancel production failed', [
                'production_order_id' => $productionOrder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to cancel production: ' . $e->getMessage());
        }
    }


    /**
     * Check material availability for a BOM.
     */
    private function checkMaterialAvailability($bom, $quantity)
    {
        $requirements = [];
        $shortages = [];
        $hasShortage = false;
        $totalMaterialCost = 0;

        foreach ($bom->items as $item) {
            $requiredQty = $item->calculateStockRequirement((float) $quantity, false);
            $totalRequired = $item->calculateStockRequirement((float) $quantity, true);
            $wastageQty = $totalRequired - $requiredQty;

            // Roll-based paper materials are stocked/consumed in kg, matching the
            // kg output of calculateStockRequirement and the authoritative kg-aware
            // FIFO consumption in StockDeductionService. Everything else keeps the
            // native stock basis and BOM/weighted-average cost exactly as before.
            $materialIsRoll = (bool) ($item->material->is_roll_based ?? false);

            $availableStock = $materialIsRoll
                ? (float) ($item->material->current_stock_kg ?? 0)
                : (float) ($item->material->current_stock ?? 0);
            $shortage = max(0, $totalRequired - $availableStock);

            if ($shortage > 0) {
                $hasShortage = true;
                $shortages[] = [
                    'material_id' => $item->material_id,
                    'material_name' => $item->material->name ?? 'Unknown',
                    'total_required' => $totalRequired,
                    'available_stock' => $availableStock,
                    'shortage' => $shortage,
                    'unit' => $materialIsRoll ? 'kg' : $item->unit,
                ];
            }

            // For roll-based materials the snapshot is costed on a kg basis
            // (landed USD/kg from the purchase batch), matching the authoritative
            // FIFO consumption. All other materials keep the existing cost basis.
            if ($materialIsRoll) {
                $rollBatch = PurchaseItem::where('product_id', $item->material_id)
                    ->where('unit', 'roll')
                    ->where('kg_per_roll', '>', 0)
                    ->whereHas('purchase', function ($q) { $q->where('status', 'arrived'); })
                    ->orderByRaw('COALESCE(
                        (SELECT purchase_date FROM purchases WHERE purchases.id = purchase_items.purchase_id),
                        purchase_items.created_at
                    ) ASC')
                    ->orderBy('purchase_items.id')
                    ->first();
                $costPerUnit = $rollBatch ? (float) $rollBatch->landedCostPerKg() : 0;
                $unitForReq = 'kg';
            } else {
                $costPerUnit = $item->material->weighted_avg_cost ?? $item->cost_per_unit;
                $unitForReq = $item->unit;
            }
            $itemTotalCost = $totalRequired * $costPerUnit;
            $totalMaterialCost += $itemTotalCost;

            $requirements[] = [
                'material_id' => $item->material_id,
                'material_name' => $item->material->name ?? 'Unknown',
                'required_quantity' => $requiredQty,
                'wastage_quantity' => $wastageQty,
                'total_required' => $totalRequired,
                'available_stock' => $availableStock,
                'shortage' => $shortage,
                'unit' => $unitForReq,
                'cost_per_unit' => $costPerUnit,
                'total_cost' => $itemTotalCost,
            ];
        }

        return [
            'has_shortage' => $hasShortage,
            'shortages' => $shortages,
            'requirements' => $requirements,
            'total_material_cost' => $totalMaterialCost,
        ];
    }

    /**
     * AJAX: Return production material requirements for a linked sale,
     * aligned with the authoritative physical costing rules used by
     * SaleProfitService. Uses the sale item's manual BOM snapshot
     * (if present) and resolves roll/KG cost from inventory batches.
     */
    public function getProductionMaterials(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
        ]);

        $sale = Sale::with([
            'items', 'items.bom.items', 'items.bom.items.material',
            'items.product', 'currency',
        ])->findOrFail($request->sale_id);

        $quantity = (float) $sale->items->sum('qty');

        $sp = new SaleProfitService();
        $requirements = $sp->productionMaterialRequirements($sale, $quantity);

        $totalMaterialCostAfn = 0.0;
        $totalMaterialCostUsd = 0.0;
        $hasShortage = false;

        foreach ($requirements as $req) {
            $totalMaterialCostAfn += $req['total_cost_afn'];
            $totalMaterialCostUsd += $req['total_cost_usd'];
            if ($req['shortage'] > 0) {
                $hasShortage = true;
            }
        }

        $firstBom = $sale->items->first()->bom ?? null;
        $workPercentage = $firstBom ? (float) ($firstBom->work_percentage ?? 40) : 40;

        return response()->json([
            'success' => true,
            'requirements' => $requirements,
            'has_shortage' => $hasShortage,
            'summary' => [
                'material_cost_afn' => $totalMaterialCostAfn,
                'material_cost_usd' => $totalMaterialCostUsd,
                'work_cost_afn' => 0,
                'work_cost_usd' => 0,
                'total_cost_afn' => $totalMaterialCostAfn,
                'total_cost_usd' => $totalMaterialCostUsd,
                'cost_per_unit_afn' => $quantity > 0 ? $totalMaterialCostAfn / $quantity : 0,
                'cost_per_unit_usd' => $quantity > 0 ? $totalMaterialCostUsd / $quantity : 0,
                'work_percentage' => $workPercentage,
                'exchange_rate' => $sale->exchange_rate ?? 66,
            ],
        ]);
    }

    /**
     * DataTable for production orders.
     */
    public function data(Request $request)
    {
        $query = ProductionOrder::with(['product', 'bom', 'createdBy']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('order_number', function ($row) {
                return '<a href="' . route('production-orders.show', $row) . '" class="fw-bold text-primary">' . e($row->order_number) . '</a>';
            })
            ->addColumn('product', function ($row) {
                return $row->product->name ?? 'N/A';
            })
            ->addColumn('quantity', function ($row) {
                return $row->quantity_ordered . ' / ' . $row->quantity_produced;
            })
            ->addColumn('progress', function ($row) {
                $percentage = $row->progress_percentage;
                $color = $percentage >= 100 ? 'success' : ($percentage >= 50 ? 'warning' : 'info');
                return '
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress" style="width: 100px; height: 6px;">
                            <div class="progress-bar bg-' . $color . '" role="progressbar" style="width: ' . $percentage . '%;"></div>
                        </div>
                        <span class="small">' . round($percentage) . '%</span>
                    </div>
                ';
            })
            ->addColumn('status', function ($row) {
                $badges = [
                    'pending' => '<span class="badge bg-warning">Pending</span>',
                    'in_progress' => '<span class="badge bg-info">In Progress</span>',
                    'completed' => '<span class="badge bg-success">Completed</span>',
                    'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
                ];
                return $badges[$row->status] ?? '<span class="badge bg-secondary">Unknown</span>';
            })
            ->addColumn('total_cost', function ($row) {
                return '$' . number_format($row->total_cost, 2);
            })
            ->addColumn('created_at', function ($row) {
                return $row->created_at->format('Y-m-d H:i');
            })
            ->addColumn('actions', function ($row) {
                $actions = '
                    <div class="btn-group">
                        <a href="' . route('production-orders.show', $row) . '" class="btn btn-sm btn-info">
                            <i class="bi bi-eye"></i>
                        </a>
                ';

                if ($row->status === 'pending') {
                    $actions .= '
                        <form action="' . route('production-orders.start', $row) . '" method="POST" class="d-inline">
                            ' . csrf_field() . '
                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm(\'Start production? This will consume materials from inventory.\')">
                                <i class="bi bi-play-fill"></i>
                            </button>
                        </form>
                    ';
                }

                if ($row->status === 'in_progress') {
                    $actions .= '
                        <form action="' . route('production-orders.complete', $row) . '" method="POST" class="d-inline">
                            ' . csrf_field() . '
                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm(\'Complete production? This will add finished goods to inventory.\')">
                                <i class="bi bi-check2"></i>
                            </button>
                        </form>
                    ';
                }

                if (in_array($row->status, ['pending', 'in_progress'])) {
                    $actions .= '
                        <form action="' . route('production-orders.cancel', $row) . '" method="POST" class="d-inline">
                            ' . csrf_field() . '
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(\'Cancel production? This will restore materials to inventory.\')">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </form>
                    ';
                }

                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['order_number', 'progress', 'status', 'actions'])
            ->make(true);
    }
}
