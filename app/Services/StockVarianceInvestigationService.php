<?php

namespace App\Services;

use App\Models\StockAdjustmentItem;
use App\Models\StockVarianceInvestigation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockVarianceInvestigationService
{
    public function openForItem(
        StockAdjustmentItem $item,
        ?int $assignedTo = null,
        ?string $notes = null
    ): StockVarianceInvestigation {
        return DB::transaction(function () use ($item, $assignedTo, $notes): StockVarianceInvestigation {
            $existing = StockVarianceInvestigation::query()
                ->where('stock_adjustment_item_id', $item->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing->fresh([
                    'adjustmentItem.product',
                    'assignee',
                    'opener',
                    'resolver',
                    'events.user',
                ]);
            }

            $openedBy = Auth::id()
                ?: $item->adjustment?->posted_by
                ?: $item->adjustment?->created_by;

            $dueDays = max(
                (int) config('stock_reconciliation.investigation.default_due_days', 7),
                1
            );

            $investigation = StockVarianceInvestigation::create([
                'stock_adjustment_item_id' => $item->id,
                'status' => $assignedTo
                    ? StockVarianceInvestigation::STATUS_INVESTIGATING
                    : StockVarianceInvestigation::STATUS_OPEN,
                'assigned_to' => $assignedTo,
                'due_date' => now()->addDays($dueDays)->toDateString(),
                'investigation_notes' => $notes ? trim($notes) : null,
                'opened_by' => $openedBy,
                'opened_at' => now(),
                'started_at' => $assignedTo ? now() : null,
            ]);

            $this->event(
                $investigation,
                'opened',
                null,
                $investigation->status,
                $notes ?: 'Variance investigation opened.',
                [
                    'adjustment_item_id' => $item->id,
                    'assigned_to' => $assignedTo,
                ]
            );

            return $investigation->fresh([
                'adjustmentItem.product',
                'assignee',
                'opener',
                'resolver',
                'events.user',
            ]);
        });
    }

    public function openIfRequired(StockAdjustmentItem $item): ?StockVarianceInvestigation
    {
        $unresolvedCodes = config(
            'stock_reconciliation.unresolved_reason_codes',
            ['unknown']
        );

        if (! in_array($item->reason_code, $unresolvedCodes, true)) {
            return null;
        }

        return $this->openForItem($item);
    }

    public function update(
        StockVarianceInvestigation $investigation,
        array $data
    ): StockVarianceInvestigation {
        return DB::transaction(function () use ($investigation, $data): StockVarianceInvestigation {
            $locked = StockVarianceInvestigation::query()
                ->lockForUpdate()
                ->findOrFail($investigation->id);

            if ($locked->status === StockVarianceInvestigation::STATUS_RESOLVED) {
                throw new RuntimeException('Resolved investigations are read-only.');
            }

            $before = $locked->only([
                'status',
                'assigned_to',
                'due_date',
                'root_cause_code',
                'root_cause_details',
                'investigation_notes',
                'corrective_action',
            ]);

            $assignedTo = array_key_exists('assigned_to', $data)
                ? ($data['assigned_to'] ? (int) $data['assigned_to'] : null)
                : $locked->assigned_to;

            $newStatus = $assignedTo
                ? StockVarianceInvestigation::STATUS_INVESTIGATING
                : StockVarianceInvestigation::STATUS_OPEN;

            $locked->fill([
                'assigned_to' => $assignedTo,
                'due_date' => $data['due_date'] ?? $locked->due_date,
                'root_cause_code' => $data['root_cause_code'] ?? $locked->root_cause_code,
                'root_cause_details' => array_key_exists('root_cause_details', $data)
                    ? $this->cleanText($data['root_cause_details'])
                    : $locked->root_cause_details,
                'investigation_notes' => array_key_exists('investigation_notes', $data)
                    ? $this->cleanText($data['investigation_notes'])
                    : $locked->investigation_notes,
                'corrective_action' => array_key_exists('corrective_action', $data)
                    ? $this->cleanText($data['corrective_action'])
                    : $locked->corrective_action,
                'status' => $newStatus,
                'started_at' => $newStatus === StockVarianceInvestigation::STATUS_INVESTIGATING
                    ? ($locked->started_at ?: now())
                    : null,
            ]);
            $locked->save();

            $after = $locked->only([
                'status',
                'assigned_to',
                'due_date',
                'root_cause_code',
                'root_cause_details',
                'investigation_notes',
                'corrective_action',
            ]);

            $this->event(
                $locked,
                'updated',
                $before['status'],
                $after['status'],
                $data['event_note'] ?? 'Investigation details updated.',
                [
                    'before' => $before,
                    'after' => $after,
                ]
            );

            return $locked->fresh([
                'adjustmentItem.product',
                'assignee',
                'opener',
                'resolver',
                'events.user',
            ]);
        });
    }

    public function resolve(
        StockVarianceInvestigation $investigation,
        array $data
    ): StockVarianceInvestigation {
        return DB::transaction(function () use ($investigation, $data): StockVarianceInvestigation {
            $locked = StockVarianceInvestigation::query()
                ->lockForUpdate()
                ->findOrFail($investigation->id);

            if ($locked->status === StockVarianceInvestigation::STATUS_RESOLVED) {
                throw new RuntimeException('This variance investigation has already been resolved.');
            }

            $rootCauseCode = trim((string) ($data['root_cause_code'] ?? $locked->root_cause_code ?? ''));
            $rootCauseDetails = $this->cleanText(
                $data['root_cause_details'] ?? $locked->root_cause_details
            );
            $correctiveAction = $this->cleanText(
                $data['corrective_action'] ?? $locked->corrective_action
            );
            $resolutionNotes = $this->cleanText($data['resolution_notes'] ?? null);

            if ($rootCauseCode === '') {
                throw new RuntimeException('A root cause is required before resolving the investigation.');
            }
            if (! $rootCauseDetails) {
                throw new RuntimeException('Root cause details are required before resolving the investigation.');
            }
            if (! $correctiveAction) {
                throw new RuntimeException('A corrective action is required before resolving the investigation.');
            }
            if (! $resolutionNotes) {
                throw new RuntimeException('Resolution notes are required before resolving the investigation.');
            }

            $resolverId = Auth::id();
            if (! $resolverId) {
                throw new RuntimeException('A logged-in user is required to resolve a variance investigation.');
            }

            $fromStatus = $locked->status;

            $locked->update([
                'status' => StockVarianceInvestigation::STATUS_RESOLVED,
                'assigned_to' => $locked->assigned_to ?: $resolverId,
                'root_cause_code' => $rootCauseCode,
                'root_cause_details' => $rootCauseDetails,
                'corrective_action' => $correctiveAction,
                'resolution_notes' => $resolutionNotes,
                'started_at' => $locked->started_at ?: now(),
                'resolved_by' => $resolverId,
                'resolved_at' => now(),
            ]);

            $this->event(
                $locked,
                'resolved',
                $fromStatus,
                StockVarianceInvestigation::STATUS_RESOLVED,
                $resolutionNotes,
                [
                    'root_cause_code' => $rootCauseCode,
                    'corrective_action' => $correctiveAction,
                ]
            );

            return $locked->fresh([
                'adjustmentItem.product',
                'assignee',
                'opener',
                'resolver',
                'events.user',
            ]);
        });
    }

    private function event(
        StockVarianceInvestigation $investigation,
        string $eventType,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $notes,
        array $metadata = []
    ): void {
        $investigation->events()->create([
            'event_type' => $eventType,
            'user_id' => Auth::id(),
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'notes' => $this->cleanText($notes),
            'metadata' => $metadata ?: null,
            'created_at' => now(),
        ]);
    }

    private function cleanText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
