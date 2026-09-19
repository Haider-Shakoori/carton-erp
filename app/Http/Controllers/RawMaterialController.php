<?php
// app/Http/Controllers/Admin/RawMaterialController.php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\RawMaterialSpecification;
use Illuminate\Http\Request;

class RawMaterialController extends Controller
{
    public function index()
    {
        $materials = Product::rawMaterials()
            ->with('category', 'rawMaterialSpecification')
            ->latest()
            ->paginate(15);

        return view('admin.raw-materials.index', compact('materials'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->get();
        return view('admin.raw-materials.create', compact('categories'));
    }

    public function store(Request $request)
    {
        // Validation for raw material
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'unit' => 'required|string|max:100',
            'min_stock_alert' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            // Raw material specific fields
            'gsm' => 'nullable|numeric|min:0',
            'thickness' => 'nullable|numeric|min:0',
            'flute_type' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:50',
            'grade' => 'nullable|string|max:50',
            'viscosity' => 'nullable|numeric|min:0',
            'density' => 'nullable|numeric|min:0',
            'ink_type' => 'nullable|string|max:50',
            'chemical_composition' => 'nullable|string',
            'ph_level' => 'nullable|numeric|min:0|max:14',
            'shelf_life' => 'nullable|string|max:50',
            'supplier_part_number' => 'nullable|string|max:100',
            'minimum_order_quantity' => 'nullable|numeric|min:0',
            'storage_conditions' => 'nullable|string',
            'safety_instructions' => 'nullable|string',
        ]);

        // Create product with type 'raw_material'
        $product = Product::create([
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
            'unit' => $validated['unit'],
            'min_stock_alert' => $validated['min_stock_alert'] ?? 0,
            'type' => 'raw_material',
            'description' => $validated['description'],
            'is_active' => $request->has('is_active'),
        ]);

        // Create raw material specifications
        RawMaterialSpecification::create([
            'product_id' => $product->id,
            'gsm' => $validated['gsm'] ?? null,
            'thickness' => $validated['thickness'] ?? null,
            'flute_type' => $validated['flute_type'] ?? null,
            'color' => $validated['color'] ?? null,
            'grade' => $validated['grade'] ?? null,
            'viscosity' => $validated['viscosity'] ?? null,
            'density' => $validated['density'] ?? null,
            'ink_type' => $validated['ink_type'] ?? null,
            'chemical_composition' => $validated['chemical_composition'] ?? null,
            'ph_level' => $validated['ph_level'] ?? null,
            'shelf_life' => $validated['shelf_life'] ?? null,
            'supplier_part_number' => $validated['supplier_part_number'] ?? null,
            'minimum_order_quantity' => $validated['minimum_order_quantity'] ?? 1,
            'storage_conditions' => $validated['storage_conditions'] ?? null,
            'safety_instructions' => $validated['safety_instructions'] ?? null,
        ]);

        return redirect()->route('admin.raw-materials.index')
            ->with('success', 'Raw Material created successfully!');
    }
}
