<?php
// app/Http/Controllers/Admin/ProductionOrderController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderMaterial;
use App\Models\Product;
use App\Models\BOM;
use App\Models\PurchaseItem;
use App\Models\PurchaseItemReel;
use App\Models\Currency;
use App\Models\Sale;
use App\Models\Transaction;
use App\Services\SaleProfitService;
use App\Services\StockDeductionService;
use App\Services\ProductionControlService;
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
                // A shortage no longer blocks creation. The production order keeps
                // the customer's requested quantity as the plan, while Start
                // Production allocates only the quantity current raw material can
                // support. The real finished quantity is entered at completion.
                \Log::warning('Production Order Store - Material shortages recorded for partial production', [
                    'shortages' => $availability['shortages'],
                    'quantity_ordered' => $request->quantity_ordered,
                ]);
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

        $maxProducibleQuantity = null;
        if ($productionOrder->status === ProductionOrder::STATUS_PENDING) {
            try {
                $maxProducibleQuantity = app(\App\Services\ProductionQuantityService::class)
                    ->maxProducibleQuantity($productionOrder);
            } catch (\Throwable $e) {
                Log::warning('Could not calculate max producible quantity', [
                    'production_order_id' => $productionOrder->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // ─── COMPLETION DECLARATION DATA ───
        // Planned quantities come from the frozen production-order material
        // snapshot. Current actuals are the FIFO allocations made when production
        // started; the operator can correct them to the real shop-floor amounts.
        $completionMaterials = [];
        if ($productionOrder->status === ProductionOrder::STATUS_IN_PROGRESS) {
            try {
                $plannedRunQty = (float) ($productionOrder->quantity_planned ?: $productionOrder->quantity_ordered);
                $plannedRows = app(\App\Services\ProductionQuantityService::class)
                    ->requirementsForQuantity($productionOrder, $plannedRunQty);

                $currentConsumption = DB::table('production_material_consumptions')
                    ->where('production_order_id', $productionOrder->id)
                    ->selectRaw('material_id, SUM(actual_quantity) AS actual_quantity, SUM(wastage_quantity) AS wastage_quantity')
                    ->groupBy('material_id')
                    ->get()
                    ->keyBy(fn ($row) => (int) $row->material_id);

                $materialIds = collect($plannedRows)
                    ->pluck('material_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                // Roll paper is not practically weighable after every job at
                // this factory. Mark roll-based materials explicitly so the
                // completion UI can use system-calculated consumption instead
                // of asking the operator to invent an "actual kg" value.
                $rollMaterialIds = PurchaseItem::query()
                    ->whereIn('product_id', $materialIds)
                    ->whereRaw('LOWER(unit) = ?', ['roll'])
                    ->whereHas('purchase', fn ($purchase) => $purchase->where('status', 'arrived'))
                    ->pluck('product_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                $currentReelUsage = DB::table('production_reel_consumptions as prc')
                    ->join(
                        'production_material_consumptions as pmc',
                        'pmc.id',
                        '=',
                        'prc.production_material_consumption_id'
                    )
                    ->where('pmc.production_order_id', $productionOrder->id)
                    ->selectRaw(
                        'prc.purchase_item_reel_id, SUM(prc.quantity_kg) AS quantity_kg'
                    )
                    ->groupBy('prc.purchase_item_reel_id')
                    ->get()
                    ->keyBy(fn ($row) => (int) $row->purchase_item_reel_id);

                $reelOptionsByMaterial = PurchaseItemReel::query()
                    ->whereHas('purchaseItem', function ($query) use ($materialIds): void {
                        $query->whereIn('product_id', $materialIds)
                            ->whereHas(
                                'purchase',
                                fn ($purchase) => $purchase->where('status', 'arrived')
                            );
                    })
                    ->with(['purchaseItem.purchase'])
                    ->orderBy('purchase_item_id')
                    ->orderBy('sequence_no')
                    ->get()
                    ->map(function (PurchaseItemReel $reel) use ($currentReelUsage): array {
                        $usedByThisRun = (float) data_get(
                            $currentReelUsage->get((int) $reel->id),
                            'quantity_kg',
                            0
                        );
                        $availableForRun = max(
                            (float) $reel->system_remaining_weight_kg
                                + $usedByThisRun,
                            0
                        );

                        $selectable = ! $reel->isBlockedFromProduction()
                            && $availableForRun > 0.000001
                            && (
                                $reel->status !== PurchaseItemReel::STATUS_CONSUMED
                                || $usedByThisRun > 0.000001
                            );

                        return [
                            'id' => (int) $reel->id,
                            'material_id' => (int) $reel->purchaseItem->product_id,
                            'reel_code' => $reel->reel_code,
                            'status' => $reel->status,
                            'selectable' => $selectable,
                            'batch_no' => $reel->purchaseItem->batch_no,
                            'purchase_no' => $reel->purchaseItem->purchase?->purchase_no,
                            'system_remaining_kg' => (float) $reel->system_remaining_weight_kg,
                            'current_run_consumed_kg' => $usedByThisRun,
                            'available_for_run_kg' => $availableForRun,
                        ];
                    })
                    ->groupBy('material_id');

                $completionMaterials = collect($plannedRows)->map(
                    function (array $row) use (
                        $currentConsumption,
                        $reelOptionsByMaterial,
                        $rollMaterialIds,
                        $plannedRunQty
                    ): array {
                        $materialId = (int) $row['material_id'];
                        $current = $currentConsumption->get($materialId);
                        $reelOptions = collect(
                            $reelOptionsByMaterial->get($materialId, collect())
                        )->values()->all();

                        return [
                            'material_id' => $materialId,
                            'material_name' => $row['material_name'] ?? ('Material #' . $materialId),
                            'unit' => $row['unit'] ?? 'unit',
                            'planned_quantity' => (float) ($row['quantity'] ?? 0),
                            'planned_wastage_quantity' => (float) ($row['wastage_quantity'] ?? 0),
                            'planned_run_quantity' => $plannedRunQty,
                            'current_actual_quantity' => (float) ($current->actual_quantity ?? 0),
                            'current_wastage_quantity' => (float) ($current->wastage_quantity ?? 0),
                            'is_roll_based' => $rollMaterialIds->contains($materialId),
                            'is_formula_based' => (bool) ($row['is_formula_based'] ?? false),
                            'formula_types' => $row['formula_types'] ?? [],
                            'consumption_source' => (string) ($row['consumption_source'] ?? 'manual'),
                            'reel_options' => $reelOptions,
                        ];
                    }
                )->values()->all();
            } catch (\Throwable $e) {
                Log::warning('Could not prepare production completion material rows', [
                    'production_order_id' => $productionOrder->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $productionVariance = null;
        try {
            $productionVariance = app(\App\Services\ProductionVarianceService::class)
                ->forProductionOrder($productionOrder);
        } catch (\Throwable $e) {
            Log::warning('Could not calculate production variance', [
                'production_order_id' => $productionOrder->id,
                'error' => $e->getMessage(),
            ]);
        }

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
                'maxProducibleQuantity',
                'completionMaterials',
                'productionVariance',
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
            'maxProducibleQuantity',
            'completionMaterials',
            'productionVariance',
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
    public function startProduction(
        ProductionOrder $productionOrder,
        ?Request $request = null
    ) {
        $request ??= request();

        $plannedInput = $request->input('quantity_planned');
        $plannedQuantity = $plannedInput === null
            ? (float) $productionOrder->quantity_ordered
            : (float) $plannedInput;

        if ($plannedInput !== null) {
            $request->validate([
                'quantity_planned' => 'required|numeric|min:0.01|max:999999999.99',
            ]);
        }

        try {
            $sale = Sale::where('production_order_id', $productionOrder->id)
                ->with('items')
                ->first();

            $result = app(\App\Services\ProductionQuantityService::class)
                ->start($productionOrder, $sale, $plannedQuantity);

            $message = sprintf(
                'Production started for %s units. Raw material and production cost were calculated for this manually entered quantity.',
                number_format($result['planned_quantity'], 2)
            );

            $variance = (float) $result['variance_quantity'];
            if ($variance > 0.000001) {
                $message .= sprintf(
                    ' Planned production is %s units above the customer order.',
                    number_format($variance, 2)
                );
            } elseif ($variance < -0.000001) {
                $message .= sprintf(
                    ' Planned production is %s units below the customer order.',
                    number_format(abs($variance), 2)
                );
            }

            $message .= ' Enter the real finished quantity when production ends.';

            return redirect()->route('production-orders.show', $productionOrder)
                ->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Start production failed', [
                'production_order_id' => $productionOrder->id,
                'planned_quantity' => $plannedQuantity,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('production-orders.show', $productionOrder)
                ->withInput()
                ->with('error', 'Failed to start production: ' . $e->getMessage());
        }
    }

    public function approveProduction(
        Request $request,
        ProductionOrder $productionOrder,
        ProductionControlService $control
    ) {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        try {
            $control->approve(
                $productionOrder,
                Auth::user(),
                $validated['reason'] ?? null
            );

            return back()->with('success', 'Production order approved successfully.');
        } catch (\Throwable $e) {
            Log::warning('Production approval failed', [
                'production_order_id' => $productionOrder->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage());
        }
    }

    public function closeProduction(
        Request $request,
        ProductionOrder $productionOrder,
        ProductionControlService $control
    ) {
        $validated = $request->validate([
            'reason' => 'required|string|min:10|max:1000',
        ]);

        try {
            $control->close($productionOrder, Auth::user(), $validated['reason']);

            return back()->with('success', 'Production order closed. Further correction now requires an authorized reopen.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reopenProduction(
        Request $request,
        ProductionOrder $productionOrder,
        ProductionControlService $control
    ) {
        $validated = $request->validate([
            'reason' => 'required|string|min:10|max:1000',
        ]);

        try {
            $control->reopen($productionOrder, Auth::user(), $validated['reason']);

            return back()->with('success', 'Production order reopened for controlled correction.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reverseCompletion(
        Request $request,
        ProductionOrder $productionOrder,
        ProductionControlService $control
    ) {
        $validated = $request->validate([
            'reason' => 'required|string|min:10|max:1000',
        ]);

        try {
            $control->reverseCompletion($productionOrder, Auth::user(), $validated['reason']);

            return redirect()
                ->route('production-orders.show', $productionOrder)
                ->with('success', 'Production completion reversed. FIFO stock and the pre-completion commercial snapshot were restored.');
        } catch (\Throwable $e) {
            Log::warning('Production completion reversal failed', [
                'production_order_id' => $productionOrder->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage());
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

        // Preserve legacy direct-controller calls used by existing regression
        // tests and internal code. Normal HTTP completion always supplies the
        // explicit completion form and therefore requires the actual materials.
        if ($request === null) {
            try {
                $sale = Sale::where('production_order_id', $productionOrder->id)
                    ->with(['items', 'currency'])
                    ->first();

                $legacyGoodQty = (float) $productionOrder->quantity_ordered;

                app(\App\Services\ProductionQuantityService::class)->complete(
                    $productionOrder,
                    $legacyGoodQty,
                    $sale,
                    $legacyGoodQty,
                    0.0,
                    null
                );

                return redirect()->route('production-orders.show', $productionOrder)
                    ->with('success', sprintf(
                        'Production completed with actual output of %s units.',
                        number_format($legacyGoodQty, 2)
                    ));
            } catch (\Throwable $e) {
                Log::error('Legacy complete production failed', [
                    'production_order_id' => $productionOrder->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return back()->with('error', 'Failed to complete production: ' . $e->getMessage());
            }
        }

        try {
            $validated = $request->validate([
                'quantity_manufactured' => 'required|numeric|min:0.01|max:999999999.99',
                'quantity_produced' => 'required|numeric|min:0.01|max:999999999.99',
                'quantity_rejected' => 'required|numeric|min:0|max:999999999.99',
                'materials' => 'required|array|min:1',
                'materials.*.material_id' => 'required|integer|distinct|exists:products,id',
                'materials.*.actual_quantity' => 'nullable|numeric|min:0|max:999999999.999999',
                'materials.*.wastage_quantity' => 'nullable|numeric|min:0|max:999999999.999999',
                'materials.*.use_measured_actual' => 'nullable|boolean',
                'materials.*.unit' => 'nullable|string|max:50',
                'materials.*.use_reel_selection' => 'nullable|boolean',
                'materials.*.selection_note' => 'nullable|string|max:1000',
                'materials.*.reels' => 'nullable|array|max:100',
                'materials.*.reels.*.reel_id' => 'nullable|integer|exists:purchase_item_reels,id',
                'materials.*.reels.*.consumed_kg' => 'nullable|numeric|min:0|max:999999999.999999',
                'materials.*.reels.*.final_remaining_kg' => 'nullable|numeric|min:0|max:999999999.999999',
            ]);

            $manufacturedQuantity = (float) $validated['quantity_manufactured'];
            $goodQuantity = (float) $validated['quantity_produced'];
            $rejectedQuantity = (float) $validated['quantity_rejected'];

            if (abs($manufacturedQuantity - ($goodQuantity + $rejectedQuantity)) > 0.01) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'quantity_manufactured' => 'Manufactured Qty must equal Good / Actual Finished Qty + Rejected / Scrap Qty.',
                ]);
            }

            $expectedMaterialIds = $productionOrder->materials()
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->sort()
                ->values();

            // Older production orders may not have frozen order-material rows.
            // In that case use the materials already allocated by Start Production.
            if ($expectedMaterialIds->isEmpty()) {
                $expectedMaterialIds = DB::table('production_material_consumptions')
                    ->where('production_order_id', $productionOrder->id)
                    ->pluck('material_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->sort()
                    ->values();
            }

            $autoRequirements = collect(
                app(\App\Services\ProductionQuantityService::class)
                    ->requirementsForQuantity($productionOrder, $manufacturedQuantity)
            )->keyBy(fn (array $row) => (int) $row['material_id']);

            $rollMaterialIds = PurchaseItem::query()
                ->whereIn('product_id', $expectedMaterialIds)
                ->whereRaw('LOWER(unit) = ?', ['roll'])
                ->whereHas('purchase', fn ($purchase) => $purchase->where('status', 'arrived'))
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $submittedMaterials = collect($validated['materials'])
                ->map(function (array $row) use ($rollMaterialIds, $autoRequirements): array {
                    $useReelSelection = (bool) (
                        $row['use_reel_selection'] ?? false
                    );

                    $reels = collect($row['reels'] ?? [])
                        ->filter(function (array $reel): bool {
                            return filled($reel['consumed_kg'] ?? null)
                                || filled($reel['final_remaining_kg'] ?? null);
                        })
                        ->map(function (array $reel): array {
                            return [
                                'reel_id' => (int) ($reel['reel_id'] ?? 0),
                                'consumed_kg' => filled($reel['consumed_kg'] ?? null)
                                    ? (float) $reel['consumed_kg']
                                    : null,
                                'final_remaining_kg' => filled(
                                    $reel['final_remaining_kg'] ?? null
                                )
                                    ? (float) $reel['final_remaining_kg']
                                    : null,
                            ];
                        })
                        ->values()
                        ->all();

                    $materialId = (int) $row['material_id'];
                    $isRollBased = $rollMaterialIds->contains($materialId);
                    $autoRequirement = $autoRequirements->get($materialId);
                    $isFormulaBased = (bool) ($autoRequirement['is_formula_based'] ?? false);
                    $useMeasuredActual = ! $isRollBased
                        && $isFormulaBased
                        && (bool) ($row['use_measured_actual'] ?? false);

                    if (($isRollBased || $isFormulaBased) && ! $autoRequirement) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'materials' => "Could not calculate standard consumption for material #{$materialId}.",
                        ]);
                    }

                    $actualProvided = array_key_exists('actual_quantity', $row)
                        && $row['actual_quantity'] !== null
                        && $row['actual_quantity'] !== '';

                    // Standard formula is the normal production source of truth.
                    // Roll paper is always automatic. Formula-based non-roll
                    // materials (for example adhesive ingredients) are automatic
                    // unless the operator explicitly switches to a measured actual.
                    $usesStandardFormula = $isRollBased
                        || ($isFormulaBased && ! $useMeasuredActual);

                    $actualQuantity = $usesStandardFormula
                        ? (float) ($autoRequirement['quantity'] ?? 0)
                        : (float) ($row['actual_quantity'] ?? 0);

                    $wastageQuantity = $usesStandardFormula
                        ? (float) ($autoRequirement['wastage_quantity'] ?? 0)
                        : (float) ($row['wastage_quantity'] ?? 0);

                    return [
                        'material_id' => $materialId,
                        'actual_quantity' => $actualQuantity,
                        'wastage_quantity' => $wastageQuantity,
                        'unit' => $row['unit'] ?? ($autoRequirement['unit'] ?? null),
                        'is_roll_based' => $isRollBased,
                        'is_formula_based' => $isFormulaBased,
                        'uses_standard_formula' => $usesStandardFormula,
                        'use_measured_actual' => $useMeasuredActual,
                        'actual_provided' => $actualProvided,
                        // Exact physical reel declaration remains an advanced,
                        // optional allocation control. It never changes the
                        // system-calculated TOTAL paper quantity for this job.
                        'use_reel_selection' => $useReelSelection,
                        'selection_note' => isset($row['selection_note'])
                            ? trim((string) $row['selection_note'])
                            : null,
                        'reels' => $useReelSelection ? $reels : [],
                    ];
                });

            $submittedIds = $submittedMaterials->pluck('material_id')->unique()->sort()->values();

            if ($expectedMaterialIds->diff($submittedIds)->isNotEmpty()
                || $submittedIds->diff($expectedMaterialIds)->isNotEmpty()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'materials' => 'Actual material consumption must be entered for exactly the materials allocated to this production order.',
                ]);
            }

            foreach ($submittedMaterials as $index => $row) {
                if (! $row['uses_standard_formula'] && ! $row['actual_provided']) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "materials.{$index}.actual_quantity" => 'Enter the measured actual quantity or use the standard consumption formula.',
                    ]);
                }

                if ($row['wastage_quantity'] > $row['actual_quantity'] + 0.000001) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "materials.{$index}.wastage_quantity" => 'Actual waste cannot exceed total actual material consumed.',
                    ]);
                }

                if ($row['use_reel_selection']) {
                    if ($row['actual_quantity'] <= 0.000001) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "materials.{$index}.actual_quantity" => 'Physical reel selection requires positive actual consumption.',
                        ]);
                    }

                    if ($row['reels'] === []) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "materials.{$index}.reels" => 'Enter consumed kg or a final measured remainder for at least one physical reel.',
                        ]);
                    }

                    $reelIds = collect($row['reels'])->pluck('reel_id');
                    if ($reelIds->contains(fn ($id) => (int) $id <= 0)) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "materials.{$index}.reels" => 'Every physical reel declaration requires a valid reel.',
                        ]);
                    }

                    if ($reelIds->unique()->count() !== $reelIds->count()) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "materials.{$index}.reels" => 'The same physical reel cannot be entered twice.',
                        ]);
                    }
                }
            }

            $sale = Sale::where('production_order_id', $productionOrder->id)
                ->with(['items', 'currency'])
                ->first();

            $result = app(\App\Services\ProductionQuantityService::class)->complete(
                $productionOrder,
                $goodQuantity,
                $sale,
                $manufacturedQuantity,
                $rejectedQuantity,
                $submittedMaterials->all()
            );

            $message = sprintf(
                'Production completed: %s manufactured, %s good/usable, %s rejected. Yield: %s%%.',
                number_format($manufacturedQuantity, 2),
                number_format($goodQuantity, 2),
                number_format($rejectedQuantity, 2),
                number_format((float) $result['yield_percentage'], 2)
            );

            $manufacturedVariance = (float) $result['manufactured_variance_quantity'];
            if ($manufacturedVariance > 0.000001) {
                $message .= sprintf(
                    ' Manufactured output is %s units above the planned run.',
                    number_format($manufacturedVariance, 2)
                );
            } elseif ($manufacturedVariance < -0.000001) {
                $message .= sprintf(
                    ' Manufactured output is %s units below the planned run.',
                    number_format(abs($manufacturedVariance), 2)
                );
            }

            $message .= ' Stock and actual production cost were reconciled. Standard-formula paper and mixing materials were calculated from manufactured quantity and deducted/reconciled automatically; measured overrides were used only where explicitly entered. Physical reel allocation remains FIFO unless reconciled separately.';

            if ($sale) {
                $message .= ' The final invoice quantity uses the good/usable finished quantity.';

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
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Complete production failed', [
                'production_order_id' => $productionOrder->id,
                'request' => $request->except(['_token']),
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
        $commercialProfit = $sp->calculate($sale);

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

        $workPercentage = (float) ($commercialProfit['standard_profit_on_material_percentage'] ?? 40);

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

                // Commercial profit is kept separate from production cost.
                // Standard Work / Profit is the client's configured commercial
                // percentage. Manual price changes are an additional profit
                // adjustment and are labelled separately in the create preview.
                'standard_work_profit_afn' => (float) ($commercialProfit['standard_work_profit_afn'] ?? 0),
                'standard_work_profit_usd' => (float) ($commercialProfit['standard_work_profit_usd'] ?? 0),
                'has_manual_price_override' => (bool) ($commercialProfit['has_manual_price_override'] ?? false),
                'price_override_afn' => (float) ($commercialProfit['price_override_afn'] ?? 0),
                'price_override_usd' => (float) ($commercialProfit['price_override_usd'] ?? 0),
                'commercial_profit_afn' => (float) ($commercialProfit['commercial_profit_afn'] ?? 0),
                'commercial_profit_usd' => (float) ($commercialProfit['commercial_profit_usd'] ?? 0),

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
