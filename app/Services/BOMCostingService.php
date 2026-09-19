<?php

namespace App\Services;

use App\Models\BOM;
use App\Models\PurchaseItem;

class BOMCostingService
{
    /**
     * Return the latest arrived purchase cost in the unit inventory is actually
     * consumed in (USD/kg for roll batches, USD/native-unit otherwise).
     */
    public function latestInventoryCost(int $materialId, float $exchangeRate = 85, bool $availableOnly = false): array
    {
        $query = PurchaseItem::query()
            ->where('product_id', $materialId)
            ->whereHas('purchase', fn ($q) => $q->where('status', 'arrived'))
            ->with(['purchase.currency']);

        if ($availableOnly) {
            $query->where(function ($q) {
                $q->where('qty_available', '>', 0)
                    ->orWhere('qty_kg_available', '>', 0);
            });
        }

        $purchaseItem = $query->get()
            ->sortByDesc(function (PurchaseItem $item) {
                $purchase = $item->purchase;
                $date = $purchase?->arrival_date
                    ?? $purchase?->purchase_date
                    ?? $item->created_at;

                return sprintf(
                    '%010d-%020d',
                    $date ? $date->getTimestamp() : 0,
                    (int) $item->id
                );
            })
            ->first();

        if (! $purchaseItem) {
            return [
                'found' => false,
                'purchase_item_id' => null,
                'cost_usd' => 0.0,
                'cost_afn' => 0.0,
                'basis_unit' => null,
                'available_quantity' => 0.0,
                'purchase_currency' => null,
                'purchase_currency_id' => null,
                'purchase_date' => null,
                'batch_no' => null,
            ];
        }

        $costUsd = $purchaseItem->landedCostPerInventoryUnitUsd();
        $rate = $exchangeRate > 0 ? $exchangeRate : 85;
        $date = $purchaseItem->purchase?->arrival_date
            ?? $purchaseItem->purchase?->purchase_date
            ?? $purchaseItem->created_at;

        return [
            'found' => $costUsd > 0,
            'purchase_item_id' => $purchaseItem->id,
            'cost_usd' => $costUsd,
            'cost_afn' => $costUsd * $rate,
            'basis_unit' => $purchaseItem->inventoryCostBasisUnit(),
            'available_quantity' => $purchaseItem->availableInventoryQuantity(),
            'purchase_currency' => $purchaseItem->purchase?->currency?->code,
            'purchase_currency_id' => $purchaseItem->purchase?->currency_id,
            'purchase_date' => $date,
            'batch_no' => $purchaseItem->batch_no,
        ];
    }

    /**
     * Reprice every BOM material against the latest arrived landed inventory
     * cost and persist physical material cost including wastage.
     */
    public function refreshBomMaterialCosts(BOM $bom): BOM
    {
        $bom->loadMissing(['items.material']);
        $exchangeRate = max((float) $bom->getUSDtoAFNRate(), 0.000001);

        foreach ($bom->items as $item) {
            $latest = $this->latestInventoryCost((int) $item->material_id, $exchangeRate);

            $costUsd = $latest['found']
                ? (float) $latest['cost_usd']
                : (float) ($item->cost_per_unit_usd ?? 0);

            if ($costUsd <= 0 && (float) ($item->cost_per_unit_afn ?? 0) > 0) {
                $costUsd = (float) $item->cost_per_unit_afn / $exchangeRate;
            }

            $costAfn = $costUsd * $exchangeRate;
            $physicalQtyWithWaste = $item->calculateStockRequirement(1, true);

            $update = [
                'cost_per_unit_usd' => $costUsd,
                'cost_per_unit_afn' => $costAfn,
                'total_cost_usd' => $physicalQtyWithWaste * $costUsd,
                'total_cost_afn' => $physicalQtyWithWaste * $costAfn,
            ];

            if ($latest['found']) {
                $update['purchase_currency'] = $latest['purchase_currency'] ?? 'USD';
                $update['purchase_currency_id'] = $latest['purchase_currency_id'];

                // Formula paper rates should use the same landed AFN/kg rate shown
                // by inventory. This prevents an old manual per-gram rate from
                // diverging from the cost actually used for production planning.
                if ($item->is_formula_based && in_array($item->formula_type, ['carton_3d', 'cut_roll'], true)) {
                    $update['per_gram_rate'] = $costAfn;
                }
            }

            $item->updateQuietly($update);
        }

        $bom->unsetRelation('items');
        $bom->load('items.material');
        $summary = $this->summarize($bom);

        $bom->forceFill([
            'total_material_cost_usd' => $summary['physical_material_cost_usd'],
            'total_material_cost_afn' => $summary['physical_material_cost_afn'],
            'total_cost_afn' => $summary['physical_production_cost_afn'],
            'selling_price_afn' => $summary['selling_price_afn'],
            'profit_afn' => $summary['expected_profit_afn'],
        ])->saveQuietly();

        return $bom->fresh(['items.material']);
    }

