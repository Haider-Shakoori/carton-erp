<?php

namespace App\Services;

use App\Models\InventoryLocationBalance;
use App\Models\InventoryMovement;
use App\Models\ProductionMaterialConsumption;
use App\Models\PurchaseItem;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WarehouseInventoryService
{
    private const EPSILON = 0.000001;

    public function defaultWarehouse(): Warehouse
    {
        $warehouse = Warehouse::query()->where('is_default', true)->where('is_active', true)->first();

        if ($warehouse) {
            return $warehouse;
        }

        return Warehouse::query()->firstOrCreate(
            ['code' => 'MAIN'],
            ['name' => 'Main Warehouse', 'is_default' => true, 'is_active' => true]
        );
    }

    public function ensureBatchBalance(PurchaseItem $batch): void
    {
        if (InventoryLocationBalance::query()->where('purchase_item_id', $batch->id)->exists()) {
            return;
        }

        $warehouse = $this->defaultWarehouse();

        InventoryLocationBalance::create([
            'purchase_item_id' => $batch->id,
            'product_id' => $batch->product_id,
            'warehouse_id' => $warehouse->id,
            'warehouse_location_id' => null,
            'quantity' => max((float) ($batch->qty_available ?? 0), 0),
            'quantity_kg' => $batch->isRollBatch()
                ? max((float) ($batch->qty_kg_available ?? 0), 0)
                : 0,
            'unit' => $batch->isRollBatch() ? 'roll' : ($batch->unit ?: 'unit'),
        ]);
    }

    public function consumeForProduction(
        ProductionMaterialConsumption $consumption,
        PurchaseItem $batch
    ): void {
        $this->ensureBatchBalance($batch);

        DB::transaction(function () use ($consumption, $batch): void {
            $remaining = (float) $consumption->actual_quantity;

            $balances = InventoryLocationBalance::query()
                ->where('purchase_item_id', $batch->id)
                ->orderByDesc('warehouse_id', $this->defaultWarehouse()->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $available = $batch->isRollBatch()
                ? (float) $balances->sum('quantity_kg')
                : (float) $balances->sum('quantity');

            if ($available + self::EPSILON < $remaining) {
                throw new RuntimeException(sprintf(
                    'Warehouse allocation is insufficient for purchase batch #%d. Required %.6f, allocated %.6f.',
                    $batch->id,
                    $remaining,
                    $available
                ));
            }

            foreach ($balances as $balance) {
                if ($remaining <= self::EPSILON) {
                    break;
                }

                $availableHere = $batch->isRollBatch()
                    ? (float) $balance->quantity_kg
                    : (float) $balance->quantity;

                $take = min($availableHere, $remaining);
                if ($take <= self::EPSILON) {
                    continue;
                }

                if ($batch->isRollBatch()) {
                    $kgPerRoll = max((float) $batch->kg_per_roll, self::EPSILON);
                    $balance->quantity_kg = max((float) $balance->quantity_kg - $take, 0);
                    $balance->quantity = max((float) $balance->quantity - ($take / $kgPerRoll), 0);
                    $movementQty = $take / $kgPerRoll;
                    $movementKg = $take;
                } else {
                    $balance->quantity = max((float) $balance->quantity - $take, 0);
                    $movementQty = $take;
                    $movementKg = 0;
                }

                $balance->save();

                InventoryMovement::create([
                    'purchase_item_id' => $batch->id,
                    'product_id' => $batch->product_id,
                    'warehouse_id' => $balance->warehouse_id,
                    'warehouse_location_id' => $balance->warehouse_location_id,
                    'movement_type' => 'production_consumption',
                    'direction' => 'out',
                    'quantity' => $movementQty,
                    'quantity_kg' => $movementKg,
                    'unit' => $batch->isRollBatch() ? 'kg' : ($consumption->unit ?: $batch->unit),
                    'unit_cost_usd' => (float) $consumption->cost_per_unit_usd,
                    'reference_type' => 'production_material_consumption',
                    'reference_id' => $consumption->id,
                    'actor_id' => Auth::id(),
                    'occurred_at' => now(),
                    'metadata' => [
                        'production_order_id' => $consumption->production_order_id,
                    ],
                ]);

                $remaining -= $take;
            }

            if ($remaining > self::EPSILON) {
                throw new RuntimeException('Warehouse location deduction did not fully reconcile.');
            }
        });
    }

    public function restoreForProduction(
        ProductionMaterialConsumption $consumption,
        PurchaseItem $batch
    ): void {
        DB::transaction(function () use ($consumption, $batch): void {
            $movements = InventoryMovement::query()
                ->where('reference_type', 'production_material_consumption')
                ->where('reference_id', $consumption->id)
                ->where('movement_type', 'production_consumption')
                ->where('direction', 'out')
                ->whereNotIn('id', function ($query) {
                    $query->select('reversal_of_id')
                        ->from('inventory_movements')
                        ->whereNotNull('reversal_of_id');
                })
                ->lockForUpdate()
                ->get();

            foreach ($movements as $movement) {
                $balance = InventoryLocationBalance::query()
                    ->where('purchase_item_id', $batch->id)
                    ->where('warehouse_id', $movement->warehouse_id)
                    ->where(function ($query) use ($movement) {
                        if ($movement->warehouse_location_id === null) {
                            $query->whereNull('warehouse_location_id');
                        } else {
                            $query->where('warehouse_location_id', $movement->warehouse_location_id);
                        }
                    })
                    ->lockForUpdate()
                    ->first();

                if (! $balance) {
                    $balance = InventoryLocationBalance::create([
                        'purchase_item_id' => $batch->id,
                        'product_id' => $batch->product_id,
                        'warehouse_id' => $movement->warehouse_id,
                        'warehouse_location_id' => $movement->warehouse_location_id,
                        'quantity' => 0,
                        'quantity_kg' => 0,
                        'unit' => $batch->isRollBatch() ? 'roll' : ($batch->unit ?: 'unit'),
                    ]);
                }

                $balance->quantity = (float) $balance->quantity + (float) $movement->quantity;
                $balance->quantity_kg = (float) $balance->quantity_kg + (float) $movement->quantity_kg;
                $balance->save();

                InventoryMovement::create([
                    'purchase_item_id' => $batch->id,
                    'product_id' => $batch->product_id,
                    'warehouse_id' => $movement->warehouse_id,
                    'warehouse_location_id' => $movement->warehouse_location_id,
                    'movement_type' => 'production_reversal',
                    'direction' => 'in',
                    'quantity' => $movement->quantity,
                    'quantity_kg' => $movement->quantity_kg,
                    'unit' => $movement->unit,
                    'unit_cost_usd' => $movement->unit_cost_usd,
                    'reference_type' => $movement->reference_type,
                    'reference_id' => $movement->reference_id,
                    'reversal_of_id' => $movement->id,
                    'actor_id' => Auth::id(),
                    'occurred_at' => now(),
                    'metadata' => ['reversal' => true],
                ]);
            }
        });
    }

    public function postTransfer(StockTransfer $transfer): StockTransfer
    {
        return DB::transaction(function () use ($transfer): StockTransfer {
            $transfer = StockTransfer::query()->with('items')->lockForUpdate()->findOrFail($transfer->id);

            if ($transfer->status !== 'draft') {
                throw new RuntimeException('Only a draft stock transfer can be posted.');
            }

            if ($transfer->from_warehouse_id === $transfer->to_warehouse_id) {
                throw new RuntimeException('Source and destination warehouse must be different.');
            }

            if ($transfer->items->isEmpty()) {
                throw new RuntimeException('A stock transfer must contain at least one item.');
            }

            foreach ($transfer->items as $item) {
                $batch = PurchaseItem::query()->lockForUpdate()->findOrFail($item->purchase_item_id);
                $this->ensureBatchBalance($batch);

                $sourceQuery = InventoryLocationBalance::query()
                    ->where('purchase_item_id', $batch->id)
                    ->where('warehouse_id', $transfer->from_warehouse_id);

                if ($item->from_location_id) {
                    $sourceQuery->where('warehouse_location_id', $item->from_location_id);
                }

                $sources = $sourceQuery->orderBy('id')->lockForUpdate()->get();
                $required = $batch->isRollBatch()
                    ? (float) $item->quantity_kg
                    : (float) $item->quantity;
                $available = $batch->isRollBatch()
                    ? (float) $sources->sum('quantity_kg')
                    : (float) $sources->sum('quantity');

                if ($required <= self::EPSILON || $available + self::EPSILON < $required) {
                    throw new RuntimeException(sprintf(
                        'Insufficient allocated stock for transfer item #%d.',
                        $item->id
                    ));
                }

                $remaining = $required;

                foreach ($sources as $source) {
                    if ($remaining <= self::EPSILON) {
                        break;
                    }

                    $sourceAvailable = $batch->isRollBatch()
                        ? (float) $source->quantity_kg
                        : (float) $source->quantity;
                    $move = min($sourceAvailable, $remaining);

                    if ($batch->isRollBatch()) {
                        $kgPerRoll = max((float) $batch->kg_per_roll, self::EPSILON);
                        $native = $move / $kgPerRoll;
                        $source->quantity_kg = max((float) $source->quantity_kg - $move, 0);
                        $source->quantity = max((float) $source->quantity - $native, 0);
                    } else {
                        $native = $move;
                        $source->quantity = max((float) $source->quantity - $move, 0);
                    }
                    $source->save();

                    $destination = InventoryLocationBalance::query()
                        ->where('purchase_item_id', $batch->id)
                        ->where('warehouse_id', $transfer->to_warehouse_id)
                        ->where(function ($query) use ($item) {
                            if ($item->to_location_id) {
                                $query->where('warehouse_location_id', $item->to_location_id);
                            } else {
                                $query->whereNull('warehouse_location_id');
                            }
                        })
                        ->lockForUpdate()
                        ->first();

                    if (! $destination) {
                        $destination = InventoryLocationBalance::create([
                            'purchase_item_id' => $batch->id,
                            'product_id' => $batch->product_id,
                            'warehouse_id' => $transfer->to_warehouse_id,
                            'warehouse_location_id' => $item->to_location_id,
                            'quantity' => 0,
                            'quantity_kg' => 0,
                            'unit' => $batch->isRollBatch() ? 'roll' : ($batch->unit ?: 'unit'),
                        ]);
                    }

                    $destination->quantity = (float) $destination->quantity + $native;
                    if ($batch->isRollBatch()) {
                        $destination->quantity_kg = (float) $destination->quantity_kg + $move;
                    }
                    $destination->save();

                    $base = [
                        'purchase_item_id' => $batch->id,
                        'product_id' => $batch->product_id,
                        'movement_type' => 'warehouse_transfer',
                        'unit' => $batch->isRollBatch() ? 'kg' : ($batch->unit ?: 'unit'),
                        'unit_cost_usd' => $batch->landedCostPerInventoryUnitUsd(),
                        'reference_type' => 'stock_transfer',
                        'reference_id' => $transfer->id,
                        'actor_id' => Auth::id(),
                        'occurred_at' => now(),
                    ];

                    InventoryMovement::create($base + [
                        'warehouse_id' => $source->warehouse_id,
                        'warehouse_location_id' => $source->warehouse_location_id,
                        'direction' => 'out',
                        'quantity' => $native,
                        'quantity_kg' => $batch->isRollBatch() ? $move : 0,
                    ]);

                    InventoryMovement::create($base + [
                        'warehouse_id' => $destination->warehouse_id,
                        'warehouse_location_id' => $destination->warehouse_location_id,
                        'direction' => 'in',
                        'quantity' => $native,
                        'quantity_kg' => $batch->isRollBatch() ? $move : 0,
                    ]);

                    $remaining -= $move;
                }
            }

            $transfer->status = 'posted';
            $transfer->posted_by = Auth::id();
            $transfer->posted_at = now();
            $transfer->save();

            return $transfer->refresh();
        });
    }
}
