<?php

use App\Http\Controllers\Admin\ProductionOrderController;
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
        ->and(Schema::hasColumn('purchase_item_reels', 'status_changed_at'))->toBeTrue()
        ->and(Schema::hasColumn('production_reel_consumptions', 'allocation_method'))->toBeTrue()
        ->and(Schema::hasColumn('production_reel_consumptions', 'selected_by'))->toBeTrue()
        ->and(Schema::hasColumn('production_reel_consumptions', 'declared_final_weight_kg'))->toBeTrue();

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
        ->and((float) $lineage[1]->quantity_kg)->toBe(100.0)
        ->and($lineage->pluck('allocation_method')->unique()->values()->all())
            ->toBe(['fifo']);
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

it('closes a physically finished reel at zero and retains its excess use as production variance', function () {
    $fx = reelTrackingFixture();
    $reelService = app(ReelInventoryService::class);
    $stock = app(StockDeductionService::class);

    $reelService->initializeBatch($fx['batch']);

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

    // Production start has provisionally allocated 100 kg from the first
    // 500 kg reel, leaving 400 kg visible on that reel.
    $stock->deductMaterials(
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

    $reel = $fx['batch']->fresh()->reels()
        ->orderBy('sequence_no')
        ->firstOrFail();

    expect((float) $reel->system_remaining_weight_kg)->toBe(400.0);

    $order = ProductionOrder::findOrFail($fx['productionOrderId']);

    $request = \Illuminate\Http\Request::create(
        '/admin/production-orders/'.$order->id.'/complete',
        'POST',
        [
            'quantity_manufactured' => 100,
            'quantity_produced' => 100,
            'quantity_rejected' => 0,
            'materials' => [[
                'material_id' => $fx['materialId'],
                'consumption_mode' => 'calculated',
                'reel_mode' => 'finished',
                'selected_reel_id' => $reel->id,
                'unit' => 'kg',
            ]],
        ]
    );

    $response = app(ProductionOrderController::class)
        ->completeProduction($order, $request);

    $reel->refresh();
    $batch = $fx['batch']->fresh();
    $consumption = ProductionMaterialConsumption::query()
        ->where('production_order_id', $order->id)
        ->where('material_id', $fx['materialId'])
        ->firstOrFail();

    expect($response->getSession()->get('success'))
        ->toContain('finished reel')
        ->and((float) $consumption->actual_quantity)->toBe(500.0)
        ->and((float) $reel->system_remaining_weight_kg)->toBe(0.0)
        ->and($reel->status)->toBe(PurchaseItemReel::STATUS_CONSUMED)
        ->and($batch->availableKg())->toBe(500.0)
        ->and((float) $consumption->actual_quantity - 100.0)->toBe(400.0);

    $lineage = ProductionReelConsumption::query()
        ->where('production_material_consumption_id', $consumption->id)
        ->firstOrFail();

    expect((int) $lineage->purchase_item_reel_id)->toBe((int) $reel->id)
        ->and((float) $lineage->quantity_kg)->toBe(500.0)
        ->and((float) $lineage->after_weight_kg)->toBe(0.0)
        ->and($lineage->allocation_method)->toBe('operator_selected');
});

it('replaces provisional FIFO with the operator selected reel at production completion', function () {
    $fx = reelTrackingFixture();
    $reelService = app(ReelInventoryService::class);
    $stock = app(StockDeductionService::class);

    $reelService->initializeBatch($fx['batch']);
    $stock->deductMaterials(
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

    $reels = $fx['batch']->fresh()->reels()
        ->orderBy('sequence_no')
        ->get();

    expect((float) $reels[0]->system_remaining_weight_kg)->toBe(400.0)
        ->and((float) $reels[1]->system_remaining_weight_kg)->toBe(500.0);

    $actuals = [[
        'material_id' => $fx['materialId'],
        'actual_quantity' => 100,
        'wastage_quantity' => 0,
        'unit' => 'kg',
        'use_reel_selection' => true,
        'selection_note' => 'Scanned on production floor',
        'reels' => [[
            'reel_id' => $reels[1]->id,
            'consumed_kg' => 100,
        ]],
    ]];

    app(ProductionQuantityService::class)->complete(
        ProductionOrder::findOrFail($fx['productionOrderId']),
        100,
        null,
        100,
        0,
        $actuals
    );

    $batch = $fx['batch']->fresh();
    $reels = $batch->reels()->orderBy('sequence_no')->get();
    $lineage = ProductionReelConsumption::query()->get();

    expect($batch->availableKg())->toBe(900.0)
        ->and((float) $reels[0]->system_remaining_weight_kg)->toBe(500.0)
        ->and($reels[0]->status)->toBe(PurchaseItemReel::STATUS_SEALED)
        ->and((float) $reels[1]->system_remaining_weight_kg)->toBe(400.0)
        ->and($reels[1]->status)->toBe(PurchaseItemReel::STATUS_OPEN)
        ->and($lineage)->toHaveCount(1)
        ->and((int) $lineage->first()->purchase_item_reel_id)
            ->toBe((int) $reels[1]->id)
        ->and($lineage->first()->allocation_method)
            ->toBe('operator_selected')
        ->and((int) $lineage->first()->selected_by)
            ->toBe((int) $fx['user']->id)
        ->and($lineage->first()->selection_note)
            ->toBe('Scanned on production floor');
});

it('keeps a declared final reel weight as observational variance instead of overwriting stock', function () {
    $fx = reelTrackingFixture();
    $reelService = app(ReelInventoryService::class);
    $stock = app(StockDeductionService::class);

    $reelService->initializeBatch($fx['batch']);
    $stock->deductMaterials(
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

    $secondReel = $fx['batch']->fresh()->reels()
        ->orderBy('sequence_no')
        ->skip(1)
        ->firstOrFail();

    app(ProductionQuantityService::class)->complete(
        ProductionOrder::findOrFail($fx['productionOrderId']),
        100,
        null,
        100,
        0,
        [[
            'material_id' => $fx['materialId'],
            'actual_quantity' => 100,
            'wastage_quantity' => 0,
            'unit' => 'kg',
            'use_reel_selection' => true,
            'reels' => [[
                'reel_id' => $secondReel->id,
                'consumed_kg' => 100,
                'final_remaining_kg' => 390,
            ]],
        ]]
    );

    $batch = $fx['batch']->fresh();
    $reel = $secondReel->fresh();
    $lineage = ProductionReelConsumption::firstOrFail();
    $measurement = $reel->measurements()->firstOrFail();

    expect($batch->availableKg())->toBe(900.0)
        ->and((float) $reel->system_remaining_weight_kg)->toBe(400.0)
        ->and((float) $reel->last_measured_weight_kg)->toBe(390.0)
        ->and((float) $reel->measurement_variance_kg)->toBe(-10.0)
        ->and((float) $measurement->system_weight_snapshot_kg)->toBe(400.0)
        ->and((float) $measurement->measured_weight_kg)->toBe(390.0)
        ->and((float) $lineage->declared_final_weight_kg)->toBe(390.0)
        ->and((float) ($batch->qty_kg_adjusted ?? 0))->toBe(0.0);
});

it('infers selected reel consumption from an end of run remainder when consumed kg is omitted', function () {
    $fx = reelTrackingFixture();
    $reelService = app(ReelInventoryService::class);
    $stock = app(StockDeductionService::class);

    $reelService->initializeBatch($fx['batch']);
    $stock->deductMaterials(
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

    $secondReel = $fx['batch']->fresh()->reels()
        ->orderBy('sequence_no')
        ->skip(1)
        ->firstOrFail();

    app(ProductionQuantityService::class)->complete(
        ProductionOrder::findOrFail($fx['productionOrderId']),
        100,
        null,
        100,
        0,
        [[
            'material_id' => $fx['materialId'],
            'actual_quantity' => 100,
            'wastage_quantity' => 0,
            'unit' => 'kg',
            'use_reel_selection' => true,
            'reels' => [[
                'reel_id' => $secondReel->id,
                'final_remaining_kg' => 400,
            ]],
        ]]
    );

    $reel = $secondReel->fresh();
    $lineage = ProductionReelConsumption::firstOrFail();

    expect((float) $lineage->quantity_kg)->toBe(100.0)
        ->and((float) $reel->system_remaining_weight_kg)->toBe(400.0)
        ->and((float) $reel->last_measured_weight_kg)->toBe(400.0)
        ->and((float) $reel->measurement_variance_kg)->toBe(0.0)
        ->and($fx['batch']->fresh()->availableKg())->toBe(900.0);
});

it('rolls back the reel replay when an operator selects a blocked reel', function () {
    $fx = reelTrackingFixture();
    $reelService = app(ReelInventoryService::class);
    $stock = app(StockDeductionService::class);

    $reelService->initializeBatch($fx['batch']);
    $stock->deductMaterials(
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

    $reels = $fx['batch']->fresh()->reels()
        ->orderBy('sequence_no')
        ->get();

    $reelService->changeControlStatus(
        $reels[1],
        'quarantined',
        'Hold before completion'
    );

    $beforeConsumption = ProductionMaterialConsumption::firstOrFail();
    $beforeConsumptionId = $beforeConsumption->id;
    $beforeCost = (float) $beforeConsumption->total_cost_usd;

    expect(fn () => $stock->replaceProductionMaterialWithReelSelections(
        $fx['productionOrderId'],
        null,
        null,
        $fx['materialId'],
        100,
        100,
        [[
            'reel_id' => $reels[1]->id,
            'consumed_kg' => 100,
        ]]
    ))->toThrow(\RuntimeException::class, 'not production eligible');

    $batch = $fx['batch']->fresh();
    $reels = $batch->reels()->orderBy('sequence_no')->get();
    $consumption = ProductionMaterialConsumption::firstOrFail();
    $lineage = ProductionReelConsumption::firstOrFail();

    expect($batch->availableKg())->toBe(900.0)
        ->and((float) $reels[0]->system_remaining_weight_kg)->toBe(400.0)
        ->and((float) $reels[1]->system_remaining_weight_kg)->toBe(500.0)
        ->and($reels[1]->status)->toBe(PurchaseItemReel::STATUS_QUARANTINED)
        ->and((int) $consumption->id)->toBe((int) $beforeConsumptionId)
        ->and((float) $consumption->total_cost_usd)->toBe($beforeCost)
        ->and((int) $lineage->purchase_item_reel_id)->toBe((int) $reels[0]->id)
        ->and($lineage->allocation_method)->toBe('fifo');
});

it('uses the selected source batch landed cost when the operator overrides FIFO', function () {
    $fx = reelTrackingFixture();
    $reelService = app(ReelInventoryService::class);
    $stock = app(StockDeductionService::class);

    $reelService->initializeBatch($fx['batch']);

    $secondBatch = PurchaseItem::create([
        'purchase_id' => $fx['batch']->purchase_id,
        'product_id' => $fx['materialId'],
        'purchase_currency_id' => $fx['currencyId'],
        'qty' => 1,
        'unit' => 'roll',
        'kg_per_roll' => 500,
        'unit_price' => 375,
        'usd_unit_price' => 375,
        'usd_total' => 375,
        'usd_expense_per_item' => 0,
        'landed_cost_per_kg' => 0.75,
        'batch_no' => 'REEL-BATCH-002',
    ]);
    $reelService->initializeBatch($secondBatch);

    $stock->deductMaterials(
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

    $selectedReel = $secondBatch->fresh()->reels()->firstOrFail();

    $records = $stock->replaceProductionMaterialWithReelSelections(
        $fx['productionOrderId'],
        null,
        null,
        $fx['materialId'],
        100,
        100,
        [[
            'reel_id' => $selectedReel->id,
            'consumed_kg' => 100,
            'note' => 'Older FIFO batch was not physically used',
        ]]
    );

    $record = $records->firstOrFail();
    $lineage = $record->reelConsumptions()->firstOrFail();

    expect($fx['batch']->fresh()->availableKg())->toBe(1000.0)
        ->and($secondBatch->fresh()->availableKg())->toBe(400.0)
        ->and((int) $record->purchase_item_id)->toBe((int) $secondBatch->id)
        ->and((float) $record->cost_per_unit_usd)->toBe(0.75)
        ->and((float) $record->total_cost_usd)->toBe(75.0)
        ->and((int) $lineage->purchase_item_reel_id)->toBe((int) $selectedReel->id)
        ->and($lineage->allocation_method)->toBe('operator_selected');
});

