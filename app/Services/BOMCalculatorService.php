<?php

namespace App\Services;

use App\Models\BOM;

class BOMCalculatorService
{
    /**
     * Return physical stock requirements for every BOM item.
     * Financial Excel pricing is deliberately kept separate in the BOM model.
     */
    public function calculateMaterialRequirements(BOM $bom, $quantity): array
    {
        $bom->loadMissing('items.material');
        $requirements = [];
        $totalKg = 0.0;

        foreach ($bom->items as $item) {
            $perUnit = $item->calculateStockKgPerUnit();
            $withoutWastage = $item->calculateStockRequirement((float) $quantity, false);
            $withWastage = $item->calculateStockRequirement((float) $quantity, true);
            $wastage = $withWastage - $withoutWastage;
            $totalKg += $withWastage;

            $requirements[] = [
                'bom_item_id' => $item->id,
                'material_id' => $item->material_id,
                'material_name' => $item->material->name ?? 'Unknown material',
                'required_per_unit' => $perUnit,
                'required_quantity' => $withWastage,
                'base_quantity' => $withoutWastage,
                'wastage_quantity' => $wastage,
                'wastage_percentage' => (float) $item->wastage_percentage,
                'unit' => $item->is_formula_based && $item->formula_type === 'carton_3d' ? 'kg' : $item->unit,
            ];
        }

        return [
            'material_requirements' => $requirements,
            'stock_summary' => [
                'production_quantity' => (float) $quantity,
                'total_required_kg' => $totalKg,
            ],
            'cost_breakdown' => [
                'excel_net_rate_per_unit' => (float) ($bom->calculated_net_rate ?? 0),
                'excel_total_cost' => (float) ($bom->calculated_net_rate ?? 0) * (float) $quantity,
            ],
        ];
    }
}
