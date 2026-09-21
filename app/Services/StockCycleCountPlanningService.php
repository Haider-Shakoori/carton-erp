<?php

namespace App\Services;

use App\Models\PurchaseItem;
use App\Models\StockReconciliation;
use App\Models\StockReconciliationItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class StockCycleCountPlanningService
{
    private const EPSILON = 0.000001;

    public function plan(?string $asOfDate = null): array
    {
        $asOf = CarbonImmutable::parse($asOfDate ?: today()->toDateString())->startOfDay();

        $batches = PurchaseItem::query()
            ->whereHas('purchase', fn ($q) => $q->where('status', 'arrived'))
            ->whereHas('product', fn ($q) => $q
                ->where('is_active', true)
                ->where('type', 'raw_material'))
            ->with(['product:id,name,unit,type'])
            ->orderBy('product_id')
            ->orderBy('id')
            ->get()
            ->filter(fn (PurchaseItem $batch) => $batch->availableInventoryQuantity() > self::EPSILON);

        $materials = $batches
            ->groupBy('product_id')
            ->map(function (Collection $rows, $productId): array {
                /** @var PurchaseItem $first */
                $first = $rows->first();
                $inventoryValueUsd = (float) $rows->sum(
                    fn (PurchaseItem $batch) =>
                        $batch->availableInventoryQuantity() * $batch->landedCostPerInventoryUnitUsd()
                );

                return [
                    'product_id' => (int) $productId,
                    'material_name' => $first->product?->name ?? ('Material #' . $productId),
                    'inventory_unit' => $first->inventoryCostBasisUnit(),
                    'inventory_quantity' => (float) $rows->sum(
                        fn (PurchaseItem $batch) => $batch->availableInventoryQuantity()
                    ),
                    'inventory_value_usd' => $inventoryValueUsd,
                    'batch_count' => $rows->count(),
                ];
            })
            ->sortByDesc('inventory_value_usd')
            ->values();

        $totalValueUsd = (float) $materials->sum('inventory_value_usd');

        $lastCounts = StockReconciliationItem::query()
            ->join(
                'stock_reconciliations',
                'stock_reconciliations.id',
                '=',
                'stock_reconciliation_items.stock_reconciliation_id'
            )
            ->where('stock_reconciliations.status', StockReconciliation::STATUS_POSTED)
            ->selectRaw('stock_reconciliation_items.product_id, MAX(stock_reconciliations.count_date) AS last_count_date')
            ->groupBy('stock_reconciliation_items.product_id')
            ->pluck('last_count_date', 'product_id');

        $aThreshold = (float) config('stock_reconciliation.abc.a_cumulative_percentage', 80.0);
        $bThreshold = (float) config('stock_reconciliation.abc.b_cumulative_percentage', 95.0);
        $frequencies = config('stock_reconciliation.abc.frequency_days', [
            'A' => 7,
            'B' => 14,
            'C' => 30,
        ]);

        $runningValue = 0.0;
        $rows = $materials->map(function (array $material) use (
            $totalValueUsd,
            $lastCounts,
            $asOf,
            $aThreshold,
            $bThreshold,
            $frequencies,
            &$runningValue
        ): array {
            $value = (float) $material['inventory_value_usd'];
            $cumulativeBefore = $totalValueUsd > self::EPSILON
                ? ($runningValue / $totalValueUsd) * 100
                : 100.0;

            $class = $totalValueUsd <= self::EPSILON
                ? 'C'
                : ($cumulativeBefore < $aThreshold ? 'A'
                    : ($cumulativeBefore < $bThreshold ? 'B' : 'C'));

            $runningValue += $value;

            $share = $totalValueUsd > self::EPSILON
                ? ($value / $totalValueUsd) * 100
                : 0.0;
            $cumulative = $totalValueUsd > self::EPSILON
                ? ($runningValue / $totalValueUsd) * 100
                : 0.0;

            $frequencyDays = max((int) ($frequencies[$class] ?? 30), 1);
            $lastDateRaw = $lastCounts->get($material['product_id']);
            $lastCountDate = $lastDateRaw
                ? CarbonImmutable::parse($lastDateRaw)->startOfDay()
                : null;
            $nextDueDate = $lastCountDate
                ? $lastCountDate->addDays($frequencyDays)
                : $asOf;
            $isDue = $nextDueDate->lessThanOrEqualTo($asOf);

            return $material + [
                'abc_class' => $class,
                'inventory_value_share_percentage' => round($share, 2),
                'cumulative_value_percentage' => round($cumulative, 2),
                'frequency_days' => $frequencyDays,
                'last_count_date' => $lastCountDate?->toDateString(),
                'next_due_date' => $nextDueDate->toDateString(),
                'is_due' => $isDue,
                'overdue_days' => $isDue ? $nextDueDate->diffInDays($asOf) : 0,
            ];
        })->values();

        return [
            'as_of_date' => $asOf->toDateString(),
            'rows' => $rows,
            'due_product_ids' => $rows
                ->where('is_due', true)
                ->pluck('product_id')
                ->values()
                ->all(),
            'summary' => [
                'materials' => $rows->count(),
                'due_materials' => $rows->where('is_due', true)->count(),
                'class_a' => $rows->where('abc_class', 'A')->count(),
                'class_b' => $rows->where('abc_class', 'B')->count(),
                'class_c' => $rows->where('abc_class', 'C')->count(),
                'inventory_value_usd' => round($totalValueUsd, 2),
                'due_inventory_value_usd' => round(
                    (float) $rows->where('is_due', true)->sum('inventory_value_usd'),
                    2
                ),
            ],
        ];
    }
}
