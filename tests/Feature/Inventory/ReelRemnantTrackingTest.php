<?php

use App\Models\PurchaseItem;
use App\Models\PurchaseItemReel;
use App\Models\ProductionReelConsumption;
use App\Models\User;
use App\Services\ReelInventoryService;
use App\Services\StockDeductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

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
        ->and(Schema::hasTable('production_reel_consumptions'))->toBeTrue();

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
    ))->toThrow(RuntimeException::class, 'out of sync');

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
