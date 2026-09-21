<?php

namespace App\Services;

use App\Models\ProductionMaterialConsumption;
use App\Models\ProductionReelConsumption;
use App\Models\PurchaseItem;
use App\Models\PurchaseItemReel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReelInventoryService
{
    private const EPSILON = 0.0001;
    private const ALIGNMENT_TOLERANCE_KG = 0.05;

    public function initializeBatch(
        PurchaseItem $batch,
        ?array $currentReelWeightsKg = null
    ): Collection {
        return DB::transaction(function () use (
            $batch,
            $currentReelWeightsKg
        ): Collection {
            $locked = PurchaseItem::query()
                ->lockForUpdate()
                ->findOrFail($batch->id);

            if (! $locked->isRollBatch()) {
                throw new RuntimeException(
                    'Physical reel tracking is only available for roll-based inventory batches.'
                );
            }

            if ($locked->reels()->exists()) {
                throw new RuntimeException(
                    'Physical reels have already been initialized for this batch.'
                );
            }

            $availableKg = $locked->availableKg();
            if ($availableKg <= self::EPSILON) {
                throw new RuntimeException(
                    'This batch has no available kg to register as physical reels.'
                );
            }

            $weights = $this->normalizeWeights($currentReelWeightsKg);

            if ($weights === []) {
                $rollCount = (float) $locked->qty;
                $wholeRolls = (int) round($rollCount);

                $hasPriorActivity =
                    (float) ($locked->qty_kg_used ?? 0) > self::EPSILON
                    || (float) ($locked->qty_kg_sold ?? 0) > self::EPSILON
                    || (float) ($locked->qty_kg_wasted ?? 0) > self::EPSILON
                    || abs((float) ($locked->qty_kg_adjusted ?? 0)) > self::EPSILON;

                if (
                    abs($rollCount - $wholeRolls) > self::EPSILON
                    || $wholeRolls < 1
                    || $hasPriorActivity
                    || abs($availableKg - $locked->totalKg())
                        > self::ALIGNMENT_TOLERANCE_KG
                ) {
                    throw new RuntimeException(
                        'This batch has prior activity or a fractional roll balance. '
                        .'Enter the current remaining reel weights manually after the batch total has been reconciled.'
                    );
                }

                $weights = array_fill(
                    0,
                    $wholeRolls,
                    (float) $locked->kg_per_roll
                );
            }

            $total = array_sum($weights);
            if (
                abs($total - $availableKg)
                > self::ALIGNMENT_TOLERANCE_KG
            ) {
                throw new RuntimeException(sprintf(
                    'Registered reel weights must total the authoritative batch balance. '
                    .'Entered %.4f kg; batch available %.4f kg. Reconcile the batch first if the physical total differs.',
                    $total,
                    $availableKg
                ));
            }

            $standardWeight = (float) $locked->kg_per_roll;
            $prefix = $this->reelPrefix($locked);
            $created = collect();

            foreach (array_values($weights) as $index => $weight) {
                $sequence = $index + 1;
                $isLikelyFull = abs($weight - $standardWeight)
                    <= self::ALIGNMENT_TOLERANCE_KG;

                $created->push(PurchaseItemReel::create([
                    'purchase_item_id' => $locked->id,
                    'sequence_no' => $sequence,
                    'reel_code' => sprintf(
                        '%s-R%03d',
                        $prefix,
                        $sequence
                    ),
                    'registered_weight_kg' => $weight,
                    'system_remaining_weight_kg' => $weight,
                    'status' => $isLikelyFull
                        ? PurchaseItemReel::STATUS_SEALED
                        : PurchaseItemReel::STATUS_OPEN,
                    'opened_at' => $isLikelyFull ? null : now(),
                ]));
            }

            $this->assertAligned($locked->fresh());

            return $created;
        });
    }

    public function recordMeasurement(
        PurchaseItemReel $reel,
        float $measuredWeightKg,
        ?string $notes = null
    ): PurchaseItemReel {
        if ($measuredWeightKg < -self::EPSILON) {
            throw new RuntimeException('Measured reel weight cannot be negative.');
        }

        return DB::transaction(function () use (
            $reel,
            $measuredWeightKg,
            $notes
        ): PurchaseItemReel {
            $locked = PurchaseItemReel::query()
                ->lockForUpdate()
                ->findOrFail($reel->id);

            $systemWeight = max(
                (float) $locked->system_remaining_weight_kg,
                0
            );
            $measured = max($measuredWeightKg, 0);
            $variance = $measured - $systemWeight;

            $measurement = $locked->measurements()->create([
                'system_weight_snapshot_kg' => $systemWeight,
                'measured_weight_kg' => $measured,
                'variance_kg' => $variance,
                'measured_by' => Auth::id(),
                'measured_at' => now(),
                'notes' => $this->cleanText($notes),
                'created_at' => now(),
            ]);

            $locked->update([
                'last_measured_weight_kg' => $measurement->measured_weight_kg,
                'measurement_variance_kg' => $measurement->variance_kg,
                'last_measured_at' => $measurement->measured_at,
                'last_measured_by' => Auth::id(),
            ]);

            return $locked->fresh([
                'purchaseItem',
                'measurements.measurer',
                'measuredBy',
            ]);
        });
    }

    /**
     * Change only the physical reel control state. This is intentionally
     * separate from stock reconciliation: it never changes batch or reel kg.
     */
    public function changeControlStatus(
        PurchaseItemReel $reel,
        string $action,
        string $reason
    ): PurchaseItemReel {
        $action = strtolower(trim($action));
        $reason = $this->cleanText($reason);

        if (! in_array($action, ['damaged', 'quarantined', 'release'], true)) {
            throw new RuntimeException('Unsupported reel control action.');
        }

        if ($reason === null) {
            throw new RuntimeException('A reason is required for every reel status change.');
        }

        return DB::transaction(function () use ($reel, $action, $reason): PurchaseItemReel {
            $locked = PurchaseItemReel::query()
                ->lockForUpdate()
                ->findOrFail($reel->id);

            $fromStatus = (string) $locked->status;
            $weight = max((float) $locked->system_remaining_weight_kg, 0);

            if ($action === 'release') {
                if (! $locked->isBlockedFromProduction()) {
                    throw new RuntimeException(
                        'Only a damaged or quarantined reel can be released.'
                    );
                }

                $toStatus = $weight <= self::EPSILON
                    ? PurchaseItemReel::STATUS_CONSUMED
                    : (
                        abs($weight - (float) $locked->registered_weight_kg)
                            <= self::ALIGNMENT_TOLERANCE_KG
                        ? PurchaseItemReel::STATUS_SEALED
                        : PurchaseItemReel::STATUS_OPEN
                    );
            } else {
                if ($weight <= self::EPSILON) {
                    throw new RuntimeException(
                        'A consumed reel cannot be marked damaged or quarantined.'
                    );
                }

                $toStatus = $action === 'damaged'
                    ? PurchaseItemReel::STATUS_DAMAGED
                    : PurchaseItemReel::STATUS_QUARANTINED;
            }

            if ($fromStatus === $toStatus) {
                throw new RuntimeException('The reel is already in the requested status.');
            }

            $changedAt = now();
            $changedBy = Auth::id();

            $locked->statusEvents()->create([
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'reason' => $reason,
                'changed_by' => $changedBy,
                'changed_at' => $changedAt,
                'created_at' => $changedAt,
            ]);

            $locked->update([
                'status' => $toStatus,
                'status_reason' => $action === 'release' ? null : $reason,
                'status_changed_at' => $changedAt,
                'status_changed_by' => $changedBy,
                'opened_at' => $toStatus === PurchaseItemReel::STATUS_OPEN
                    ? ($locked->opened_at ?: $changedAt)
                    : $locked->opened_at,
                'depleted_at' => $toStatus === PurchaseItemReel::STATUS_CONSUMED
                    ? ($locked->depleted_at ?: $changedAt)
                    : null,
            ]);

            return $locked->fresh([
                'statusEvents.changedBy',
                'statusChangedBy',
            ]);
        });
    }

    public function rebaselineFromLatestMeasurements(
        PurchaseItem $batch
    ): Collection {
        return DB::transaction(function () use ($batch): Collection {
            $locked = PurchaseItem::query()
                ->lockForUpdate()
                ->findOrFail($batch->id);

            if (! $locked->isRollBatch()) {
                throw new RuntimeException(
                    'Only roll batches can be re-baselined from reel measurements.'
                );
            }

            $reels = $locked->reels()
                ->orderBy('sequence_no')
                ->lockForUpdate()
                ->get();

            if ($reels->isEmpty()) {
                throw new RuntimeException(
                    'No physical reels are registered for this batch.'
                );
            }

            $reelsToMeasure = $reels->filter(
                fn (PurchaseItemReel $reel) =>
                    $reel->status !== PurchaseItemReel::STATUS_CONSUMED
                    || (float) ($reel->last_measured_weight_kg ?? 0)
                        > self::EPSILON
            );

            $unmeasured = $reelsToMeasure->filter(
                fn (PurchaseItemReel $reel) =>
                    $reel->last_measured_weight_kg === null
            );

            if ($unmeasured->isNotEmpty()) {
                throw new RuntimeException(
                    'Every active physical reel must have a current measured weight before re-baselining.'
                );
            }

            $batchChangedAt = $locked->updated_at;
            $staleMeasurements = $reelsToMeasure->filter(
                fn (PurchaseItemReel $reel) =>
                    ! $reel->last_measured_at
                    || (
                        $batchChangedAt
                        && $reel->last_measured_at->lt($batchChangedAt)
                    )
            );

            if ($staleMeasurements->isNotEmpty()) {
                throw new RuntimeException(
                    'Reel measurements are stale relative to the latest batch stock change. '
                    .'Re-weigh every registered reel before re-baselining.'
                );
            }

            $measuredTotal = (float) $reelsToMeasure->sum(
                fn (PurchaseItemReel $reel) =>
                    (float) $reel->last_measured_weight_kg
            );
            $batchTotal = $locked->availableKg();

            if (
                abs($measuredTotal - $batchTotal)
                > self::ALIGNMENT_TOLERANCE_KG
            ) {
                throw new RuntimeException(sprintf(
                    'Measured reel total %.4f kg does not match the authoritative batch balance %.4f kg. '
                    .'Post the approved stock reconciliation first, then re-baseline the reels.',
                    $measuredTotal,
                    $batchTotal
                ));
            }

            foreach ($reels as $reel) {
                $weight = max(
                    (float) $reel->last_measured_weight_kg,
                    0
                );

                // A warehouse control hold is independent of physical weight.
                // Re-baselining may align kg after an approved reconciliation,
                // but it must not silently release damaged/quarantined material.
                $status = $reel->isBlockedFromProduction()
                    ? $reel->status
                    : (
                        $weight <= self::EPSILON
                            ? PurchaseItemReel::STATUS_CONSUMED
                            : (
                                abs(
                                    $weight
                                    - (float) $reel->registered_weight_kg
                                ) <= self::ALIGNMENT_TOLERANCE_KG
                                    ? PurchaseItemReel::STATUS_SEALED
                                    : PurchaseItemReel::STATUS_OPEN
                            )
                    );

                $reel->update([
                    'system_remaining_weight_kg' => $weight,
                    'status' => $status,
                    'opened_at' => $status === PurchaseItemReel::STATUS_OPEN
                        ? ($reel->opened_at ?: now())
                        : $reel->opened_at,
                    'depleted_at' => $status === PurchaseItemReel::STATUS_CONSUMED
                        ? ($reel->depleted_at ?: now())
                        : null,
                ]);
            }

            $this->assertAligned($locked->fresh());

            return $reels->fresh();
        });
    }

    public function consumeForConsumption(
        ProductionMaterialConsumption $consumption
    ): Collection {
        $quantityKg = (float) $consumption->actual_quantity;
        if ($quantityKg <= self::EPSILON) {
            return collect();
        }

        if (! $consumption->purchase_item_id) {
            return collect();
        }

        return DB::transaction(function () use (
            $consumption,
            $quantityKg
        ): Collection {
            $batch = PurchaseItem::query()
                ->lockForUpdate()
                ->findOrFail($consumption->purchase_item_id);

            if (! $batch->isRollBatch() || ! $batch->reels()->exists()) {
                return collect();
            }

            // Lock every reel so the alignment check includes stock that is
            // physically present but blocked from production.
            $reels = PurchaseItemReel::query()
                ->where('purchase_item_id', $batch->id)
                ->orderByRaw(
                    "CASE
                        WHEN status = 'open' THEN 0
                        WHEN status = 'sealed' THEN 1
                        WHEN status = 'quarantined' THEN 2
                        WHEN status = 'damaged' THEN 3
                        ELSE 4
                    END"
                )
                ->orderBy('sequence_no')
                ->lockForUpdate()
                ->get();

            $trackedBefore = (float) $reels->sum(
                'system_remaining_weight_kg'
            );

            $expectedBefore = $batch->availableKg() + $quantityKg;
            if (
                abs($trackedBefore - $expectedBefore)
                > self::ALIGNMENT_TOLERANCE_KG
            ) {
                throw new RuntimeException(sprintf(
                    'Physical reel tracking is out of sync for batch %s. '
                    .'Tracked before-consumption weight %.4f kg; expected %.4f kg. '
                    .'Measure/reconcile and re-baseline the reels before production.',
                    $batch->batch_no ?: $batch->id,
                    $trackedBefore,
                    $expectedBefore
                ));
            }

            if ($trackedBefore + self::EPSILON < $quantityKg) {
                throw new RuntimeException(
                    'Tracked reels do not contain enough system weight for this production consumption.'
                );
            }

            $eligibleReels = $reels->filter(
                fn (PurchaseItemReel $reel) => $reel->isProductionEligible()
            );
            $eligibleKg = (float) $eligibleReels->sum(
                'system_remaining_weight_kg'
            );

            if ($eligibleKg + self::EPSILON < $quantityKg) {
                $blockedKg = max($trackedBefore - $eligibleKg, 0);

                throw new RuntimeException(sprintf(
                    'Production-eligible reels contain only %.4f kg; %.4f kg is blocked as damaged/quarantined. Release or replace the blocked reel before production.',
                    $eligibleKg,
                    $blockedKg
                ));
            }

            $remaining = $quantityKg;
            $allocations = collect();

            foreach ($eligibleReels as $reel) {
                if ($remaining <= self::EPSILON) {
                    break;
                }

                $before = (float) $reel->system_remaining_weight_kg;
                $used = min($before, $remaining);

                if ($used <= self::EPSILON) {
                    continue;
                }

                $after = max($before - $used, 0);
                $status = $after <= self::EPSILON
                    ? PurchaseItemReel::STATUS_CONSUMED
                    : PurchaseItemReel::STATUS_OPEN;

                $reel->update([
                    'system_remaining_weight_kg' => $after,
                    'status' => $status,
                    'opened_at' => $reel->opened_at ?: now(),
                    'depleted_at' => $status === PurchaseItemReel::STATUS_CONSUMED
                        ? now()
                        : null,
                ]);

                $allocations->push(ProductionReelConsumption::create([
                    'production_material_consumption_id' => $consumption->id,
                    'purchase_item_reel_id' => $reel->id,
                    'quantity_kg' => $used,
                    'before_weight_kg' => $before,
                    'after_weight_kg' => $after,
                    'consumed_at' => $consumption->consumed_at ?: now(),
                    'created_at' => now(),
                ]));

                $remaining -= $used;
            }

            if ($remaining > self::EPSILON) {
                throw new RuntimeException(sprintf(
                    'Unable to allocate %.4f kg of production consumption to physical reels.',
                    $remaining
                ));
            }

            $this->assertAligned($batch->fresh());

            return $allocations;
        });
    }

    public function restoreConsumption(
        ProductionMaterialConsumption $consumption,
        ?float $quantityKg = null
    ): float {
        $requested = $quantityKg ?? (float) $consumption->actual_quantity;
        if ($requested <= self::EPSILON) {
            return 0.0;
        }

        return DB::transaction(function () use (
            $consumption,
            $requested
        ): float {
            $allocations = ProductionReelConsumption::query()
                ->where(
                    'production_material_consumption_id',
                    $consumption->id
                )
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();

            if ($allocations->isEmpty()) {
                return 0.0;
            }

            $remaining = $requested;
            $restored = 0.0;

            foreach ($allocations as $allocation) {
                if ($remaining <= self::EPSILON) {
                    break;
                }

                $allocated = (float) $allocation->quantity_kg;
                $toRestore = min($allocated, $remaining);

                if ($toRestore <= self::EPSILON) {
                    continue;
                }

                $reel = PurchaseItemReel::query()
                    ->lockForUpdate()
                    ->findOrFail($allocation->purchase_item_reel_id);

                $current = (float) $reel->system_remaining_weight_kg;
                $newWeight = $current + $toRestore;

                // Restoring an accounting/production allocation must not
                // silently clear an independent warehouse control hold.
                $status = $reel->isBlockedFromProduction()
                    ? $reel->status
                    : (
                        $newWeight <= self::EPSILON
                            ? PurchaseItemReel::STATUS_CONSUMED
                            : (
                                abs(
                                    $newWeight
                                    - (float) $reel->registered_weight_kg
                                ) <= self::ALIGNMENT_TOLERANCE_KG
                                    ? PurchaseItemReel::STATUS_SEALED
                                    : PurchaseItemReel::STATUS_OPEN
                            )
                    );

                $reel->update([
                    'system_remaining_weight_kg' => $newWeight,
                    'status' => $status,
                    'depleted_at' => $status === PurchaseItemReel::STATUS_CONSUMED
                        ? $reel->depleted_at
                        : null,
                ]);

                $newAllocated = $allocated - $toRestore;
                if ($newAllocated <= self::EPSILON) {
                    $allocation->delete();
                } else {
                    $allocation->quantity_kg = $newAllocated;
                    $allocation->after_weight_kg =
                        (float) $allocation->after_weight_kg
                        + $toRestore;
                    $allocation->save();
                }

                $remaining -= $toRestore;
                $restored += $toRestore;
            }

            if ($remaining > self::EPSILON) {
                throw new RuntimeException(sprintf(
                    'Unable to restore %.4f kg to the original physical reel lineage.',
                    $remaining
                ));
            }

            $batch = PurchaseItem::query()->find(
                $consumption->purchase_item_id
            );
            if ($batch) {
                $this->assertAligned($batch);
            }

            return $restored;
        });
    }

    public function summary(PurchaseItem $batch): array
    {
        $batch->loadMissing('purchase');

        $reels = $batch->reels()
            ->with(['measuredBy', 'statusChangedBy'])
            ->orderBy('sequence_no')
            ->get();

        $trackedKg = (float) $reels->sum('system_remaining_weight_kg');
        $measuredReels = $reels->filter(
            fn (PurchaseItemReel $reel) =>
                $reel->last_measured_weight_kg !== null
        );
        $measuredKg = (float) $measuredReels->sum(
            fn (PurchaseItemReel $reel) =>
                (float) $reel->last_measured_weight_kg
        );
        $batchKg = $batch->availableKg();
        $eligibleReels = $reels->filter(
            fn (PurchaseItemReel $reel) => $reel->isProductionEligible()
        );
        $blockedReels = $reels->filter(
            fn (PurchaseItemReel $reel) => $reel->isBlockedFromProduction()
        );
        $eligibleKg = (float) $eligibleReels->sum('system_remaining_weight_kg');
        $blockedKg = (float) $blockedReels->sum('system_remaining_weight_kg');

        $ageAnchor = $batch->purchase?->purchase_date ?: $batch->created_at;
        $ageDays = $ageAnchor
            ? max(
                (int) \Illuminate\Support\Carbon::parse($ageAnchor)
                    ->startOfDay()
                    ->diffInDays(now()->startOfDay()),
                0
            )
            : null;

        return [
            'tracked' => $reels->isNotEmpty(),
            'healthy' => $reels->isEmpty()
                || abs($trackedKg - $batchKg)
                    <= self::ALIGNMENT_TOLERANCE_KG,
            'batch_available_kg' => $batchKg,
            'tracked_system_kg' => $trackedKg,
            'difference_kg' => $trackedKg - $batchKg,
            'reel_count' => $reels->count(),
            'sealed_count' => $reels->where(
                'status',
                PurchaseItemReel::STATUS_SEALED
            )->count(),
            'open_count' => $reels->where(
                'status',
                PurchaseItemReel::STATUS_OPEN
            )->count(),
            'consumed_count' => $reels->where(
                'status',
                PurchaseItemReel::STATUS_CONSUMED
            )->count(),
            'damaged_count' => $reels->where(
                'status',
                PurchaseItemReel::STATUS_DAMAGED
            )->count(),
            'quarantined_count' => $reels->where(
                'status',
                PurchaseItemReel::STATUS_QUARANTINED
            )->count(),
            'blocked_count' => $blockedReels->count(),
            'eligible_kg' => $eligibleKg,
            'blocked_kg' => $blockedKg,
            'inventory_value_usd' => $batchKg * $batch->landedCostPerKg(),
            'age_days' => $ageDays,
            'measured_count' => $measuredReels->count(),
            'unmeasured_count' => $reels->count()
                - $measuredReels->count(),
            'latest_measured_total_kg' => $measuredKg,
            'reels' => $reels,
        ];
    }

    public function assertAligned(PurchaseItem $batch): void
    {
        $trackedTotal = (float) $batch->reels()
            ->sum('system_remaining_weight_kg');

        if (! $batch->reels()->exists()) {
            return;
        }

        $batchTotal = $batch->availableKg();

        if (
            abs($trackedTotal - $batchTotal)
            > self::ALIGNMENT_TOLERANCE_KG
        ) {
            throw new RuntimeException(sprintf(
                'Physical reel tracking is out of sync for batch %s: reels %.4f kg vs batch %.4f kg.',
                $batch->batch_no ?: $batch->id,
                $trackedTotal,
                $batchTotal
            ));
        }
    }

    private function normalizeWeights(?array $weights): array
    {
        if (! $weights) {
            return [];
        }

        return collect($weights)
            ->map(function ($weight): float {
                if (! is_numeric($weight)) {
                    throw new RuntimeException(
                        'Every reel weight must be numeric.'
                    );
                }

                $value = (float) $weight;
                if ($value <= self::EPSILON) {
                    throw new RuntimeException(
                        'Every registered reel weight must be greater than zero.'
                    );
                }

                return $value;
            })
            ->values()
            ->all();
    }

    private function reelPrefix(PurchaseItem $batch): string
    {
        $raw = trim((string) ($batch->batch_no ?: 'PI'.$batch->id));
        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $raw);

        return trim((string) $safe, '-') ?: 'PI'.$batch->id;
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
