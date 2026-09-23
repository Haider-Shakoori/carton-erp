<?php
// app/Http/Controllers/Admin/ProductController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $arrivedStock = function ($query) {
            $query->whereHas('purchase', function ($purchaseQuery) {
                $purchaseQuery->where('status', 'arrived');
            });
        };

        // The page uses client-side DataTables. Return the complete catalog
        // instead of pre-paginating to 15 rows (which made later records
        // impossible to reach from the UI).
        $rawProducts = Product::with('category')
            ->withCount('purchaseItems')
            ->withSum(['purchaseItems as catalog_current_stock' => $arrivedStock], 'qty_available')
            ->where(function ($query) {
                $query->where('type', Product::TYPE_RAW_MATERIAL)
                    ->orWhereNull('type');
            })
            ->latest()
            ->get();

        $finishedProducts = Product::with('category')
            ->withCount('purchaseItems')
            ->where('type', Product::TYPE_FINISHED_GOOD)
            ->latest()
            ->get();

        $categories = Category::withCount('products')
            ->latest()
            ->get();

        // Keep the modal selectors complete even when the category table grows.
        $categoryOptions = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        $lowStockCount = $rawProducts->filter(function (Product $product): bool {
            $currentStock = (float) ($product->catalog_current_stock ?? 0);

            return (int) ($product->min_stock_alert ?? 0) > 0
                && $currentStock <= (float) $product->min_stock_alert;
        })->count();

        $catalogStats = [
            'total_products' => $rawProducts->count() + $finishedProducts->count(),
            'raw_materials' => $rawProducts->count(),
            'finished_goods' => $finishedProducts->count(),
            'categories' => $categories->count(),
            'active_products' => $rawProducts->where('is_active', true)->count()
                + $finishedProducts->where('is_active', true)->count(),
            'low_stock' => $lowStockCount,
        ];

        return view('admin.products.index', compact(
            'rawProducts',
            'finishedProducts',
            'categories',
            'categoryOptions',
            'catalogStats'
        ));
    }

    public function list()
    {
        $products = Product::with('category')
            ->where('is_active', true)
            ->get()
            ->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category->name ?? 'Uncategorized',
                ];
            });

        return response()->json([
            'success' => true,
            'products' => $products
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'nullable|exists:products,id',
                'type' => 'nullable|string|in:raw,raw_material,finished,finished_good,equipment,service',
                'name' => 'required|string|max:255',
                'category_id' => 'required|exists:categories,id',
                'unit' => 'nullable|string|max:100',
                'default_kg_per_roll' => 'nullable|numeric|min:0.0001',
                'min_stock_alert' => 'nullable|integer|min:0',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if (strtolower($validated['unit'] ?? '') !== 'roll') {
                $validated['default_kg_per_roll'] = null;
            } elseif (empty($validated['default_kg_per_roll'])) {
                $validated['default_kg_per_roll'] = null;
            }

            $requestedType = $validated['type'] ?? Product::TYPE_FINISHED_GOOD;
            $validated['type'] = match ($requestedType) {
                'raw' => Product::TYPE_RAW_MATERIAL,
                'finished' => Product::TYPE_FINISHED_GOOD,
                default => $requestedType,
            };
            $validated['min_stock_alert'] = $request->min_stock_alert ?? 0;
            $validated['is_active'] = $request->has('is_active');

            // Handle image upload
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('products', 'public');
                $validated['image'] = $imagePath;
            }

            if (!empty($request->product_id)) {
                // Update existing product
                $product = Product::findOrFail($request->product_id);

                if ($request->hasFile('image') && $product->image) {
                    Storage::disk('public')->delete($product->image);
                }

                $product->update($validated);
                $message = 'Product updated successfully.';
            } else {
                // Create new product
                Product::create($validated);
                $message = 'Product created successfully.';
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Product $product)
    {
        try {
            $validated = $request->validate([
                'type' => 'nullable|string|in:raw,raw_material,finished,finished_good,equipment,service',
                'name' => 'required|string|max:255',
                'category_id' => 'required|exists:categories,id',
                'unit' => 'nullable|string|max:100',
                'default_kg_per_roll' => 'nullable|numeric|min:0.0001',
                'min_stock_alert' => 'nullable|integer|min:0',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if (strtolower($validated['unit'] ?? '') !== 'roll') {
                $validated['default_kg_per_roll'] = null;
            } elseif (empty($validated['default_kg_per_roll'])) {
                $validated['default_kg_per_roll'] = null;
            }

            $requestedType = $validated['type'] ?? $product->type ?? Product::TYPE_FINISHED_GOOD;
            $validated['type'] = match ($requestedType) {
                'raw' => Product::TYPE_RAW_MATERIAL,
                'finished' => Product::TYPE_FINISHED_GOOD,
                default => $requestedType,
            };
            $validated['min_stock_alert'] = $request->min_stock_alert ?? 0;
            $validated['is_active'] = $request->has('is_active');

            // Handle image upload
            if ($request->hasFile('image')) {
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }
                $imagePath = $request->file('image')->store('products', 'public');
                $validated['image'] = $imagePath;
            }

            $product->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Product $product)
    {
        try {
            if ($product->purchaseItems()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this product because it has associated purchase items. Please remove or reassign the purchase items first.'
                ], 422);
            }

            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkPurchaseItems(Product $product)
    {
        return response()->json([
            'has_purchase_items' => $product->purchaseItems()->exists(),
            'purchase_items_count' => $product->purchaseItems()->count()
        ]);
    }

    // API endpoint for low stock products (for AJAX polling)
    public function getLowStockProducts()
    {
        $currentStocks = DB::table('purchase_items')
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->where('purchases.status', 'arrived')
            ->groupBy('purchase_items.product_id')
            ->select('purchase_items.product_id')
            ->selectRaw('SUM(purchase_items.qty_available) as stock')
            ->pluck('stock', 'product_id');

        $lowStockProducts = Product::with('category')
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

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category->name ?? 'Uncategorized',
                    'current_stock' => $stock !== null ? (string) number_format((float) $stock, 4, '.', '') : 0,
                    'min_stock_alert' => $product->min_stock_alert,
                    'unit' => $product->unit ?? 'unit',
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'count' => $lowStockProducts->count(),
            'products' => $lowStockProducts
        ]);
    }
}