    /**
     * Canonical BOM summary.
     *
     * Physical production material cost includes wastage. Commercial pricing
     * deliberately excludes wastage from the quotation basis, then applies each
     * row's standard work/profit percentage, print cost, and optional BOM markup.
     */
    public function summarize(BOM $bom): array
    {
        $bom->loadMissing('items');
        $exchangeRate = max((float) $bom->getUSDtoAFNRate(), 0.000001);

        $baseMaterialUsd = 0.0;
        $physicalMaterialUsd = 0.0;
        $standardWorkProfitAfn = 0.0;
        $printAfn = 0.0;

        foreach ($bom->items as $item) {
            $costUsd = (float) ($item->cost_per_unit_usd ?? 0);
            if ($costUsd <= 0 && (float) ($item->cost_per_unit_afn ?? 0) > 0) {
                $costUsd = (float) $item->cost_per_unit_afn / $exchangeRate;
            }

            $baseQty = $item->calculateStockRequirement(1, false);
            $withWasteQty = $item->calculateStockRequirement(1, true);

            $lineBaseUsd = $baseQty * $costUsd;
            $linePhysicalUsd = $withWasteQty * $costUsd;
            $lineWorkPercent = (float) ($item->work_percentage ?? $bom->work_percentage ?? 40);

            $baseMaterialUsd += $lineBaseUsd;
            $physicalMaterialUsd += $linePhysicalUsd;
            $standardWorkProfitAfn += ($lineBaseUsd * $exchangeRate) * ($lineWorkPercent / 100);
            $printAfn += (float) ($item->print ?? 0);
        }

        $baseMaterialAfn = $baseMaterialUsd * $exchangeRate;
        $physicalMaterialAfn = $physicalMaterialUsd * $exchangeRate;
        $commercialBaseAfn = $baseMaterialAfn + $standardWorkProfitAfn + $printAfn;
        $profitMargin = max((float) ($bom->profit_margin_percentage ?? 0), 0);
        $additionalMarkupAfn = $commercialBaseAfn * ($profitMargin / 100);
        $sellingPriceAfn = $commercialBaseAfn + $additionalMarkupAfn;

        return [
            'exchange_rate' => $exchangeRate,
            'base_material_cost_usd' => $baseMaterialUsd,
            'base_material_cost_afn' => $baseMaterialAfn,
            'physical_material_cost_usd' => $physicalMaterialUsd,
            'physical_material_cost_afn' => $physicalMaterialAfn,
            'wastage_cost_usd' => max($physicalMaterialUsd - $baseMaterialUsd, 0),
            'wastage_cost_afn' => max($physicalMaterialAfn - $baseMaterialAfn, 0),
            'standard_work_profit_afn' => $standardWorkProfitAfn,
            'print_cost_afn' => $printAfn,
            'commercial_base_afn' => $commercialBaseAfn,
            'additional_markup_afn' => $additionalMarkupAfn,
            'profit_margin_percentage' => $profitMargin,
            'selling_price_afn' => $sellingPriceAfn,
            'selling_price_usd' => $sellingPriceAfn / $exchangeRate,
            'physical_production_cost_afn' => $physicalMaterialAfn,
            'physical_production_cost_usd' => $physicalMaterialUsd,
            'expected_profit_afn' => $sellingPriceAfn - $physicalMaterialAfn,
            'expected_profit_usd' => ($sellingPriceAfn - $physicalMaterialAfn) / $exchangeRate,
        ];
    }
}
