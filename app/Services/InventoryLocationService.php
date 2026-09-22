<?php

namespace App\Services;

use App\Models\InventoryLocationBalance;
use App\Models\InventoryTransfer;
use App\Models\PurchaseItem;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Support\Business\BusinessUnitContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class InventoryLocationService
{
    private const EPSILON = 0.000001;

    public function enabled(): bool
    {
        return Schema::hasTable('inventory_location_balances')
            && Schema::hasTable('warehouse_locations')
            && Schema::hasColumn('purchase_items', 'warehouse_location_id');
    }

    public function defaultLocation(?int $businessUnitId = null): ?WarehouseLocation
    {
        if (! $this->enabled()) {
            return null;
        }

        $warehouse = Warehouse::query()
            ->withoutGlobalScope('business_unit')
            ->where('is_active', true)
            ->when(
                $businessUnitId,
                fn ($q) => $q->where('business_unit_id', $businessUnitId),
                fn ($q) => $q->whereNull('business_unit_id')
            )
            ->where('is_default', true)
            ->orderBy('id')
            ->first();

        if (! $warehouse && $businessUnitId) {
            $warehouse = Warehouse::query()
                ->withoutGlobalScope('business_unit')
                ->whereNull('business_unit_id')
                ->where('is_active', true)
                ->where('is_default', true)
                ->orderBy('id')
                ->first();
        }

        if (! $warehouse) {
            return null;
        }

        return WarehouseLocation::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('is_active', true)
            ->orderByDesc('is_receiving')
            ->orderBy('id')
            ->first();
    }

    public function ensureBatch(PurchaseItem $batch): ?InventoryLocationBalance
    {
        if (! $this->enabled()) {
            return null;
        }

        $existing = InventoryLocationBalance::query()
            ->where('purchase_item_id', $batch->id)
            ->orderBy('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $batch->loadMissing('purchase');

        $businessUnitId = $batch->purchase?->business_unit_id;
        $location = $batch->warehouse_location_id
            ? WarehouseLocation::query()->find($batch->warehouse_location_id)
            : $this->defaultLocation($businessUnitId ? (int) $businessUnitId : null);

        if (! $location) {
            return null;
        }

        if (! $batch->warehouse_location_id) {
            $batch->warehouse_location_id = $location->id;
            $batch->saveQuietly();
        }

        $quantity = $batch->availableInventoryQuantity();
        if ($quantity <= self::EPSILON) {
            return null;
        }

        return InventoryLocationBalance::query()->create([
            'purchase_item_id' => $batch->id,
            'warehouse_location_id' => $location->id,
            'condition_status' => 'available',
            'quantity' => $quantity,
            'unit' => $batch->inventoryCostBasisUnit(),
        ]);
    }

    public function availableForBatch(PurchaseItem $batch): float
    {
        if (! $this->enabled()) {
            return $batch->availableInventoryQuantity();
        }

        $hasBalances = InventoryLocationBalance::query()
            ->where('purchase_item_id', $batch->id)
            ->exists();

        if (! $hasBalances) {
            $this->ensureBatch($batch);
        }

        $sum = (float) InventoryLocationBalance::query()
            ->where('purchase_item_id', $batch->id)
            ->where('condition_status', 'available')
            ->sum('quantity');

        if (! InventoryLocationBalance::query()
            ->where('purchase_item_id', $batch->id)
            ->exists()) {
            return $batch->availableInventoryQuantity();
        }

        // Purchase-batch counters remain the authoritative physical ceiling.
        // Location balances classify that stock by warehouse/condition, but a stale
        // location balance must never make more stock available than the batch
        // itself currently contains.
        return min(
            max($sum, 0.0),
            max($batch->availableInventoryQuantity(), 0.0)
        );
    }

    public function syncAfterBatchChange(PurchaseItem $batch): void
    {
        if (! $this->enabled()) {
            return;
        }

        DB::transaction(function () use ($batch): void {
            $batch = PurchaseItem::query()
                ->with('purchase')
                ->lockForUpdate()
                ->findOrFail($batch->id);

            $this->ensureBatch($batch);

            $balances = InventoryLocationBalance::query()
                ->where('purchase_item_id', $batch->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($balances->isEmpty()) {
                return;
            }

            $targetPhysical = $batch->availableInventoryQuantity();
            $currentPhysical = (float) $balances->sum('quantity');
            $difference = $currentPhysical - $targetPhysical;

            if ($difference > self::EPSILON) {
                $remaining = $difference;

                foreach ($balances->where('condition_status', 'available') as $balance) {
                    if ($remaining <= self::EPSILON) {
                        break;
                    }

                    $take = min((float) $balance->quantity, $remaining);
                    $balance->quantity = max((float) $balance->quantity - $take, 0);
                    $balance->save();
                    $remaining -= $take;
                }

                if ($remaining > self::EPSILON) {
                    throw new RuntimeException(
                        'The requested stock movement would consume blocked or damaged warehouse stock.'
                    );
                }
            } elseif ($difference < -self::EPSILON) {
                $restore = abs($difference);
                $target = $balances->firstWhere('condition_status', 'available');

                if (! $target) {
                    $location = $this->defaultLocation(
                        $batch->purchase?->business_unit_id
                            ? (int) $batch->purchase->business_unit_id
                            : null
                    );

                    if (! $location) {
                        throw new RuntimeException('No active warehouse location exists for restored stock.');
                    }

                    $target = InventoryLocationBalance::query()->create([
                        'purchase_item_id' => $batch->id,
                        'warehouse_location_id' => $location->id,
                        'condition_status' => 'available',
                        'quantity' => 0,
                        'unit' => $batch->inventoryCostBasisUnit(),
                    ]);
                }

                $target->quantity = (float) $target->quantity + $restore;
                $target->unit = $batch->inventoryCostBasisUnit();
                $target->save();
            }

            InventoryLocationBalance::query()
                ->where('purchase_item_id', $batch->id)
                ->where('quantity', '<=', self::EPSILON)
                ->delete();
        });
    }

    public function reclassify(
        InventoryLocationBalance $balance,
        string $condition,
        float $quantity
    ): void {
        if (! in_array($condition, ['available', 'blocked', 'damaged'], true)) {
            throw new RuntimeException('Invalid stock condition.');
        }

        if ($condition === $balance->condition_status) {
            return;
        }

        if ($quantity <= self::EPSILON || $quantity > (float) $balance->quantity + self::EPSILON) {
            throw new RuntimeException('Invalid quantity for stock-condition change.');
        }

        DB::transaction(function () use ($balance, $condition, $quantity): void {
            $source = InventoryLocationBalance::query()
                ->lockForUpdate()
                ->findOrFail($balance->id);

            if ($quantity > (float) $source->quantity + self::EPSILON) {
                throw new RuntimeException('Warehouse stock changed before the condition update was posted.');
            }

            $source->quantity = max((float) $source->quantity - $quantity, 0);
            $source->save();

            $target = InventoryLocationBalance::query()
                ->where('purchase_item_id', $source->purchase_item_id)
                ->where('warehouse_location_id', $source->warehouse_location_id)
                ->where('condition_status', $condition)
                ->lockForUpdate()
                ->first();

            if (! $target) {
                $target = InventoryLocationBalance::query()->create([
                    'purchase_item_id' => $source->purchase_item_id,
                    'warehouse_location_id' => $source->warehouse_location_id,
                    'condition_status' => $condition,
                    'quantity' => 0,
                    'unit' => $source->unit,
                ]);
            }

            $target->quantity = (float) $target->quantity + $quantity;
            $target->save();

            if ((float) $source->quantity <= self::EPSILON) {
                $source->delete();
            }
        });
    }

    public function approveTransfer(InventoryTransfer $transfer, int $userId): InventoryTransfer
    {
        return DB::transaction(function () use ($transfer, $userId) {
            $transfer = InventoryTransfer::query()->lockForUpdate()->findOrFail($transfer->id);

            if ($transfer->status !== 'draft') {
                throw new RuntimeException('Only draft transfers can be approved.');
            }

            if ((int) $transfer->requested_by === $userId) {
                throw new RuntimeException('The requester cannot approve their own stock transfer.');
            }

            $transfer->status = 'approved';
            $transfer->approved_by = $userId;
            $transfer->approved_at = now();
            $transfer->save();

            return $transfer->fresh();
        });
    }

    public function completeTransfer(InventoryTransfer $transfer, int $userId): InventoryTransfer
    {
        return DB::transaction(function () use ($transfer, $userId) {
            $transfer = InventoryTransfer::query()
                ->with('items.purchaseItem')
                ->lockForUpdate()
                ->findOrFail($transfer->id);

            if ($transfer->status !== 'approved') {
                throw new RuntimeException('Only approved stock transfers can be completed.');
            }

            if ((int) $transfer->from_location_id === (int) $transfer->to_location_id) {
                throw new RuntimeException('Source and destination warehouse locations must be different.');
            }

            foreach ($transfer->items as $item) {
                $batch = PurchaseItem::query()
                    ->lockForUpdate()
                    ->findOrFail($item->purchase_item_id);

                $this->ensureBatch($batch);

                $remaining = (float) $item->quantity;
                $sourceRows = InventoryLocationBalance::query()
                    ->where('purchase_item_id', $batch->id)
                    ->where('warehouse_location_id', $transfer->from_location_id)
                    ->where('condition_status', 'available')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                foreach ($sourceRows as $source) {
                    if ($remaining <= self::EPSILON) {
                        break;
                    }

                    $moved = min((float) $source->quantity, $remaining);
                    $source->quantity = max((float) $source->quantity - $moved, 0);
                    $source->save();

                    $target = InventoryLocationBalance::query()
                        ->where('purchase_item_id', $batch->id)
                        ->where('warehouse_location_id', $transfer->to_location_id)
                        ->where('condition_status', 'available')
                        ->lockForUpdate()
                        ->first();

                    if (! $target) {
                        $target = InventoryLocationBalance::query()->create([
                            'purchase_item_id' => $batch->id,
                            'warehouse_location_id' => $transfer->to_location_id,
                            'condition_status' => 'available',
                            'quantity' => 0,
                            'unit' => $item->unit,
                        ]);
                    }

                    $target->quantity = (float) $target->quantity + $moved;
                    $target->save();

                    $remaining -= $moved;
                }

                if ($remaining > self::EPSILON) {
                    throw new RuntimeException(sprintf(
                        'Insufficient available stock in the source location for purchase batch #%d.',
                        $batch->id
                    ));
                }
            }

            InventoryLocationBalance::query()
                ->where('quantity', '<=', self::EPSILON)
                ->delete();

            $transfer->status = 'completed';
            $transfer->completed_by = $userId;
            $transfer->completed_at = now();
            $transfer->save();

            return $transfer->fresh();
        });
    }

    public function nextTransferNumber(): string
    {
        $prefix = 'TRF-'.now()->format('Ym').'-';
        $last = InventoryTransfer::query()
            ->withoutGlobalScope('business_unit')
            ->where('transfer_no', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('transfer_no');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
