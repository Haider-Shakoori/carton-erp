<?php
// app/Http/Controllers/Admin/SaleController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BOM;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\BOMItem;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\SaleReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\ProductionService;
use App\Services\SaleProfitService;

class SaleController extends Controller
{
    /**
     * Display a listing of sales orders.
     */
    public function index(Request $request)
    {
        $query = Sale::with(['customer', 'currency', 'items']);

        // Filter by production status
        if ($request->filled('production_status')) {
            if ($request->production_status === 'no_production') {
                $query->whereNull('production_order_id')->where('is_produced', false);
            } elseif ($request->production_status === 'has_production') {
                $query->whereNotNull('production_order_id')->orWhere('is_produced', true);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sale_no', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $sales = $query->latest()->paginate(15);

        // Report ACTUAL FIFO production cost for production sales that already
        // have material consumption (consistent with SaleProfitService) so the
        // profit column reflects realized profit, not the quotation snapshot.
        foreach (\App\Services\SaleProfitService::realizedCostBySales($sales->getCollection()) as $saleId => $cost) {
            $sale = $sales->getCollection()->firstWhere('id', $saleId);
            if ($sale) {
                $sale->realized_cost_usd = $cost['cost_usd'];
            }
        }

        $stats = [
            'total' => Sale::count(),
            'draft' => Sale::where('status', 'draft')->count(),
            'confirmed' => Sale::where('status', 'confirmed')->count(),
            'shipped' => Sale::where('status', 'shipped')->count(),
            'delivered' => Sale::where('status', 'delivered')->count(),
        ];

        return view('admin.sales.index', compact('sales', 'stats'));
    }

    /**
     * Show the form for creating a new sale.
     */
    public function create()
    {
        $customers = Account::where('account_type', 'customer')
            ->where('is_active', true)
            ->get();

        $currencies = Currency::where('is_active', true)->get();
        $defaultCurrency = Currency::where('is_default', true)->first();

        // ONLY finished goods for sales
        $products = Product::finishedGoods()
            ->where('is_active', true)
            ->get();

        $nextSaleNo = Sale::generateNumber();

        return view('admin.sales.create', compact(
            'customers',
            'currencies',
            'defaultCurrency',
            'products',
            'nextSaleNo'
        ));
    }

    /**
     * Store a newly created sale.
     */

    public function store(Request $request)
    {
        // ─── LOG 1: Request received ───
        \Log::info('Sale Order Store - Request Received', [
            'request_data' => $request->all(),
            'request_method' => $request->method(),
            'request_url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_id' => auth()->id(),
        ]);

        try {
            // ─── LOG 2: Validation starting ───
            \Log::info('Sale Order Store - Validating request');

            $validated = $request->validate([
                'sale_no' => 'required|unique:sales,sale_no',
                'customer_id' => 'required|exists:accounts,id',
                'currency_id' => 'required|exists:currencies,id',
                'sale_date' => 'nullable|date',
                'exchange_rate' => 'nullable|numeric|min:0.0001',
                'shipping_address' => 'nullable|string',
                'notes' => 'nullable|string',
            ]);

            // ─── LOG 3: Validation passed ───
            \Log::info('Sale Order Store - Validation passed', [
                'validated_data' => $validated
            ]);

            DB::beginTransaction();

            // ─── LOG 4: Fetching currency ───
            $currency = Currency::find($request->currency_id);
            \Log::info('Sale Order Store - Currency found', [
                'currency_id' => $request->currency_id,
                'currency_code' => $currency ? $currency->code : 'NOT_FOUND',
                'currency_exchange_rate' => $currency ? $currency->exchange_rate : null,
            ]);

            $exchangeRate = $request->exchange_rate ?? ($currency->exchange_rate ?? 1);

            // ─── LOG 5: Preparing sale data ───
            $saleData = [
                'sale_no' => $request->sale_no,
                'customer_id' => $request->customer_id,
                'currency_id' => $request->currency_id,
                'sale_date' => $request->sale_date ?? now(),
                'status' => 'draft',
                'exchange_rate' => $exchangeRate,
                'shipping_address' => $request->shipping_address,
                'notes' => $request->notes,
            ];

            \Log::info('Sale Order Store - Preparing to create', [
                'sale_data' => $saleData
            ]);

            // ─── LOG 6: Creating sale ───
            $sale = Sale::create($saleData);

            // ─── LOG 7: Sale created successfully ───
            \Log::info('Sale Order Store - Sale created successfully', [
                'sale_id' => $sale->id,
                'sale_no' => $sale->sale_no,
                'sale_data' => $sale->toArray()
            ]);

            DB::commit();

            // ─── LOG 8: Redirecting ───
            \Log::info('Sale Order Store - Redirecting to show', [
                'sale_id' => $sale->id,
                'redirect_url' => route('admin.sales.show', $sale->id)
            ]);

            return redirect()->route('admin.sales.show', $sale->id)
                ->with('success', 'Sale order created successfully.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            // ─── LOG 9: Validation error ───
            \Log::error('Sale Order Store - Validation Error', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);

            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();

        } catch (\Illuminate\Database\QueryException $e) {
            // ─── LOG 10: Database error ───
            \Log::error('Sale Order Store - Database Error', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'sql' => $e->getSql() ?? 'N/A',
                'bindings' => $e->getBindings() ?? [],
                'request_data' => $request->all()
            ]);

            DB::rollBack();
            return back()->withErrors(['error' => 'Database error: ' . $e->getMessage()])->withInput();

        } catch (\Exception $e) {
            // ─── LOG 11: General error ───
            \Log::error('Sale Order Store - General Error', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            DB::rollBack();
            return back()->withErrors(['error' => 'Error creating sale: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified sale.
     */
    public function show($id)
    {
        $sale = Sale::with([
            'customer',
            'currency',
            'items.product',
            'items.bom',
            'items.purchaseItem.purchase',
            'items.purchaseItem.purchase.currency',
            'productionOrder',
        ])->findOrFail($id);

        // ─── CALCULATE COGS AND PROFIT ───
        $totalCost = 0;
        $totalProfit = 0;

        foreach ($sale->items as $item) {
            if ($item->total_cost_usd == 0 && $item->bom_id) {
                $item->calculateCostFromBOM();
                $item->saveQuietly();
            }
            $totalCost += $item->total_cost_usd ?? 0;
            $totalProfit += $item->profit_usd ?? 0;
        }

        $profitMargin = $totalCost > 0 ? ($totalProfit / $totalCost) * 100 : 0;

        // ─── CURRENCY INFORMATION ───
        $currencyCode = $sale->currency->code ?? 'AFN';
        $currencySymbol = $sale->currency->symbol ?? '؋';
        $isUSD = $currencyCode === 'USD';
        $exchangeRate = $sale->exchange_rate ?? 85;

        // ─── EXACT PROFIT ───
        $profitSummary = app(\App\Services\SaleProfitService::class)->calculate($sale);

        // ─── ONLY FINISHED GOODS FOR SALES ───
        $products = Product::finishedGoods()
            ->where('is_active', true)
            ->with(['category'])
            ->get();

        // ─── FIX: Add raw materials for the modal dropdown ───
        $rawMaterialProducts = Product::rawMaterials()
            ->where('is_active', true)
            ->with(['category'])
            ->get();

        // Pre-fetch the latest arrived purchase item per raw material in one query
        // (previously one LIMIT-1 query per material). Selecting the highest purchase
        // item id per product reproduces ->latest('id')->first() with identical results.
        $rawMaterialIds = $rawMaterialProducts->pluck('id')->all();
        $latestPurchaseItems = PurchaseItem::whereIn('product_id', $rawMaterialIds)
            ->whereHas('purchase', function ($q) {
                $q->where('status', 'arrived');
            })
            ->with(['purchase.currency'])
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('product_id')
            ->map(function ($items) {
                return $items->last();
            });

        $materials = $rawMaterialProducts->map(function ($material) use ($latestPurchaseItems) {
            $latestPurchaseItem = $latestPurchaseItems->get($material->id);
            if ($latestPurchaseItem && $latestPurchaseItem->purchase) {
                $material->purchase_currency = $latestPurchaseItem->purchase->currency->code ?? 'AFN';
                $material->purchase_currency_id = $latestPurchaseItem->purchase->currency_id ?? null;
            } else {
                $material->purchase_currency = 'AFN';
                $material->purchase_currency_id = null;
            }

            return $material;
        });

        $customers = Account::where('account_type', 'customer')
            ->where('is_active', true)
            ->get();

        $currencies = Currency::where('is_active', true)->get();

        // ─── GET BOMS FOR EACH PRODUCT ───
        $productIds = $products->pluck('id')->all();
        $allBoms = BOM::whereIn('product_id', $productIds)
            ->where('status', 'active')
            ->where('is_active', true)
            ->with(['items.material'])
            ->get()
            ->groupBy('product_id');

        $productBoms = [];
        foreach ($products as $product) {
            $boms = $allBoms->get($product->id, collect());

            if ($boms->isNotEmpty()) {
                $productBoms[$product->id] = $boms->map(function($bom) use ($currencyCode, $exchangeRate) {
                    $isUSD = $currencyCode === 'USD';
                    $materialCost = 0;
                    $materials = [];

                    foreach ($bom->items as $item) {
                        $wastagePercent = floatval($item->wastage_percentage ?? 0);
                        $requiredQty = floatval($item->quantity) * (1 + ($wastagePercent / 100));

                        if ($isUSD) {
                            $costPerUnit = floatval($item->cost_per_unit_usd ?? 0);
                            if ($costPerUnit <= 0 && $item->cost_per_unit_afn > 0) {
                                $costPerUnit = floatval($item->cost_per_unit_afn) / $exchangeRate;
                            }
                        } else {
                            $costPerUnit = floatval($item->cost_per_unit_afn ?? 0);
                            if ($costPerUnit <= 0 && $item->cost_per_unit_usd > 0) {
                                $costPerUnit = floatval($item->cost_per_unit_usd) * $exchangeRate;
                            }
                        }

                        $totalCost = $requiredQty * $costPerUnit;
                        $materialCost += $totalCost;

                        $materials[] = [
                            'material_id' => $item->material_id,
                            'material_name' => $item->material->name ?? 'Unknown',
                            'required_qty' => $requiredQty,
                            'unit' => $item->unit,
                            'wastage_percentage' => $wastagePercent,
                            'cost_per_unit' => $costPerUnit,
                            'total_cost' => $totalCost,
                            'purchase_currency' => $item->purchase_currency ?? 'AFN',
                            'length_inch' => (float) ($item->length_inch ?? 0),
                            'width_inch' => (float) ($item->width_inch ?? 0),
                            'height_inch' => (float) ($item->height_inch ?? 0),
                            'paper_gsm' => (float) ($item->paper_gsm ?? 125),
                            'multiplication_layer' => (float) ($item->multiplication_layer ?? 1),
                            'formula_constant' => (float) ($item->formula_constant ?? 1550000),
                            'print' => (float) ($item->print ?? 0),
                            'work_percentage' => (float) ($item->work_percentage ?? 40),
                            'cut_length_inch' => (float) ($item->cut_length_inch ?? 0),
                            'cut_width_inch' => (float) ($item->cut_width_inch ?? 0),
                            'grh' => (float) ($item->grh ?? 0),
                            'ply' => (float) ($item->ply ?? 1),
                            'multiplication_method' => $item->multiplication_method ?? 'multiply',
                            'formula_type' => $item->formula_type ?? 'carton_3d',
                        ];
                    }

                    $sellingPrice = 0;
                    if ($isUSD) {
                        $sellingPrice = floatval($bom->selling_price_usd ?? 0);
                        if ($sellingPrice <= 0) {
                            $sellingPrice = floatval($bom->selling_price_afn ?? 0) / $exchangeRate;
                        }
                    } else {
                        $sellingPrice = floatval($bom->selling_price_afn ?? 0);
                        if ($sellingPrice <= 0) {
                            $sellingPrice = floatval($bom->selling_price_usd ?? 0) * $exchangeRate;
                        }
                    }

                    return [
                        'id' => $bom->id,
                        'code' => $bom->code,
                        'name' => $bom->name,
                        'version' => $bom->version,
                        'labor_cost' => floatval($bom->labor_cost_per_unit ?? 0),
                        'overhead_cost' => floatval($bom->overhead_cost_per_unit ?? 0),
                        'profit_margin_percentage' => floatval($bom->profit_margin_percentage ?? 0),
                        'material_cost' => $materialCost,
                        'total_cost' => $materialCost + floatval($bom->labor_cost_per_unit ?? 0) + floatval($bom->overhead_cost_per_unit ?? 0),
                        'selling_price' => $sellingPrice,
                        'selling_price_afn' => floatval($bom->selling_price_afn ?? 0),
                        'selling_price_usd' => floatval($bom->selling_price_usd ?? 0),
                        'exchange_rate' => $exchangeRate,
                        'materials' => $materials,
                        'items' => $materials,
                    ];
                })->values()->toArray();
            } else {
                $productBoms[$product->id] = [];
            }
        }

        // Pass variables to view
        return view('admin.sales.show', compact(
            'sale',
            'products',
            'materials', // ─── FIX: Pass materials to view ───
            'customers',
            'currencies',
            'totalProfit',
            'totalCost',
            'profitMargin',
            'currencyCode',
            'currencySymbol',
            'isUSD',
            'exchangeRate',
            'productBoms',
            'profitSummary'
        ));
    }

    /**
     * Display deleted sales.
     */
    public function deleted(Request $request)
    {
        $query = Sale::onlyTrashed()
            ->with(['customer', 'currency', 'deletedBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sale_no', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $deletedSales = $query->latest('deleted_at')->paginate(15);

        $stats = [
            'total_deleted' => Sale::onlyTrashed()->count(),
            'deleted_draft' => Sale::onlyTrashed()->where('status', 'draft')->count(),
            'deleted_confirmed' => Sale::onlyTrashed()->where('status', 'confirmed')->count(),
            'deleted_shipped' => Sale::onlyTrashed()->where('status', 'shipped')->count(),
            'deleted_delivered' => Sale::onlyTrashed()->where('status', 'delivered')->count(),
        ];

        return view('admin.sales.deleted', compact('deletedSales', 'stats'));
    }

    /**
     * Get available purchase items for a product.
     */
    public function getAvailableBatches(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $batches = PurchaseItem::where('product_id', $request->product_id)
            ->where('qty_available', '>', 0)
            ->whereHas('purchase', function ($query) {
                $query->where('status', 'arrived');
            })
            ->with(['purchase', 'purchase.currency'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($item) {
                $costPerUnit = $item->usd_cost_per_item ?? 0;
                if ($costPerUnit == 0 && $item->qty > 0) {
                    $costPerUnit = $item->usd_total / $item->qty;
                }
                $purchaseCurrency = $item->purchase->currency;

                return [
                    'id' => $item->id,
                    'purchase_no' => $item->purchase->purchase_no ?? 'N/A',
                    'purchase_date' => $item->purchase->purchase_date
                        ? date('Y-m-d', strtotime($item->purchase->purchase_date))
                        : date('Y-m-d', strtotime($item->created_at)),
                    'batch_no' => $item->batch_no ?? 'N/A',
                    'available_qty' => (float) $item->qty_available,
                    'cost_per_unit_usd' => (float) $costPerUnit,
                    'cost_per_unit_local' => (float) ($costPerUnit * ($item->rate ?? 1)),
                    'currency_symbol' => $purchaseCurrency->symbol ?? '$',
                    'currency_code' => $purchaseCurrency->code ?? 'USD',
                    'rate' => (float) ($item->rate ?? 1),
                    'total_cost_usd' => (float) ($item->qty_available * $costPerUnit),
                ];
            });

        return response()->json([
            'success' => true,
            'batches' => $batches
        ]);
    }

    /**
     * Add items to sale (supports multiple items with BOM)
     */
    public function addItem(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'items' => 'required|array|min:1',
            'items.*.bom_id' => 'required|exists:boms,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.remarks' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $sale = Sale::with(['items', 'currency'])->findOrFail($request->sale_id);
            if ($sale->status !== 'draft') {
                throw new \RuntimeException('Cannot add items to a confirmed sale.');
            }

            $itemsAdded = collect();
            $exchangeRate = max((float) ($sale->exchange_rate ?? 0), 0.000001);
            if ($exchangeRate <= 0.000001) {
                $exchangeRate = 85;
            }
            $saleCurrency = $sale->currency?->code ?? 'AFN';

            foreach ($request->items as $itemData) {
                $bom = BOM::with(['items.material'])->findOrFail($itemData['bom_id']);
                $qty = (float) $itemData['qty'];

                if ((float) ($sale->exchange_rate ?? 0) <= 0) {
                    $exchangeRate = max((float) $bom->getUSDtoAFNRate(), 0.000001);
                }

                $summary = app(\App\Services\BOMCostingService::class)->summarize($bom);
                $costPerUnitUsd = (float) $summary['physical_production_cost_usd'];
                $totalCostUsd = $costPerUnitUsd * $qty;

                $unitPriceAfn = (float) ($bom->selling_price_afn ?? 0);
                if ($unitPriceAfn <= 0) {
                    $unitPriceAfn = (float) $summary['selling_price_afn'];
                }

                if ($unitPriceAfn <= 0) {
                    throw new \RuntimeException("BOM {$bom->code} has no valid selling price.");
                }

                $unitPrice = $saleCurrency === 'USD'
                    ? $unitPriceAfn / $exchangeRate
                    : $unitPriceAfn;
                $total = $unitPrice * $qty;
                $usdUnitPrice = $saleCurrency === 'USD'
                    ? $unitPrice
                    : $unitPrice / $exchangeRate;
                $usdTotal = $usdUnitPrice * $qty;

                $profitUsd = $usdTotal - $totalCostUsd;
                $profitAfn = $profitUsd * $exchangeRate;
                $profitPercentage = $usdTotal > 0
                    ? ($profitUsd / $usdTotal) * 100
                    : 0;

                $materialBreakdown = $bom->items->map(function ($bomItem) use ($qty, $exchangeRate) {
                    $requiredQty = $bomItem->calculateStockRequirement($qty, true);
                    $costPerUnitUsd = (float) ($bomItem->cost_per_unit_usd ?? 0);
                    $totalUsd = $requiredQty * $costPerUnitUsd;

                    return [
                        'material_id' => $bomItem->material_id,
                        'material_name' => $bomItem->material->name ?? 'Unknown',
                        'required_qty' => $requiredQty,
                        'unit' => $bomItem->material?->is_roll_based ? 'kg' : $bomItem->unit,
                        'wastage_percentage' => (float) ($bomItem->wastage_percentage ?? 0),
                        'cost_per_unit_usd' => $costPerUnitUsd,
                        'total_cost_usd' => $totalUsd,
                        'total_cost_afn' => $totalUsd * $exchangeRate,
                    ];
                })->all();

                $remarks = $itemData['remarks'] ?? "BOM: {$bom->code}";
                $remarks .= " | Rate: 1 USD = {$exchangeRate} AFN";
                $remarks .= " | Materials: " . count($materialBreakdown) . " items";

                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $bom->product_id,
                    'bom_id' => $bom->id,
                    'purchase_item_id' => null,
                    'sale_currency_id' => $sale->currency_id,
                    'qty' => $qty,
                    'cost_per_unit_usd' => $costPerUnitUsd,
                    'total_cost_usd' => $totalCostUsd,
                    'unit_price' => $unitPrice,
                    'total' => $total,
                    'discount' => 0,
                    'tax' => 0,
                    'usd_unit_price' => $usdUnitPrice,
                    'usd_total' => $usdTotal,
                    'usd_discount' => 0,
                    'usd_tax' => 0,
                    'rate' => $exchangeRate,
                    'profit_usd' => $profitUsd,
                    'profit_afn' => $profitAfn,
                    'profit_percentage' => $profitPercentage,
                    'remarks' => $remarks,
                ]);

                $itemsAdded->push($saleItem);
            }

            $sale->recalculateTotals();

            // Build the response while the transaction is still open so any
            // unexpected serialization/relationship error cannot leave the user
            // with a committed item and a failed HTTP response.
            $responseItems = $itemsAdded->map(function ($item) {
                $item->loadMissing('product');

                return [
                    'id' => $item->id,
                    'product_name' => $item->product->name ?? 'N/A',
                    'qty' => $item->qty,
                    'unit_price' => $item->unit_price,
                    'total' => $item->total,
                    'cost_per_unit_usd' => $item->cost_per_unit_usd,
                    'profit_usd' => $item->profit_usd,
                    'exchange_rate' => $item->rate,
                ];
            })->values();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $itemsAdded->count() . ' item(s) added successfully',
                'items' => $responseItems,
                'exchange_rate' => $exchangeRate,
                'currency' => $saleCurrency,
                'sale_total' => $sale->grand_total,
                'sale_total_usd' => $sale->usd_grand_total,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('Error adding items to sale', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error adding items: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sale items with BOM data
     */
    public function getSaleItemsWithBOM($id)
    {
        $sale = Sale::with(['items', 'items.bom', 'items.product'])->findOrFail($id);

        $items = $sale->items->map(function($item) {
            return [
                'id' => $item->id,
                'product_name' => $item->product->name ?? 'N/A',
                'bom_code' => $item->bom->code ?? 'N/A',
                'qty' => $item->qty,
                'unit_price_afn' => $item->unit_price,
                'total_afn' => $item->total,
                'cost_per_unit_usd' => $item->cost_per_unit_usd,
                'total_cost_usd' => $item->total_cost_usd,
                'profit_usd' => $item->profit_usd,
                'profit_percentage' => $item->profit_percentage,
                'exchange_rate' => $item->rate,
                'remarks' => $item->remarks,
            ];
        });

        return response()->json([
            'success' => true,
            'sale' => [
                'id' => $sale->id,
                'sale_no' => $sale->sale_no,
                'status' => $sale->status,
                'grand_total_afn' => $sale->grand_total,
                'usd_grand_total' => $sale->usd_grand_total,
            ],
            'items' => $items
        ]);
    }

    /**
     * Get USD to AFN exchange rate
     */
    private function getUSDtoAFNRate()
    {
        $afn = Currency::where('code', 'AFN')->first();
        $usd = Currency::where('code', 'USD')->first();

        if ($afn && $usd && $usd->exchange_rate > 0) {
            return $afn->exchange_rate / $usd->exchange_rate;
        }

        // Fallback to settings
        $setting = Currency::first();
        return $setting->default_exchange_rate ?? 85;
    }

    /**
     * Get material stock cost (latest purchase cost)
     */
    public function getMaterialStockCost(Request $request)
    {
        $request->validate([
            'material_id' => 'required|exists:products,id',
            'exchange_rate' => 'nullable|numeric|min:0.0001',
        ]);

        try {
            $materialId = $request->material_id;
            $exchangeRate = (float) ($request->exchange_rate ?? 85);

            // ─── GET LATEST PURCHASE ITEM ───
            $purchaseItem = PurchaseItem::where('product_id', $materialId)
                ->where('qty_available', '>', 0)
                ->whereHas('purchase', function($q) {
                    $q->where('status', 'arrived');
                })
                ->with(['purchase.currency', 'purchase.supplier'])
                ->latest('id')
                ->first();

            if (!$purchaseItem) {
                // ─── NO STOCK AVAILABLE ───
                $material = Product::find($materialId);
                return response()->json([
                    'success' => true,
                    'data' => [
                        'material_id' => $materialId,
                        'cost' => 0,
                        'currency' => 'AFN',
                        'unit' => $material->unit ?? 'unit',
                        'available_qty' => 0,
                        'batch_no' => null,
                        'purchase_date' => null,
                        'supplier' => null,
                    ]
                ]);
            }

            // ─── GET COST PER UNIT ───
            $costUsd = (float) ($purchaseItem->usd_cost_per_item ?? 0);
            if ($costUsd <= 0 && (float) ($purchaseItem->qty ?? 0) > 0) {
                $costUsd = (float) ($purchaseItem->usd_total ?? 0) / (float) $purchaseItem->qty;
            }

            // ─── GET CURRENCY ───
            $currency = 'AFN';
            if ($purchaseItem->purchase && $purchaseItem->purchase->currency) {
                $currency = $purchaseItem->purchase->currency->code ?? 'AFN';
            }

            // ─── CALCULATE COST IN AFN ───
            $costAfn = $costUsd * $exchangeRate;
            $costToReturn = $currency === 'USD' ? $costUsd : $costAfn;

            return response()->json([
                'success' => true,
                'data' => [
                    'material_id' => $materialId,
                    'cost' => $costToReturn,
                    'currency' => $currency,
                    'unit' => $purchaseItem->unit ?? 'unit',
                    'available_qty' => (float) ($purchaseItem->qty_available ?? 0),
                    'batch_no' => $purchaseItem->batch_no ?? null,
                    'purchase_date' => optional($purchaseItem->purchase)->purchase_date
                        ? date('Y-m-d', strtotime($purchaseItem->purchase->purchase_date))
                        : null,
                    'supplier' => optional(optional($purchaseItem->purchase)->supplier)->name,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching material stock cost', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'material_id' => $request->material_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching material cost: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create BOM from calculator modal
     */
    public function createBOMFromCalculator(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'name' => 'required|string|max:255',
                'items' => 'required|json',
                'exchange_rate' => 'nullable|numeric|min:0.0001',
                'currency_code' => 'nullable|string|in:USD,AFN',
                'total_cost_usd' => 'nullable|numeric|min:0',
                'total_cost_afn' => 'nullable|numeric|min:0',
                'selling_price' => 'nullable|numeric|min:0',
                'sale_id' => 'nullable|exists:sales,id',
                'work_percentage' => 'nullable|numeric|min:0',
                'profit_margin_percentage' => 'nullable|numeric|min:0',
            ]);

            DB::beginTransaction();

            $product = Product::findOrFail($request->product_id);
            $items = json_decode($request->items, true);
            $exchangeRate = (float) ($request->exchange_rate ?? 85);
            $currencyCode = $request->currency_code ?? 'AFN';
            $isUSD = $currencyCode === 'USD';

            // ─── CREATE BOM ───
            $bom = BOM::create([
                'name' => $request->name,
                'product_id' => $request->product_id,
                'code' => 'BOM-' . strtoupper(uniqid()),
                'version' => '1.0',
                'status' => 'active',
                'is_active' => true,
                'work_percentage' => $request->work_percentage ?? 40,
                'profit_margin_percentage' => $request->profit_margin_percentage ?? 0,
                'exchange_rate' => $exchangeRate,
                'exchange_rate_updated_at' => now(),
                'created_by' => Auth::id(),
                'total_material_cost_usd' => $request->total_cost_usd ?? 0,
                'total_material_cost_afn' => $request->total_cost_afn ?? 0,
                'selling_price_afn' => $request->selling_price ?? 0,
            ]);

            // ─── CREATE BOM ITEMS ───
            foreach ($items as $itemData) {
                $materialId = $itemData['material_id'] ?? null;
                $materialName = $itemData['material_name'] ?? 'Unknown Material';
                $quantity = (float) ($itemData['quantity'] ?? 0);
                $unit = $itemData['unit'] ?? 'unit';
                $costPerUnitUsd = (float) ($itemData['cost_per_unit_usd'] ?? 0);
                $costPerUnitAfn = (float) ($itemData['cost_per_unit_afn'] ?? 0);
                $purchaseCurrency = $itemData['purchase_currency'] ?? 'AFN';
                $formulaType = $itemData['formula_type'] ?? 'fixed';
                $isFormulaBased = $itemData['is_formula_based'] ?? false;
                $wastage = (float) ($itemData['wastage_percentage'] ?? 0);

                // If material_id is not provided, create or find by name
                if (!$materialId) {
                    $material = Product::where('name', $materialName)
                        ->where('product_type', 'raw_material')
                        ->first();

                    if (!$material) {
                        $material = Product::create([
                            'name' => $materialName,
                            'product_type' => 'raw_material',
                            'unit' => $unit,
                            'is_active' => true,
                        ]);
                    }
                    $materialId = $material->id;
                }

                $bomItemData = [
                    'bom_id' => $bom->id,
                    'material_id' => $materialId,
                    'quantity' => $quantity,
                    'unit' => $unit,
                    'wastage_percentage' => $wastage,
                    'cost_per_unit_usd' => $costPerUnitUsd,
                    'cost_per_unit_afn' => $costPerUnitAfn,
                    'total_cost_usd' => $quantity * $costPerUnitUsd,
                    'total_cost_afn' => $quantity * $costPerUnitAfn,
                    'purchase_currency' => $purchaseCurrency,
                    'formula_type' => $formulaType,
                    'is_formula_based' => $isFormulaBased,
                ];

                // Add formula fields based on type
                if ($formulaType === 'carton_3d') {
                    $bomItemData['length_inch'] = $itemData['length_inch'] ?? null;
                    $bomItemData['width_inch'] = $itemData['width_inch'] ?? null;
                    $bomItemData['height_inch'] = $itemData['height_inch'] ?? null;
                    $bomItemData['paper_gsm'] = $itemData['paper_gsm'] ?? null;
                    $bomItemData['per_gram_rate'] = $itemData['per_gram_rate'] ?? null;
                    $bomItemData['layers'] = $itemData['layers'] ?? null;
                    $bomItemData['multiplication_layer'] = $itemData['multiplication_layer'] ?? null;
                    $bomItemData['print'] = $itemData['print'] ?? null;
                    $bomItemData['formula_constant'] = $itemData['formula_constant'] ?? null;
                    $bomItemData['work_percentage'] = $itemData['work_percentage'] ?? null;
                } elseif ($formulaType === 'cut_roll') {
                    $bomItemData['cut_length_inch'] = $itemData['cut_length_inch'] ?? null;
                    $bomItemData['cut_width_inch'] = $itemData['cut_width_inch'] ?? null;
                    $bomItemData['grh'] = $itemData['grh'] ?? null;
                    $bomItemData['per_gram_rate'] = $itemData['per_gram_rate'] ?? null;
                    $bomItemData['ply'] = $itemData['ply'] ?? null;
                    $bomItemData['multiplication_layer'] = $itemData['multiplication_layer'] ?? null;
                    $bomItemData['print'] = $itemData['print'] ?? null;
                    $bomItemData['formula_constant'] = $itemData['formula_constant'] ?? null;
                    $bomItemData['work_percentage'] = $itemData['work_percentage'] ?? null;
                    $bomItemData['multiplication_method'] = $itemData['multiplication_method'] ?? null;
                } elseif ($formulaType === 'fixed_percentage') {
                    $bomItemData['base_material_id'] = $itemData['base_material_id'] ?? null;
                    $bomItemData['percentage_of_base'] = $itemData['percentage_of_base'] ?? null;
                } elseif ($formulaType === 'fixed_rate') {
                    $bomItemData['rate_per_unit'] = $itemData['rate_per_unit'] ?? null;
                    $bomItemData['rate_base_units'] = $itemData['rate_base_units'] ?? null;
                }

                BOMItem::create($bomItemData);
            }

            // ─── IF SALE ID IS PROVIDED, SELECT THIS BOM ───
            if ($request->sale_id) {
                session(['selected_bom_id' => $bom->id]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'BOM created successfully with ' . count($items) . ' materials!',
                'bom_id' => $bom->id,
                'bom' => $bom->load('items'),
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error creating BOM from calculator', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating BOM: ' . $e->getMessage(),
            ], 422);
        }
    }
    /**
     * Add calculated item to sale (from calculator)
     */
    public function addCalculatedItem(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0.0001',
            'exchange_rate' => 'nullable|numeric|min:0.0001',
            'currency_code' => 'nullable|string|in:USD,AFN',
            'formula_type' => 'required|string|in:carton_3d,cut_roll',
            'formula_params' => 'required|json',
            'remarks' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $sale = Sale::with('currency')->findOrFail($request->sale_id);
            if ($sale->status !== 'draft') {
                throw new \RuntimeException('Cannot add items to a confirmed sale.');
            }

            $product = Product::findOrFail($request->product_id);
            $qty = (float) $request->qty;
            $exchangeRate = (float) ($request->exchange_rate ?? $sale->exchange_rate ?? 85);
            $currencyCode = $request->currency_code ?? ($sale->currency->code ?? 'AFN');
            $isUSD = $currencyCode === 'USD';
            $unitPrice = (float) $request->unit_price;
            $formulaParams = json_decode($request->formula_params, true);

            // ─── CALCULATE COST ───
            // Since we already calculated the selling price, we need to calculate the cost
            // The cost is the selling price divided by (1 + profit margin)
            $profitMargin = (float) ($formulaParams['profit_margin'] ?? 0);
            $costPerUnitAfn = $unitPrice / (1 + $profitMargin / 100);
            $costPerUnitUsd = $costPerUnitAfn / $exchangeRate;

            $totalCostUsd = $costPerUnitUsd * $qty;
            $totalPrice = $unitPrice * $qty;
            $usdUnitPrice = $isUSD ? $unitPrice : $unitPrice / $exchangeRate;
            $usdTotal = $usdUnitPrice * $qty;

            // ─── PROFIT CALCULATION ───
            $profitUsd = $usdTotal - $totalCostUsd;
            $profitAfn = $profitUsd * $exchangeRate;
            $profitPercentage = $totalCostUsd > 0 ? ($profitUsd / $totalCostUsd) * 100 : 0;

            // ─── BUILD REMARKS ───
            $remarks = $request->remarks ?? "Calculated using Excel formula";
            $remarks .= " | Type: " . ($request->formula_type === 'carton_3d' ? '3D Carton' : 'Cut/Roll');
            $remarks .= " | Qty: {$qty} units";

            // ─── CREATE SALE ITEM ───
            $saleItem = SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $request->product_id,
                'bom_id' => null,
                'purchase_item_id' => null,
                'sale_currency_id' => $sale->currency_id,
                'qty' => $qty,
                'cost_per_unit_usd' => $costPerUnitUsd,
                'total_cost_usd' => $totalCostUsd,
                'unit_price' => $unitPrice,
                'total' => $totalPrice,
                'discount' => 0,
                'tax' => 0,
                'usd_unit_price' => $usdUnitPrice,
                'usd_total' => $usdTotal,
                'usd_discount' => 0,
                'usd_tax' => 0,
                'rate' => $exchangeRate,
                'profit_usd' => $profitUsd,
                'profit_afn' => $profitAfn,
                'profit_percentage' => $profitPercentage,
                'remarks' => $remarks,
                'formula_snapshot' => $request->formula_params,
            ]);

            // ─── LOG ───
            \Log::info('Calculated item added to sale', [
                'sale_item_id' => $saleItem->id,
                'sale_id' => $sale->id,
                'product_id' => $request->product_id,
                'unit_price' => $unitPrice,
                'qty' => $qty,
                'cost_per_unit_usd' => $costPerUnitUsd,
                'profit_percentage' => $profitPercentage,
                'formula_type' => $request->formula_type,
                'formula_params' => $formulaParams,
            ]);

            // ─── RECALCULATE SALE TOTALS ───
            $sale->recalculateTotals();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item added successfully.',
                'item' => $saleItem->load(['product']),
                'sale_total' => $sale->grand_total,
                'usd_total' => $sale->usd_grand_total,
                'profit_afn' => $profitAfn,
                'profit_usd' => $profitUsd,
                'profit_percentage' => $profitPercentage,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error adding calculated item', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }


    /**
     * Get BOMs for a specific product (for AJAX)
     */
    public function getBomsForProduct(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'currency_code' => 'nullable|string|in:USD,AFN',
            'exchange_rate' => 'nullable|numeric|min:0.0001',
        ]);

        $currencyCode = $request->currency_code ?? 'AFN';
        $saleRate = (float) ($request->exchange_rate ?? 85);
        $isUSD = $currencyCode === 'USD';

        $boms = BOM::query()
            ->where('product_id', $request->product_id)
            ->where('status', 'active')
            ->where('is_active', true)
            ->with('items.material')
            ->get();

        $bomData = $boms->map(function ($bom) use ($currencyCode, $saleRate, $isUSD) {
            // ─── CALCULATE MATERIAL COST ───
            $materialCost = 0;
            $items = $bom->items->map(function ($item) use ($saleRate, $isUSD) {
                $inventory = $this->latestInventoryCostForMaterial(
                    (int) $item->material_id,
                    $saleRate
                );

                $costPerUnit = $isUSD
                    ? (float) ($item->cost_per_unit_usd ?? $inventory['cost_usd'] ?? 0)
                    : (float) ($item->cost_per_unit_afn ?? $inventory['cost_afn'] ?? 0);

                $wastage = (float) ($item->wastage_percentage ?? 0);
                $requiredQty = (float) $item->quantity * (1 + $wastage / 100);

                return [
                    'id' => $item->id,
                    'material_id' => $item->material_id,
                    'material_name' => $item->material->name ?? 'Unknown',
                    'quantity' => (float) $item->quantity,
                    'unit' => $item->unit ?: ($inventory['unit'] ?? 'unit'),
                    'wastage_percentage' => $wastage,
                    'cost_per_unit' => $costPerUnit,
                    'required_qty' => $requiredQty,
                    'total_cost' => $requiredQty * $costPerUnit,
                    'inventory_cost_found' => (bool) $inventory['found'],
                    'latest_material_cost_afn' => (float) $inventory['cost_afn'],
                    'latest_material_cost_usd' => (float) $inventory['cost_usd'],
                ];
            })->values();

            // ─── CALCULATE TOTAL COST ───
            $totalMaterialCost = $items->sum('total_cost');
            $laborCost = (float) ($bom->labor_cost_per_unit ?? 0);
            $overheadCost = (float) ($bom->overhead_cost_per_unit ?? 0);
            $totalCost = $totalMaterialCost + $laborCost + $overheadCost;

            // ─── CALCULATE SELLING PRICE WITH PROFIT MARGIN ───
            $profitMarginPercent = (float) ($bom->profit_margin_percentage ?? 0);
            $sellingPrice = $totalCost * (1 + ($profitMarginPercent / 100));

            return [
                'id' => $bom->id,
                'code' => $bom->code,
                'name' => $bom->name,
                'version' => $bom->version,
                'labor_cost_per_unit' => $laborCost,
                'overhead_cost_per_unit' => $overheadCost,
                'profit_margin_percentage' => $profitMarginPercent,
                'exchange_rate' => (float) ($bom->exchange_rate ?? $saleRate),
                'total_cost_afn' => $totalCost,
                'total_cost_usd' => $totalCost / $saleRate,
                'selling_price_afn' => $sellingPrice,
                'selling_price_usd' => $sellingPrice / $saleRate,
                'currency_code' => $currencyCode,
                'materials' => $items,
                'items' => $items,
            ];
        });

        return response()->json([
            'success' => true,
            'boms' => $bomData,
        ]);
    }
    public function getBomDetails(Request $request)
    {
        $request->validate([
            'bom_id' => 'required|exists:boms,id',
            'quantity' => 'nullable|numeric|min:0.01',
            'currency_code' => 'nullable|string|in:USD,AFN',
            'exchange_rate' => 'nullable|numeric|min:0.0001',
        ]);

        $bom = BOM::with(['items.material'])->findOrFail($request->bom_id);
        $quantity = (float) ($request->quantity ?? 1);
        $currencyCode = $request->currency_code ?? 'AFN';
        $saleRate = (float) ($request->exchange_rate ?? $bom->exchange_rate ?? 85);
        $isUSD = $currencyCode === 'USD';

        $materialDetails = $bom->items->map(function ($item) use ($saleRate, $quantity, $isUSD) {
            $inventory = $this->latestInventoryCostForMaterial(
                (int) $item->material_id,
                $saleRate
            );

            // ─── AUTHORITATIVE PURCHASE RATE (USD per kg) FOR MANUAL BOM ───
            // The manual BOM estimator must default per-gram-rate to the latest
            // valid purchase cost per kg, never the stale template per_gram_rate.
            $purchaseRateKg = $this->latestPurchaseRateKgForMaterial((int) $item->material_id);

            // ─── GET BOM ITEM COSTS ───
            // First try to get from the BOM item itself
            $costPerUnitUsd = (float) ($item->cost_per_unit_usd ?? 0);
            $costPerUnitAfn = (float) ($item->cost_per_unit_afn ?? 0);

            // If cost is 0, try to get from inventory
            if ($costPerUnitUsd <= 0 && $inventory['found']) {
                $costPerUnitUsd = (float) $inventory['cost_usd'];
                $costPerUnitAfn = (float) $inventory['cost_afn'];
            }

            // ─── FALLBACK: If still 0, use the item's material weighted average ───
            if ($costPerUnitUsd <= 0 && $item->material) {
                $costPerUnitUsd = (float) ($item->material->weighted_avg_cost ?? 0);
                $costPerUnitAfn = $costPerUnitUsd * $saleRate;
            }

            // ─── DETERMINE COST TO USE ───
            $costToUse = $isUSD ? $costPerUnitUsd : $costPerUnitAfn;

            // ─── CALCULATE WASTAGE ───
            $wastage = (float) ($item->wastage_percentage ?? 0);
            $requiredQty = (float) ($item->quantity ?? 0) * (1 + $wastage / 100);

            // ─── CALCULATE TOTAL COSTS ───
            $totalCostUsd = $requiredQty * $costPerUnitUsd * $quantity;
            $totalCostAfn = $requiredQty * $costPerUnitAfn * $quantity;

            return [
                'id' => $item->id,
                'material_id' => $item->material_id,
                'material_name' => $item->material->name ?? 'Unknown',
                'quantity' => (float) ($item->quantity ?? 0),
                'required_qty' => $requiredQty * $quantity,
                'unit' => $item->unit ?: ($inventory['unit'] ?? 'unit'),
                'wastage_percentage' => $wastage,

                // ─── BOM ITEM COSTS (CRITICAL FOR MANUAL BOM) ───
                'cost_per_unit_usd' => $costPerUnitUsd,
                'cost_per_unit_afn' => $costPerUnitAfn,
                'total_cost_usd' => $totalCostUsd,
                'total_cost_afn' => $totalCostAfn,
                'cost_to_use' => $costToUse, // The cost that should be used in calculations

                // ─── 3D CARTON FORMULA FIELDS ───
                'length_inch' => (float) ($item->length_inch ?? 0),
                'width_inch' => (float) ($item->width_inch ?? 0),
                'height_inch' => (float) ($item->height_inch ?? 0),
                'reel_length_inch' => (float) ($item->reel_length_inch ?? 0),
                'reel_height_inch' => (float) ($item->reel_height_inch ?? 0),
                'paper_gsm' => (float) ($item->paper_gsm ?? 125),
                'layers' => (float) ($item->layers ?? 1),
                'division_factor' => (float) ($item->division_factor ?? 1),

                // ─── CUT/ROLL FORMULA FIELDS ───
                'cut_length_inch' => (float) ($item->cut_length_inch ?? 0),
                'cut_width_inch' => (float) ($item->cut_width_inch ?? 0),
                'grh' => (float) ($item->grh ?? 0),
                'ply' => (float) ($item->ply ?? 1),

                // ─── COMMON FORMULA FIELDS ───
                'per_gram_rate' => (float) ($item->per_gram_rate ?? 43),
                'multiplication_layer' => (float) ($item->multiplication_layer ?? 1),
                'formula_constant' => (float) ($item->formula_constant ?? 1550000),
                'work_percentage' => (float) ($item->work_percentage ?? 40),
                'multiplication_method' => $item->multiplication_method ?? 'multiply',

                // ─── COST FIELDS ───
                'print' => (float) ($item->print ?? 0),
                'print_cost_afn' => (float) ($item->print ?? 0),

                // ─── INVENTORY INFO (FOR REFERENCE) ───
                'inventory_cost_found' => (bool) $inventory['found'],
                'latest_material_cost' => $isUSD ? (float) $inventory['cost_usd'] : (float) $inventory['cost_afn'],
                'latest_material_cost_afn' => (float) $inventory['cost_afn'],
                'latest_material_cost_usd' => (float) $inventory['cost_usd'],
                'latest_purchase_item_id' => $inventory['purchase_item_id'],
                'latest_purchase_date' => $inventory['purchase_date'],
                'latest_supplier_name' => $inventory['supplier_name'],
                'latest_batch_no' => $inventory['batch_no'],
                'latest_available_qty' => (float) $inventory['available_qty'],
                'latest_inventory_exchange_rate' => (float) $inventory['exchange_rate'],
                'inventory_unit' => $inventory['unit'] ?? 'unit',

                // ─── AUTHORITATIVE PURCHASE RATE (per kg) ───
                'purchase_rate_usd_kg' => $purchaseRateKg,
                'purchase_rate_afn_kg' => $purchaseRateKg > 0 ? round($purchaseRateKg * $saleRate, 6) : 0.0,
                'purchase_rate_found' => $purchaseRateKg > 0,
                'material_is_roll_based' => (bool) ($item->material->is_roll_based ?? false),
            ];
        })->values();

        // ─── CHECK IF ANY MATERIAL HAS MISSING COST ───
        $hasMissingCost = $materialDetails->contains(function ($material) {
            return $material['cost_per_unit_usd'] <= 0 && $material['cost_per_unit_afn'] <= 0;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'bom' => [
                    'id' => (int) $bom->id,
                    'code' => $bom->code,
                    'name' => $bom->name,
                    'version' => $bom->version,
                ],
                'quantity' => $quantity,
                'currency' => $currencyCode,
                'currency_symbol' => $isUSD ? '$' : '؋',
                'exchange_rate' => $saleRate,
                'materials' => $materialDetails,
                'has_missing_inventory_cost' => $hasMissingCost,
            ],
        ]);
    }


    /**
     * Add item to sale with BOM selection
     */
    public function addItemWithBOM(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'bom_id' => 'required|exists:boms,id',
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|numeric|min:0.01',
            'exchange_rate' => 'nullable|numeric|min:0.0001',
            'currency_code' => 'nullable|string|in:USD,AFN',
            'pricing_mode' => 'required|in:saved,manual',
            'quoted_unit_price' => 'nullable|numeric|min:0',
            'formula_snapshot' => 'nullable|string',
            'remarks' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $sale = Sale::with('currency')->findOrFail($validated['sale_id']);
            if ($sale->status !== 'draft') {
                throw new \RuntimeException('Cannot add items to a confirmed sale.');
            }

            $bom = BOM::with(['items.material'])->findOrFail($validated['bom_id']);
            if ((int) $bom->product_id !== (int) $validated['product_id']) {
                throw new \RuntimeException('The selected BOM does not belong to the selected product.');
            }

            $qty = (float) $validated['qty'];
            $exchangeRate = (float) ($validated['exchange_rate'] ?? $sale->exchange_rate ?? $bom->exchange_rate ?? 85);
            $currencyCode = $validated['currency_code'] ?? ($sale->currency->code ?? 'AFN');
            $isUSD = $currencyCode === 'USD';
            $pricingMode = $validated['pricing_mode'];
            $quotedUnitPrice = (float) ($validated['quoted_unit_price'] ?? 0);

            $totalCostUsd = 0.0;
            $materialBreakdown = [];
            $formulaSnapshot = [];

            if ($pricingMode === 'manual') {
                // ─── MANUAL BOM MODE ───
                $formulaSnapshot = json_decode($validated['formula_snapshot'] ?? '[]', true);
                if (!is_array($formulaSnapshot) || count($formulaSnapshot) === 0) {
                    throw new \RuntimeException('Manual BOM calculation details are missing. Please recalculate the quotation.');
                }

                // ─── CALCULATE TOTAL COST PER UNIT ───
                $totalCostPerUnitAfn = 0;
                foreach ($formulaSnapshot as $row) {
                    $materialId = (int) ($row['material_id'] ?? 0);
                    if ($materialId <= 0) {
                        throw new \RuntimeException('A material is missing from the manual calculation.');
                    }

                    // ─── AUTHORITATIVE MATERIAL RATE (USD per kg → AFN per kg) ───
                    // Material prices for a NEW manual BOM quotation must come
                    // from the latest valid purchase for that material, never
                    // from the stale per_gram_rate stored on the BOM template
                    // or in old quotation snapshots. The client-sent rate is
                    // deliberately ignored here.
                    $materialName = optional(Product::find($materialId))->name ?? 'Unknown';
                    $purchaseRateKg = $this->latestPurchaseRateKgForMaterial($materialId);
                    if ($purchaseRateKg <= 0) {
                        throw new \RuntimeException("No valid purchase price found for [{$materialName}]. Please check the material's purchase history.");
                    }
                    $perGramRate = $purchaseRateKg * $exchangeRate;

                    // ─── VALIDATE REQUIRED FIELDS ───
                    $length = (float) ($row['length'] ?? 0);
                    $width = (float) ($row['width'] ?? 0);
                    $height = (float) ($row['height'] ?? 0);
                    $paperGsm = (float) ($row['paper_gsm'] ?? 0);
                    $multiplicationLayer = (float) ($row['multiplication_layer'] ?? 1);
                    $formulaConstant = (float) ($row['formula_constant'] ?? 1550000);
                    $workPercentage = (float) ($row['work_percentage'] ?? 40);
                    $wastage = (float) ($row['wastage'] ?? 0);
                    $printCost = (float) ($row['print_cost'] ?? 0);

                    if ($length <= 0 || $width <= 0 || $height <= 0 || $paperGsm <= 0 || $perGramRate <= 0 || $formulaConstant <= 0) {
                        throw new \RuntimeException('Carton dimensions, GSM, Per Gram Rate and Formula Constant must be greater than zero.');
                    }

                    // ─── CALCULATE USING THE EXCEL FORMULA ───
                    $reelLength = (($length + $width) * 2) + 4;
                    $reelHeight = $width + $height + 1;
                    $divisionValue = $reelLength * $reelHeight * $paperGsm * $perGramRate;
                    $paperRate = $divisionValue / $formulaConstant;
                    $paperRateByLayers = $multiplicationLayer * $paperRate;
                    $workAmount = $paperRateByLayers * ($workPercentage / 100);
                    $rowNetRate = $printCost + $paperRateByLayers + $workAmount;
                    // Wastage affects the physical production requirement, not the
                    // first Excel section's commercial quotation/net rate.
                    $finalRateAfn = $rowNetRate;

                    $totalCostPerUnitAfn += $finalRateAfn;

                    $materialBreakdown[] = [
                        'material_id' => $materialId,
                        'material_name' => $materialName,
                        'length' => $length,
                        'width' => $width,
                        'height' => $height,
                        'paper_gsm' => $paperGsm,
                        'per_gram_rate' => $perGramRate,
                        'multiplication_layer' => $multiplicationLayer,
                        'formula_constant' => $formulaConstant,
                        'work_percentage' => $workPercentage,
                        'wastage' => $wastage,
                        'print_cost' => $printCost,
                        'row_net_rate' => $rowNetRate,
                        'final_rate_afn' => $finalRateAfn,
                    ];
                }

                // ─── GET PROFIT MARGIN FROM BOM ───
                $profitMarginPercent = (float) ($bom->profit_margin_percentage ?? 0);

                // ─── CALCULATE UNIT PRICE WITH PROFIT MARGIN ───
                $totalCostPerUnitUsd = $totalCostPerUnitAfn / $exchangeRate;

                // ─── Excel Net Rate is the final default selling rate ───
                // The client's Excel Net Rate already includes the standard 40%
                // work/profit (Net Rate = Print + Paper Rate by Layers + Work/Profit).
                // Per the authoritative workflow, NO additional profit_margin_percentage
                // markup is added after the Net Rate for Excel/BOM quotation sales.
                $sellingPricePerUnitAfn = $totalCostPerUnitAfn;
                $sellingPricePerUnitUsd = $sellingPricePerUnitAfn / $exchangeRate;

                // ─── USE THE QUOTED PRICE IF PROVIDED, OTHERWISE USE CALCULATED SELLING PRICE ───
                if ($quotedUnitPrice > 0) {
                    $unitPrice = $quotedUnitPrice;
                } else {
                    $unitPrice = $isUSD ? $sellingPricePerUnitUsd : $sellingPricePerUnitAfn;
                }

                // ─── TOTAL COST FOR ALL QUANTITY ───
                $totalCostUsd = $totalCostPerUnitUsd * $qty;

            } else {
                // ─── SAVED BOM MODE ───
                // Get the selling price from the BOM
                if ($isUSD) {
                    $unitPrice = (float) ($bom->selling_price_usd ?? 0);
                } else {
                    $unitPrice = (float) ($bom->selling_price_afn ?? 0);
                }

                // A saved selling price does not replace the BOM cost basis.
                foreach ($bom->items as $item) {
                    $inventory = $this->latestInventoryCostForMaterial((int) $item->material_id, $exchangeRate);
                    $costPerUnitUsd = (float) ($item->cost_per_unit_usd ?? 0);
                    if ($costPerUnitUsd <= 0) {
                        $costPerUnitUsd = $inventory['found'] ? (float) $inventory['cost_usd'] : 0;
                    }

                    $requiredQty = (float) $item->quantity
                        * (1 + ((float) ($item->wastage_percentage ?? 0) / 100));
                    $itemTotalUsd = $requiredQty * $costPerUnitUsd * $qty;
                    $totalCostUsd += $itemTotalUsd;

                    $materialBreakdown[] = [
                        'material_id' => $item->material_id,
                        'material_name' => $item->material->name ?? 'Unknown',
                        'purchase_item_id' => $inventory['purchase_item_id'],
                        'required_qty' => $requiredQty * $qty,
                        'cost_per_unit_usd' => $costPerUnitUsd,
                        'total_cost_usd' => $itemTotalUsd,
                    ];
                }

                // If no selling price set, calculate from material costs with profit margin
                if ($unitPrice <= 0) {

                    // Add labor and overhead
                    $laborCostUsd = ((float) ($bom->labor_cost_per_unit ?? 0)) / $exchangeRate * $qty;
                    $overheadCostUsd = ((float) ($bom->overhead_cost_per_unit ?? 0)) / $exchangeRate * $qty;
                    $totalCostUsd += $laborCostUsd + $overheadCostUsd;

                    // ─── CALCULATE SELLING PRICE WITH PROFIT MARGIN ───
                    $costPerUnitUsd = $totalCostUsd / $qty;
                    $profitMargin = (float) ($bom->profit_margin_percentage ?? 0) / 100;
                    $sellingPriceUsd = $costPerUnitUsd * (1 + $profitMargin);

                    if ($isUSD) {
                        $unitPrice = $sellingPriceUsd;
                    } else {
                        $unitPrice = $sellingPriceUsd * $exchangeRate;
                    }
                }

                // If still 0, use the quoted price or default
                if ($unitPrice <= 0 && $quotedUnitPrice > 0) {
                    $unitPrice = $quotedUnitPrice;
                }

                if ($unitPrice <= 0) {
                    throw new \RuntimeException('Could not determine unit price for the BOM. Please set a selling price on the BOM or use manual pricing.');
                }
            }

            // ─── CALCULATE FINAL TOTALS ───
            $totalPrice = $unitPrice * $qty;
            $usdUnitPrice = $isUSD ? $unitPrice : $unitPrice / $exchangeRate;
            $usdTotal = $usdUnitPrice * $qty;

            // Cost per unit in USD
            $costPerUnitUsd = $qty > 0 ? $totalCostUsd / $qty : 0;

            // ─── PROFIT CALCULATION ───
            $profitUsd = $usdTotal - $totalCostUsd;
            $profitAfn = $profitUsd * $exchangeRate;
            $profitPercentage = $usdTotal > 0 ? ($profitUsd / $usdTotal) * 100 : 0;

            // ─── BUILD REMARKS ───
            $remarks = $validated['remarks'] ?? ($pricingMode === 'manual'
                ? "Manual BOM quotation using {$bom->code}"
                : "Saved BOM price: {$bom->code}");
            $remarks .= ' | Pricing mode: ' . strtoupper($pricingMode);
            $remarks .= ' | Materials: ' . count($materialBreakdown);

            // ─── GUARD AGAINST ACCIDENTAL DUPLICATE ADD ───
            // A retry/double-submit of the SAME add-item request must not create
            // a second identical sale line. Two legitimate separate lines for the
            // same product are still allowed (e.g. different qty/price/mode).
            $duplicate = SaleItem::where('sale_id', $sale->id)
                ->where('product_id', $validated['product_id'])
                ->where('bom_id', $bom->id)
                ->where('qty', $qty)
                ->where('unit_price', $unitPrice)
                ->where('rate', $exchangeRate)
                ->where('remarks', $remarks)
                ->where('created_at', '>', now()->subMinutes(2))
                ->first();

            if ($duplicate) {
                \Log::warning('Duplicate sale item add skipped', [
                    'sale_id' => $sale->id,
                    'sale_item_id' => $duplicate->id,
                    'product_id' => $validated['product_id'],
                    'bom_id' => $bom->id,
                    'qty' => $qty,
                ]);

                $sale->recalculateTotals();
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Item already added to this sale.',
                    'duplicate_skipped' => true,
                    'sale_item_id' => $duplicate->id,
                ]);
            }

            // ─── CREATE SALE ITEM ───
            $saleItem = SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $validated['product_id'],
                'bom_id' => $bom->id,
                'purchase_item_id' => null,
                'sale_currency_id' => $sale->currency_id,
                'qty' => $qty,
                'cost_per_unit_usd' => $costPerUnitUsd,
                'total_cost_usd' => $totalCostUsd,
                'unit_price' => $unitPrice,
                'total' => $totalPrice,
                'discount' => 0,
                'tax' => 0,
                'usd_unit_price' => $usdUnitPrice,
                'usd_total' => $usdTotal,
                'usd_discount' => 0,
                'usd_tax' => 0,
                'rate' => $exchangeRate,
                'profit_usd' => $profitUsd,
                'profit_afn' => $profitAfn,
                'profit_percentage' => $profitPercentage,
                'remarks' => $remarks,
                'manual_bom_snapshot' => $pricingMode === 'manual' ? $materialBreakdown : null,
            ]);

            // ─── LOG THE CREATION ───
            \Log::info('Sale item created with BOM', [
                'sale_item_id' => $saleItem->id,
                'sale_id' => $sale->id,
                'bom_id' => $bom->id,
                'pricing_mode' => $pricingMode,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'cost_per_unit_usd' => $costPerUnitUsd,
                'total_cost_usd' => $totalCostUsd,
                'profit_usd' => $profitUsd,
                'profit_afn' => $profitAfn,
                'profit_percentage' => $profitPercentage,
                'exchange_rate' => $exchangeRate,
                'currency_code' => $currencyCode,
                'material_breakdown' => $materialBreakdown,
            ]);

            // ─── RECALCULATE SALE TOTALS ───
            $sale->recalculateTotals();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item added successfully.',
                'item' => $saleItem->load(['product', 'bom']),
                'sale_total' => $sale->grand_total,
                'usd_total' => $sale->usd_grand_total,
                'profit_afn' => $profitAfn,
                'profit_usd' => $profitUsd,
                'profit_percentage' => $profitPercentage,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error adding BOM item to sale', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->except(['formula_snapshot']),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    /**
     * Get latest inventory cost for a material
     */
    private function latestInventoryCostForMaterial(int $materialId, float $fallbackRate = 85): array
    {
        $purchaseItem = PurchaseItem::query()
            ->where('product_id', $materialId)
            ->where('qty_available', '>', 0)
            ->whereHas('purchase', function ($query) {
                $query->where('status', 'arrived');
            })
            ->with(['purchase.currency', 'purchase.supplier'])
            ->latest('id')
            ->first();

        if (!$purchaseItem) {
            return [
                'found' => false,
                'purchase_item_id' => null,
                'cost_usd' => 0.0,
                'cost_afn' => 0.0,
                'unit' => null,
                'purchase_date' => null,
                'supplier_name' => null,
                'batch_no' => null,
                'available_qty' => 0.0,
                'exchange_rate' => $fallbackRate,
            ];
        }

        $costUsd = (float) ($purchaseItem->usd_cost_per_item ?? 0);
        if ($costUsd <= 0 && (float) ($purchaseItem->qty ?? 0) > 0) {
            $costUsd = (float) ($purchaseItem->usd_total ?? 0) / (float) $purchaseItem->qty;
        }

        $rate = (float) ($purchaseItem->rate ?? $fallbackRate);
        if ($rate <= 0) {
            $rate = $fallbackRate > 0 ? $fallbackRate : 85;
        }

        $purchaseDate = optional($purchaseItem->purchase)->purchase_date
            ?? $purchaseItem->created_at;

        return [
            'found' => $costUsd > 0,
            'purchase_item_id' => $purchaseItem->id,
            'cost_usd' => $costUsd,
            'cost_afn' => $costUsd * $rate,
            'unit' => $purchaseItem->unit ?? null,
            'purchase_date' => $purchaseDate
                ? date('Y-m-d', strtotime($purchaseDate))
                : null,
            'supplier_name' => optional(optional($purchaseItem->purchase)->supplier)->name,
            'batch_no' => $purchaseItem->batch_no ?? null,
            'available_qty' => (float) ($purchaseItem->qty_available ?? 0),
            'exchange_rate' => $rate,
        ];
    }

    /**
     * Latest landed USD cost per kg for a material from its most recent
     * valid (arrived) purchase batch. Returns 0.0 when no valid rate exists.
     *
     * This is the authoritative per-kilogram price used to build NEW manual
     * BOM quotations in Sale Orders. It deliberately ignores the stale
     * per_gram_rate stored on the BOM template or in old quotation snapshots.
     *
     * "Latest purchase price" intentionally does NOT depend on remaining FIFO
     * stock. A batch that has already been fully consumed still defines the
     * market price for a NEW commercial quotation. FIFO availability is a
     * production-costing concern and must not filter quotation pricing.
     */
    private function latestPurchaseRateKgForMaterial(int $materialId): float
    {
        $purchaseItem = PurchaseItem::query()
            ->where('product_id', $materialId)
            ->whereHas('purchase', function ($query) {
                $query->where('status', 'arrived');
            })
            ->with('purchase')
            ->get()
            ->sortByDesc(function ($item) {
                $purchase = $item->purchase;
                $date = optional($purchase)->arrival_date
                    ?? optional($purchase)->purchase_date
                    ?? $item->created_at;

                return [$date ? $date->getTimestamp() : 0, (int) $item->id];
            })
            ->first();

        if (!$purchaseItem) {
            return 0.0;
        }

        return (float) $purchaseItem->landedCostPerKg();
    }

    /**
     * Add route for getting sale items with BOM data
     */


    /**
     * Remove item from sale.
     */
    public function removeItem($id)
    {
        try {
            DB::beginTransaction();

            $saleItem = SaleItem::findOrFail($id);
            $sale = $saleItem->sale;

            if ($sale->status !== 'draft') {
                throw new \Exception('Cannot remove items from a confirmed sale.');
            }

            $saleItem->delete();
            $sale->recalculateTotals();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item removed successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error removing item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get confirmation data for the sale.
     */
    public function getConfirmationData($id)
    {
        try {
            $sale = Sale::with(['customer', 'currency', 'items.product'])->findOrFail($id);

            $subtotal = $sale->items->sum('total');
            $usdSubtotal = $sale->items->sum('usd_total');
            $totalQty = $sale->items->sum('qty');
            $itemCount = $sale->items->count();

            return response()->json([
                'success' => true,
                'sale' => [
                    'id' => $sale->id,
                    'sale_no' => $sale->sale_no,
                    'status' => $sale->status,
                    'customer' => $sale->customer ? [
                        'id' => $sale->customer->id,
                        'name' => $sale->customer->name,
                        'code' => $sale->customer->code ?? null,
                    ] : null,
                    'currency' => $sale->currency ? [
                        'id' => $sale->currency->id,
                        'code' => $sale->currency->code,
                        'name' => $sale->currency->name,
                        'symbol' => $sale->currency->symbol,
                    ] : null,
                    'exchange_rate' => $sale->exchange_rate,
                    'created_at' => $sale->created_at->toISOString(),
                ],
                'subtotal' => (float) $subtotal,
                'usd_subtotal' => (float) $usdSubtotal,
                'total_qty' => (float) $totalQty,
                'item_count' => (int) $itemCount,
                'currency_symbol' => $sale->currency->symbol ?? '$',
                'currency_code' => $sale->currency->code ?? 'USD',
                'exchange_rate' => (float) ($sale->exchange_rate ?? 1),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading confirmation data: ' . $e->getMessage()
            ], 500);
        }
    }


// app/Http/Controllers/Admin/SaleController.php

    /**
     * Add items to sale (for MTO - no batch selection needed)
     */



    /**
     * Confirm the sale with discount, advance payment, and due amount.
     */
    public function confirmSale(Request $request, $id)
    {
        $request->validate([
            'discount_amount' => 'nullable|numeric|min:0',
            'advance_payment' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'start_production' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $sale = Sale::with(['items.bom', 'items.product', 'currency', 'customer'])->findOrFail($id);

            if ($sale->status === 'confirmed') {
                throw new \Exception('This sale is already confirmed.');
            }

            if ($sale->items->count() === 0) {
                throw new \Exception('Cannot confirm a sale with no items.');
            }

            $exchangeRate = $sale->exchange_rate ?? 1;
            $discountAmount = (float) $request->discount_amount ?? 0;
            $advancePayment = (float) $request->advance_payment ?? 0;
            $startProduction = (bool) $request->start_production ?? false;

            $subtotal = $sale->items->sum('total');
            if ($discountAmount > $subtotal) {
                throw new \Exception('Discount amount cannot exceed subtotal (' . $sale->currency->symbol . ' ' . number_format($subtotal, 2) . ')');
            }

            $grandTotal = $subtotal - $discountAmount;
            if ($advancePayment > $grandTotal) {
                throw new \Exception('Advance payment cannot exceed grand total (' . $sale->currency->symbol . ' ' . number_format($grandTotal, 2) . ')');
            }

            $usdDiscountAmount = $discountAmount / $exchangeRate;
            $usdAdvancePayment = $advancePayment / $exchangeRate;

            // ─── Update profit calculations with discount ───
            foreach ($sale->items as $item) {
                // Recalculate profit with discount proportionally
                $itemProfitUsd = $item->profit_usd;
                $itemProfitAfn = $item->profit_afn;

                // If discount is applied, reduce profit proportionally
                if ($discountAmount > 0) {
                    $itemShare = $item->total / $subtotal;
                    $itemDiscountUsd = $usdDiscountAmount * $itemShare;
                    $item->profit_usd = $itemProfitUsd - $itemDiscountUsd;
                    $item->profit_afn = ($item->profit_usd) * $exchangeRate;
                    $netRevenueUsd = max((float) $item->usd_total - $itemDiscountUsd, 0);
                    $item->profit_percentage = $netRevenueUsd > 0
                        ? ($item->profit_usd / $netRevenueUsd) * 100
                        : 0;
                    $item->save();
                }
            }

            // ─── Update stock ───
            foreach ($sale->items as $item) {
                if ($item->purchase_item_id) {
                    $purchaseItem = PurchaseItem::find($item->purchase_item_id);
                    if ($purchaseItem) {
                        $purchaseItem->qty_sold += $item->qty;
                        $purchaseItem->save();
                    }
                }
            }

            $sale->discount_total = $discountAmount;
            $sale->usd_discount_total = $usdDiscountAmount;
            $sale->advance_payment = $advancePayment;
            $sale->usd_advance_payment = $usdAdvancePayment;

            if ($request->notes) {
                $sale->notes = $request->notes;
            }

            $sale->recalculateTotals();

            $grandTotal = $sale->grand_total;
            $advancePayment = $sale->advance_payment;
            $currencySymbol = $sale->currency->symbol ?? '$';
            $saleNo = $sale->sale_no;

            // ─── Create transactions ───
            Transaction::create([
                'type' => 'sale',
                'table_name' => 'sales',
                'table_row_id' => $sale->id,
                'account_id' => $sale->customer_id,
                'currency_id' => $sale->currency_id,
                'amount' => $grandTotal,
                'transaction_type' => 'debit',
                'is_cash' => false,
                'description' => "Sale #{$saleNo} - Invoice amount " . $currencySymbol . ' ' . number_format($grandTotal, 2),
                'is_visible' => true,
                'status' => 'active',
                'created_by' => Auth::id(),
            ]);

            if ($advancePayment > 0) {
                Transaction::create([
                    'type' => 'sale',
                    'table_name' => 'sales',
                    'table_row_id' => $sale->id,
                    'account_id' => $sale->customer_id,
                    'currency_id' => $sale->currency_id,
                    'amount' => $advancePayment,
                    'transaction_type' => 'credit',
                    'is_cash' => true,
                    'description' => "Sale #{$saleNo} - Cash payment received " . $currencySymbol . ' ' . number_format($advancePayment, 2),
                    'is_visible' => true,
                    'status' => 'active',
                    'created_by' => Auth::id(),
                ]);
            }

            $sale->status = 'confirmed';
            $sale->confirmed_at = now();
            $sale->save();

            // ─── Start production if checked ───
            $productionStarted = false;
            $productionMessage = '';
            $productionOrderId = null;

            if ($startProduction) {
                try {
                    $productionService = new \App\Services\ProductionService();

                    $missingBOM = [];
                    foreach ($sale->items as $item) {
                        $bom = $item->bom;
                        if (!$bom) {
                            $bom = BOM::where('product_id', $item->product_id)
                                ->where('status', 'active')
                                ->where('is_active', true)
                                ->first();
                        }

                        if (!$bom) {
                            $missingBOM[] = $item->product->name;
                        }
                    }

                    if (!empty($missingBOM)) {
                        throw new \Exception("No active BOM found for products: " . implode(', ', $missingBOM));
                    }

                    $productionOrders = [];
                    foreach ($sale->items as $item) {
                        $bom = $item->bom;
                        if (!$bom) {
                            $bom = BOM::where('product_id', $item->product_id)
                                ->where('status', 'active')
                                ->where('is_active', true)
                                ->first();
                        }

                        if ($bom) {
                            $productionOrder = $productionService->createProductionOrderFromSaleItem($item, $bom, $sale);
                            if ($productionOrder) {
                                $productionOrders[] = $productionOrder;
                                $productionOrderId = $productionOrder->id;
                            }
                        }
                    }

                    $sale->refresh();
                    if ($productionOrderId && !$sale->production_order_id) {
                        $sale->production_order_id = $productionOrderId;
                        $sale->save();
                    }

                    $sale->is_produced = false;
                    $sale->save();

                    $productionStarted = true;
                    $productionMessage = ' Production order(s) created successfully! Please start production when ready.';

                } catch (\Exception $e) {
                    $productionMessage = ' Sale confirmed, but production order creation failed: ' . $e->getMessage();
                    \Log::error('Production order creation failed during sale confirmation', [
                        'sale_id' => $sale->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            // ─── Calculate total profit in AFN ───
            $totalProfitAfn = $sale->items->sum('profit_afn');

            return response()->json([
                'success' => true,
                'message' => 'Sale confirmed successfully!' . $productionMessage,
                'production_started' => $productionStarted,
                'production_order_id' => $productionOrderId,
                'total_profit_afn' => $totalProfitAfn,
                'redirect' => route('admin.sales.show', $sale->id)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error confirming sale', [
                'sale_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error confirming sale: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Update sale status.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:draft,confirmed,shipped,delivered'
        ]);

        try {
            DB::beginTransaction();

            $sale = Sale::findOrFail($id);
            $oldStatus = $sale->status;
            $newStatus = $request->status;

            $validTransitions = [
                'draft' => ['confirmed'],
                'confirmed' => ['shipped'],
                'shipped' => ['delivered'],
                'delivered' => [],
            ];

            if (!in_array($newStatus, $validTransitions[$oldStatus] ?? [])) {
                throw new \Exception("Invalid status transition from {$oldStatus} to {$newStatus}");
            }

            $sale->status = $newStatus;

            if ($newStatus === 'delivered' && !$sale->delivery_date) {
                $sale->delivery_date = now();
            }

            $sale->save();

            DB::commit();

            return redirect()->back()->with('success', "Sale status changed from {$oldStatus} to {$newStatus}");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error updating status: ' . $e->getMessage());
        }
    }

    /**
     * Delete sale.
     */
    public function destroy(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $sale = Sale::with(['items', 'items.purchaseItem'])->findOrFail($id);

            if (!$sale->canBeDeleted()) {
                throw new \Exception('This sale cannot be deleted.');
            }

            // Check for processed returns
            $processedReturns = SaleReturn::where('sale_id', $sale->id)
                ->where('status', 'processed')
                ->count();

            if ($processedReturns > 0) {
                throw new \Exception('Cannot delete sale with processed returns. Please reverse the returns first.');
            }

            // Check for approved returns
            $approvedReturns = SaleReturn::where('sale_id', $sale->id)
                ->where('status', 'approved')
                ->count();

            if ($approvedReturns > 0) {
                throw new \Exception('Cannot delete sale with approved returns. Please reject or process them first.');
            }

            // Reverse stock for confirmed sales
            if (in_array($sale->status, ['confirmed', 'shipped', 'delivered'])) {
                foreach ($sale->items as $item) {
                    if ($item->purchaseItem) {
                        $purchaseItem = PurchaseItem::find($item->purchaseItem->id);
                        if ($purchaseItem) {
                            $purchaseItem->qty_sold -= $item->qty;
                            $purchaseItem->save();
                        }
                    }
                }
            }

            // Delete transactions
            Transaction::where('table_name', 'sales')
                ->where('table_row_id', $sale->id)
                ->delete();

            // Delete draft/rejected returns
            SaleReturn::where('sale_id', $sale->id)
                ->whereIn('status', ['draft', 'rejected'])
                ->delete();

            $sale->items()->delete();

            $sale->deleted_by = Auth::id();
            $sale->deletion_reason = $request->input('reason', 'Deleted via admin interface');
            $sale->save();

            $saleNo = $sale->sale_no;
            $sale->delete();

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sale #' . $saleNo . ' has been deleted successfully.',
                    'redirect' => route('admin.sales.index')
                ]);
            }

            return redirect()->route('admin.sales.index')
                ->with('success', 'Sale #' . $saleNo . ' has been deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting sale: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error deleting sale: ' . $e->getMessage());
        }
    }

    /**
     * Restore a soft-deleted sale.
     */
    public function restore($id)
    {
        try {
            DB::beginTransaction();

            $sale = Sale::withTrashed()->with(['items', 'items.purchaseItem'])->findOrFail($id);

            if (!$sale->trashed()) {
                throw new \Exception('This sale is not deleted.');
            }

            // Restore stock for confirmed sales
            if (in_array($sale->status, ['confirmed', 'shipped', 'delivered'])) {
                foreach ($sale->items as $item) {
                    if ($item->purchaseItem) {
                        $purchaseItem = PurchaseItem::find($item->purchaseItem->id);
                        if ($purchaseItem) {
                            $purchaseItem->qty_sold += $item->qty;
                            $purchaseItem->save();
                        }
                    }
                }
            }

            $sale->deleted_by = null;
            $sale->deleted_reason = null;
            $sale->deleted_at = null;
            $sale->save();

            DB::commit();

            return redirect()->route('admin.sales.show', $sale->id)
                ->with('success', 'Sale #' . $sale->sale_no . ' has been restored successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error restoring sale: ' . $e->getMessage());
        }
    }

    /**
     * Force delete a sale permanently.
     */
    public function forceDelete($id)
    {
        try {
            DB::beginTransaction();

            $sale = Sale::withTrashed()->findOrFail($id);

            if (!$sale->trashed()) {
                throw new \Exception('Sale must be soft-deleted first before permanent deletion.');
            }

            $saleNo = $sale->sale_no;
            $sale->forceDelete();

            DB::commit();

            return redirect()->route('admin.sales.index')
                ->with('success', 'Sale #' . $saleNo . ' has been permanently deleted.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error permanently deleting sale: ' . $e->getMessage());
        }
    }

    /**
     * Print sale invoice.
     */
    public function printInvoice($id)
    {
        $sale = Sale::with([
            'customer',
            'currency',
            'items.product',
            'items.purchaseItem.purchase',
            'items.purchaseItem.purchase.currency',
        ])->findOrFail($id);

        $totalProfit = $sale->items->sum('profit_usd');
        $totalCost = $sale->items->sum('total_cost_usd');
        $profitMargin = $totalCost > 0 ? ($totalProfit / $totalCost) * 100 : 0;
        $currencySymbol = $sale->currency->symbol ?? '$';
        $currencyCode = $sale->currency->code ?? 'USD';

        $settings = \App\Models\Setting::first();

        $companyName = $settings->company_name ?? config('app.name', 'Your Company');
        $companyAddress = $settings->address ?? '123 Business Street, City, Country';
        $companyPhone = $settings->contact ?? '+1 234 567 8900';
        $companyEmail = $settings->email ?? 'info@yourcompany.com';
        $companyLogo = $settings->logo ?? null;
        $companyTagline = 'Quality Products, Trusted Service';

        return view('admin.sales.print', compact(
            'sale',
            'totalProfit',
            'totalCost',
            'profitMargin',
            'currencySymbol',
            'currencyCode',
            'companyName',
            'companyAddress',
            'companyPhone',
            'companyEmail',
            'companyLogo',
            'companyTagline'
        ));
    }

    public function produce(Sale $sale, ProductionService $productionService)
    {
        try {
            // Check if sale is confirmed
            if ($sale->status !== 'confirmed') {
                return back()->with('error', 'Only confirmed sales can be produced.');
            }

            // Check if already produced
            if ($sale->is_produced) {
                return back()->with('error', 'This sale has already been produced.');
            }

            // Check if sale has items
            if ($sale->items->count() === 0) {
                return back()->with('error', 'No items found in this sale.');
            }

            // Create production order from sale
            $productionOrder = $productionService->createProductionFromSale($sale);

            // Start and complete production (consume materials, deliver to customer)
            $productionService->startAndCompleteProduction($productionOrder, $sale);

            return redirect()->route('admin.sales.show', $sale)
                ->with('success', 'Production completed and order ready for delivery!');

        } catch (\Exception $e) {
            \Log::error('Production failed', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to start production: ' . $e->getMessage());
        }
    }


    /**
     * Deliver the sale
     */
    public function deliver($id)
    {
        try {
            $sale = Sale::findOrFail($id);

            if (!$sale->is_produced) {
                return back()->with('error', 'Please complete production first.');
            }

            if ($sale->status === 'delivered') {
                return back()->with('error', 'This sale is already delivered.');
            }

            $sale->status = 'delivered';
            $sale->delivery_date = now();
            $sale->save();

            return redirect()->route('admin.sales.show', $sale)
                ->with('success', 'Order delivered successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to deliver: ' . $e->getMessage());
        }
    }

    public function getSaleCurrency($id)
    {
        try {
            $sale = Sale::findOrFail($id);
            $currency = $sale->currency;

            return response()->json([
                'success' => true,
                'currency_id' => $currency->id ?? null,
                'currency_code' => $currency->code ?? 'AFN',
                'currency_symbol' => $currency->symbol ?? '؋',
                'currency_name' => $currency->name ?? null,
                'exchange_rate' => $sale->exchange_rate ?? 66,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading sale currency: ' . $e->getMessage()
            ]);
        }
    }

    public function getSaleDetails($id)
    {
        try {
            $sale = Sale::with(['customer', 'items', 'items.product', 'items.bom'])->findOrFail($id);

            // ─── Get first sale item details ───
            $firstItem = $sale->items->first();

            // ─── Get BOM ID directly from the sale item ───
            $bomId = $firstItem ? $firstItem->bom_id : null;
            $productId = $firstItem ? $firstItem->product_id : null;
            $totalQuantity = $sale->items->sum('qty');

            return response()->json([
                'success' => true,
                'sale_no' => $sale->sale_no,
                'customer_name' => $sale->customer->name ?? 'N/A',
                'total_amount' => number_format($sale->grand_total, 2),
                'item_count' => $sale->items->count(),
                'status' => $sale->status,
                'product_id' => $productId,
                'product_name' => $firstItem ? $firstItem->product->name : 'N/A',
                'quantity' => $totalQuantity,
                'bom_id' => $bomId, // ─── FIX: Directly from sale item ───
                'bom_code' => $firstItem && $firstItem->bom ? $firstItem->bom->code : null,
                'bom_name' => $firstItem && $firstItem->bom ? $firstItem->bom->name : null,
                'exchange_rate' => $sale->exchange_rate ?? 66,
                'currency_code' => $sale->currency->code ?? 'AFN',
                'currency_symbol' => $sale->currency->symbol ?? '؋',
                'sale_items' => $sale->items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name ?? 'N/A',
                        'bom_id' => $item->bom_id,
                        'qty' => $item->qty,
                        'unit_price' => $item->unit_price,
                        'total' => $item->total,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading sale details: ' . $e->getMessage()
            ]);
        }
    }

    public function startProductionFromSale($id)
    {
        try {
            \Log::info('Starting production from sale', ['sale_id' => $id]);

            DB::beginTransaction();

            $sale = Sale::with(['productionOrder', 'items', 'items.product'])->findOrFail($id);

            // ─── Validation checks ───
            if ($sale->is_produced) {
                throw new \Exception('This sale has already been produced.');
            }

            if (!$sale->productionOrder) {
                throw new \Exception('No production order found for this sale. Please create a production order first.');
            }

            $productionOrder = $sale->productionOrder;

            if ($productionOrder->status !== 'pending') {
                throw new \Exception('Production order is not in pending status. Current status: ' . $productionOrder->status);
            }

            // ─── Check material availability before starting ───
            $bom = $productionOrder->bom;
            $quantity = $productionOrder->quantity_ordered;

            if (!$bom) {
                throw new \Exception('No BOM found for this production order.');
            }

            // Check if BOM has items
            if ($bom->items->count() === 0) {
                throw new \Exception('BOM has no items defined.');
            }

            // ─── Use the production material snapshot as the authoritative requirement ───
            $stockService = new \App\Services\StockDeductionService();
            $materials = [];

            $materialSnapshots = $productionOrder->materials()->get();
            $bomItemsByMaterial = $bom->items->groupBy('material_id')->map->values();
            $materialOccurrences = [];

            foreach ($materialSnapshots as $snapshot) {
                $materialId = (int) $snapshot->product_id;
                $occurrence = $materialOccurrences[$materialId] ?? 0;
                $bomItem = $bomItemsByMaterial->get($materialId)?->get($occurrence);
                $materialOccurrences[$materialId] = $occurrence + 1;

                $totalRequired = (float) $snapshot->required_quantity;
                $wastagePercent = (float) ($bomItem->wastage_percentage ?? 0);
                $requiredQty = $wastagePercent > 0
                    ? $totalRequired / (1 + ($wastagePercent / 100))
                    : $totalRequired;
                $wastageQty = $totalRequired - $requiredQty;

                $materials[] = [
                    'material_id' => $materialId,
                    'quantity' => $totalRequired,
                    'planned_quantity' => $requiredQty,
                    'wastage_quantity' => $wastageQty,
                    'unit' => $snapshot->unit ?? 'unit',
                    'material_name' => $snapshot->product->name ?? 'Unknown Material',
                ];
            }

            if (empty($materials)) {
                throw new \Exception('Production order has no material requirement snapshot.');
            }

            \Log::info('Checking material availability', [
                'bom_id' => $bom->id,
                'quantity' => $quantity,
                'materials' => $materials
            ]);

            $availability = $stockService->checkAvailability($materials);

            if (!$availability['available']) {
                // ─── FIX: Use a helper variable to avoid complex expression in string ───
                $shortageMessages = [];
                foreach ($availability['materials'] as $material) {
                    if (!$material['available']) {
                        $materialName = $material['material_name'] ?? 'Material';
                        $shortageMessages[] = $materialName . ': Shortage of ' . $material['shortage_quantity'] . ' ' . $material['unit'] . ' (Available: ' . $material['available_quantity'] . ')';
                    }
                }
                $shortages = implode("\n", $shortageMessages);

                throw new \Exception("Cannot start production. Material shortages:\n" . $shortages);
            }

            // ─── Start the production order using the service ───
            \Log::info('Starting production order', ['production_order_id' => $productionOrder->id]);

            // Update production order status to 'in_progress' BEFORE consuming materials
            $productionOrder->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            $deductionResult = $stockService->deductMaterials(
                $productionOrder->id,
                $sale->id,
                $materials
            );

            \Log::info('Materials consumed', [
                'production_order_id' => $productionOrder->id,
                'deduction_count' => count($deductionResult),
                'total_cost' => $deductionResult->sum('total_cost_usd')
            ]);

            DB::commit();

            // ─── Return success response ───
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Production started successfully! Materials have been consumed.',
                    'production_order_id' => $productionOrder->id,
                    'redirect' => route('production-orders.show', $productionOrder->id)
                ]);
            }

            return redirect()->route('admin.sales.show', $sale)
                ->with('success', 'Production started successfully! Materials have been consumed.');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Start production from sale failed', [
                'sale_id' => $id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to start production: ' . $e->getMessage()
                ], 422);
            }

            return back()->with('error', 'Failed to start production: ' . $e->getMessage());
        }
    }

    public function checkStatus($id)
    {
        try {
            $sale = Sale::findOrFail($id);

            // Get the current status from the request (sent from JavaScript)
            $currentStatus = request('current_status');

            // Check if status has changed
            $statusChanged = $currentStatus && $currentStatus !== $sale->status;

            return response()->json([
                'success' => true,
                'status' => $sale->status,
                'status_changed' => $statusChanged,
                'is_produced' => $sale->is_produced,
                'production_order_id' => $sale->production_order_id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking status: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Apply discount to a sale item.
     */
    public function applyDiscount(Request $request, SaleItem $item)
    {
        try {
            $validated = $request->validate([
                'discount_type' => 'required|in:discount_percent,discount_fixed,manual',
                'value' => 'required|numeric|min:0',
                'note' => 'nullable|string|max:500',
            ]);

            $item->applyDiscount(
                $validated['discount_type'],
                $validated['value']
            );

            $item->price_adjustment_note = $validated['note'] ?? null;
            $item->save();

            // Update sale totals
            $item->sale->recalculateTotals();

            return response()->json([
                'success' => true,
                'message' => 'Discount applied successfully.',
                'data' => [
                    'item' => $item->fresh(),
                    'profit' => [
                        'usd' => $item->profit_usd,
                        'percentage' => $item->profit_percentage,
                    ],
                    'discount' => [
                        'amount' => $item->discount_amount,
                        'percentage' => $item->discount_percentage,
                        'type' => $item->price_adjustment_type,
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error applying discount: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset item to base price.
     */
    public function resetPrice(SaleItem $item)
    {
        try {
            $item->resetToBasePrice();
            $item->price_adjustment_note = null;
            $item->save();

            // Update sale totals
            $item->sale->recalculateTotals();

            return response()->json([
                'success' => true,
                'message' => 'Price reset to base.',
                'data' => $item->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error resetting price: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk apply discount to all items.
     */
    public function bulkApplyDiscount(Request $request, Sale $sale)
    {
        try {
            $validated = $request->validate([
                'discount_type' => 'required|in:discount_percent,discount_fixed',
                'value' => 'required|numeric|min:0',
                'note' => 'nullable|string|max:500',
            ]);

            foreach ($sale->items as $item) {
                $item->applyDiscount(
                    $validated['discount_type'],
                    $validated['value']
                );
                $item->price_adjustment_note = $validated['note'] ?? null;
                $item->save();
            }

            // Update sale totals
            $sale->recalculateTotals();

            return response()->json([
                'success' => true,
                'message' => 'Discount applied to all items.',
                'data' => $sale->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error applying bulk discount: ' . $e->getMessage()
            ], 500);
        }
    }

}
