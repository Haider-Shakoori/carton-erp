<?php

namespace App\Services;

use App\Models\PurchaseItem;
use App\Models\StockReconciliation;
use App\Models\StockReconciliationItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class StockReconciliationService
{
    private const EPSILON = 0.000001;

    public function createSnapshot(?string $countDate = null, ?string $notes = null): StockReconciliation
    {
        return DB::transaction(function () use ($countDate, $notes): StockReconciliation {
            $snapshotAt = now();

            $batches = PurchaseItem::query()
                ->whereHas('purchase', fn ($q) => $q->where('status', 'arrived'))
                ->whereHas('product', fn ($q) => $q->where('is_active', true))
                ->with(['purchase:id,purchase_no,status', 'product:id,name,unit,type'])
                ->orderBy('product_id')
                ->orderBy('id')
                ->get()
                ->filter(fn (PurchaseItem $batch) => $batch->availableInventoryQuantity() > self::EPSILON)
                ->values();

            if ($batches->isEmpty()) {
                throw new RuntimeException('No arrived inventory with available stock exists to count.');
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

    private function generateNumber(): string
    {
        do {
            $number = 'SR-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
        } while (StockReconciliation::where('reconciliation_no', $number)->exists());

        return $number;
    }
}
