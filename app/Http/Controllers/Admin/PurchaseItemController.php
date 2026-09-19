<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseItemController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'rate' => 'required|numeric|min:0.01',
            'remarks' => 'nullable|string|max:255',
            'batch_no' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:50',
            'kg_per_roll' => 'nullable|numeric|min:0',
            'total_weight_kg' => 'nullable|numeric|min:0',
        ]);

        $unit = (string) ($request->unit ?? '');
        $isRoll = strtolower($unit) === 'roll';

        if ($isRoll) {
            $kgPerRoll = (float) ($request->kg_per_roll ?? 0);
            $totalWeightKg = (float) ($request->total_weight_kg ?? 0);
            if ($kgPerRoll <= 0 && $totalWeightKg <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Roll purchases require a conversion. Provide kg_per_roll (or total_weight_kg).',
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $purchase = Purchase::findOrFail($request->purchase_id);

            // Check for duplicate product
            $existing = PurchaseItem::where('purchase_id', $purchase->id)
                ->where('product_id', $request->product_id)
                ->first();

            if ($existing) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Product already exists in this purchase order.'
                ], 422);
            }

            // Calculate all values
            $qty = (float) $request->qty;
            $unitPrice = (float) $request->unit_price;
            $rate = (float) $request->rate;
            $total = $qty * $unitPrice;
            $usdUnitPrice = $unitPrice / $rate;
            $usdTotal = $total / $rate;

            // Resolve kg conversion for roll-based paper batches
            $kgPerRoll = null;
            $totalWeightKg = null;
            if ($isRoll) {
                $kgPerRoll = (float) ($request->kg_per_roll ?? 0);
                $totalWeightKg = $kgPerRoll > 0
                    ? $totalWeightKg = $kgPerRoll * $qty
                    : (float) $request->total_weight_kg;
            }

            // Create item
            $item = PurchaseItem::create([
                'purchase_id' => $request->purchase_id,
                'product_id' => $request->product_id,
                'purchase_currency_id' => $purchase->currency_id,
                'qty' => $qty,
                'remarks' => $request->remarks,
                'batch_no' => $request->batch_no,
                'unit_price' => $unitPrice,
                'total' => $total,
                'rate' => $rate,
                'expense' => 0,
                'expense_per_item' => 0,
                'usd_unit_price' => $usdUnitPrice,
                'usd_total' => $usdTotal,
                'usd_expense' => 0,
                'usd_expense_per_item' => 0,
                'usd_total_cost' => $usdTotal,
                'usd_cost_per_item' => $usdUnitPrice,
                'unit' => $unit ?: null,
                'kg_per_roll' => $isRoll ? $kgPerRoll : null,
                'total_weight_kg' => $isRoll ? $totalWeightKg : null,
            ]);

            // Recalculate purchase totals and distribute expenses
            $purchase->recalculateTotals();

            if ($purchase->expenses()->count() > 0) {
                $purchase->distributeExpenses();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item added successfully',
                'item' => $item
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error adding item: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $item = PurchaseItem::with('product')->findOrFail($id);
            return response()->json($item);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'rate' => 'required|numeric|min:0.01',
            'remarks' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:50',
            'kg_per_roll' => 'nullable|numeric|min:0',
            'total_weight_kg' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $unit = (string) ($request->unit ?? '');
            $isRoll = strtolower($unit) === 'roll';
            $kgPerRoll = $isRoll ? (float) ($request->kg_per_roll ?? 0) : 0;
            $totalWeightKg = $isRoll ? (float) ($request->total_weight_kg ?? 0) : 0;

            if ($isRoll && $kgPerRoll <= 0 && $totalWeightKg <= 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Roll purchases require a conversion. Provide kg_per_roll (or total_weight_kg).',
                ], 422);
            }

            $item = PurchaseItem::findOrFail($id);
            $purchase = Purchase::findOrFail($item->purchase_id);

            // Calculate new values
            $qty = (float) $request->qty;
            $unitPrice = (float) $request->unit_price;
            $rate = (float) $request->rate;
            $total = $qty * $unitPrice;
            $usdUnitPrice = $unitPrice / $rate;
            $usdTotal = $total / $rate;

            if ($isRoll && $kgPerRoll > 0) {
                $totalWeightKg = $kgPerRoll * $qty;
            }

            // Update item
            $item->update([
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'total' => $total,
                'rate' => $rate,
                'remarks' => $request->remarks,
                'usd_unit_price' => $usdUnitPrice,
                'usd_total' => $usdTotal,
                'unit' => $unit ?: null,
                'kg_per_roll' => $isRoll ? $kgPerRoll : null,
                'total_weight_kg' => $isRoll ? $totalWeightKg : null,
            ]);

            // Recalculate purchase totals and distribute expenses
            $purchase->recalculateTotals();

            if ($purchase->expenses()->count() > 0) {
                $purchase->distributeExpenses();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating item: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $item = PurchaseItem::findOrFail($id);
            $purchase = Purchase::findOrFail($item->purchase_id);

            $item->delete();

            // Recalculate purchase totals and distribute expenses
            $purchase->recalculateTotals();

            if ($purchase->expenses()->count() > 0 && $purchase->items()->count() > 0) {
                $purchase->distributeExpenses();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting item: ' . $e->getMessage()
            ], 500);
        }
    }
}
