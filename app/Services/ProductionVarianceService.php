<?php

namespace App\Services;

use App\Models\ProductionMaterialConsumption;
use App\Models\ProductionOrder;
use App\Models\Sale;
use App\Models\SaleItem;

/**
 * Planned vs actual production variance (Batch 8).
 *
 * Planned quantities come from the FROZEN technical specification (or the
 * production order materials it generated). Actual quantities come only from
 * real production_material_consumptions records — nothing is estimated or
 * invented to fill gaps.
 */
class ProductionVarianceService
{
    public function __construct(
        private readonly CartonSpecificationService $specification
    ) {
    }

    public function forSale(Sale $sale): array
    {
        $sale->loadMissing(['items.bom.items.material', 'currency', 'productionOrder']);

        $planned = [];
        $actual = [];
        $order = null;

        if ($sale->production_order_id) {
            $order = ProductionOrder::with('materials.product')->find($sale->production_order_id);
        }

        // ─── Planned rows: frozen specification first, order materials next ───
        $allowOrderMaterials = $sale->items->count() === 1;

        foreach ($sale->items as $saleItem) {
            foreach ($this->plannedRowsForSaleItem($saleItem, $order, $allowOrderMaterials) as $row) {
                $materialId = (int) $row['material_id'];
                $planned[$materialId] = [
                    'material_id' => $materialId,
                    'material_name' => $row['material_name'],
                    'unit' => $row['unit'],
                    'planned_quantity' => (float) $row['required_quantity'],
                    'planned_base_quantity' => (float) ($row['base_quantity'] ?? 0),
                    'planned_wastage_quantity' => (float) ($row['wastage_quantity'] ?? 0),
                    'planned_cost_per_unit_usd' => (float) $row['cost_per_unit_usd'],
                    'planned_cost_usd' => (float) $row['required_quantity'] * (float) $row['cost_per_unit_usd'],
                ];
            }
        }

        // ─── Actual rows: real FIFO consumption records only ───
        $consumptionQuery = ProductionMaterialConsumption::query()
            ->selectRaw('material_id, SUM(actual_quantity) AS actual_quantity, SUM(wastage_quantity) AS wastage_quantity, SUM(total_cost_usd) AS total_cost_usd, MAX(unit) AS unit')
            ->groupBy('material_id');

        if (ProductionMaterialConsumption::where('sale_id', $sale->id)->exists()) {
            $consumptionQuery->where('sale_id', $sale->id);
        } elseif ($sale->production_order_id) {
            $consumptionQuery->where('production_order_id', $sale->production_order_id);
        }

        foreach ($consumptionQuery->get() as $row) {
            $actual[(int) $row->material_id] = [
                'actual_quantity' => (float) $row->actual_quantity,
                'actual_wastage_quantity' => (float) $row->wastage_quantity,
                'actual_cost_usd' => (float) $row->total_cost_usd,
                'unit' => $row->unit ?: 'kg',
            ];
        }

        $materialIds = collect(array_keys($planned))
            ->merge(array_keys($actual))
            ->unique()
            ->sort()
            ->values();

        $rows = [];
        $plannedMaterialCostUsd = 0.0;
        $actualMaterialCostUsd = 0.0;
        $plannedWastageCostUsd = 0.0;
        $actualWastageCostUsd = 0.0;
        $materialUsageVarianceUsd = 0.0;
        $materialRateVarianceUsd = 0.0;

        foreach ($materialIds as $materialId) {
            $plannedRow = $planned[$materialId] ?? null;
            $actualRow = $actual[$materialId] ?? null;

            $plannedQty = (float) ($plannedRow['planned_quantity'] ?? 0);
            $actualQty = (float) ($actualRow['actual_quantity'] ?? 0);
            $plannedUnitCost = (float) ($plannedRow['planned_cost_per_unit_usd'] ?? 0);
            $plannedCost = (float) ($plannedRow['planned_cost_usd'] ?? 0);
            $actualCost = (float) ($actualRow['actual_cost_usd'] ?? 0);

            $plannedWastageQty = (float) ($plannedRow['planned_wastage_quantity'] ?? 0);
            $actualWastageQty = (float) ($actualRow['actual_wastage_quantity'] ?? 0);

            $plannedMaterialCostUsd += $plannedCost;
            $actualMaterialCostUsd += $actualCost;
            $plannedWastageCostUsd += $plannedWastageQty * $plannedUnitCost;
            $actualWastageCostUsd += $actualWastageQty * $plannedUnitCost;

            $costVarianceUsd = $actualCost - $plannedCost;
            $actualUnitCost = $actualQty > 0.000001 ? $actualCost / $actualQty : 0.0;
            $usageVarianceUsd = ($actualQty - $plannedQty) * $plannedUnitCost;
            $rateVarianceUsd = $actualCost - ($actualQty * $plannedUnitCost);
            $wastageVarianceUsd = ($actualWastageQty - $plannedWastageQty) * $plannedUnitCost;

            $materialUsageVarianceUsd += $usageVarianceUsd;
            $materialRateVarianceUsd += $rateVarianceUsd;

            $rows[] = [
                'material_id' => (int) $materialId,
                'material_name' => $plannedRow['material_name']
                    ?? $actualRow['material_name']
                    ?? ('Material #' . $materialId),
                'unit' => $plannedRow['unit'] ?? ($actualRow['unit'] ?? 'kg'),
                'planned_quantity' => round($plannedQty, 6),
                'actual_quantity' => round($actualQty, 6),
                'variance_quantity' => round($actualQty - $plannedQty, 6),
                'planned_cost_per_unit_usd' => round($plannedUnitCost, 6),
                'planned_cost_usd' => round($plannedCost, 6),
                'actual_cost_per_unit_usd' => round($actualUnitCost, 6),
                'actual_cost_usd' => round($actualCost, 6),
                'usage_variance_usd' => round($usageVarianceUsd, 6),
                'rate_variance_usd' => round($rateVarianceUsd, 6),
                'wastage_variance_usd' => round($wastageVarianceUsd, 6),
                'cost_variance_usd' => round($costVarianceUsd, 6),
                'planned_wastage_quantity' => round($plannedWastageQty, 6),
                'actual_wastage_quantity' => round($actualWastageQty, 6),
                'has_actual' => $actualRow !== null,
                'indicator' => $costVarianceUsd > 0.000001 ? 'unfavorable'
                    : ($costVarianceUsd < -0.000001 ? 'favorable' : 'neutral'),
            ];
        }

        $saleProfit = app(SaleProfitService::class)->calculate($sale);
        $hasActual = ! empty($actual);

        $estimatedProductionCostUsd = (float) $saleProfit['estimated_cost_usd'];
        $realizedProductionCostUsd = (float) $saleProfit['actual_production_cost_usd'];
        $grossSalesUsd = (float) $saleProfit['gross_sales_usd'];

        $estimatedMargin = $grossSalesUsd > 0
            ? (($grossSalesUsd - $estimatedProductionCostUsd) / $grossSalesUsd) * 100
            : 0.0;
        $realizedMargin = $hasActual && $grossSalesUsd > 0
            ? (($grossSalesUsd - $realizedProductionCostUsd) / $grossSalesUsd) * 100
            : 0.0;

        return [
            'has_actual' => $hasActual,
            'sale_id' => (int) $sale->id,
            'production_order_id' => $sale->production_order_id ? (int) $sale->production_order_id : null,
            'materials' => $rows,
            'summary' => [
                'planned_material_cost_usd' => round($plannedMaterialCostUsd, 4),
                'actual_material_cost_usd' => round($actualMaterialCostUsd, 4),
                'material_cost_variance_usd' => round($actualMaterialCostUsd - $plannedMaterialCostUsd, 4),
                'material_usage_variance_usd' => round($materialUsageVarianceUsd, 4),
                'material_rate_variance_usd' => round($materialRateVarianceUsd, 4),
                'planned_wastage_cost_usd' => round($plannedWastageCostUsd, 4),
                'actual_wastage_cost_usd' => round($actualWastageCostUsd, 4),
                'estimated_production_cost_usd' => round($estimatedProductionCostUsd, 4),
                'actual_production_cost_usd' => round($realizedProductionCostUsd, 4),
                'estimated_profit_usd' => round((float) $saleProfit['estimated_profit_usd'], 4),
                'realized_profit_usd' => $hasActual
                    ? round((float) $saleProfit['actual_profit_usd'], 4)
                    : null,
                'estimated_profit_afn' => round((float) $saleProfit['estimated_profit_afn'], 2),
                'realized_profit_afn' => $hasActual
                    ? round((float) $saleProfit['actual_profit_afn'], 2)
                    : null,
                'estimated_margin_percentage' => round($estimatedMargin, 2),
                'realized_margin_percentage' => $hasActual ? round($realizedMargin, 2) : null,
                'margin_variance_percentage' => $hasActual
                    ? round($realizedMargin - $estimatedMargin, 2)
                    : null,
                'indicator' => $hasActual
                    ? (($realizedProductionCostUsd - $estimatedProductionCostUsd) > 0.000001
                        ? 'unfavorable'
                        : 'favorable')
                    : 'pending',
            ],
        ];
    }

