<?php

namespace App\Services;

use App\Models\ProductionMaterialConsumption;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProductionCostRecorder
{
    public function consumeBatch(
        int $productionOrderId,
        ?int $saleId,
        ?int $saleItemId,
        int $materialId,
        int $purchaseItemId,
        float $actualQuantity,
        float $plannedQuantity = 0,
        float $wastageQuantity = 0,
        ?string $unit = null
    ): ProductionMaterialConsumption {
        if ($actualQuantity <= 0) {
            throw new RuntimeException('Actual consumed quantity must be greater than zero.');
        }

        return DB::transaction(function () use (
            $productionOrderId, $saleId, $saleItemId, $materialId,
            $purchaseItemId, $actualQuantity, $plannedQuantity,
            $wastageQuantity, $unit
        ) {
            $batch = PurchaseItem::query()->lockForUpdate()->findOrFail($purchaseItemId);

            if ((int) $batch->product_id !== $materialId) {
                throw new RuntimeException('The selected inventory batch does not belong to this material.');
            }

            $isRoll = $batch->isRollBatch();

            // For roll-based paper batches consumption is tracked in kg.
            if ($isRoll) {
                if ((float) $batch->qty_kg_available < $actualQuantity) {
                    throw new RuntimeException('Insufficient quantity (kg) in the selected inventory batch.');
                }
            } elseif ((float) $batch->qty_available < $actualQuantity) {
                throw new RuntimeException('Insufficient quantity in the selected inventory batch.');
            }

            if ($isRoll) {
                $usdUnitCost = $batch->landedCostPerKg();
                $consumedRolls = $actualQuantity / max((float) $batch->kg_per_roll, 0.000001);
                $batch->qty_kg_available = (float) $batch->qty_kg_available - $actualQuantity;
                $batch->qty_kg_used = (float) ($batch->qty_kg_used ?? 0) + $actualQuantity;
                $batch->qty_available = max((float) $batch->qty_available - $consumedRolls, 0);
                $batch->qty_used = (float) ($batch->qty_used ?? 0) + $consumedRolls;
                $recordUnit = $unit ?: 'kg';
            } else {
                $usdUnitCost = (float) ($batch->usd_cost_per_item ?? 0);
                if ($usdUnitCost <= 0) {
                    $usdUnitCost = (float) ($batch->usd_unit_price ?? 0)
                        + (float) ($batch->usd_expense_per_item ?? 0);
                }
                if ($usdUnitCost <= 0 && (float) $batch->qty > 0) {
                    $usdUnitCost = (float) ($batch->usd_total_cost ?: $batch->usd_total)
                        / (float) $batch->qty;
                }
                $batch->qty_available = (float) $batch->qty_available - $actualQuantity;
                $recordUnit = $unit;
            }

            $rate = max((float) ($batch->rate ?? 1), 0.000001);
            $afnUnitCost = $usdUnitCost * $rate;

            $batch->save();

            $record = ProductionMaterialConsumption::create([
                'production_order_id' => $productionOrderId,
                'sale_id' => $saleId,
                'sale_item_id' => $saleItemId,
                'material_id' => $materialId,
                'purchase_item_id' => $purchaseItemId,
                'planned_quantity' => $plannedQuantity,
                'actual_quantity' => $actualQuantity,
                'wastage_quantity' => $wastageQuantity,
                'unit' => $recordUnit,
                'cost_per_unit_usd' => $usdUnitCost,
                'cost_per_unit_afn' => $afnUnitCost,
                'total_cost_usd' => $actualQuantity * $usdUnitCost,
                'total_cost_afn' => $actualQuantity * $afnUnitCost,
                'wastage_cost_usd' => $wastageQuantity * $usdUnitCost,
                'wastage_cost_afn' => $wastageQuantity * $afnUnitCost,
                'consumed_at' => now(),
                'created_by' => Auth::id(),
            ]);

            if ($isRoll) {
                app(ReelInventoryService::class)
                    ->consumeForConsumption($record);
            }

            return $record;
        });
    }
}
