<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductionMaterialConsumption;
use App\Models\ProductionOrderMaterial;
use App\Models\StockAdjustmentItem;
use App\Models\StockReconciliation;
use App\Models\StockReconciliationItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class InventoryControlAnalysisService
{
    private const EPSILON = 0.000001;

    public function varianceTrend(
        ?string $fromDate = null,
        ?string $toDate = null,
        string $interval = 'week'
    ): array {
        [$from, $to] = $this->dateRange($fromDate, $toDate);
        $interval = $interval === 'month' ? 'month' : 'week';

        $lines = StockAdjustmentItem::query()
            ->with(['adjustment.reconciliation'])
            ->whereHas('adjustment', fn ($q) => $q
                ->whereDate('adjustment_date', '>=', $from->toDateString())
                ->whereDate('adjustment_date', '<=', $to->toDateString()))
            ->get();

        $periods = $lines
            ->groupBy(function (StockAdjustmentItem $line) use ($interval): string {
                $date = CarbonImmutable::parse(
                    $line->adjustment?->adjustment_date?->toDateString()
                        ?? $line->created_at?->toDateString()
                        ?? today()->toDateString()
                );

                return $interval === 'month'
                    ? $date->startOfMonth()->toDateString()
                    : $date->startOfWeek()->toDateString();
            })
            ->map(function (Collection $rows, string $period) use ($interval): array {
                $start = CarbonImmutable::parse($period);
                $end = $interval === 'month'
                    ? $start->endOfMonth()
                    : $start->endOfWeek();

                return [
                    'period_start' => $start->toDateString(),
                    'period_end' => $end->toDateString(),
                    'label' => $interval === 'month'
                        ? $start->format('M Y')
                        : $start->format('d M') . ' – ' . $end->format('d M'),
                    'lines' => $rows->count(),
                    'reconciliations' => $rows
                        ->pluck('adjustment.stock_reconciliation_id')
                        ->filter()
                        ->unique()
                        ->count(),
                    'shortage_value_usd' => round(abs((float) $rows
                        ->where('adjustment_value_usd', '<', 0)
                        ->sum('adjustment_value_usd')), 2),
                    'surplus_value_usd' => round((float) $rows
                        ->where('adjustment_value_usd', '>', 0)
                        ->sum('adjustment_value_usd'), 2),
                    'net_value_usd' => round((float) $rows->sum('adjustment_value_usd'), 2),
                    'absolute_value_usd' => round((float) $rows
                        ->sum(fn ($row) => abs((float) $row->adjustment_value_usd)), 2),
                ];
            })
            ->sortKeys()
            ->values();

        $reasonCodes = config('stock_reconciliation.reason_codes', []);
        $topReasons = $lines
            ->groupBy(fn ($line) => $line->reason_code ?: 'unclassified')
            ->map(function (Collection $rows, string $code) use ($reasonCodes): array {
                return [
                    'code' => $code,
                    'label' => $reasonCodes[$code] ?? ($code === 'unclassified' ? 'Unclassified' : $code),
                    'lines' => $rows->count(),
                    'absolute_value_usd' => round((float) $rows
                        ->sum(fn ($row) => abs((float) $row->adjustment_value_usd)), 2),
                ];
            })
            ->sortByDesc('absolute_value_usd')
            ->take(10)
            ->values();

        return [
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
            'interval' => $interval,
            'periods' => $periods,
            'top_reasons' => $topReasons,
            'summary' => [
                'lines' => $lines->count(),
                'reconciliations' => $lines
                    ->pluck('adjustment.stock_reconciliation_id')
                    ->filter()
                    ->unique()
                    ->count(),
                'shortage_value_usd' => round(abs((float) $lines
                    ->where('adjustment_value_usd', '<', 0)
                    ->sum('adjustment_value_usd')), 2),
                'surplus_value_usd' => round((float) $lines
                    ->where('adjustment_value_usd', '>', 0)
                    ->sum('adjustment_value_usd'), 2),
                'net_value_usd' => round((float) $lines->sum('adjustment_value_usd'), 2),
                'absolute_value_usd' => round((float) $lines
                    ->sum(fn ($row) => abs((float) $row->adjustment_value_usd)), 2),
            ],
        ];
    }

    public function controlAnalysis(
        ?string $fromDate = null,
        ?string $toDate = null,
        ?int $productId = null
    ): array {
        [$from, $to] = $this->dateRange($fromDate, $toDate);

        $consumptionByOrder = ProductionMaterialConsumption::query()
            ->whereDate('consumed_at', '>=', $from->toDateString())
            ->whereDate('consumed_at', '<=', $to->toDateString())
            ->when($productId, fn ($q) => $q->where('material_id', $productId))
            ->selectRaw(
                'production_order_id, material_id, SUM(actual_quantity) AS actual_quantity, '
                . 'SUM(wastage_quantity) AS wastage_quantity, SUM(total_cost_usd) AS actual_cost_usd, '
                . 'MAX(unit) AS unit'
            )
            ->groupBy('production_order_id', 'material_id')
            ->get();

        $productionOrderIds = $consumptionByOrder
            ->pluck('production_order_id')
            ->unique()
            ->values();

        $planned = ProductionOrderMaterial::query()
            ->when(
                $productionOrderIds->isNotEmpty(),
                fn ($q) => $q->whereIn('production_order_id', $productionOrderIds),
                fn ($q) => $q->whereRaw('1 = 0')
            )
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->selectRaw(
                'product_id, SUM(required_quantity) AS planned_quantity, '
                . 'SUM(total_cost) AS planned_cost_usd, MAX(unit) AS unit'
            )
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $actual = $consumptionByOrder
            ->groupBy('material_id')
            ->map(function (Collection $rows) {
                return [
                    'actual_quantity' => (float) $rows->sum('actual_quantity'),
                    'wastage_quantity' => (float) $rows->sum('wastage_quantity'),
                    'actual_cost_usd' => (float) $rows->sum('actual_cost_usd'),
                    'unit' => $rows->first()?->unit ?: 'unit',
                ];
            });

        $countItems = StockReconciliationItem::query()
            ->select(
                'stock_reconciliation_items.*',
                'stock_reconciliations.count_date as reconciliation_count_date'
            )
            ->join(
                'stock_reconciliations',
                'stock_reconciliations.id',
                '=',
                'stock_reconciliation_items.stock_reconciliation_id'
            )
            ->where('stock_reconciliations.status', StockReconciliation::STATUS_POSTED)
            ->whereDate('stock_reconciliations.count_date', '>=', $from->toDateString())
            ->whereDate('stock_reconciliations.count_date', '<=', $to->toDateString())
            ->when($productId, fn ($q) => $q->where('stock_reconciliation_items.product_id', $productId))
            ->orderByDesc('stock_reconciliations.count_date')
            ->orderByDesc('stock_reconciliations.id')
            ->get();

        $latestPhysical = $countItems
            ->groupBy('product_id')
            ->map(function (Collection $rows): array {
                $latestReconciliationId = (int) $rows->first()->stock_reconciliation_id;
                $latestRows = $rows->where(
                    'stock_reconciliation_id',
                    $latestReconciliationId
                );

                return [
                    'stock_reconciliation_id' => $latestReconciliationId,
                    'count_date' => $latestRows->first()?->reconciliation_count_date,
                    'system_quantity' => (float) $latestRows->sum('system_quantity'),
                    'physical_quantity' => (float) $latestRows->sum('physical_quantity'),
                    'variance_quantity' => (float) $latestRows->sum('variance_quantity'),
                    'unit' => $latestRows->first()?->inventory_unit ?: 'unit',
                ];
            });

        $adjustments = StockAdjustmentItem::query()
            ->whereHas('adjustment', fn ($q) => $q
                ->whereDate('adjustment_date', '>=', $from->toDateString())
                ->whereDate('adjustment_date', '<=', $to->toDateString()))
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->selectRaw(
                'product_id, SUM(adjustment_quantity) AS adjustment_quantity, '
                . 'SUM(adjustment_value_usd) AS adjustment_value_usd'
            )
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $materialIds = collect($planned->keys())
            ->merge($actual->keys())
            ->merge($latestPhysical->keys())
            ->merge($adjustments->keys())
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values();

        $products = Product::query()
            ->whereIn('id', $materialIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        $rows = $materialIds->map(function (int $materialId) use (
            $planned,
            $actual,
            $latestPhysical,
            $adjustments,
            $products
        ): array {
            $plannedRow = $planned->get($materialId);
            $actualRow = $actual->get($materialId, []);
            $physicalRow = $latestPhysical->get($materialId, []);
            $adjustmentRow = $adjustments->get($materialId);

            $plannedQty = (float) ($plannedRow->planned_quantity ?? 0);
            $actualQty = (float) ($actualRow['actual_quantity'] ?? 0);
            $physicalVariance = (float) ($physicalRow['variance_quantity'] ?? 0);

            return [
                'product_id' => $materialId,
                'material_name' => $products->get($materialId)?->name ?? ('Material #' . $materialId),
                'unit' => $plannedRow->unit
                    ?? ($actualRow['unit'] ?? ($physicalRow['unit'] ?? 'unit')),
                'planned_quantity' => round($plannedQty, 6),
                'actual_quantity' => round($actualQty, 6),
                'production_variance_quantity' => round($actualQty - $plannedQty, 6),
                'production_wastage_quantity' => round(
                    (float) ($actualRow['wastage_quantity'] ?? 0),
                    6
                ),
                'planned_cost_usd' => round((float) ($plannedRow->planned_cost_usd ?? 0), 4),
                'actual_cost_usd' => round((float) ($actualRow['actual_cost_usd'] ?? 0), 4),
                'latest_count_date' => $physicalRow['count_date'] ?? null,
                'system_quantity_at_count' => isset($physicalRow['system_quantity'])
                    ? round((float) $physicalRow['system_quantity'], 6)
                    : null,
                'physical_quantity' => isset($physicalRow['physical_quantity'])
                    ? round((float) $physicalRow['physical_quantity'], 6)
                    : null,
                'physical_variance_quantity' => round($physicalVariance, 6),
                'posted_adjustment_quantity' => round(
                    (float) ($adjustmentRow->adjustment_quantity ?? 0),
                    6
                ),
                'posted_adjustment_value_usd' => round(
                    (float) ($adjustmentRow->adjustment_value_usd ?? 0),
                    4
                ),
                'control_signal' => abs($physicalVariance) > self::EPSILON
                    ? 'inventory_variance'
                    : (abs($actualQty - $plannedQty) > self::EPSILON
                        ? 'production_variance'
                        : 'aligned'),
            ];
        })->values();

        return [
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
            'rows' => $rows,
            'summary' => [
                'materials' => $rows->count(),
                'materials_with_production_variance' => $rows
                    ->filter(fn ($row) => abs((float) $row['production_variance_quantity']) > self::EPSILON)
                    ->count(),
                'materials_with_inventory_variance' => $rows
                    ->filter(fn ($row) => abs((float) $row['physical_variance_quantity']) > self::EPSILON)
                    ->count(),
                'planned_material_cost_usd' => round((float) $rows->sum('planned_cost_usd'), 4),
                'actual_material_cost_usd' => round((float) $rows->sum('actual_cost_usd'), 4),
                'production_cost_variance_usd' => round(
                    (float) $rows->sum('actual_cost_usd')
                    - (float) $rows->sum('planned_cost_usd'),
                    4
                ),
                'reconciliation_absolute_value_usd' => round(
                    (float) $rows->sum(
                        fn ($row) => abs((float) $row['posted_adjustment_value_usd'])
                    ),
                    4
                ),
            ],
        ];
    }

    private function dateRange(?string $fromDate, ?string $toDate): array
    {
        $defaultDays = max(
            (int) config('stock_reconciliation.trend_default_days', 90),
            1
        );

        $to = CarbonImmutable::parse($toDate ?: today()->toDateString())->endOfDay();
        $from = CarbonImmutable::parse(
            $fromDate ?: $to->subDays($defaultDays - 1)->toDateString()
        )->startOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
        }

        return [$from, $to];
    }
}
