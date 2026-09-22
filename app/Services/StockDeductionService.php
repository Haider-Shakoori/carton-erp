<?php

namespace App\Services;

use App\Models\ProductionMaterialConsumption;
use App\Models\PurchaseItem;
use App\Models\PurchaseItemReel;
use App\Models\Product;
use App\Models\Currency;
use App\Models\Sale;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class StockDeductionService
{
    private const EPSILON = 0.000001;

    /**
     * Return the total available quantity for one raw material.
     * ✅ SUMS ALL BATCHES for the same product_id
     */
    public function availableQuantity(int $materialId): float
    {
        // ✅ FIX: Sum ALL batches for this product_id
        $total = PurchaseItem::query()
            ->where('product_id', $materialId)
            ->where('qty_available', '>', 0)
            ->whereHas('purchase', function ($query): void {
                $query->where('status', 'arrived');
            })
            ->sum('qty_available');

        Log::info('availableQuantity result', [
            'material_id' => $materialId,
            'total_available' => $total,
            'batches_count' => PurchaseItem::query()
                ->where('product_id', $materialId)
                ->where('qty_available', '>', 0)
                ->whereHas('purchase', function ($query): void {
                    $query->where('status', 'arrived');
                })
                ->count(),
        ]);

        return (float) $total;
    }

    /**
     * Available production quantity in the unit inventory is actually consumed
     * in: kilograms for roll batches, native units otherwise.
     */
    public function availableProductionQuantity(int $materialId): float
    {
        $batches = PurchaseItem::query()
            ->where('product_id', $materialId)
            ->whereHas('purchase', fn ($q) => $q->where('status', 'arrived'))
            ->get();

        if ($batches->isEmpty()) {
            return 0.0;
        }

        $locationInventory = app(InventoryLocationService::class);

        return (float) $batches->sum(
            fn (PurchaseItem $batch) => $locationInventory->availableForBatch($batch)
        );
    }

    /**
     * Check whether all requested materials are currently available.
     * ✅ COMPLETE FIX: Sums ALL batches for each product_id
     */
    public function checkAvailability(array|Collection $materials): array
    {
        $normalised = $this->normaliseMaterials($materials);
        $materialIds = collect($normalised)->pluck('material_id')->unique()->values();

        Log::info('🔍 Stock availability check - START', [
            'material_ids' => $materialIds->toArray(),
            'materials' => $normalised,
        ]);

        // ✅ FIX: Get ALL batches for these materials with DETAILED info
        $allBatches = PurchaseItem::query()
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->whereIn('purchase_items.product_id', $materialIds)
            ->where('purchases.status', 'arrived')
            ->select(
                'purchase_items.id',
                'purchase_items.product_id',
                'purchase_items.qty',
                'purchase_items.qty_available',
                'purchase_items.qty_used',
                'purchase_items.qty_sold',
                'purchase_items.qty_wasted',
                'purchase_items.unit',
                'purchase_items.kg_per_roll',
                'purchase_items.qty_kg_available',
                'purchases.purchase_no',
                'purchases.purchase_date'
            )
            ->orderBy('purchase_items.id')
            ->get();

        Log::info('🔍 ALL batches found', [
            'total_batches' => $allBatches->count(),
            'batches' => $allBatches->map(function($batch) {
                return [
                    'id' => $batch->id,
                    'product_id' => $batch->product_id,
                    'unit' => $batch->unit,
                    'qty' => (float) $batch->qty,
                    'qty_available' => (float) $batch->qty_available,
                    'qty_kg_available' => (float) $batch->qty_kg_available,
                    'qty_used' => (float) ($batch->qty_used ?? 0),
                    'qty_sold' => (float) ($batch->qty_sold ?? 0),
                    'purchase_no' => $batch->purchase_no,
                ];
            })->toArray(),
        ]);

        // Warehouse condition is part of usable availability. Blocked and
        // damaged stock remains physically on hand but is never production-eligible.
        $locationInventory = app(InventoryLocationService::class);
        $batchModels = PurchaseItem::query()
            ->whereIn('id', $allBatches->pluck('id'))
            ->get()
            ->keyBy('id');

        $availableByMaterial = $allBatches
            ->groupBy('product_id')
            ->map(function ($batches) use ($locationInventory, $batchModels) {
                $first = $batches->first();
                $isRoll = $first && strtolower((string) $first->unit) === 'roll';

                $details = $batches->map(function ($batch) use ($locationInventory, $batchModels, $isRoll) {
                    $model = $batchModels->get($batch->id);
                    $available = $model
                        ? $locationInventory->availableForBatch($model)
                        : ($isRoll
                            ? (float) $batch->qty_kg_available
                            : (float) $batch->qty_available);

                    return [
                        'id' => $batch->id,
                        'qty_available' => $available,
                        'purchase_no' => $batch->purchase_no,
                    ];
                });

                return [
                    'is_roll' => $isRoll,
                    'total_available' => (float) $details->sum('qty_available'),
                    'batches' => $details->toArray(),
                ];
            })
            ->toArray();

        Log::info('🔍 GROUPED by product_id with SUM', [
            'available_by_material' => $availableByMaterial,
        ]);

        $rows = [];
        $hasShortage = false;

        foreach ($normalised as $material) {
            $materialId = $material['material_id'];
            $required = $material['quantity'];

            // ✅ Get the total available for this product_id
            $available = (float) ($availableByMaterial[$materialId]['total_available'] ?? 0);
            $shortage = max($required - $available, 0);

            if ($shortage > self::EPSILON) {
                $hasShortage = true;
            }

            $materialName = $this->getMaterialName($materialId);
            $batchDetails = $availableByMaterial[$materialId]['batches'] ?? [];

            Log::info('📦 Stock check for material', [
                'material_id' => $materialId,
                'material_name' => $materialName,
                'required_quantity' => $required,
                'available_quantity' => $available,
                'shortage_quantity' => $shortage,
                'unit' => $material['unit'] ?? 'unit',
                'available' => $shortage <= self::EPSILON,
                'batches_found' => count($batchDetails),
                'batch_details' => $batchDetails,
            ]);

            $rows[] = [
                'material_id' => $materialId,
                'material_name' => $materialName,
                'required_quantity' => $required,
                'available_quantity' => $available,
                'shortage_quantity' => $shortage,
                'unit' => $material['unit'] ?? 'unit',
                'available' => $shortage <= self::EPSILON,
                'batches' => $batchDetails,
            ];
        }

        Log::info('✅ Stock availability check - RESULT', [
            'has_shortage' => $hasShortage,
            'rows' => $rows,
        ]);

        return [
            'available' => !$hasShortage,
            'materials' => $rows,
        ];
    }

    /**
     * Deduct several materials for a production order using FIFO batches.
     */
    public function deductMaterials(
        int $productionOrderId,
        ?int $saleId,
        array|Collection $materials
    ): Collection {
        $normalised = $this->normaliseMaterials($materials);

        Log::info('StockDeductionService::deductMaterials - START', [
            'production_order_id' => $productionOrderId,
            'sale_id' => $saleId,
            'materials' => $normalised,
        ]);

        return DB::transaction(function () use (
            $productionOrderId,
            $saleId,
            $normalised
        ): Collection {
            $availability = $this->checkAvailability($normalised);

            if (!$availability['available']) {
                $shortages = collect($availability['materials'])
                    ->filter(static fn (array $row): bool => !$row['available'])
                    ->map(static fn (array $row): string => sprintf(
                        '%s: Need %.4f %s, but only %.4f is available (Batches: %s)',
                        $row['material_name'] ?? 'Material #' . $row['material_id'],
                        $row['required_quantity'],
                        $row['unit'] ?: 'unit',
                        $row['available_quantity'],
                        collect($row['batches'] ?? [])->map(fn($b) => "Batch #{$b['id']}: {$b['qty_available']}")->implode(', ')
                    ))
                    ->implode('; ');

                Log::error('❌ Stock deduction failed - INSUFFICIENT STOCK', [
                    'shortages' => $shortages,
                    'production_order_id' => $productionOrderId,
                ]);

                throw new RuntimeException('Insufficient raw-material stock. ' . $shortages);
            }

            $consumptions = collect();

            foreach ($normalised as $material) {
                $records = $this->deductMaterialWithinTransaction(
                    productionOrderId: $productionOrderId,
                    saleId: $saleId,
                    saleItemId: $material['sale_item_id'] ?? null,
                    materialId: $material['material_id'],
                    actualQuantity: $material['quantity'],
                    plannedQuantity: $material['planned_quantity'] ?? $material['quantity'],
                    wastageQuantity: $material['wastage_quantity'] ?? 0,
                    unit: $material['unit'] ?? null
                );

                $consumptions = $consumptions->concat($records);
            }

            Log::info('✅ Stock deduction completed', [
                'production_order_id' => $productionOrderId,
                'total_consumptions' => $consumptions->count(),
                'total_cost_usd' => $consumptions->sum('total_cost_usd'),
            ]);

            return $consumptions->values();
        });
    }

    /**
     * Replace this production order's provisional allocation for one roll
     * material with an explicit operator reel declaration.
     *
     * The whole operation is transactional: existing provisional consumption is
     * restored first, then the declared reels are consumed exactly. Any invalid
     * reel, blocked status, shortage, or quantity mismatch rolls everything back.
     */
    public function replaceProductionMaterialWithReelSelections(
        int $productionOrderId,
        ?int $saleId,
        ?int $saleItemId,
        int $materialId,
        float $actualQuantity,
        float $plannedQuantity,
        array $selections
    ): Collection {
        if ($actualQuantity <= self::EPSILON) {
            throw new RuntimeException(
                'Operator reel selection requires a positive actual material quantity.'
            );
        }

        if ($selections === []) {
            throw new RuntimeException(
                'At least one physical reel must be declared when reel selection is enabled.'
            );
        }

        return DB::transaction(function () use (
            $productionOrderId,
            $saleId,
            $saleItemId,
            $materialId,
            $actualQuantity,
            $plannedQuantity,
            $selections
        ): Collection {
            $current = ProductionMaterialConsumption::query()
                ->where('production_order_id', $productionOrderId)
                ->where('material_id', $materialId)
                ->where('actual_quantity', '>', 0)
                ->lockForUpdate()
                ->get();

            $currentActual = (float) $current->sum('actual_quantity');
            if ($currentActual > self::EPSILON) {
                $this->restoreProductionMaterialQuantity(
                    $productionOrderId,
                    $materialId,
                    $currentActual
                );
            }

            $rows = collect($selections)
                ->map(function ($row): array {
                    if (! is_array($row)) {
                        throw new RuntimeException(
                            'Every reel selection must be an array.'
                        );
                    }

                    $reelId = (int) ($row['reel_id'] ?? 0);
                    $consumed = array_key_exists('consumed_kg', $row)
                        && $row['consumed_kg'] !== null
                        && $row['consumed_kg'] !== ''
                            ? (float) $row['consumed_kg']
                            : null;
                    $final = array_key_exists('final_remaining_kg', $row)
                        && $row['final_remaining_kg'] !== null
                        && $row['final_remaining_kg'] !== ''
                            ? (float) $row['final_remaining_kg']
                            : null;

                    if ($reelId <= 0) {
                        throw new RuntimeException(
                            'Every reel declaration requires a valid reel.'
                        );
                    }

                    if ($consumed === null && $final === null) {
                        throw new RuntimeException(sprintf(
                            'Reel #%d needs consumed kg or a final measured remainder.',
                            $reelId
                        ));
                    }

                    if ($consumed !== null && $consumed < -self::EPSILON) {
                        throw new RuntimeException(
                            'Selected reel consumption cannot be negative.'
                        );
                    }

                    if ($final !== null && $final < -self::EPSILON) {
                        throw new RuntimeException(
                            'Final measured reel remainder cannot be negative.'
                        );
                    }

                    return [
                        'reel_id' => $reelId,
                        'consumed_kg' => $consumed,
                        'final_remaining_kg' => $final,
                        'note' => isset($row['note'])
                            ? trim((string) $row['note'])
                            : null,
                    ];
                })
                ->values();

            if ($rows->pluck('reel_id')->unique()->count() !== $rows->count()) {
                throw new RuntimeException(
                    'The same physical reel cannot be declared more than once.'
                );
            }

            $reels = PurchaseItemReel::query()
                ->whereIn('id', $rows->pluck('reel_id')->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($reels->count() !== $rows->count()) {
                throw new RuntimeException(
                    'One or more selected physical reels no longer exist.'
                );
            }

            $batchIds = $reels->pluck('purchase_item_id')->unique()->values();
            $batches = PurchaseItem::query()
                ->with('purchase')
                ->whereIn('id', $batchIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $prepared = collect();

            foreach ($rows as $row) {
                /** @var PurchaseItemReel $reel */
                $reel = $reels->get($row['reel_id']);
                /** @var PurchaseItem|null $batch */
                $batch = $batches->get($reel->purchase_item_id);

                if (! $batch || (int) $batch->product_id !== $materialId) {
                    throw new RuntimeException(sprintf(
                        'Reel %s does not belong to material #%d.',
                        $reel->reel_code,
                        $materialId
                    ));
                }

                if (
                    ! $batch->isRollBatch()
                    || strtolower((string) ($batch->purchase?->status ?? '')) !== 'arrived'
                ) {
                    throw new RuntimeException(sprintf(
                        'Reel %s is not in an arrived roll inventory batch.',
                        $reel->reel_code
                    ));
                }

                if (! $reel->isProductionEligible()) {
                    throw new RuntimeException(sprintf(
                        'Reel %s is not production eligible because its status is %s.',
                        $reel->reel_code,
                        $reel->status
                    ));
                }

                $before = (float) $reel->system_remaining_weight_kg;
                $consumed = $row['consumed_kg'];
                $final = $row['final_remaining_kg'];

                if ($consumed === null) {
                    if ($final > $before + 0.0001) {
                        throw new RuntimeException(sprintf(
                            'Final remainder %.4f kg for reel %s exceeds its pre-run system weight %.4f kg, so consumed kg cannot be inferred.',
                            $final,
                            $reel->reel_code,
                            $before
                        ));
                    }

                    $consumed = max($before - $final, 0);
                }

                if ($consumed <= self::EPSILON) {
                    throw new RuntimeException(sprintf(
                        'Declared consumption for reel %s must be greater than zero.',
                        $reel->reel_code
                    ));
                }

                if ($consumed > $before + self::EPSILON) {
                    throw new RuntimeException(sprintf(
                        'Reel %s has %.4f kg available for this run but %.4f kg was declared consumed.',
                        $reel->reel_code,
                        $before,
                        $consumed
                    ));
                }

                $prepared->push([
                    'reel' => $reel,
                    'batch' => $batch,
                    'quantity_kg' => $consumed,
                    'final_remaining_kg' => $final,
                    'note' => $row['note'],
                ]);
            }

            $declaredTotal = (float) $prepared->sum('quantity_kg');
            if (abs($declaredTotal - $actualQuantity) > 0.0001) {
                throw new RuntimeException(sprintf(
                    'Selected reel consumption totals %.4f kg but actual material consumption is %.4f kg.',
                    $declaredTotal,
                    $actualQuantity
                ));
            }

            $records = collect();
            $plannedRemaining = max($plannedQuantity, 0);

            foreach ($prepared as $selection) {
                /** @var PurchaseItem $batch */
                $batch = $selection['batch'];
                /** @var PurchaseItemReel $reel */
                $reel = $selection['reel'];
                $quantityKg = (float) $selection['quantity_kg'];

                if ($batch->availableKg() + self::EPSILON < $quantityKg) {
                    throw new RuntimeException(sprintf(
                        'Batch %s contains only %.4f kg but reel %s declares %.4f kg consumed.',
                        $batch->batch_no ?: $batch->id,
                        $batch->availableKg(),
                        $reel->reel_code,
                        $quantityKg
                    ));
                }

                $kgPerRoll = max((float) $batch->kg_per_roll, self::EPSILON);
                $consumedRolls = $quantityKg / $kgPerRoll;

                $batch->qty_kg_available = max(
                    (float) $batch->qty_kg_available - $quantityKg,
                    0
                );
                $batch->qty_kg_used = (float) ($batch->qty_kg_used ?? 0)
                    + $quantityKg;
                $batch->qty_available = max(
                    (float) $batch->qty_available - $consumedRolls,
                    0
                );
                $batch->qty_used = (float) ($batch->qty_used ?? 0)
                    + $consumedRolls;
                $batch->save();
                $locationInventory->syncAfterBatchChange($batch);

                $usdUnitCost = $batch->landedCostPerKg();
                $exchangeRate = $this->resolveConsumptionExchangeRate(
                    $saleId,
                    $batch
                );
                $afnUnitCost = $usdUnitCost * $exchangeRate;

                $plannedForReel = min(
                    $plannedRemaining,
                    $quantityKg
                );
                $plannedRemaining = max(
                    $plannedRemaining - $plannedForReel,
                    0
                );

                $record = ProductionMaterialConsumption::create([
                    'production_order_id' => $productionOrderId,
                    'sale_id' => $saleId,
                    'sale_item_id' => $saleItemId,
                    'material_id' => $materialId,
                    'purchase_item_id' => $batch->id,
                    'planned_quantity' => $plannedForReel,
                    'actual_quantity' => $quantityKg,
                    'wastage_quantity' => 0,
                    'unit' => 'kg',
                    'cost_per_unit_usd' => $usdUnitCost,
                    'cost_per_unit_afn' => $afnUnitCost,
                    'total_cost_usd' => $quantityKg * $usdUnitCost,
                    'total_cost_afn' => $quantityKg * $afnUnitCost,
                    'wastage_cost_usd' => 0,
                    'wastage_cost_afn' => 0,
                    'consumed_at' => now(),
                    'created_by' => Auth::id(),
                ]);

                app(ReelInventoryService::class)
                    ->consumeSelectedReelForConsumption(
                        $record,
                        $reel,
                        $quantityKg,
                        $selection['final_remaining_kg'],
                        $selection['note']
                    );

                $records->push($record);
            }

            Log::info('Production material allocation replaced by operator reel declaration', [
                'production_order_id' => $productionOrderId,
                'material_id' => $materialId,
                'actual_quantity' => $actualQuantity,
                'reel_ids' => $prepared->pluck('reel.id')->all(),
                'consumption_ids' => $records->pluck('id')->all(),
            ]);

            return $records;
        });
    }

    /**
     * Deduct one material using FIFO purchase batches.
     * ✅ Uses qty_available from each batch
     */
    private function deductMaterialWithinTransaction(
        int $productionOrderId,
        ?int $saleId,
        ?int $saleItemId,
        int $materialId,
        float $actualQuantity,
        float $plannedQuantity,
        float $wastageQuantity,
        ?string $unit
    ): Collection {
        if ($actualQuantity <= self::EPSILON) {
            throw new RuntimeException('Consumed quantity must be greater than zero.');
        }

        if ($plannedQuantity < 0 || $wastageQuantity < 0) {
            throw new RuntimeException('Planned quantity and wastage quantity cannot be negative.');
        }

        $remaining = $actualQuantity;
        $records = collect();

        // ✅ FIFO: Get ALL batches with qty_available > 0
        $batches = PurchaseItem::query()
            ->where('product_id', $materialId)
            ->where('qty_available', '>', 0)
            ->whereHas('purchase', function ($query): void {
                $query->where('status', 'arrived');
            })
            ->with('purchase')
            ->orderByRaw('COALESCE(
                (SELECT purchase_date FROM purchases WHERE purchases.id = purchase_items.purchase_id),
                purchase_items.created_at
            ) ASC')
            ->orderBy('purchase_items.id')
            ->lockForUpdate()
            ->get();

        Log::info('📦 Deducting material - batches found', [
            'material_id' => $materialId,
            'required_quantity' => $actualQuantity,
            'batches_found' => $batches->count(),
            'total_available' => $batches->sum('qty_available'),
            'batch_details' => $batches->map(function($batch) {
                return [
                    'id' => $batch->id,
                    'qty_available' => (float) $batch->qty_available,
                    'purchase_no' => $batch->purchase->purchase_no ?? 'N/A',
                ];
            })->toArray(),
        ]);

        // Availability is the warehouse-eligible quantity: blocked/damaged
        // balances remain physically on hand but cannot be consumed.
        $isRequirementRoll = $batches->contains(fn (PurchaseItem $b) => $b->isRollBatch());
        $locationInventory = app(InventoryLocationService::class);
        $totalAvailable = (float) $batches->sum(
            fn (PurchaseItem $batch) => $locationInventory->availableForBatch($batch)
        );

        if ($totalAvailable + self::EPSILON < $actualQuantity) {
            Log::error('❌ Insufficient stock', [
                'material_id' => $materialId,
                'required' => $actualQuantity,
                'available' => $totalAvailable,
                'unit' => $isRequirementRoll ? 'kg' : 'unit',
            ]);

            throw new RuntimeException(sprintf(
                'Insufficient stock for material #%d. Required %.4f %s, available %.4f %s.',
                $materialId,
                $actualQuantity,
                $isRequirementRoll ? 'kg' : 'unit',
                $totalAvailable,
                $isRequirementRoll ? 'kg' : 'unit'
            ));
        }

        foreach ($batches as $batch) {
            if ($remaining <= self::EPSILON) {
                break;
            }

            $isRoll = $batch->isRollBatch();

            // For roll-based paper batches inventory is tracked and consumed in kg.
            // The incoming $remaining/$actualQuantity is the physical weight (kg)
            // required by the BOM. For every other unit we keep the legacy native
            // quantity behaviour untouched.
            if ($isRoll) {
                $warehouseAvailable = $locationInventory->availableForBatch($batch);
                $consumedKg = min(
                    (float) $batch->qty_kg_available,
                    $warehouseAvailable,
                    $remaining
                );
                $consumedKg = max($consumedKg, 0);

                if ($consumedKg <= self::EPSILON) {
                    continue;
                }

                $kgPerRoll = (float) $batch->kg_per_roll;
                $consumedRolls = $consumedKg / $kgPerRoll;

                $batch->qty_kg_available = max((float) $batch->qty_kg_available - $consumedKg, 0);
                $batch->qty_kg_used = (float) ($batch->qty_kg_used ?? 0) + $consumedKg;
                $batch->qty_available = max((float) $batch->qty_available - $consumedRolls, 0);
                $batch->qty_used = (float) ($batch->qty_used ?? 0) + $consumedRolls;
                $batch->save();
                app(InventoryLocationService::class)->syncAfterBatchChange($batch);

                $usdUnitCost = $batch->landedCostPerKg();
                $exchangeRate = $this->resolveConsumptionExchangeRate($saleId, $batch);
                $afnUnitCost = $usdUnitCost * $exchangeRate;

                $quantityFromBatch = $consumedKg;
                $consumptionUnit = 'kg';

                Log::info('✅ Deducted roll batch (kg)', [
                    'purchase_item_id' => $batch->id,
                    'kg_per_roll' => $kgPerRoll,
                    'deducted_kg' => $consumedKg,
                    'deducted_rolls' => $consumedRolls,
                    'cost_per_kg_usd' => $usdUnitCost,
                    'new_qty_kg_available' => $batch->qty_kg_available,
                    'new_qty_available' => $batch->qty_available,
                ]);
            } else {
                $available = min(
                    (float) $batch->qty_available,
                    $locationInventory->availableForBatch($batch)
                );
                $quantityFromBatch = min($available, $remaining);

                if ($quantityFromBatch <= self::EPSILON) {
                    continue;
                }

                $usdUnitCost = $this->resolveUsdUnitCost($batch);
                $exchangeRate = $this->resolveConsumptionExchangeRate($saleId, $batch);
                $afnUnitCost = $usdUnitCost * $exchangeRate;

                // ✅ Update qty_available (reduce by consumed amount)
                $batch->qty_available = max($available - $quantityFromBatch, 0);

                // ✅ Update qty_used (track consumption)
                if (in_array('qty_used', $batch->getFillable())) {
                    $batch->qty_used = (float) ($batch->qty_used ?? 0) + $quantityFromBatch;
                }

                $batch->save();
                $locationInventory->syncAfterBatchChange($batch);

                Log::info('✅ Deducted from batch', [
                    'purchase_item_id' => $batch->id,
                    'deducted_quantity' => $quantityFromBatch,
                    'new_qty_available' => $batch->qty_available,
                    'qty_used' => $batch->qty_used ?? 0,
                ]);

                $consumptionUnit = $unit ?: ($batch->unit ?? null);
            }

            // Planned quantity and wastage are stored once across split batches.
            // For roll batches these are kg values; for native batches native units.
            $plannedForBatch = min(max($plannedQuantity, 0), $quantityFromBatch);
            $wastageForBatch = min(max($wastageQuantity, 0), $quantityFromBatch);
            $plannedQuantity = max($plannedQuantity - $plannedForBatch, 0);
            $wastageQuantity = max($wastageQuantity - $wastageForBatch, 0);

            $record = ProductionMaterialConsumption::create([
                'production_order_id' => $productionOrderId,
                'sale_id' => $saleId,
                'sale_item_id' => $saleItemId,
                'material_id' => $materialId,
                'purchase_item_id' => $batch->id,
                'planned_quantity' => $plannedForBatch,
                'actual_quantity' => $quantityFromBatch,
                'wastage_quantity' => $wastageForBatch,
                'unit' => $consumptionUnit,
                'cost_per_unit_usd' => $usdUnitCost,
                'cost_per_unit_afn' => $afnUnitCost,
                'total_cost_usd' => $quantityFromBatch * $usdUnitCost,
                'total_cost_afn' => $quantityFromBatch * $afnUnitCost,
                'wastage_cost_usd' => $wastageForBatch * $usdUnitCost,
                'wastage_cost_afn' => $wastageForBatch * $afnUnitCost,
                'consumed_at' => now(),
                'created_by' => Auth::id(),
            ]);

            if ($isRoll) {
                app(ReelInventoryService::class)
                    ->consumeForConsumption($record);
            }

            $records->push($record);
            $remaining -= $quantityFromBatch;
        }

        if ($remaining > self::EPSILON) {
            Log::error('❌ Stock deduction incomplete', [
                'material_id' => $materialId,
                'remaining' => $remaining,
            ]);

            throw new RuntimeException(sprintf(
                'Stock deduction for material #%d was incomplete. Remaining quantity: %.6f.',
                $materialId,
                $remaining
            ));
        }

        Log::info('✅ Production material stock deducted', [
            'production_order_id' => $productionOrderId,
            'sale_id' => $saleId,
            'sale_item_id' => $saleItemId,
            'material_id' => $materialId,
            'actual_quantity' => $actualQuantity,
            'batch_count' => $records->count(),
            'consumption_ids' => $records->pluck('id')->all(),
        ]);

        return $records;
    }

    private function resolveUsdUnitCost(PurchaseItem $batch): float
    {
        // Prefer the stored landed USD cost. Historical rows may have that field
        // unset while still carrying an allocated per-unit purchase expense.
        $cost = (float) ($batch->usd_cost_per_item ?? 0);

        if ($cost <= self::EPSILON) {
            $baseUnitCost = (float) ($batch->usd_unit_price ?? 0);
            $allocatedExpense = (float) ($batch->usd_expense_per_item ?? 0);
            $cost = $baseUnitCost + $allocatedExpense;
        }

        if ($cost <= self::EPSILON) {
            $cost = (float) ($batch->cost_per_unit ?? 0);
        }

        if ($cost <= self::EPSILON && (float) ($batch->qty ?? 0) > self::EPSILON) {
            $totalCostUsd = (float) ($batch->usd_total_cost ?? 0);
            if ($totalCostUsd <= self::EPSILON) {
                $totalCostUsd = (float) ($batch->usd_total ?? 0);
            }
            $cost = $totalCostUsd / (float) $batch->qty;
        }

        if ($cost < 0) {
            throw new RuntimeException("Invalid negative unit cost on purchase item #{$batch->id}.");
        }

        return $cost;
    }

    private function resolveExchangeRate(PurchaseItem $batch): float
    {
        $rate = (float) ($batch->rate ?? 0);

        if ($rate <= self::EPSILON && $batch->relationLoaded('purchase')) {
            $rate = (float) ($batch->purchase->exchange_rate ?? 0);
        }

        if ($rate <= self::EPSILON) {
            $rate = 1;
        }

        return $rate;
    }

    /**
     * Authoritative exchange rate for AFN cost fields on consumption records.
     *
     * Mirrors the display architecture used across the production module
     * (sale.exchange_rate ?? default currency rate): a sale-linked production
     * order converts USD costs with the linked Sale exchange rate, a standalone
     * order with the default currency rate. Only when neither is available the
     * purchase/rate fallback applies. Never injects USD and AFN differences.
     */
    private function resolveConsumptionExchangeRate(?int $saleId, PurchaseItem $batch): float
    {
        if ($saleId !== null) {
            $saleRate = (float) optional(Sale::query()->find($saleId))->exchange_rate;

            if ($saleRate > self::EPSILON) {
                return $saleRate;
            }
        }

        $defaultCurrency = Currency::query()->where('is_default', true)->orderBy('id')->first();

        if ($defaultCurrency && (float) $defaultCurrency->exchange_rate > self::EPSILON) {
            return (float) $defaultCurrency->exchange_rate;
        }

        return max($this->resolveExchangeRate($batch), self::EPSILON);
    }

    private function normaliseMaterials(array|Collection $materials): array
    {
        $rows = $materials instanceof Collection ? $materials->all() : $materials;

        return collect($rows)
            ->map(function ($row): array {
                if (is_object($row)) {
                    $row = method_exists($row, 'toArray') ? $row->toArray() : (array) $row;
                }

                if ($row === null || !is_array($row)) {
                    throw new RuntimeException('Each material entry must be an array or model-like object.');
                }

                $materialId = (int) ($row['material_id'] ?? $row['product_id'] ?? 0);
                $quantity = (float) (
                    $row['actual_quantity']
                    ?? $row['required_quantity']
                    ?? $row['quantity']
                    ?? $row['qty']
                    ?? 0
                );

                if ($materialId <= 0) {
                    throw new RuntimeException('Every stock deduction row requires a valid material_id.');
                }

                if ($quantity <= self::EPSILON) {
                    throw new RuntimeException(
                        "The deduction quantity for material #{$materialId} must be greater than zero."
                    );
                }

                return [
                    'material_id' => $materialId,
                    'quantity' => $quantity,
                    'planned_quantity' => (float) ($row['planned_quantity'] ?? $quantity),
                    'wastage_quantity' => (float) ($row['wastage_quantity'] ?? 0),
                    'unit' => isset($row['unit']) ? (string) $row['unit'] : null,
                    'sale_item_id' => isset($row['sale_item_id'])
                        ? (int) $row['sale_item_id']
                        : null,
                ];
            })
            ->groupBy('material_id')
            ->map(function (Collection $group): array {
                $first = $group->first();

                return [
                    'material_id' => $first['material_id'],
                    'quantity' => (float) $group->sum('quantity'),
                    'planned_quantity' => (float) $group->sum('planned_quantity'),
                    'wastage_quantity' => (float) $group->sum('wastage_quantity'),
                    'unit' => $first['unit'],
                    'sale_item_id' => $first['sale_item_id'],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Get material name by ID
     */
    private function getMaterialName(int $materialId): string
    {
        $product = Product::find($materialId);
        return $product ? $product->name : "Material #{$materialId}";
    }

    /**
     * Restore all stock deducted for a production order.
     */
    public function restoreProductionStock(int $productionOrderId): void
    {
        Log::info('🔄 Restoring production stock', [
            'production_order_id' => $productionOrderId,
        ]);

        DB::transaction(function () use ($productionOrderId): void {
            $consumptions = ProductionMaterialConsumption::query()
                ->where('production_order_id', $productionOrderId)
                ->lockForUpdate()
                ->get();

            if ($consumptions->isEmpty()) {
                Log::warning('No consumptions found to restore', [
                    'production_order_id' => $productionOrderId,
                ]);
                return;
            }

            foreach ($consumptions as $consumption) {
                if (!$consumption->purchase_item_id) {
                    Log::error('Cannot restore: missing purchase_item_id', [
                        'consumption_id' => $consumption->id,
                    ]);
                    continue;
                }

                $batch = PurchaseItem::query()
                    ->lockForUpdate()
                    ->find($consumption->purchase_item_id);

                if (!$batch) {
                    Log::error('Cannot restore: purchase batch not found', [
                        'consumption_id' => $consumption->id,
                        'purchase_item_id' => $consumption->purchase_item_id,
                    ]);
                    continue;
                }

                // ✅ Restore qty_available
                $batch->qty_available = (float) $batch->qty_available + (float) $consumption->actual_quantity;

                // ✅ Reduce qty_used
                if (in_array('qty_used', $batch->getFillable())) {
                    $batch->qty_used = max(0, (float) ($batch->qty_used ?? 0) - (float) $consumption->actual_quantity);
                }

                // ✅ Restore kg counters for roll batches
                if ($batch->isRollBatch() && (float) $batch->kg_per_roll > 0) {
                    $restoredKg = (float) $consumption->actual_quantity;
                    $restoredRolls = $restoredKg / (float) $batch->kg_per_roll;
                    $batch->qty_kg_available = (float) ($batch->qty_kg_available ?? 0) + $restoredKg;
                    $batch->qty_kg_used = max(0, (float) ($batch->qty_kg_used ?? 0) - $restoredKg);
                    $batch->qty_available = max(0, (float) ($batch->qty_available ?? 0) - (float) $consumption->actual_quantity + $restoredRolls);
                    $batch->qty_used = max(0, (float) ($batch->qty_used ?? 0) - $restoredRolls);
                }

                $batch->save();
                app(InventoryLocationService::class)->syncAfterBatchChange($batch);

                if ($batch->isRollBatch()) {
                    app(ReelInventoryService::class)
                        ->restoreConsumption($consumption);
                }

                Log::info('✅ Restored batch', [
                    'purchase_item_id' => $batch->id,
                    'restored_quantity' => $consumption->actual_quantity,
                    'new_qty_available' => $batch->qty_available,
                ]);
            }

            // Delete consumption records after restoration
            ProductionMaterialConsumption::query()
                ->where('production_order_id', $productionOrderId)
                ->delete();

            Log::info('✅ Production stock restored', [
                'production_order_id' => $productionOrderId,
                'consumptions_deleted' => $consumptions->count(),
            ]);
        });
    }

    /**
     * Set actual waste for one material after total actual consumption has been
     * reconciled. Waste is a subset of consumed stock and never causes another
     * stock deduction.
     */
    public function setProductionMaterialWastage(
        int $productionOrderId,
        int $materialId,
        float $wastageQuantity
    ): void {
        if ($wastageQuantity < -self::EPSILON) {
            throw new RuntimeException('Actual wastage cannot be negative.');
        }

        DB::transaction(function () use ($productionOrderId, $materialId, $wastageQuantity): void {
            $consumptions = ProductionMaterialConsumption::query()
                ->where('production_order_id', $productionOrderId)
                ->where('material_id', $materialId)
                ->where('actual_quantity', '>', 0)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $totalActual = (float) $consumptions->sum('actual_quantity');

            if ($wastageQuantity > $totalActual + self::EPSILON) {
                throw new RuntimeException(sprintf(
                    'Actual wastage %.6f cannot exceed actual consumption %.6f for material #%d.',
                    $wastageQuantity,
                    $totalActual,
                    $materialId
                ));
            }

            $remaining = max($wastageQuantity, 0.0);

            foreach ($consumptions as $consumption) {
                $actual = (float) $consumption->actual_quantity;
                $assigned = min($actual, $remaining);

                $consumption->wastage_quantity = $assigned;
                $consumption->wastage_cost_usd = $assigned * (float) $consumption->cost_per_unit_usd;
                $consumption->wastage_cost_afn = $assigned * (float) $consumption->cost_per_unit_afn;
                $consumption->save();

                $remaining -= $assigned;
            }

            if ($remaining > self::EPSILON) {
                throw new RuntimeException(sprintf(
                    'Unable to allocate %.6f units of actual wastage for material #%d.',
                    $remaining,
                    $materialId
                ));
            }
        });
    }

    /**
     * Restore part of one material previously consumed by a production order.
     *
     * The newest FIFO consumption records are unwound first. This preserves the
     * original batch trace while allowing the completion step to reconcile an
     * under-produced order back to the real quantity produced.
     */
    public function restoreProductionMaterialQuantity(
        int $productionOrderId,
        int $materialId,
        float $quantity
    ): float {
        if ($quantity <= self::EPSILON) {
            return 0.0;
        }

        return DB::transaction(function () use ($productionOrderId, $materialId, $quantity): float {
            $remaining = $quantity;
            $restored = 0.0;

            $consumptions = ProductionMaterialConsumption::query()
                ->where('production_order_id', $productionOrderId)
                ->where('material_id', $materialId)
                ->where('actual_quantity', '>', 0)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();

            foreach ($consumptions as $consumption) {
                if ($remaining <= self::EPSILON) {
                    break;
                }

                $current = (float) $consumption->actual_quantity;
                $toRestore = min($current, $remaining);

                if ($toRestore <= self::EPSILON) {
                    continue;
                }

                $batch = PurchaseItem::query()
                    ->lockForUpdate()
                    ->findOrFail($consumption->purchase_item_id);

                if ($batch->isRollBatch()) {
                    $kgPerRoll = max((float) $batch->kg_per_roll, self::EPSILON);
                    $restoredRolls = $toRestore / $kgPerRoll;

                    $batch->qty_kg_available = (float) ($batch->qty_kg_available ?? 0) + $toRestore;
                    if ((float) ($batch->total_weight_kg ?? 0) > 0) {
                        $batch->qty_kg_available = min(
                            (float) $batch->qty_kg_available,
                            (float) $batch->total_weight_kg
                        );
                    }

                    $batch->qty_kg_used = max(
                        (float) ($batch->qty_kg_used ?? 0) - $toRestore,
                        0
                    );
                    $batch->qty_available = min(
                        (float) $batch->qty_available + $restoredRolls,
                        (float) $batch->qty
                    );
                    $batch->qty_used = max(
                        (float) ($batch->qty_used ?? 0) - $restoredRolls,
                        0
                    );
                } else {
                    $batch->qty_available = min(
                        (float) $batch->qty_available + $toRestore,
                        (float) $batch->qty
                    );
                    $batch->qty_used = max(
                        (float) ($batch->qty_used ?? 0) - $toRestore,
                        0
                    );
                }

                $batch->save();

                if ($batch->isRollBatch()) {
                    app(ReelInventoryService::class)
                        ->restoreConsumption($consumption, $toRestore);
                }

                $newActual = max($current - $toRestore, 0);
                if ($newActual <= self::EPSILON) {
                    $consumption->delete();
                } else {
                    $ratio = $newActual / max($current, self::EPSILON);
                    $newPlanned = (float) $consumption->planned_quantity * $ratio;
                    $newWastage = (float) $consumption->wastage_quantity * $ratio;

                    $consumption->actual_quantity = $newActual;
                    $consumption->planned_quantity = $newPlanned;
                    $consumption->wastage_quantity = $newWastage;
                    $consumption->total_cost_usd = $newActual * (float) $consumption->cost_per_unit_usd;
                    $consumption->total_cost_afn = $newActual * (float) $consumption->cost_per_unit_afn;
                    $consumption->wastage_cost_usd = $newWastage * (float) $consumption->cost_per_unit_usd;
                    $consumption->wastage_cost_afn = $newWastage * (float) $consumption->cost_per_unit_afn;
                    $consumption->save();
                }

                $remaining -= $toRestore;
                $restored += $toRestore;
            }

            if ($remaining > self::EPSILON) {
                throw new RuntimeException(sprintf(
                    'Unable to restore %.6f units of material #%d for production order #%d.',
                    $remaining,
                    $materialId,
                    $productionOrderId
                ));
            }

            return $restored;
        });
    }

    /**
     * Backwards-compatible restore alias.
     */
    public function restoreStock(int $productionOrderId): void
    {
        $this->restoreProductionStock($productionOrderId);
    }

    /**
     * Force sync stock quantities for all purchase items
     */
    public function syncStockQuantities(): array
    {
        $items = PurchaseItem::whereHas('purchase', function($q) {
            $q->where('status', 'arrived');
        })->get();

        $results = [];

        foreach ($items as $item) {
            $oldAvailable = $item->qty_available;

            // ✅ Recalculate qty_available from qty - used - sold - wasted
            $item->qty_available = (float) $item->qty
                - (float) ($item->qty_used ?? 0)
                - (float) ($item->qty_sold ?? 0)
                - (float) ($item->qty_wasted ?? 0);

            // ✅ Ensure it's not negative
            if ($item->qty_available < 0) {
                $item->qty_available = 0;
            }

            $item->save();

            $results[] = [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'qty' => $item->qty,
                'old_available' => $oldAvailable,
                'new_available' => $item->qty_available,
                'fixed' => $oldAvailable != $item->qty_available,
            ];
        }

        Log::info('✅ Stock quantities synced', [
            'total_items' => count($results),
            'fixed_count' => collect($results)->where('fixed', true)->count(),
        ]);

        return $results;
    }
}
