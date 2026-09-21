<?php

namespace App\Services;

use App\Models\StockAdjustmentItem;
use App\Models\StockVarianceInvestigation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class InventoryPreventionIntelligenceService
{
    public function analyze(
        ?string $fromDate = null,
        ?string $toDate = null,
        ?int $productId = null,
        ?string $rootCauseCode = null
    ): array {
        [$from, $to] = $this->dateRange($fromDate, $toDate);

        $rootCauseLabels = config(
            'stock_reconciliation.investigation.root_cause_codes',
            []
        );

        $recurrenceThreshold = max(
            (int) config(
                'stock_reconciliation.prevention.recurrence_count_threshold',
                3
            ),
            2
        );
        $criticalCountThreshold = max(
            (int) config(
                'stock_reconciliation.prevention.critical_count_threshold',
                5
            ),
            $recurrenceThreshold
        );
        $highValueThresholdUsd = max(
            (float) config(
                'stock_reconciliation.prevention.high_value_threshold_usd',
                100.00
            ),
            0.0
        );
        $preWindowDays = max(
            (int) config(
                'stock_reconciliation.prevention.effectiveness_pre_days',
                30
            ),
            1
        );
        $postWindowDays = max(
            (int) config(
                'stock_reconciliation.prevention.effectiveness_post_days',
                30
            ),
            1
        );

        $varianceLines = StockAdjustmentItem::query()
            ->with(['product', 'adjustment.reconciliation', 'investigation'])
            ->whereHas('adjustment', fn ($q) => $q
                ->whereDate('adjustment_date', '>=', $from->toDateString())
                ->whereDate('adjustment_date', '<=', $to->toDateString()))
            ->when(
                $productId,
                fn ($q) => $q->where('product_id', $productId)
            )
            ->when(
                $rootCauseCode,
                fn ($q) => $q->whereHas(
                    'investigation',
                    fn ($case) => $case->where(
                        'root_cause_code',
                        $rootCauseCode
                    )
                )
            )
            ->get();

        $investigations = StockVarianceInvestigation::query()
            ->with([
                'adjustmentItem.product',
                'adjustmentItem.adjustment.reconciliation',
                'assignee',
                'resolver',
            ])
            ->whereHas('adjustmentItem.adjustment', fn ($q) => $q
                ->whereDate('adjustment_date', '>=', $from->toDateString())
                ->whereDate('adjustment_date', '<=', $to->toDateString()))
            ->when(
                $productId,
                fn ($q) => $q->whereHas(
                    'adjustmentItem',
                    fn ($line) => $line->where('product_id', $productId)
                )
            )
            ->when(
                $rootCauseCode,
                fn ($q) => $q->where('root_cause_code', $rootCauseCode)
            )
            ->get();

        $classified = $investigations
            ->filter(fn ($case) => filled($case->root_cause_code))
            ->values();

        $patterns = $classified
            ->groupBy(function (StockVarianceInvestigation $case): string {
                return (int) $case->adjustmentItem->product_id
                    .'|'.$case->root_cause_code;
            })
            ->map(function (
                Collection $cases,
                string $key
            ) use (
                $rootCauseLabels,
                $recurrenceThreshold,
                $criticalCountThreshold,
                $highValueThresholdUsd
            ): array {
                $ordered = $cases
                    ->sortBy(fn ($case) => $this->occurrenceDate($case))
                    ->values();

                $first = $ordered->first();
                $dates = $ordered
                    ->map(fn ($case) => $this->occurrenceDate($case))
                    ->filter()
                    ->values();

                $intervals = collect();
                for ($i = 1; $i < $dates->count(); $i++) {
                    $intervals->push(
                        $dates[$i - 1]->diffInDays($dates[$i])
                    );
                }

                $absoluteValue = round((float) $cases->sum(
                    fn ($case) => abs(
                        (float) $case->adjustmentItem->adjustment_value_usd
                    )
                ), 2);

                $count = $cases->count();
                $isRecurring = $count >= $recurrenceThreshold;
                $severity = match (true) {
                    $count >= $criticalCountThreshold
                        || $absoluteValue >= ($highValueThresholdUsd * 2)
                        => 'critical',
                    $isRecurring
                        || $absoluteValue >= $highValueThresholdUsd
                        => 'high',
                    $count >= 2 => 'watch',
                    default => 'normal',
                };

                return [
                    'key' => $key,
                    'product_id' => (int) $first->adjustmentItem->product_id,
                    'material_name' => $first->adjustmentItem->product?->name
                        ?? ('Product #'.$first->adjustmentItem->product_id),
                    'root_cause_code' => $first->root_cause_code,
                    'root_cause_label' => $rootCauseLabels[
                        $first->root_cause_code
                    ] ?? $first->root_cause_code,
                    'occurrences' => $count,
                    'first_seen' => $dates->first()?->toDateString(),
                    'last_seen' => $dates->last()?->toDateString(),
                    'average_days_between' => $intervals->isNotEmpty()
                        ? round((float) $intervals->avg(), 1)
                        : null,
                    'absolute_value_usd' => $absoluteValue,
                    'active_cases' => $cases->where(
                        'status',
                        '!=',
                        StockVarianceInvestigation::STATUS_RESOLVED
                    )->count(),
                    'resolved_cases' => $cases->where(
                        'status',
                        StockVarianceInvestigation::STATUS_RESOLVED
                    )->count(),
                    'is_recurring' => $isRecurring,
                    'severity' => $severity,
                ];
            })
            ->sortByDesc(function (array $row): string {
                $weight = match ($row['severity']) {
                    'critical' => 4,
                    'high' => 3,
                    'watch' => 2,
                    default => 1,
                };

                return sprintf(
                    '%d-%08d-%012.2f',
                    $weight,
                    $row['occurrences'],
                    $row['absolute_value_usd']
                );
            })
            ->values();

        $materialHotspots = $varianceLines
            ->groupBy('product_id')
            ->map(function (Collection $lines): array {
                $first = $lines->first();
                $absoluteValue = round((float) $lines->sum(
                    fn ($line) => abs((float) $line->adjustment_value_usd)
                ), 2);

                return [
                    'product_id' => (int) $first->product_id,
                    'material_name' => $first->product?->name
                        ?? ('Product #'.$first->product_id),
                    'variance_lines' => $lines->count(),
                    'shortage_lines' => $lines
                        ->where('adjustment_quantity', '<', 0)
                        ->count(),
                    'surplus_lines' => $lines
                        ->where('adjustment_quantity', '>', 0)
                        ->count(),
                    'absolute_value_usd' => $absoluteValue,
                    'net_value_usd' => round(
                        (float) $lines->sum('adjustment_value_usd'),
                        2
                    ),
                    'investigated_lines' => $lines
                        ->filter(fn ($line) => $line->investigation !== null)
                        ->count(),
                ];
            })
            ->sortByDesc('absolute_value_usd')
            ->take(15)
            ->values();

        $resolvedCases = $investigations
            ->where('status', StockVarianceInvestigation::STATUS_RESOLVED)
            ->filter(fn ($case) => filled($case->root_cause_code)
                && $case->resolved_at !== null)
            ->sortByDesc('resolved_at')
            ->values();

        $expandedStart = $resolvedCases->isNotEmpty()
            ? CarbonImmutable::parse(
                $resolvedCases->min(
                    fn ($case) => $case->resolved_at->toDateString()
                )
            )->subDays($preWindowDays)
            : $from;
        $expandedEnd = $resolvedCases->isNotEmpty()
            ? CarbonImmutable::parse(
                $resolvedCases->max(
                    fn ($case) => $case->resolved_at->toDateString()
                )
            )->addDays($postWindowDays)
            : $to;

        $effectivenessLines = StockAdjustmentItem::query()
            ->with(['adjustment'])
            ->whereHas('adjustment', fn ($q) => $q
                ->whereDate(
                    'adjustment_date',
                    '>=',
                    $expandedStart->toDateString()
                )
                ->whereDate(
                    'adjustment_date',
                    '<=',
                    $expandedEnd->toDateString()
                ))
            ->when(
                $productId,
                fn ($q) => $q->where('product_id', $productId)
            )
            ->get()
            ->groupBy('product_id');

        $effectivenessCases = StockVarianceInvestigation::query()
            ->with(['adjustmentItem.adjustment'])
            ->whereNotNull('root_cause_code')
            ->whereHas('adjustmentItem.adjustment', fn ($q) => $q
                ->whereDate(
                    'adjustment_date',
                    '>=',
                    $expandedStart->toDateString()
                )
                ->whereDate(
                    'adjustment_date',
                    '<=',
                    $expandedEnd->toDateString()
                ))
            ->when(
                $productId,
                fn ($q) => $q->whereHas(
                    'adjustmentItem',
                    fn ($line) => $line->where('product_id', $productId)
                )
            )
            ->get();

        $effectiveness = $resolvedCases
            ->map(function (
                StockVarianceInvestigation $case
            ) use (
                $effectivenessLines,
                $effectivenessCases,
                $preWindowDays,
                $postWindowDays,
                $rootCauseLabels
            ): array {
                $resolvedAt = CarbonImmutable::parse($case->resolved_at);
                $preStart = $resolvedAt->subDays($preWindowDays);
                $postEnd = $resolvedAt->addDays($postWindowDays);
                $now = CarbonImmutable::now();
                $observedPostEnd = $postEnd->lessThan($now)
                    ? $postEnd
                    : $now;
                $postWindowComplete = $now->greaterThanOrEqualTo($postEnd);

                $productId = (int) $case->adjustmentItem->product_id;
                $lines = $effectivenessLines->get($productId, collect());

                $preLines = $lines->filter(function ($line) use (
                    $preStart,
                    $resolvedAt
                ): bool {
                    $date = $this->lineDate($line);

                    return $date
                        && $date->greaterThanOrEqualTo($preStart)
                        && $date->lessThanOrEqualTo($resolvedAt);
                });

                $postLines = $lines->filter(function ($line) use (
                    $resolvedAt,
                    $observedPostEnd
                ): bool {
                    $date = $this->lineDate($line);

                    return $date
                        && $date->greaterThan($resolvedAt)
                        && $date->lessThanOrEqualTo($observedPostEnd);
                });

                $confirmedRecurrences = $effectivenessCases->filter(
                    function ($later) use (
                        $case,
                        $productId,
                        $resolvedAt,
                        $observedPostEnd
                    ): bool {
                        if ((int) $later->id === (int) $case->id) {
                            return false;
                        }
                        if (
                            (int) $later->adjustmentItem->product_id
                            !== $productId
                            || $later->root_cause_code
                            !== $case->root_cause_code
                        ) {
                            return false;
                        }

                        $date = $this->occurrenceDate($later);

                        return $date
                            && $date->greaterThan($resolvedAt)
                            && $date->lessThanOrEqualTo($observedPostEnd);
                    }
                );

                $preValue = round((float) $preLines->sum(
                    fn ($line) => abs((float) $line->adjustment_value_usd)
                ), 2);
                $postValue = round((float) $postLines->sum(
                    fn ($line) => abs((float) $line->adjustment_value_usd)
                ), 2);

                $signal = 'monitoring';
                if ($confirmedRecurrences->isNotEmpty()) {
                    $signal = 'recurrent';
                } elseif ($postWindowComplete && $postLines->isEmpty()) {
                    $signal = 'no_recurrence';
                } elseif ($postWindowComplete && $postValue < $preValue) {
                    $signal = 'improved';
                } elseif ($postWindowComplete) {
                    $signal = 'needs_review';
                }

                $changePercentage = null;
                if ($preValue > 0) {
                    $changePercentage = round(
                        (($postValue - $preValue) / $preValue) * 100,
                        1
                    );
                }

                return [
                    'investigation_id' => $case->id,
                    'product_id' => $productId,
                    'material_name' => $case->adjustmentItem->product?->name
                        ?? ('Product #'.$productId),
                    'root_cause_code' => $case->root_cause_code,
                    'root_cause_label' => $rootCauseLabels[
                        $case->root_cause_code
                    ] ?? $case->root_cause_code,
                    'resolved_at' => $resolvedAt->toDateString(),
                    'corrective_action' => $case->corrective_action,
                    'pre_window_days' => $preWindowDays,
                    'post_window_days' => $postWindowDays,
                    'post_window_complete' => $postWindowComplete,
                    'pre_lines' => $preLines->count(),
                    'post_lines' => $postLines->count(),
                    'pre_absolute_value_usd' => $preValue,
                    'post_absolute_value_usd' => $postValue,
                    'value_change_percentage' => $changePercentage,
                    'confirmed_recurrences' => $confirmedRecurrences->count(),
                    'signal' => $signal,
                ];
            })
            ->values();

        $flags = collect();

        foreach ($patterns as $pattern) {
            if ($pattern['is_recurring']) {
                $flags->push([
                    'type' => 'recurring_root_cause',
                    'severity' => $pattern['severity'],
                    'title' => 'Recurring root cause',
                    'material_name' => $pattern['material_name'],
                    'message' => sprintf(
                        '%s has %d confirmed %s investigations in the selected period.',
                        $pattern['material_name'],
                        $pattern['occurrences'],
                        $pattern['root_cause_label']
                    ),
                    'product_id' => $pattern['product_id'],
                    'root_cause_code' => $pattern['root_cause_code'],
                    'investigation_id' => null,
                    'occurrences' => $pattern['occurrences'],
                    'absolute_value_usd' => $pattern['absolute_value_usd'],
                ]);
            }
        }

        foreach ($effectiveness as $row) {
            if ($row['signal'] === 'recurrent') {
                $flags->push([
                    'type' => 'post_corrective_recurrence',
                    'severity' => 'critical',
                    'title' => 'Recurrence after corrective action',
                    'material_name' => $row['material_name'],
                    'message' => sprintf(
                        '%s repeated the %s root cause after investigation INV-%06d was resolved.',
                        $row['material_name'],
                        $row['root_cause_label'],
                        $row['investigation_id']
                    ),
                    'product_id' => $row['product_id'],
                    'root_cause_code' => $row['root_cause_code'],
                    'investigation_id' => $row['investigation_id'],
                    'occurrences' => $row['confirmed_recurrences'] + 1,
                    'absolute_value_usd' => $row['post_absolute_value_usd'],
                ]);
            } elseif ($row['signal'] === 'needs_review') {
                $flags->push([
                    'type' => 'corrective_action_review',
                    'severity' => 'high',
                    'title' => 'Corrective action needs review',
                    'material_name' => $row['material_name'],
                    'message' => sprintf(
                        '%s has not shown a lower variance burden after investigation INV-%06d.',
                        $row['material_name'],
                        $row['investigation_id']
                    ),
                    'product_id' => $row['product_id'],
                    'root_cause_code' => $row['root_cause_code'],
                    'investigation_id' => $row['investigation_id'],
                    'occurrences' => max($row['post_lines'], 1),
                    'absolute_value_usd' => $row['post_absolute_value_usd'],
                ]);
            }
        }

        $overdueCases = $investigations
            ->filter(fn ($case) => $case->is_overdue)
            ->values();

        foreach ($overdueCases as $case) {
            $flags->push([
                'type' => 'overdue_investigation',
                'severity' => 'high',
                'title' => 'Overdue investigation',
                'material_name' => $case->adjustmentItem->product?->name
                    ?? ('Product #'.$case->adjustmentItem->product_id),
                'message' => sprintf(
                    'INV-%06d is overdue and remains %s.',
                    $case->id,
                    $case->status
                ),
                'product_id' => (int) $case->adjustmentItem->product_id,
                'root_cause_code' => $case->root_cause_code,
                'investigation_id' => $case->id,
                'occurrences' => 1,
                'absolute_value_usd' => abs(
                    (float) $case->adjustmentItem->adjustment_value_usd
                ),
            ]);
        }

        $flags = $flags
            ->sortByDesc(fn ($flag) => match ($flag['severity']) {
                'critical' => 3,
                'high' => 2,
                default => 1,
            })
            ->values();

        return [
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
            'product_id' => $productId,
            'root_cause_code' => $rootCauseCode,
            'thresholds' => [
                'recurrence_count' => $recurrenceThreshold,
                'critical_count' => $criticalCountThreshold,
                'high_value_usd' => $highValueThresholdUsd,
                'effectiveness_pre_days' => $preWindowDays,
                'effectiveness_post_days' => $postWindowDays,
            ],
            'summary' => [
                'variance_lines' => $varianceLines->count(),
                'absolute_variance_value_usd' => round((float) $varianceLines
                    ->sum(
                        fn ($line) => abs(
                            (float) $line->adjustment_value_usd
                        )
                    ), 2),
                'investigations' => $investigations->count(),
                'classified_investigations' => $classified->count(),
                'recurring_patterns' => $patterns
                    ->where('is_recurring', true)
                    ->count(),
                'management_flags' => $flags->count(),
                'critical_flags' => $flags
                    ->where('severity', 'critical')
                    ->count(),
                'post_corrective_recurrences' => $effectiveness
                    ->where('signal', 'recurrent')
                    ->count(),
            ],
            'patterns' => $patterns,
            'material_hotspots' => $materialHotspots,
            'effectiveness' => $effectiveness,
            'flags' => $flags,
        ];
    }

    private function occurrenceDate(
        StockVarianceInvestigation $case
    ): ?CarbonImmutable {
        $date = $case->adjustmentItem?->adjustment?->adjustment_date
            ?? $case->opened_at
            ?? $case->created_at;

        return $date ? CarbonImmutable::parse($date) : null;
    }

    private function lineDate(StockAdjustmentItem $line): ?CarbonImmutable
    {
        $date = $line->adjustment?->adjustment_date ?? $line->created_at;

        return $date ? CarbonImmutable::parse($date) : null;
    }

    private function dateRange(
        ?string $fromDate,
        ?string $toDate
    ): array {
        $defaultDays = max(
            (int) config(
                'stock_reconciliation.prevention.default_lookback_days',
                180
            ),
            1
        );

        $to = $toDate
            ? CarbonImmutable::parse($toDate)->endOfDay()
            : CarbonImmutable::now()->endOfDay();
        $from = $fromDate
            ? CarbonImmutable::parse($fromDate)->startOfDay()
            : $to->subDays($defaultDays - 1)->startOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [
                $to->startOfDay(),
                $from->endOfDay(),
            ];
        }

        return [$from, $to];
    }
}
