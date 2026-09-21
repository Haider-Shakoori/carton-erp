<?php

use App\Models\PurchaseItem;
use App\Models\PurchaseItemReel;
use App\Models\ProductionMaterialConsumption;
use App\Models\ProductionOrder;
use App\Models\ProductionReelConsumption;
use App\Models\User;
use App\Services\ProductionQuantityService;
use App\Services\ReelInventoryService;
use App\Services\StockDeductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function reelTrackingFixture(int $rolls = 2, float $kgPerRoll = 500): array
{
    $user = User::factory()->create(['is_active' => true]);
    Auth::login($user);
    $now = now();

    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Reel Tracking Paper',
        'slug' => 'reel-tracking-paper',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $materialId = DB::table('products')->insertGetId([
        'name' => 'Reel Tracking Kraft',
        'slug' => 'reel-tracking-kraft',
        'unit' => 'roll',
        'default_kg_per_roll' => $kgPerRoll,
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $finishedId = DB::table('products')->insertGetId([
        'name' => 'Reel Tracking Finished Carton',
        'slug' => 'reel-tracking-finished-carton',
        'unit' => 'piece',
        'category_id' => $categoryId,
        'type' => 'finished_good',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $currencyId = DB::table('currencies')->insertGetId([
        'name' => 'Reel Tracking USD',
        'code' => 'RTUSD',
        'symbol' => '$',
        'exchange_rate' => 1,
        'is_default' => 1,
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $supplierId = DB::table('accounts')->insertGetId([
        'name' => 'Reel Tracking Supplier',
        'code' => 'RT-SUP',
        'account_type' => 'supplier',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $purchaseId = DB::table('purchases')->insertGetId([
        'purchase_no' => 'PO-REEL-001',
        'supplier_id' => $supplierId,
        'currency_id' => $currencyId,
        'status' => 'arrived',
        'exchange_rate' => 1,
        'purchase_date' => today()->toDateString(),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $batch = PurchaseItem::create([
        'purchase_id' => $purchaseId,
        'product_id' => $materialId,
        'purchase_currency_id' => $currencyId,
        'qty' => $rolls,
        'unit' => 'roll',
        'kg_per_roll' => $kgPerRoll,
        'unit_price' => 100,
        'usd_unit_price' => 100,
        'usd_total' => 100 * $rolls,
        'usd_expense_per_item' => 0,
        'batch_no' => 'REEL-BATCH-001',
    ]);

    $bomId = DB::table('boms')->insertGetId([
        'name' => 'Reel Tracking BOM',
        'code' => 'REEL-BOM',
        'product_id' => $finishedId,
        'status' => 'active',
        'created_by' => $user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $productionOrderId = DB::table('production_orders')->insertGetId([
        'order_number' => 'PROD-REEL-001',
        'product_id' => $finishedId,
        'bom_id' => $bomId,
        'quantity_ordered' => 100,
        'quantity_planned' => 100,
        'status' => 'in_progress',
        'start_date' => today()->toDateString(),
        'created_by' => $user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return compact(
        'user',
        'materialId',
        'finishedId',
        'currencyId',
        'batch',
        'productionOrderId'
    );
}

it('creates physical reel schema and initializes an untouched roll batch without changing stock', function () {
    expect(Schema::hasTable('purchase_item_reels'))->toBeTrue()
        ->and(Schema::hasTable('purchase_item_reel_measurements'))->toBeTrue()
        ->and(Schema::hasTable('production_reel_consumptions'))->toBeTrue()
        ->and(Schema::hasTable('purchase_item_reel_status_events'))->toBeTrue()
        ->and(Schema::hasColumn('purchase_item_reels', 'status_reason'))->toBeTrue()
        ->and(Schema::hasColumn('purchase_item_reels', 'status_changed_at'))->toBeTrue();

    $fx = reelTrackingFixture(3, 500);
    $batch = $fx['batch']->fresh();
    $beforeKg = $batch->availableKg();
    $beforeRolls = (float) $batch->qty_available;

    $reels = app(ReelInventoryService::class)
        ->initializeBatch($batch);

    expect($reels)->toHaveCount(3)
        ->and($reels->sum('system_remaining_weight_kg'))->toBe(1500.0)
        ->and($reels->where('status', PurchaseItemReel::STATUS_SEALED)->count())->toBe(3)
        ->and($batch->fresh()->availableKg())->toBe($beforeKg)
        ->and((float) $batch->fresh()->qty_available)->toBe($beforeRolls);
});

it('records remnant measurements as observations without mutating authoritative batch stock', function () {
    $fx = reelTrackingFixture();
    $service = app(ReelInventoryService::class);
    $service->initializeBatch($fx['batch']);

    $reel = $fx['batch']->reels()->firstOrFail();
    $batchBefore = $fx['batch']->fresh()->availableKg();

    $measured = $service->recordMeasurement(
        $reel,
        480,
        'Warehouse scale check'
    );

    expect((float) $measured->last_measured_weight_kg)->toBe(480.0)
        ->and((float) $measured->measurement_variance_kg)->toBe(-20.0)
        ->and($measured->measurements)->toHaveCount(1)
        ->and((float) $measured->measurements->first()->system_weight_snapshot_kg)->toBe(500.0)
        ->and($fx['batch']->fresh()->availableKg())->toBe($batchBefore);
});

it('allocates actual FIFO production consumption across physical reels and preserves lineage', function () {
    $fx = reelTrackingFixture();
    $reelService = app(ReelInventoryService::class);
    $reelService->initializeBatch($fx['batch']);

    $consumptions = app(StockDeductionService::class)->deductMaterials(
        $fx['productionOrderId'],
        null,
        [[
            'material_id' => $fx['materialId'],
            'quantity' => 600,
            'planned_quantity' => 600,
            'wastage_quantity' => 0,
            'unit' => 'kg',
        ]]
    );

    expect($consumptions)->toHaveCount(1);

    $batch = $fx['batch']->fresh();
    $reels = $batch->reels()->orderBy('sequence_no')->get();
    $lineage = ProductionReelConsumption::query()
        ->orderBy('id')
        ->get();

    expect($batch->availableKg())->toBe(400.0)
        ->and((float) $batch->qty_available)->toBe(0.8)
        ->and($reels)->toHaveCount(2)
        ->and((float) $reels[0]->system_remaining_weight_kg)->toBe(0.0)
        ->and($reels[0]->status)->toBe(PurchaseItemReel::STATUS_CONSUMED)
        ->and((float) $reels[1]->system_remaining_weight_kg)->toBe(400.0)
        ->and($reels[1]->status)->toBe(PurchaseItemReel::STATUS_OPEN)
        ->and($lineage)->toHaveCount(2)
        ->and((float) $lineage[0]->quantity_kg)->toBe(500.0)
        ->and((float) $lineage[1]->quantity_kg)->toBe(100.0);
});

it('restores partial and full production quantities back through the original reel lineage', function () {
    $fx = reelTrackingFixture();
    $service = app(ReelInventoryService::class);
    $service->initializeBatch($fx['batch']);

    app(StockDeductionService::class)->deductMaterials(
        $fx['productionOrderId'],
        null,
        [[
            'material_id' => $fx['materialId'],
            'quantity' => 600,
            'planned_quantity' => 600,
            'wastage_quantity' => 0,
            'unit' => 'kg',
        ]]
    );

    $stock = app(StockDeductionService::class);
    $restored = $stock->restoreProductionMaterialQuantity(
        $fx['productionOrderId'],
        $fx['materialId'],
        150
    );

    $batch = $fx['batch']->fresh();
    $reels = $batch->reels()->orderBy('sequence_no')->get();

    expect($restored)->toBe(150.0)
        ->and($batch->availableKg())->toBe(550.0)
        ->and((float) $reels[0]->system_remaining_weight_kg)->toBe(50.0)
        ->and($reels[0]->status)->toBe(PurchaseItemReel::STATUS_OPEN)
        ->and((float) $reels[1]->system_remaining_weight_kg)->toBe(500.0)
        ->and($reels[1]->status)->toBe(PurchaseItemReel::STATUS_SEALED)
        ->and((float) ProductionReelConsumption::sum('quantity_kg'))->toBe(450.0);

    $stock->restoreProductionStock($fx['productionOrderId']);

    $batch = $fx['batch']->fresh();
    $reels = $batch->reels()->orderBy('sequence_no')->get();

    expect($batch->availableKg())->toBe(1000.0)
        ->and((float) $batch->qty_available)->toBe(2.0)
        ->and((float) $reels[0]->system_remaining_weight_kg)->toBe(500.0)
        ->and((float) $reels[1]->system_remaining_weight_kg)->toBe(500.0)
        ->and($reels->where('status', PurchaseItemReel::STATUS_SEALED)->count())->toBe(2)
        ->and(ProductionReelConsumption::count())->toBe(0);
});

it('blocks production when tracked reel totals drift from the authoritative batch balance', function () {
    $fx = reelTrackingFixture();
    app(ReelInventoryService::class)->initializeBatch($fx['batch']);

    $batch = $fx['batch']->fresh();
    $batch->qty_kg_adjusted = -50;
    $batch->save();

    expect($batch->fresh()->availableKg())->toBe(950.0);

    expect(fn () => app(StockDeductionService::class)->deductMaterials(
        $fx['productionOrderId'],
        null,
        [[
            'material_id' => $fx['materialId'],
            'quantity' => 100,
            'planned_quantity' => 100,
            'wastage_quantity' => 0,
            'unit' => 'kg',
        ]]
    ))->toThrow(\RuntimeException::class, 'out of sync');

    expect($batch->fresh()->availableKg())->toBe(950.0)
        ->and((float) $batch->fresh()->reels()->sum('system_remaining_weight_kg'))->toBe(1000.0)
        ->and(ProductionReelConsumption::count())->toBe(0);
});

it('re-baselines out-of-sync reels only from fresh measurements that equal the approved batch balance', function () {
    $fx = reelTrackingFixture();
    $service = app(ReelInventoryService::class);
    $service->initializeBatch($fx['batch']);

    $batch = $fx['batch']->fresh();
    $batch->qty_kg_adjusted = -50;
    $batch->save();
    $batch->refresh();

    $reels = $batch->reels()->orderBy('sequence_no')->get();

    $service->recordMeasurement($reels[0], 450, 'Post-reconciliation scale weight');
    $service->recordMeasurement($reels[1], 500, 'Post-reconciliation scale weight');

    $service->rebaselineFromLatestMeasurements($batch->fresh());

    $batch = $batch->fresh();
    $reels = $batch->reels()->orderBy('sequence_no')->get();
    $summary = $service->summary($batch);

    expect($batch->availableKg())->toBe(950.0)
        ->and($summary['healthy'])->toBeTrue()
        ->and((float) $reels[0]->system_remaining_weight_kg)->toBe(450.0)
        ->and($reels[0]->status)->toBe(PurchaseItemReel::STATUS_OPEN)
        ->and((float) $reels[1]->system_remaining_weight_kg)->toBe(500.0)
        ->and($reels[1]->status)->toBe(PurchaseItemReel::STATUS_SEALED);

    app(StockDeductionService::class)->deductMaterials(
        $fx['productionOrderId'],
        null,
        [[
            'material_id' => $fx['materialId'],
            'quantity' => 100,
            'planned_quantity' => 100,
            'wastage_quantity' => 0,
            'unit' => 'kg',
        ]]
    );

    $reels = $batch->fresh()->reels()->orderBy('sequence_no')->get();

    expect((float) $reels[0]->system_remaining_weight_kg)->toBe(350.0)
        ->and((float) $reels[1]->system_remaining_weight_kg)->toBe(500.0)
        ->and($batch->fresh()->availableKg())->toBe(850.0);
});

it('records damaged and quarantined control states without changing stock and keeps an audit history', function () {
    $fx = reelTrackingFixture();
    $service = app(ReelInventoryService::class);
    $service->initializeBatch($fx['batch']);

    $reel = $fx['batch']->reels()->firstOrFail();
    $batchBefore = $fx['batch']->fresh()->availableKg();
    $reelBefore = (float) $reel->system_remaining_weight_kg;

    $damaged = $service->changeControlStatus(
        $reel,
        'damaged',
        'Edge damage found during warehouse inspection'
    );

    expect($damaged->status)->toBe(PurchaseItemReel::STATUS_DAMAGED)
        ->and($damaged->status_reason)->toBe('Edge damage found during warehouse inspection')
        ->and((float) $damaged->system_remaining_weight_kg)->toBe($reelBefore)
        ->and($fx['batch']->fresh()->availableKg())->toBe($batchBefore)
        ->and($damaged->statusEvents)->toHaveCount(1)
        ->and($damaged->statusEvents->first()->from_status)->toBe(PurchaseItemReel::STATUS_SEALED)
        ->and($damaged->statusEvents->first()->to_status)->toBe(PurchaseItemReel::STATUS_DAMAGED);

    $quarantined = $service->changeControlStatus(
        $damaged,
        'quarantined',
        'Awaiting supervisor inspection'
    );

    expect($quarantined->status)->toBe(PurchaseItemReel::STATUS_QUARANTINED)
        ->and($quarantined->statusEvents)->toHaveCount(2)
        ->and($fx['batch']->fresh()->availableKg())->toBe($batchBefore);
});

it('keeps damaged and quarantined kilograms in inventory but blocks them from production atomically', function () {
    $fx = reelTrackingFixture();
    $service = app(ReelInventoryService::class);
    $service->initializeBatch($fx['batch']);

    $reels = $fx['batch']->reels()->orderBy('sequence_no')->get();
    $service->changeControlStatus(
        $reels[0],
        'quarantined',
        'Quality hold'
    );

    expect(fn () => app(StockDeductionService::class)->deductMaterials(
        $fx['productionOrderId'],
        null,
        [[
            'material_id' => $fx['materialId'],
            'quantity' => 600,
            'planned_quantity' => 600,
            'wastage_quantity' => 0,
            'unit' => 'kg',
        ]]
    ))->toThrow(\RuntimeException::class, 'Production-eligible reels contain only');

    $batch = $fx['batch']->fresh();
    $reels = $batch->reels()->orderBy('sequence_no')->get();

    expect($batch->availableKg())->toBe(1000.0)
        ->and((float) $reels[0]->system_remaining_weight_kg)->toBe(500.0)
        ->and($reels[0]->status)->toBe(PurchaseItemReel::STATUS_QUARANTINED)
        ->and((float) $reels[1]->system_remaining_weight_kg)->toBe(500.0)
        ->and(ProductionMaterialConsumption::count())->toBe(0)
        ->and(ProductionReelConsumption::count())->toBe(0);
});

it('uses only production eligible reels while preserving blocked reel weight and batch alignment', function () {
    $fx = reelTrackingFixture();
    $service = app(ReelInventoryService::class);
    $service->initializeBatch($fx['batch']);

    $reels = $fx['batch']->reels()->orderBy('sequence_no')->get();
    $service->changeControlStatus(
        $reels[0],
        'damaged',
        'Core damage'
    );

    app(StockDeductionService::class)->deductMaterials(
        $fx['productionOrderId'],
        null,
        [[
            'material_id' => $fx['materialId'],
            'quantity' => 400,
            'planned_quantity' => 400,
            'wastage_quantity' => 0,
            'unit' => 'kg',
        ]]
    );

    $batch = $fx['batch']->fresh();
    $reels = $batch->reels()->orderBy('sequence_no')->get();
    $summary = $service->summary($batch);

    expect($batch->availableKg())->toBe(600.0)
        ->and((float) $reels[0]->system_remaining_weight_kg)->toBe(500.0)
        ->and($reels[0]->status)->toBe(PurchaseItemReel::STATUS_DAMAGED)
        ->and((float) $reels[1]->system_remaining_weight_kg)->toBe(100.0)
        ->and($reels[1]->status)->toBe(PurchaseItemReel::STATUS_OPEN)
        ->and($summary['healthy'])->toBeTrue()
        ->and($summary['blocked_kg'])->toBe(500.0)
        ->and($summary['eligible_kg'])->toBe(100.0);
});

it('preserves a warehouse control hold through physical re-baselining and releases it explicitly', function () {
    $fx = reelTrackingFixture();
    $service = app(ReelInventoryService::class);
    $service->initializeBatch($fx['batch']);

    $batch = $fx['batch']->fresh();
    $reels = $batch->reels()->orderBy('sequence_no')->get();
    $service->changeControlStatus($reels[0], 'damaged', 'Torn outer layers');

    $batch->qty_kg_adjusted = -50;
    $batch->save();
    $batch->refresh();

    $service->recordMeasurement($reels[0], 450, 'Approved post-count weight');
    $service->recordMeasurement($reels[1], 500, 'Approved post-count weight');
    $service->rebaselineFromLatestMeasurements($batch);

    $reel = $batch->fresh()->reels()->orderBy('sequence_no')->firstOrFail();

    expect($reel->status)->toBe(PurchaseItemReel::STATUS_DAMAGED)
        ->and((float) $reel->system_remaining_weight_kg)->toBe(450.0);

    $released = $service->changeControlStatus(
        $reel,
        'release',
        'Supervisor cleared remaining material for production'
    );

    expect($released->status)->toBe(PurchaseItemReel::STATUS_OPEN)
        ->and($released->status_reason)->toBeNull()
        ->and($released->statusEvents)->toHaveCount(2)
        ->and($batch->fresh()->availableKg())->toBe(950.0);
});

it('preserves FIFO landed cost on reel-backed production consumption and reports valuation', function () {
    $fx = reelTrackingFixture();
    $service = app(ReelInventoryService::class);
    $service->initializeBatch($fx['batch']);

    $expectedLandedCost = $fx['batch']->fresh()->landedCostPerKg();

    $records = app(StockDeductionService::class)->deductMaterials(
        $fx['productionOrderId'],
        null,
        [[
            'material_id' => $fx['materialId'],
            'quantity' => 100,
            'planned_quantity' => 100,
            'wastage_quantity' => 0,
            'unit' => 'kg',
        ]]
    );

    $record = $records->first();
    $summary = $service->summary($fx['batch']->fresh());

    expect((float) $record->cost_per_unit_usd)->toBe($expectedLandedCost)
        ->and((float) $record->total_cost_usd)->toBe(100 * $expectedLandedCost)
        ->and($summary['inventory_value_usd'])->toBe(
            $fx['batch']->fresh()->availableKg() * $expectedLandedCost
        );
});

it('prevents negative reel balances and rolls back the whole stock deduction', function () {
    $fx = reelTrackingFixture();
    app(ReelInventoryService::class)->initializeBatch($fx['batch']);

    expect(fn () => app(StockDeductionService::class)->deductMaterials(
        $fx['productionOrderId'],
        null,
        [[
            'material_id' => $fx['materialId'],
            'quantity' => 1200,
            'planned_quantity' => 1200,
            'wastage_quantity' => 0,
            'unit' => 'kg',
        ]]
    ))->toThrow(\RuntimeException::class);

    $batch = $fx['batch']->fresh();

    expect($batch->availableKg())->toBe(1000.0)
        ->and((float) $batch->reels()->sum('system_remaining_weight_kg'))->toBe(1000.0)
        ->and(ProductionMaterialConsumption::count())->toBe(0)
        ->and(ProductionReelConsumption::count())->toBe(0);
});

it('rejects repeated production completion without consuming any additional reel stock', function () {
    $fx = reelTrackingFixture();
    app(ReelInventoryService::class)->initializeBatch($fx['batch']);

    DB::table('production_order_materials')->insert([
        'production_order_id' => $fx['productionOrderId'],
        'product_id' => $fx['materialId'],
        'required_quantity' => 100,
        'available_quantity' => 1000,
        'shortage_quantity' => 0,
        'unit' => 'kg',
        'cost_per_unit' => $fx['batch']->landedCostPerKg(),
        'total_cost' => 100 * $fx['batch']->landedCostPerKg(),
        'consumed_quantity' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $order = ProductionOrder::findOrFail($fx['productionOrderId']);
    $actuals = [[
        'material_id' => $fx['materialId'],
        'actual_quantity' => 100,
        'wastage_quantity' => 0,
        'unit' => 'kg',
    ]];

    app(ProductionQuantityService::class)->complete(
        $order,
        100,
        null,
        100,
        0,
        $actuals
    );

    $afterFirst = $fx['batch']->fresh()->availableKg();
    $reelAfterFirst = (float) $fx['batch']->fresh()->reels()
        ->sum('system_remaining_weight_kg');
    $consumptionCount = ProductionMaterialConsumption::count();

    expect(fn () => app(ProductionQuantityService::class)->complete(
        $order->fresh(),
        100,
        null,
        100,
        0,
        $actuals
    ))->toThrow(\RuntimeException::class, 'Only in-progress production orders can be completed');

    expect($fx['batch']->fresh()->availableKg())->toBe($afterFirst)
        ->and((float) $fx['batch']->fresh()->reels()->sum('system_remaining_weight_kg'))->toBe($reelAfterFirst)
        ->and(ProductionMaterialConsumption::count())->toBe($consumptionCount);
});

it('initializes legacy partially used batches from current reel weights without rewriting historical consumption', function () {
    $fx = reelTrackingFixture();
    $batch = $fx['batch']->fresh();

    $batch->qty_kg_used = 100;
    $batch->qty_kg_available = 900;
    $batch->qty_used = 0.2;
    $batch->qty_available = 1.8;
    $batch->save();

    $historical = ProductionMaterialConsumption::create([
        'production_order_id' => $fx['productionOrderId'],
        'material_id' => $fx['materialId'],
        'purchase_item_id' => $batch->id,
        'planned_quantity' => 100,
        'actual_quantity' => 100,
        'wastage_quantity' => 0,
        'unit' => 'kg',
        'cost_per_unit_usd' => $batch->landedCostPerKg(),
        'cost_per_unit_afn' => $batch->landedCostPerKg(),
        'total_cost_usd' => 100 * $batch->landedCostPerKg(),
        'total_cost_afn' => 100 * $batch->landedCostPerKg(),
        'wastage_cost_usd' => 0,
        'wastage_cost_afn' => 0,
        'consumed_at' => now()->subDay(),
        'created_by' => $fx['user']->id,
    ]);

    $before = $historical->toArray();

    $reels = app(ReelInventoryService::class)->initializeBatch(
        $batch->fresh(),
        [400, 500]
    );

    $historical->refresh();

    expect($reels)->toHaveCount(2)
        ->and((float) $reels->sum('system_remaining_weight_kg'))->toBe(900.0)
        ->and((float) $historical->actual_quantity)->toBe((float) $before['actual_quantity'])
        ->and((float) $historical->total_cost_usd)->toBe((float) $before['total_cost_usd'])
        ->and($historical->reelConsumptions()->count())->toBe(0)
        ->and($batch->fresh()->availableKg())->toBe(900.0);
});

