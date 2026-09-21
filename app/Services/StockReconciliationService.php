<?php

namespace App\Services;

use App\Models\PurchaseItem;
use App\Models\StockReconciliation;
use App\Models\StockReconciliationItem;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class StockReconciliationService
{
    private const EPSILON = 0.000001;

    public function createSnapshot(
        ?string $countDate = null,
        ?string $notes = null,
        ?array $productIds = null
    ): StockReconciliation {
        return DB::transaction(function () use ($countDate, $notes, $productIds): StockReconciliation {
            $snapshotAt = now();

            $normalisedProductIds = $productIds === null
                ? null
                : collect($productIds)
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn (int $id) => $id > 0)
                    ->unique()
                    ->values()
                    ->all();

            if ($productIds !== null && $normalisedProductIds === []) {
                throw new RuntimeException('At least one material must be selected for a targeted cycle count.');
            }

            $batches = PurchaseItem::query()
                ->whereHas('purchase', fn ($q) => $q->where('status', 'arrived'))
                ->whereHas('product', fn ($q) => $q->where('is_active', true))
                ->when(
                    $normalisedProductIds !== null,
                    fn ($q) => $q->whereIn('product_id', $normalisedProductIds)
                )
                ->with(['purchase:id,purchase_no,status', 'product:id,name,unit,type'])
                ->orderBy('product_id')
                ->orderBy('id')
                ->get()
                ->filter(fn (PurchaseItem $batch) => $batch->availableInventoryQuantity() > self::EPSILON)
                ->values();

            if ($batches->isEmpty()) {
                throw new RuntimeException(
                    $normalisedProductIds === null
                        ? 'No arrived inventory with available stock exists to count.'
                        : 'None of the selected materials has arrived inventory with available stock to count.'
                );
            }

            $reconciliation = StockReconciliation::create([
                'reconciliation_no' => $this->generateNumber(),
                'count_date' => $countDate ?: today()->toDateString(),
                'snapshot_at' => $snapshotAt,
                'status' => StockReconciliation::STATUS_COUNTING,
                'notes' => $notes,
                'created_by' => Auth::id(),
            ]);

            $rows = $batches->map(function (PurchaseItem $batch) use ($reconciliation, $snapshotAt): array {
                $systemQuantity = $batch->availableInventoryQuantity();

                return [
                    'stock_reconciliation_id' => $reconciliation->id,
                    'product_id' => $batch->product_id,
                    'purchase_item_id' => $batch->id,
                    'batch_no' => $batch->batch_no,
                    'purchase_no' => $batch->purchase?->purchase_no,
                    'inventory_unit' => $batch->inventoryCostBasisUnit(),
                    'system_quantity' => $systemQuantity,
                    'physical_quantity' => null,
                    'variance_quantity' => null,
                    'cost_per_unit_usd' => $batch->landedCostPerInventoryUnitUsd(),
                    'variance_value_usd' => null,
                    'reason_code' => null,
                    'notes' => null,
                    'batch_updated_at_snapshot' => $batch->updated_at,
                    'batch_native_quantity_snapshot' => (float) ($batch->qty_available ?? 0),
                    'batch_kg_quantity_snapshot' => (float) ($batch->qty_kg_available ?? 0),
                    'created_at' => $snapshotAt,
                    'updated_at' => $snapshotAt,
                ];
            })->all();

            StockReconciliationItem::insert($rows);

            return $reconciliation->load(['items.product', 'creator']);
        });
    }

    public function updateCount(
        StockReconciliation $reconciliation,
        StockReconciliationItem $item,
        float $physicalQuantity,
        ?string $reasonCode = null,
        ?string $notes = null
    ): StockReconciliationItem {
        if (! $reconciliation->isEditable()) {
            throw new RuntimeException('Only a reconciliation that is still being counted can be edited.');
        }

        if ((int) $item->stock_reconciliation_id !== (int) $reconciliation->id) {
            throw new RuntimeException('The reconciliation item does not belong to this count.');
        }

        if ($physicalQuantity < 0) {
            throw new RuntimeException('Physical quantity cannot be negative.');
        }

        $variance = $physicalQuantity - (float) $item->system_quantity;
        $varianceValue = $variance * (float) $item->cost_per_unit_usd;

        $item->update([
            'physical_quantity' => $physicalQuantity,
            'variance_quantity' => $variance,
            'variance_value_usd' => $varianceValue,
            'reason_code' => $reasonCode ?: null,
            'notes' => $notes ?: null,
        ]);

        return $item->fresh(['product', 'purchaseItem']);
    }

    public function submit(StockReconciliation $reconciliation): StockReconciliation
    {
        return DB::transaction(function () use ($reconciliation): StockReconciliation {
            $reconciliation->refresh()->load('items');

            if (! $reconciliation->isEditable()) {
                throw new RuntimeException('Only a reconciliation in counting status can be submitted.');
            }

            if ($reconciliation->items->isEmpty()) {
                throw new RuntimeException('The reconciliation has no inventory lines.');
            }

            $missingCount = $reconciliation->items
                ->first(fn (StockReconciliationItem $item) => $item->physical_quantity === null);

            if ($missingCount) {
                throw new RuntimeException(
                    'Every inventory line must have a physical quantity before submission.'
                );
            }

            foreach ($reconciliation->items as $item) {
                if ($this->reasonIsRequired($item) && blank($item->reason_code)) {
                    throw new RuntimeException(sprintf(
                        'A variance reason is required for %s (batch %s).',
                        $item->product?->name ?? ('Product #' . $item->product_id),
                        $item->batch_no ?: $item->purchase_item_id
                    ));
                }
            }

            $reconciliation->update([
                'status' => StockReconciliation::STATUS_SUBMITTED,
                'submitted_by' => Auth::id(),
                'submitted_at' => now(),
            ]);

            return $reconciliation->fresh(['items.product', 'creator', 'submitter']);
        });
    }

    public function cancel(StockReconciliation $reconciliation): StockReconciliation
    {
        return DB::transaction(function () use ($reconciliation): StockReconciliation {
            $locked = StockReconciliation::query()
                ->lockForUpdate()
                ->findOrFail($reconciliation->id);

            if ($locked->status !== StockReconciliation::STATUS_COUNTING) {
                throw new RuntimeException('Only a reconciliation that is still being counted can be cancelled.');
            }

            $locked->update([
                'status' => StockReconciliation::STATUS_CANCELLED,
            ]);

            return $locked->fresh(['items.product', 'creator']);
        });
    }

    public function approve(StockReconciliation $reconciliation): StockReconciliation
    {
        return DB::transaction(function () use ($reconciliation): StockReconciliation {
            $locked = StockReconciliation::query()
                ->lockForUpdate()
                ->findOrFail($reconciliation->id);

            if ($locked->status !== StockReconciliation::STATUS_SUBMITTED) {
                throw new RuntimeException('Only a submitted stock reconciliation can be approved.');
            }

            $locked->load('items');

            $thresholdUsd = (float) config(
                'stock_reconciliation.independent_approval_required_above_usd',
                100.00
            );
            $absoluteVarianceValueUsd = $this->absoluteVarianceValueUsd($locked);
            $approverId = Auth::id();

            if (
                $thresholdUsd >= 0
                && $absoluteVarianceValueUsd > $thresholdUsd
                && $approverId !== null
                && (
                    (int) $locked->created_by === (int) $approverId
                    || (int) $locked->submitted_by === (int) $approverId
                )
            ) {
                throw new RuntimeException(sprintf(
                    'This reconciliation has %.2f USD of absolute variance and requires an independent approver because it exceeds the %.2f USD control threshold.',
                    $absoluteVarianceValueUsd,
                    $thresholdUsd
                ));
            }

            $locked->update([
                'status' => StockReconciliation::STATUS_APPROVED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            return $locked->fresh(['items.product', 'creator', 'submitter', 'approver']);
        });
    }

    public function reject(StockReconciliation $reconciliation, string $reason): StockReconciliation
    {
        if (trim($reason) === '') {
            throw new RuntimeException('A rejection reason is required.');
        }

        return DB::transaction(function () use ($reconciliation, $reason): StockReconciliation {
            $locked = StockReconciliation::query()
                ->lockForUpdate()
                ->findOrFail($reconciliation->id);

            if ($locked->status !== StockReconciliation::STATUS_SUBMITTED) {
                throw new RuntimeException('Only a submitted stock reconciliation can be rejected.');
            }

            $locked->update([
                'status' => StockReconciliation::STATUS_REJECTED,
                'rejected_by' => Auth::id(),
                'rejected_at' => now(),
                'rejection_reason' => trim($reason),
            ]);

            return $locked->fresh(['items.product', 'creator', 'submitter', 'rejecter']);
        });
    }

    public function post(StockReconciliation $reconciliation): StockAdjustment
    {
        return DB::transaction(function () use ($reconciliation): StockAdjustment {
            $locked = StockReconciliation::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($reconciliation->id);

            if ($locked->status !== StockReconciliation::STATUS_APPROVED) {
                throw new RuntimeException('Only an approved stock reconciliation can be posted.');
            }

            if (StockAdjustment::where('stock_reconciliation_id', $locked->id)->exists()) {
                throw new RuntimeException('This stock reconciliation has already been posted.');
            }

            $now = now();
            $adjustment = StockAdjustment::create([
                'adjustment_no' => $this->generateAdjustmentNumber(),
                'stock_reconciliation_id' => $locked->id,
                'adjustment_date' => $locked->count_date,
                'type' => 'reconciliation',
                'status' => 'posted',
                'notes' => 'Posted from ' . $locked->reconciliation_no,
                'created_by' => Auth::id(),
                'posted_by' => Auth::id(),
                'posted_at' => $now,
            ]);

            foreach ($locked->items as $item) {
                if ($item->physical_quantity === null) {
                    throw new RuntimeException(
                        'Cannot post a reconciliation with an uncounted inventory line.'
                    );
                }

                $variance = (float) $item->physical_quantity - (float) $item->system_quantity;

                if (abs($variance) <= self::EPSILON) {
                    continue;
                }

                $batch = PurchaseItem::query()
                    ->lockForUpdate()
                    ->findOrFail($item->purchase_item_id);

                if ((int) $batch->product_id !== (int) $item->product_id) {
                    throw new RuntimeException(
                        'The purchase batch no longer belongs to the expected product.'
                    );
                }

                $before = $batch->availableInventoryQuantity();
                $after = $before + $variance;

                if ($after < -self::EPSILON) {
                    throw new RuntimeException(sprintf(
                        'Posting %s would make batch %s negative: current %.6f %s, adjustment %.6f %s.',
                        $locked->reconciliation_no,
                        $item->batch_no ?: $item->purchase_item_id,
                        $before,
                        $item->inventory_unit,
                        $variance,
                        $item->inventory_unit
                    ));
                }

                if ($batch->isRollBatch()) {
                    $batch->qty_kg_adjusted = (float) ($batch->qty_kg_adjusted ?? 0) + $variance;
                } else {
                    $batch->qty_adjusted = (float) ($batch->qty_adjusted ?? 0) + $variance;
                }

                $batch->save();
                $batch->refresh();

                $actualAfter = $batch->availableInventoryQuantity();
                if (abs($actualAfter - max($after, 0.0)) > 0.0001) {
                    throw new RuntimeException(sprintf(
                        'Inventory adjustment verification failed for batch %s.',
                        $item->batch_no ?: $item->purchase_item_id
                    ));
                }

                StockAdjustmentItem::create([
                    'stock_adjustment_id' => $adjustment->id,
                    'product_id' => $item->product_id,
                    'purchase_item_id' => $item->purchase_item_id,
                    'inventory_unit' => $item->inventory_unit,
                    'before_quantity' => $before,
                    'adjustment_quantity' => $variance,
                    'after_quantity' => $actualAfter,
                    'cost_per_unit_usd' => (float) $item->cost_per_unit_usd,
                    'adjustment_value_usd' => $variance * (float) $item->cost_per_unit_usd,
                    'reason_code' => $item->reason_code,
                    'notes' => $item->notes,
                ]);
            }

            $locked->update([
                'status' => StockReconciliation::STATUS_POSTED,
                'posted_by' => Auth::id(),
                'posted_at' => $now,
            ]);

            return $adjustment->fresh(['items.product', 'items.purchaseItem', 'reconciliation']);
        });
    }

    public function absoluteVarianceValueUsd(StockReconciliation $reconciliation): float
    {
        $reconciliation->loadMissing('items');

        return (float) $reconciliation->items->sum(
            fn (StockReconciliationItem $item) => abs((float) ($item->variance_value_usd ?? 0))
        );
    }

    public function requiresIndependentApproval(StockReconciliation $reconciliation): bool
    {
        $thresholdUsd = (float) config(
            'stock_reconciliation.independent_approval_required_above_usd',
            100.00
        );

        return $thresholdUsd >= 0
            && $this->absoluteVarianceValueUsd($reconciliation) > $thresholdUsd;
    }

    public function reasonIsRequired(StockReconciliationItem $item): bool
    {
        if ($item->physical_quantity === null || $item->variance_quantity === null) {
            return false;
        }

        $absoluteVariance = abs((float) $item->variance_quantity);
        $absoluteTolerance = (float) config(
            'stock_reconciliation.reason_required_absolute_tolerance',
            0.01
        );

        $system = abs((float) $item->system_quantity);
        $percentage = $system > self::EPSILON
            ? ($absoluteVariance / $system) * 100
            : ($absoluteVariance > self::EPSILON ? 100 : 0);

        $percentageTolerance = (float) config(
            'stock_reconciliation.reason_required_percentage_tolerance',
            0.10
        );

        return $absoluteVariance > $absoluteTolerance
            && $percentage > $percentageTolerance;
    }

    private function generateAdjustmentNumber(): string
    {
        do {
            $number = 'SA-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
        } while (StockAdjustment::where('adjustment_no', $number)->exists());

        return $number;
    }

    private function generateNumber(): string
    {
        do {
            $number = 'SR-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
        } while (StockReconciliation::where('reconciliation_no', $number)->exists());

        return $number;
    }
}
