<?php
// app/Http/Controllers/Admin/StockController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\ProductionOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    /**
     * Display arrived inventory profiles that have active available stock with global stats.
     */
    public function index(Request $request)
    {
        // Get all products with purchase items
        $stocks = Product::query()
            ->with(['category'])
            ->whereHas('purchaseItems', function ($query) {
                $query->whereHas('purchase', function ($pQuery) {
                    $pQuery->where('status', 'arrived');
                });
            })
            ->where('is_active', true)
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->paginate(15);

        // Pre-fetch all arrived purchase items (with their purchase + expenses)
        // for the paginated products in a single batch to avoid per-product N+1.
        $stocksProductIds = $stocks->pluck('id')->all();
        $allPurchaseItems = PurchaseItem::whereIn('product_id', $stocksProductIds)
            ->whereHas('purchase', function ($q) {
                $q->where('status', 'arrived');
            })
            ->with(['purchase.expenses'])
            ->get()
            ->groupBy('product_id');

        // Calculate totals for each product
        foreach ($stocks as $product) {
            $allItems = $allPurchaseItems->get($product->id, collect());

            $product->total_purchased = $allItems->sum('qty') ?? 0;
            $product->total_available = $allItems->sum('qty_available') ?? 0;
            $product->total_sold = $allItems->sum('qty_sold') ?? 0;
            $product->total_used = $allItems->sum('qty_used') ?? 0;
            $product->total_wasted = $allItems->sum('qty_wasted') ?? 0;

            // Calculate values
            $totalCost = 0;
            $totalStockValue = 0;

            foreach ($allItems as $item) {
                $totalExpenses = $item->purchase->expenses->sum('usd_amount') ?? 0;
                $expensePerUnit = $item->qty > 0 ? ($totalExpenses / $item->qty) : 0;
                $costPerUnit = ($item->usd_unit_price ?? 0) + $expensePerUnit;

                $totalCost += $item->usd_total ?? 0;
                $totalStockValue += $item->qty_available * $costPerUnit;
            }

            $product->total_usd_cost = $totalCost;
            $product->total_stock_value = $totalStockValue;
        }

        return view('admin.stock.index', compact('stocks'));
    }


    /**
     * Display product details with stock IN information (Purchase Orders).
     */
    public function stockIn(Request $request, Product $product)
    {
        $query = PurchaseItem::where('product_id', $product->id)
            ->whereHas('purchase', function ($query) {
                $query->where('status', 'arrived');
            })
            ->with([
                'purchase',
                'purchase.currency',
                'purchase.supplier',
                'purchase.expenses'
            ])
            ->orderBy('created_at', 'desc');

        // Apply date filters if provided
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $purchaseItems = $query->paginate(15);

        // Calculate totals and per-unit costs with expenses
        $totalPurchased = 0;
        $totalCost = 0;
        $totalCostWithExpenses = 0;

        foreach ($purchaseItems as $item) {
            // Calculate expense per unit
            $totalExpenses = $item->purchase->expenses->sum('usd_amount') ?? 0;
            $item->expense_per_unit = $item->qty > 0 ? ($totalExpenses / $item->qty) : 0;

            // Calculate unit cost (without expenses)
            $item->usd_unit_price = $item->qty > 0 ? ($item->usd_total / $item->qty) : 0;

            // Calculate unit cost with expenses
            $item->usd_unit_price_with_expenses = $item->usd_unit_price + $item->expense_per_unit;

            // Calculate total cost with expenses
            $item->total_cost_with_expenses = $item->usd_total + ($item->expense_per_unit * $item->qty);

            // Accumulate totals
            $totalPurchased += $item->qty;
            $totalCost += $item->usd_total;
            $totalCostWithExpenses += $item->total_cost_with_expenses;
        }

        return view('admin.stock.stock-in', compact(
            'product',
            'purchaseItems',
            'totalPurchased',
            'totalCost',
            'totalCostWithExpenses'
        ));
    }

    /**
     * Display product details with stock OUT information (Sales).
     */
    public function stockOut(Request $request, Product $product)
    {
        // ─── CASE 1: DIRECT SALES (Products sold directly with purchase_item_id) ───
        $directSales = SaleItem::where('product_id', $product->id)
            ->whereNotNull('purchase_item_id')
            ->with([
                'sale',
                'sale.customer',
                'sale.currency',
                'purchaseItem.purchase',
                'purchaseItem.purchase.expenses',
            ])
            ->orderBy('created_at', 'desc');

        // ─── CASE 2: PRODUCTION CONSUMPTION ───
        $productionConsumption = PurchaseItem::where('product_id', $product->id)
            ->where('qty_used', '>', 0)
            ->whereHas('purchase', function($q) {
                $q->where('status', 'arrived');
            })
            ->with([
                'purchase',
                'purchase.currency',
                'purchase.supplier',
                'product',
            ])
            ->orderBy('updated_at', 'desc');

        // Apply date filters
        if ($request->filled('from_date')) {
            $directSales->whereDate('created_at', '>=', $request->from_date);
            $productionConsumption->whereDate('updated_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $directSales->whereDate('created_at', '<=', $request->to_date);
            $productionConsumption->whereDate('updated_at', '<=', $request->to_date);
        }

        // Get results
        $directSalesResults = $directSales->get();
        $productionResults = $productionConsumption->get();

        // ─── MERGE RESULTS ───
        $allItems = collect();

        // Add direct sales
        foreach ($directSalesResults as $item) {
            $purchaseItem = $item->purchaseItem;
            $totalExpenses = $purchaseItem->purchase->expenses->sum('usd_amount') ?? 0;
            $expensePerUnit = $purchaseItem->qty > 0 ? ($totalExpenses / $purchaseItem->qty) : 0;
            $unitCost = $purchaseItem->qty > 0 ? ($purchaseItem->usd_total / $purchaseItem->qty) : 0;

            $allItems->push((object) [
                'type' => 'sale',
                'date' => $item->created_at,
                'reference' => $item->sale->sale_no ?? 'N/A',
                'customer' => $item->sale->customer->name ?? 'N/A',
                'qty' => $item->qty,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
                'cost_per_unit' => $unitCost + $expensePerUnit,
                'profit' => $item->profit_usd ?? 0,
                'profit_margin' => $item->profit_percentage ?? 0,
                'batch_no' => $purchaseItem->batch_no ?? 'N/A',
                'purchase_no' => $purchaseItem->purchase->purchase_no ?? 'N/A',
                'source' => 'Direct Sale',
                'color' => 'success',
                'icon' => 'bi-cart-check',
                'sale_id' => $item->sale_id,
                'currency_symbol' => $item->sale->currency->symbol ?? '$',
                'product_name' => $item->product->name ?? 'N/A',
            ]);
        }

        // Add production consumption - ENHANCED with more details
        foreach ($productionResults as $item) {
            // Find production order that used this material
            $productionOrder = ProductionOrder::whereHas('materials', function($q) use ($item) {
                $q->where('product_id', $item->product_id);
            })->first();

            // Find sale linked to this production order
            $sale = null;
            if ($productionOrder) {
                $sale = Sale::where('production_order_id', $productionOrder->id)->first();
            }

            // Calculate cost details
            $totalExpenses = $item->purchase->expenses->sum('usd_amount') ?? 0;
            $expensePerUnit = $item->qty > 0 ? ($totalExpenses / $item->qty) : 0;
            $unitCost = $item->qty > 0 ? ($item->usd_total / $item->qty) : 0;
            $costPerUnit = $unitCost + $expensePerUnit;

            // Get the finished product that was produced
            $finishedProduct = null;
            if ($productionOrder) {
                $finishedProduct = $productionOrder->product;
            }

            $allItems->push((object) [
                'type' => 'production',
                'date' => $item->updated_at,
                'reference' => $productionOrder->order_number ?? 'PROD-N/A',
                'customer' => $sale ? ($sale->customer->name ?? 'N/A') : 'Production Use',
                'qty' => $item->qty_used,
                'unit_price' => null,  // No sale price for raw materials
                'total' => null,       // No revenue for raw materials
                'cost_per_unit' => $costPerUnit,
                'profit' => null,      // No profit for raw materials
                'profit_margin' => null,
                'batch_no' => $item->batch_no ?? 'N/A',
                'purchase_no' => $item->purchase->purchase_no ?? 'N/A',
                'source' => 'Production Consumption',
                'color' => 'info',
                'icon' => 'bi-industry',
                'sale_id' => $sale ? $sale->id : null,
                'production_order_id' => $productionOrder ? $productionOrder->id : null,
                'currency_symbol' => '$',  // Raw materials cost in USD
                'product_name' => $item->product->name ?? 'N/A',
                'finished_product' => $finishedProduct ? $finishedProduct->name : 'N/A',
                'quantity_produced' => $productionOrder ? $productionOrder->quantity_produced : 0,
                'status' => $productionOrder ? $productionOrder->status : 'unknown',
            ]);
        }

        // Sort by date (newest first)
        $allItems = $allItems->sortByDesc('date')->values();

        // Paginate
        $perPage = 15;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $currentItems = $allItems->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $saleItems = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $allItems->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        // Calculate totals (only for direct sales)
        $totalSold = $allItems->sum('qty');
        $totalRevenue = $directSalesResults->sum('total');
        $totalCost = $allItems->sum(function($item) {
            return ($item->cost_per_unit ?? 0) * ($item->qty ?? 0);
        });
        $totalProfit = $totalRevenue - $directSalesResults->sum(function($item) {
                return ($item->cost_per_unit ?? 0) * ($item->qty ?? 0);
            });
        $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

        return view('admin.stock.stock-out', compact(
            'product',
            'saleItems',
            'totalSold',
            'totalRevenue',
            'totalCost',
            'totalProfit',
            'profitMargin'
        ));
    }

    public function show(Product $product)
    {
        // Get all purchase items for this product with arrived status
        $purchaseItems = PurchaseItem::where('product_id', $product->id)
            ->whereHas('purchase', function ($query) {
                $query->where('status', 'arrived');
            })
            ->with(['purchase', 'purchase.currency', 'purchase.expenses'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate product-level statistics
        $stats = [
            'total_purchased' => $purchaseItems->sum('qty'),
            'total_sold' => $purchaseItems->sum('qty_sold'),
            'total_wasted' => $purchaseItems->sum('qty_wasted'),
            'total_returned' => $purchaseItems->sum('qty_returned'),
            'total_available' => $purchaseItems->sum('qty_available'),
            'total_value_usd' => $purchaseItems->sum('usd_total'),
            'avg_cost_per_unit' => $purchaseItems->sum('usd_total') / max($purchaseItems->sum('qty'), 1),
        ];

        // Calculate batch performance metrics
        $batchMetrics = $purchaseItems->map(function ($item) {
            $totalExpenses = $item->purchase->expenses->sum('usd_amount') ?? 0;
            $expensePerUnit = $item->qty > 0 ? ($totalExpenses / $item->qty) : 0;
            $costPerUnit = $item->qty > 0 ? ($item->usd_total / $item->qty) : 0;
            $costPerUnitWithExpenses = $costPerUnit + $expensePerUnit;

            $currentValue = $item->qty_available * $costPerUnitWithExpenses;
            $wastageValue = $item->qty_wasted * $costPerUnitWithExpenses;
            $soldValue = $item->qty_sold * $costPerUnitWithExpenses;

            $profitLoss = 0;
            if ($item->sale_price_usd > 0 && $item->qty_sold > 0) {
                $totalRevenue = $item->sale_price_usd * $item->qty_sold;
                $totalCost = $item->usd_total * ($item->qty_sold / $item->qty);
                $profitLoss = $totalRevenue - $totalCost;
            }

            return [
                'purchase_no' => $item->purchase->purchase_no ?? 'N/A',
                'batch_no' => $item->batch_no ?? 'N/A',
                'purchase_id' => $item->purchase_id ?? 'N/A',
                'purchase_date' => $item->purchase->purchase_date ?? $item->created_at,
                'qty' => $item->qty,
                'qty_sold' => $item->qty_sold,
                'qty_wasted' => $item->qty_wasted,
                'qty_available' => $item->qty_available,
                'cost_per_unit' => $costPerUnit,
                'cost_per_unit_with_expenses' => $costPerUnitWithExpenses,
                'expense_per_unit' => $expensePerUnit,
                'current_value' => $currentValue,
                'wastage_value' => $wastageValue,
                'sold_value' => $soldValue,
                'profit_loss' => $profitLoss,
                'utilization_rate' => $item->qty > 0 ? (($item->qty_sold + $item->qty_wasted) / $item->qty) * 100 : 0,
                'currency' => $item->purchase->currency->code ?? 'USD',
                'rate' => $item->rate ?? 1,
                'unit' => $item->unit ?? 'unit',
                'is_roll_batch' => $item->isRollBatch(),
                'kg_per_roll' => (float) ($item->kg_per_roll ?? 0),
                'total_weight_kg' => (float) $item->totalKg(),
                'qty_kg_available' => (float) $item->availableKg(),
                'landed_cost_per_kg' => (float) $item->landedCostPerKg(),
            ];
        });

        // Get recent activity (last 30 days)
        $recentActivity = PurchaseItem::where('product_id', $product->id)
            ->whereHas('purchase', function ($query) {
                $query->where('status', 'arrived');
            })
            ->where('created_at', '>=', now()->subDays(30))
            ->with(['purchase'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.products.show', compact('product', 'purchaseItems', 'stats', 'batchMetrics', 'recentActivity'));
    }
}