    public function forProductionOrder(ProductionOrder $order): array
    {
        $order->loadMissing(['materials.product', 'product']);

        $planned = $order->materials
            ->map(fn ($material) => [
                'material_id' => (int) $material->product_id,
                'material_name' => $material->product?->name ?? ('Material #' . $material->product_id),
                'unit' => $material->unit ?: 'kg',
                'planned_quantity' => (float) $material->required_quantity,
                'planned_base_quantity' => (float) $material->required_quantity,
                'planned_wastage_quantity' => 0.0,
                'planned_cost_per_unit_usd' => (float) $material->cost_per_unit,
                'planned_cost_usd' => (float) $material->total_cost,
            ])
            ->groupBy('material_id')
            ->map(function ($rows): array {
                $first = $rows->first();
                $quantity = (float) $rows->sum('planned_quantity');
                $cost = (float) $rows->sum('planned_cost_usd');

                return [
                    'material_id' => (int) $first['material_id'],
                    'material_name' => $first['material_name'],
                    'unit' => $first['unit'],
                    'planned_quantity' => $quantity,
                    'planned_base_quantity' => (float) $rows->sum('planned_base_quantity'),
                    'planned_wastage_quantity' => (float) $rows->sum('planned_wastage_quantity'),
                    'planned_cost_per_unit_usd' => $quantity > 0 ? $cost / $quantity : 0.0,
                    'planned_cost_usd' => $cost,
                ];
            })
            ->all();

        $actual = ProductionMaterialConsumption::query()
            ->where('production_order_id', $order->id)
            ->selectRaw('material_id, SUM(actual_quantity) AS actual_quantity, SUM(wastage_quantity) AS wastage_quantity, SUM(total_cost_usd) AS total_cost_usd')
            ->groupBy('material_id')
            ->get()
            ->keyBy('material_id');

        $materialIds = collect(array_keys($planned))
            ->merge($actual->keys()->map(fn ($id) => (int) $id)->all())
            ->unique()
            ->sort()
            ->values();

        $rows = [];
        $plannedTotal = 0.0;
        $actualTotal = 0.0;
        $usageVarianceTotal = 0.0;
        $rateVarianceTotal = 0.0;

        foreach ($materialIds as $materialId) {
            $plannedRow = $planned[$materialId] ?? null;
            $actualRow = $actual->get($materialId);

            $plannedQty = (float) ($plannedRow['planned_quantity'] ?? 0);
            $actualQty = (float) ($actualRow->actual_quantity ?? 0);
            $plannedCost = (float) ($plannedRow['planned_cost_usd'] ?? 0);
            $actualCost = (float) ($actualRow->total_cost_usd ?? 0);

            $plannedTotal += $plannedCost;
            $actualTotal += $actualCost;

            $plannedUnitCost = (float) ($plannedRow['planned_cost_per_unit_usd'] ?? 0);
            $actualUnitCost = $actualQty > 0.000001 ? $actualCost / $actualQty : 0.0;
            $usageVariance = ($actualQty - $plannedQty) * $plannedUnitCost;
            $rateVariance = $actualCost - ($actualQty * $plannedUnitCost);
            $usageVarianceTotal += $usageVariance;
            $rateVarianceTotal += $rateVariance;

            $rows[] = [
                'material_id' => (int) $materialId,
                'material_name' => $plannedRow['material_name'] ?? ('Material #' . $materialId),
                'unit' => $plannedRow['unit'] ?? 'kg',
                'planned_quantity' => round($plannedQty, 6),
                'actual_quantity' => round($actualQty, 6),
                'variance_quantity' => round($actualQty - $plannedQty, 6),
                'planned_cost_per_unit_usd' => round($plannedUnitCost, 6),
                'planned_cost_usd' => round($plannedCost, 6),
                'actual_cost_per_unit_usd' => round($actualUnitCost, 6),
                'actual_cost_usd' => round($actualCost, 6),
                'usage_variance_usd' => round($usageVariance, 6),
                'rate_variance_usd' => round($rateVariance, 6),
                'cost_variance_usd' => round($actualCost - $plannedCost, 6),
                'planned_wastage_quantity' => (float) ($plannedRow['planned_wastage_quantity'] ?? 0),
                'actual_wastage_quantity' => round((float) ($actualRow->wastage_quantity ?? 0), 6),
                'has_actual' => $actualRow !== null,
                'indicator' => ($actualCost - $plannedCost) > 0.000001 ? 'unfavorable'
                    : (($actualCost - $plannedCost) < -0.000001 ? 'favorable' : 'neutral'),
            ];
        }

        $completionSnapshot = $order->events()
            ->where('event_type', 'completion_snapshot')
            ->latest('id')
            ->first();

        $snapshotOrder = is_array($completionSnapshot?->metadata['order'] ?? null)
            ? $completionSnapshot->metadata['order']
            : [];

        $plannedConversionCost = (float) ($snapshotOrder['total_labor_cost'] ?? $order->total_labor_cost ?? 0)
            + (float) ($snapshotOrder['total_overhead_cost'] ?? $order->total_overhead_cost ?? 0);
        $actualConversionCost = (float) ($order->total_labor_cost ?? 0)
            + (float) ($order->total_overhead_cost ?? 0);

        $plannedOutput = (float) ($order->quantity_planned ?: $order->quantity_ordered);
        $manufacturedOutput = (float) ($order->quantity_manufactured ?? $order->quantity_produced ?? 0);
        $goodOutput = (float) ($order->quantity_produced ?? 0);
        $rejectedOutput = (float) ($order->quantity_rejected ?? 0);

        return [
            'has_actual' => $actual->isNotEmpty(),
            'production_order_id' => (int) $order->id,
            'sale_id' => $order->relationLoaded('sale') && $order->sale?->id ? (int) $order->sale->id : null,
            'output' => [
                'ordered_quantity' => (float) $order->quantity_ordered,
                'planned_quantity' => $plannedOutput,
                'manufactured_quantity' => $manufacturedOutput,
                'good_quantity' => $goodOutput,
                'rejected_quantity' => $rejectedOutput,
                'manufactured_variance_quantity' => round($manufacturedOutput - $plannedOutput, 2),
                'yield_percentage' => $manufacturedOutput > 0
                    ? round(($goodOutput / $manufacturedOutput) * 100, 2)
                    : 0.0,
            ],
            'materials' => $rows,
            'summary' => [
                'planned_material_cost_usd' => round($plannedTotal, 4),
                'actual_material_cost_usd' => round($actualTotal, 4),
                'material_cost_variance_usd' => round($actualTotal - $plannedTotal, 4),
                'material_usage_variance_usd' => round($usageVarianceTotal, 4),
                'material_rate_variance_usd' => round($rateVarianceTotal, 4),
                'planned_conversion_cost_usd' => round($plannedConversionCost, 4),
                'actual_conversion_cost_usd' => round($actualConversionCost, 4),
                'conversion_cost_variance_usd' => round($actualConversionCost - $plannedConversionCost, 4),
                'planned_total_production_cost_usd' => round($plannedTotal + $plannedConversionCost, 4),
                'actual_total_production_cost_usd' => round($actualTotal + $actualConversionCost, 4),
                'total_production_cost_variance_usd' => round(
                    ($actualTotal + $actualConversionCost) - ($plannedTotal + $plannedConversionCost),
                    4
                ),
                'indicator' => $actual->isEmpty()
                    ? 'pending'
                    : (($actualTotal - $plannedTotal) > 0.000001 ? 'unfavorable' : 'favorable'),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function plannedRowsForSaleItem(SaleItem $saleItem, ?ProductionOrder $order, bool $allowOrderMaterials = true): array
    {
        $frozenRows = $this->specification->frozenRows($saleItem);

        if (! empty($frozenRows)) {
            // Planned variance compares against the ORIGINAL accepted quantity,
            // not the final invoiced quantity after under/over production.
            $plannedQuantity = (float) ($saleItem->ordered_qty ?? $saleItem->qty);

            return $this->specification->requirementsFromSnapshot(
                $saleItem->carton_spec_snapshot,
                $plannedQuantity
            );
        }

        if ($allowOrderMaterials && $order && $order->materials->isNotEmpty()) {
            return $order->materials->map(fn ($material) => [
                'material_id' => (int) $material->product_id,
                'material_name' => $material->product?->name ?? ('Material #' . $material->product_id),
                'unit' => $material->unit ?: 'kg',
                'required_quantity' => (float) $material->required_quantity,
                'base_quantity' => (float) $material->required_quantity,
                'wastage_quantity' => 0.0,
                'cost_per_unit_usd' => (float) $material->cost_per_unit,
            ])->all();
        }

        $bom = $saleItem->bom;

        if (! $bom) {
            return [];
        }

        $quantity = (float) ($saleItem->ordered_qty ?? $saleItem->qty);

        return $bom->items->map(fn ($item) => [
            'material_id' => (int) $item->material_id,
            'material_name' => $item->material?->name ?? ('Material #' . $item->material_id),
            'unit' => $item->material?->is_roll_based ? 'kg' : ($item->unit ?: 'kg'),
            'required_quantity' => $item->calculateStockRequirement($quantity, true),
            'base_quantity' => $item->calculateStockRequirement($quantity, false),
            'wastage_quantity' => max(
                $item->calculateStockRequirement($quantity, true)
                - $item->calculateStockRequirement($quantity, false),
                0
            ),
            'cost_per_unit_usd' => (float) ($item->cost_per_unit_usd ?? 0),
        ])->all();
    }
}
