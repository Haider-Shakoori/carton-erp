<?php
// app/Http/Controllers/Admin/CategoryController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:categories,name',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $category = Category::create([
                'name' => $request->name,
                'description' => $request->description,
                'is_active' => $request->has('is_active'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully.',
                'category' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, Category $category)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $category->update([
                'name' => $request->name,
                'description' => $request->description,
                'is_active' => $request->has('is_active'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully.',
                'category' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified category
     */
    public function destroy(Category $category)
    {
        try {
            // Check if category has products with purchase items
            $hasPurchaseItems = $category->products()
                ->whereHas('purchaseItems')
                ->exists();

            if ($hasPurchaseItems) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this category because it contains products with associated purchase items. Please remove or reassign those products first.'
                ], 422);
            }

            // Check if category has any products
            if ($category->products()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this category because it has associated products. Please delete or reassign the products first.'
                ], 422);
            }

            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
