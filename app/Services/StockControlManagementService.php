<?php

namespace App\Services;

use App\Models\StockControlEscalation;
use App\Models\StockControlReview;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockControlManagementService
{
    public function __construct(
        private readonly InventoryPreventionIntelligenceService $intelligence
    ) {
    }

    public function syncEscalations(
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        $analysis = $this->intelligence->analyze($fromDate, $toDate);

        $created = 0;
        $updated = 0;
        $reopened = 0;

        foreach ($analysis['flags'] as $flag) {
            if (! in_array($flag['severity'], ['high', 'critical'], true)) {
                continue;
            }

            $result = DB::transaction(function () use ($flag, $analysis): string {
                $sourceKey = $this->sourceKey($flag);
                $level = $this->levelForSeverity($flag['severity']);
                $dueDate = now()->addDays($this->dueDaysForLevel($level))
                    ->toDateString();

                $escalation = StockControlEscalation::query()
                    ->where('source_key', $sourceKey)
                    ->lockForUpdate()
                    ->first();

                $metadata = [
                    'material_name' => $flag['material_name'] ?? null,
                    'analysis_from_date' => $analysis['from_date'],
                    'analysis_to_date' => $analysis['to_date'],
                    'flag' => $flag,
                ];

                if (! $escalation) {
                    $escalation = StockControlEscalation::create([
                        'source_key' => $sourceKey,
                        'source_type' => $flag['type'],
                        'severity' => $flag['severity'],
                        'level' => $level,
                        'status' => StockControlEscalation::STATUS_OPEN,
                        'product_id' => $flag['product_id'] ?? null,
                        'root_cause_code' => $flag['root_cause_code'] ?? null,
                        'investigation_id' => $flag['investigation_id'] ?? null,
                        'title' => $flag['title'],
                        'message' => $flag['message'],
                        'occurrences' => max((int) ($flag['occurrences'] ?? 1), 1),
                        'absolute_value_usd' => (float) ($flag['absolute_value_usd'] ?? 0),
                        'review_due_date' => $dueDate,
                        'opened_at' => now(),
                        'last_detected_at' => now(),
                        'metadata' => $metadata,
                    ]);

                    $this->event(
                        $escalation,
                        'opened',
                        null,
                        StockControlEscalation::STATUS_OPEN,
                        'Management escalation opened from prevention intelligence.',
                        ['flag' => $flag]
                    );

                    return 'created';
                }

                $wasClosed = $escalation->status === StockControlEscalation::STATUS_CLOSED;
                $oldLevel = (int) $escalation->level;
                $oldSeverity = $escalation->severity;
                $oldOccurrences = (int) $escalation->occurrences;
                $oldValue = (float) $escalation->absolute_value_usd;
                $oldMessage = $escalation->message;

                $newDue = CarbonImmutable::parse($dueDate);
                $currentDue = $escalation->review_due_date
                    ? CarbonImmutable::parse($escalation->review_due_date)
                    : null;

                $fields = [
                    'source_type' => $flag['type'],
                    'severity' => $flag['severity'],
                    'level' => max($oldLevel, $level),
                    'product_id' => $flag['product_id'] ?? $escalation->product_id,
                    'root_cause_code' => $flag['root_cause_code']
                        ?? $escalation->root_cause_code,
                    'investigation_id' => $flag['investigation_id']
                        ?? $escalation->investigation_id,
                    'title' => $flag['title'],
                    'message' => $flag['message'],
                    'occurrences' => max(
                        (int) ($flag['occurrences'] ?? 1),
                        $oldOccurrences
                    ),
                    'absolute_value_usd' => max(
                        (float) ($flag['absolute_value_usd'] ?? 0),
                        $oldValue
                    ),
                    'last_detected_at' => now(),
                    'metadata' => $metadata,
                ];

                if (
                    ! $currentDue
                    || $newDue->lessThan($currentDue)
                    || $wasClosed
                ) {
                    $fields['review_due_date'] = $newDue->toDateString();
                }

                $signalAdvanced = (int) ($flag['occurrences'] ?? 1) > $oldOccurrences
                    || (float) ($flag['absolute_value_usd'] ?? 0) > ($oldValue + 0.0001)
                    || $level > $oldLevel;

                if ($wasClosed && $signalAdvanced) {
                    $fields += [
                        'status' => StockControlEscalation::STATUS_OPEN,
                        'acknowledged_by' => null,
                        'acknowledged_at' => null,
                        'closed_by' => null,
                        'closed_at' => null,
                        'resolution_notes' => null,
                    ];
                }

                $escalation->fill($fields);
                $escalation->save();

                if ($wasClosed && $signalAdvanced) {
                    $this->event(
                        $escalation,
                        'reopened',
                        StockControlEscalation::STATUS_CLOSED,
                        StockControlEscalation::STATUS_OPEN,
                        'The management signal advanced after the escalation had been closed.',
                        ['flag' => $flag]
                    );

                    return 'reopened';
                }

                if ($wasClosed) {
                    return 'unchanged';
                }

                $materiallyChanged = $oldLevel !== (int) $escalation->level
                    || $oldSeverity !== $escalation->severity
                    || $oldOccurrences !== (int) $escalation->occurrences
                    || abs($oldValue - (float) $escalation->absolute_value_usd) > 0.0001
                    || $oldMessage !== $escalation->message;

                if ($materiallyChanged) {
                    $this->event(
                        $escalation,
                        'signal_updated',
                        $escalation->status,
                        $escalation->status,
                        'Prevention intelligence updated the management signal.',
                        [
                            'old_level' => $oldLevel,
                            'new_level' => (int) $escalation->level,
                            'old_occurrences' => $oldOccurrences,
                            'new_occurrences' => (int) $escalation->occurrences,
                        ]
                    );

                    return 'updated';
                }

                return 'unchanged';
            });

            match ($result) {
                'created' => $created++,
                'updated' => $updated++,
                'reopened' => $reopened++,
                default => null,
            };
        }

        return compact('created', 'updated', 'reopened');
    }

    public function assign(
        StockControlEscalation $escalation,
        ?int $managerId,
        ?string $reviewDueDate = null,
        ?string $notes = null
    ): StockControlEscalation {
        return DB::transaction(function () use (
            $escalation,
            $managerId,
            $reviewDueDate,
            $notes
        ): StockControlEscalation {
            $locked = StockControlEscalation::query()
                ->lockForUpdate()
                ->findOrFail($escalation->id);

            if ($locked->status === StockControlEscalation::STATUS_CLOSED) {
                throw new RuntimeException('Closed escalations are read-only until the signal recurs.');
            }

            if ($managerId !== null && ! User::query()->whereKey($managerId)->exists()) {
                throw new RuntimeException('Selected escalation owner does not exist.');
            }

            $before = [
                'escalated_to' => $locked->escalated_to,
                'review_due_date' => $locked->review_due_date?->toDateString(),
            ];

            $locked->update([
                'escalated_to' => $managerId,
                'review_due_date' => $reviewDueDate
                    ?: $locked->review_due_date,
            ]);

            $this->event(
                $locked,
                'assignment_updated',
                $locked->status,
                $locked->status,
                $notes ?: 'Escalation ownership or review deadline updated.',
                [
                    'before' => $before,
                    'after' => [
                        'escalated_to' => $locked->escalated_to,
                        'review_due_date' => $locked->review_due_date?->toDateString(),
                    ],
                ]
            );

            return $locked->fresh(['manager', 'events.user']);
        });
    }

    public function acknowledge(
        StockControlEscalation $escalation,
        ?string $notes = null
    ): StockControlEscalation {
        return DB::transaction(function () use ($escalation, $notes): StockControlEscalation {
            $locked = StockControlEscalation::query()
                ->lockForUpdate()
                ->findOrFail($escalation->id);

            if ($locked->status === StockControlEscalation::STATUS_CLOSED) {
                throw new RuntimeException('Closed escalations cannot be acknowledged.');
            }

            $userId = Auth::id();
            if (! $userId) {
                throw new RuntimeException('A logged-in user is required to acknowledge an escalation.');
            }

            $from = $locked->status;
            $locked->update([
                'status' => StockControlEscalation::STATUS_ACKNOWLEDGED,
                'acknowledged_by' => $userId,
                'acknowledged_at' => now(),
            ]);

            $this->event(
                $locked,
                'acknowledged',
                $from,
                StockControlEscalation::STATUS_ACKNOWLEDGED,
                $notes ?: 'Management escalation acknowledged.'
            );

            return $locked->fresh(['manager', 'acknowledger', 'events.user']);
        });
    }

    public function close(
        StockControlEscalation $escalation,
        string $resolutionNotes
    ): StockControlEscalation {
        return DB::transaction(function () use (
            $escalation,
            $resolutionNotes
        ): StockControlEscalation {
            $locked = StockControlEscalation::query()
                ->lockForUpdate()
                ->findOrFail($escalation->id);

            if ($locked->status === StockControlEscalation::STATUS_CLOSED) {
                throw new RuntimeException('This management escalation is already closed.');
            }

            $resolutionNotes = trim($resolutionNotes);
            if ($resolutionNotes === '') {
                throw new RuntimeException('Management closure notes are required.');
            }

            $userId = Auth::id();
            if (! $userId) {
                throw new RuntimeException('A logged-in user is required to close an escalation.');
            }

            $from = $locked->status;
            $locked->update([
                'status' => StockControlEscalation::STATUS_CLOSED,
                'closed_by' => $userId,
                'closed_at' => now(),
                'resolution_notes' => $resolutionNotes,
            ]);

            $this->event(
                $locked,
                'closed',
                $from,
                StockControlEscalation::STATUS_CLOSED,
                $resolutionNotes
            );

            return $locked->fresh(['manager', 'closer', 'events.user']);
        });
    }

    public function ensureWeeklyReview(
        ?string $referenceDate = null,
        ?int $ownerId = null
    ): StockControlReview {
        $this->syncEscalations();

        $reference = CarbonImmutable::parse(
            $referenceDate ?: today()->toDateString()
        );
        $weekStart = $reference->startOfWeek(CarbonImmutable::MONDAY);
        $weekEnd = $weekStart->endOfWeek(CarbonImmutable::SUNDAY);

        return DB::transaction(function () use (
            $weekStart,
            $weekEnd,
            $ownerId
        ): StockControlReview {
            $review = StockControlReview::query()
                ->whereDate('week_start', $weekStart->toDateString())
                ->lockForUpdate()
                ->first();

            $dueWeekday = min(max(
                (int) config(
                    'stock_reconciliation.management_control.weekly_review_due_weekday',
                    5
                ),
                1
            ), 7);
            $dueDate = $weekStart->addDays($dueWeekday - 1);

            if (! $review) {
                $review = StockControlReview::create([
                    'week_start' => $weekStart->toDateString(),
                    'week_end' => $weekEnd->toDateString(),
                    'status' => StockControlReview::STATUS_OPEN,
                    'owner_id' => $ownerId,
                    'due_date' => $dueDate->toDateString(),
                    'generated_at' => now(),
                ]);
            } elseif (
                $review->status === StockControlReview::STATUS_OPEN
                && $ownerId
                && ! $review->owner_id
            ) {
                $review->update(['owner_id' => $ownerId]);
            }

            if ($review->status === StockControlReview::STATUS_OPEN) {
                $activeEscalations = StockControlEscalation::query()
                    ->where('status', '!=', StockControlEscalation::STATUS_CLOSED)
                    ->orderByDesc('level')
                    ->orderBy('review_due_date')
                    ->get();

                foreach ($activeEscalations as $escalation) {
                    $review->items()->updateOrCreate(
                        ['stock_control_escalation_id' => $escalation->id],
                        [
                            'severity_snapshot' => $escalation->severity,
                            'level_snapshot' => $escalation->level,
                            'status_snapshot' => $escalation->status,
                        ]
                    );
                }

                $review->update([
                    'summary_snapshot' => $this->reviewSummary($activeEscalations),
                    'generated_at' => now(),
                ]);
            }

            return $review->fresh([
                'owner',
                'completer',
                'items.escalation.product',
                'items.escalation.manager',
            ]);
        });
    }

    public function updateReview(
        StockControlReview $review,
        ?int $ownerId,
        ?string $reviewNotes
    ): StockControlReview {
        if ($review->status === StockControlReview::STATUS_COMPLETED) {
            throw new RuntimeException('Completed management reviews are read-only.');
        }

        $review->update([
            'owner_id' => $ownerId,
            'review_notes' => $this->cleanText($reviewNotes),
        ]);

        return $review->fresh(['owner', 'items.escalation']);
    }

    public function completeReview(
        StockControlReview $review,
        string $reviewNotes,
        string $decisions
    ): StockControlReview {
        return DB::transaction(function () use (
            $review,
            $reviewNotes,
            $decisions
        ): StockControlReview {
            $locked = StockControlReview::query()
                ->lockForUpdate()
                ->findOrFail($review->id);

            if ($locked->status === StockControlReview::STATUS_COMPLETED) {
                throw new RuntimeException('This weekly management review is already completed.');
            }

            $reviewNotes = trim($reviewNotes);
            $decisions = trim($decisions);

            if ($reviewNotes === '' || $decisions === '') {
                throw new RuntimeException('Review notes and management decisions are required.');
            }

            $userId = Auth::id();
            if (! $userId) {
                throw new RuntimeException('A logged-in user is required to complete a review.');
            }

            $activeEscalations = StockControlEscalation::query()
                ->where('status', '!=', StockControlEscalation::STATUS_CLOSED)
                ->get();

            foreach ($activeEscalations as $escalation) {
                $locked->items()->updateOrCreate(
                    ['stock_control_escalation_id' => $escalation->id],
                    [
                        'severity_snapshot' => $escalation->severity,
                        'level_snapshot' => $escalation->level,
                        'status_snapshot' => $escalation->status,
                    ]
                );
            }

            $locked->update([
                'status' => StockControlReview::STATUS_COMPLETED,
                'review_notes' => $reviewNotes,
                'decisions' => $decisions,
                'summary_snapshot' => $this->reviewSummary($activeEscalations),
                'completed_by' => $userId,
                'completed_at' => now(),
            ]);

            return $locked->fresh([
                'owner',
                'completer',
                'items.escalation.product',
                'items.escalation.manager',
            ]);
        });
    }

    private function reviewSummary($escalations): array
    {
        return [
            'active_escalations' => $escalations->count(),
            'level_1' => $escalations->where('level', 1)->count(),
            'level_2' => $escalations->where('level', 2)->count(),
            'level_3' => $escalations->where('level', 3)->count(),
            'unassigned' => $escalations->whereNull('escalated_to')->count(),
            'overdue' => $escalations->filter(fn ($row) => $row->is_overdue)->count(),
            'absolute_value_usd' => round((float) $escalations->sum(
                fn ($row) => abs((float) $row->absolute_value_usd)
            ), 2),
            'captured_at' => now()->toIso8601String(),
        ];
    }

    private function sourceKey(array $flag): string
    {
        $parts = [
            $flag['type'] ?? 'unknown',
            (string) ($flag['product_id'] ?? 0),
            (string) ($flag['root_cause_code'] ?? 'none'),
        ];

        if (! empty($flag['investigation_id'])) {
            $parts[] = 'inv-'.$flag['investigation_id'];
        }

        return implode(':', $parts);
    }

    private function levelForSeverity(string $severity): int
    {
        return match ($severity) {
            'critical' => 3,
            'high' => 2,
            default => 1,
        };
    }

    private function dueDaysForLevel(int $level): int
    {
        return max((int) config(
            'stock_reconciliation.management_control.level_due_days.'.$level,
            match ($level) {
                3 => 1,
                2 => 3,
                default => 7,
            }
        ), 1);
    }

    private function event(
        StockControlEscalation $escalation,
        string $eventType,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $notes,
        array $metadata = []
    ): void {
        $escalation->events()->create([
            'event_type' => $eventType,
            'user_id' => Auth::id(),
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'notes' => $this->cleanText($notes),
            'metadata' => $metadata ?: null,
            'created_at' => now(),
        ]);
    }

    private function cleanText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
