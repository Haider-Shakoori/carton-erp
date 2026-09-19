<?php
// app/Http/Controllers/Admin/NotificationController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class NotificationController extends Controller
{
    /**
     * Show low stock notifications page
     */
    public function lowStock()
    {
        return view('admin.notifications.low-stock');
    }

    /**
     * Get low stock data for AJAX requests
     */
    public function getLowStockData(Request $request)
    {
        $currentStocks = DB::table('purchase_items')
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->where('purchases.status', 'arrived')
            ->groupBy('purchase_items.product_id')
            ->select('purchase_items.product_id')
            ->selectRaw('SUM(purchase_items.qty_available) as stock')
            ->pluck('stock', 'product_id');

        $products = Product::with('category')
            ->where('min_stock_alert', '>', 0)
            ->active()
            ->get()
            ->filter(function ($product) use ($currentStocks) {
                $stock = $currentStocks->get($product->id);
                $currentStock = $stock !== null ? (float) $stock : 0;
                return $currentStock <= $product->min_stock_alert;
            })
            ->map(function ($product) use ($currentStocks) {
                $stock = $currentStocks->get($product->id);
                $currentStock = $stock !== null ? (string) number_format((float) $stock, 4, '.', '') : 0;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name
                    ] : null,
                    'unit' => $product->unit,
                    'current_stock' => $currentStock,
                    'min_stock_alert' => $product->min_stock_alert,
                    'is_low_stock' => $currentStock <= $product->min_stock_alert,
                    'is_out_of_stock' => null,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'count' => $products->count(),
            'products' => $products
        ]);
    }

    /**
     * Export low stock items to CSV
     */
    public function exportLowStockCSV(Request $request)
    {
        $products = Product::with('category')
            ->where('min_stock_alert', '>', 0)
            ->active()
            ->get()
            ->filter(function($product) {
                return $product->is_low_stock;
            })
            ->values();

        if ($products->isEmpty()) {
            return back()->with('info', 'No low stock items to export.');
        }

        $filename = 'low-stock-report-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function() use ($products) {
            $handle = fopen('php://output', 'w');

            // Add UTF-8 BOM for Excel compatibility
            fputs($handle, "\xEF\xBB\xBF");

            // Add CSV headers
            fputcsv($handle, [
                'ID',
                'Product Name',
                'Category',
                'Unit',
                'Current Stock',
                'Min Stock Alert',
                'Status',
                'Stock Level',
            ]);

            // Add data rows
            foreach ($products as $product) {
                $isOutOfStock = $product->current_stock <= 0;
                $status = $isOutOfStock ? 'Out of Stock' : 'Low Stock';
                $stockLevel = $isOutOfStock ? 'Critical' : 'Warning';

                fputcsv($handle, [
                    $product->id,
                    $product->name,
                    $product->category ? $product->category->name : 'Uncategorized',
                    $product->unit ?? 'N/A',
                    $product->current_stock,
                    $product->min_stock_alert,
                    $status,
                    $stockLevel,
                ]);
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }
}
