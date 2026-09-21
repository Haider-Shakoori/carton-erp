<?php

use App\Models\PurchaseItem;
use App\Models\StockReconciliation;
use App\Models\User;
use App\Services\ReelInventoryService;
use App\Services\StockReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function reelReconciliationFixture(): array
{
    $user = User::factory()->create(['is_active' => true]);
    Auth::login($user);
    $now = now();

    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Reel Reconciliation Paper',
        'slug' => 'reel-reconciliation-paper',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $materialId = DB::table('products')->insertGetId([
        'name' => 'Reel Reconciliation Kraft 150 GSM',
        'slug' => 'reel-reconciliation-kraft-150-gsm',
        'unit' => 'roll',
        'default_kg_per_roll' => 500,
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $currencyId = DB::table('currencies')->insertGetId([
        'name' => 'Reel Reconciliation USD',
        'code' => 'RRUSD',
        'symbol' => '$',
        'exchange_rate' => 1,
        'is_default' => 1,
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $supplierId = DB::table('accounts')->insertGetId([
        'name' => 'Reel Reconciliation Supplier',
        'code' => 'RR-SUP',
        'account_type' => 'supplier',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $purchaseId = DB::table('purchases')->insertGetId([
        'purchase_no' => 'PO-RR-001',
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
        'qty' => 2,
        'unit' => 'roll',
        'kg_per_roll' => 500,
        'unit_price' => 450,
        'usd_unit_price' => 450,
        'usd_total' => 900,
        'usd_expense_per_item' => 0,
        'landed_cost_per_kg' => 0.90,
        'batch_no' => 'RR-BATCH-001',
    ]);

    $reels = app(ReelInventoryService::class)->initializeBatch($batch);

    return compact('user', 'batch', 'reels');
}

it('creates a single-batch draft reconciliation prefilled from fresh reel measurements without changing stock', function () {
    $fx = reelReconciliationFixture();
    $reelService = app(ReelInventoryService::class);

    $reelService->recordMeasurement($fx['reels'][0], 480, 'Scale count');
    $reelService->recordMeasurement($fx['reels'][1], 500, 'Scale count');

    $beforeKg = $fx['batch']->fresh()->availableKg();

    $reconciliation = app(StockReconciliationService::class)
        ->createFromReelMeasurements($fx['batch']);

    $item = $reconciliation->items->firstOrFail();

    expect($reconciliation->status)->toBe(StockReconciliation::STATUS_COUNTING)
        ->and($reconciliation->items)->toHaveCount(1)
        ->and((int) $item->purchase_item_id)->toBe((int) $fx['batch']->id)
        ->and($item->inventory_unit)->toBe('kg')
        ->and((float) $item->system_quantity)->toBe(1000.0)
        ->and((float) $item->physical_quantity)->toBe(980.0)
        ->and((float) $item->variance_quantity)->toBe(-20.0)
        ->and((float) $item->variance_value_usd)->toBe(-18.0)
        ->and($item->reason_code)->toBe('reel_weight_difference')
        ->and($item->notes)->toContain('Prefilled from 2 current reel measurement')
        ->and($fx['batch']->fresh()->availableKg())->toBe($beforeKg);
});

it('requires every active reel to have a fresh measurement before handoff', function () {
    $fx = reelReconciliationFixture();
    app(ReelInventoryService::class)->recordMeasurement(
        $fx['reels'][0],
        480,
        'Only one reel weighed'
    );

    expect(fn () => app(StockReconciliationService::class)
        ->createFromReelMeasurements($fx['batch']))
        ->toThrow(
            RuntimeException::class,
            'Every active physical reel must be weighed'
        );

    expect(StockReconciliation::count())->toBe(0)
        ->and($fx['batch']->fresh()->availableKg())->toBe(1000.0);
});

it('rejects stale reel measurements after a later stock change', function () {
    $fx = reelReconciliationFixture();
    $reelService = app(ReelInventoryService::class);

    foreach ($fx['reels'] as $reel) {
        $reelService->recordMeasurement($reel, 490, 'Current count');
    }

    DB::table('purchase_items')
        ->where('id', $fx['batch']->id)
        ->update(['updated_at' => now()->addMinute()]);

    expect(fn () => app(StockReconciliationService::class)
        ->createFromReelMeasurements($fx['batch']->fresh()))
        ->toThrow(
            RuntimeException::class,
            'Reel measurements are stale'
        );

    expect(StockReconciliation::count())->toBe(0);
});

it('does not create reconciliation noise when reel measurements already match ERP stock', function () {
    $fx = reelReconciliationFixture();
    $reelService = app(ReelInventoryService::class);

    foreach ($fx['reels'] as $reel) {
        $reelService->recordMeasurement($reel, 500, 'Matched count');
    }

    expect(fn () => app(StockReconciliationService::class)
        ->createFromReelMeasurements($fx['batch']))
        ->toThrow(
            RuntimeException::class,
            'already matches the authoritative batch balance'
        );

    expect(StockReconciliation::count())->toBe(0);
});

it('prevents duplicate open reconciliations for the same reel batch', function () {
    $fx = reelReconciliationFixture();
    $reelService = app(ReelInventoryService::class);
    $service = app(StockReconciliationService::class);

    $reelService->recordMeasurement($fx['reels'][0], 490, 'Scale count');
    $reelService->recordMeasurement($fx['reels'][1], 500, 'Scale count');

    $first = $service->createFromReelMeasurements($fx['batch']);

    expect(fn () => $service->createFromReelMeasurements($fx['batch']))
        ->toThrow(
            RuntimeException::class,
            'already included in open reconciliation'
        );

    expect(StockReconciliation::count())->toBe(1)
        ->and($first->items)->toHaveCount(1)
        ->and($fx['batch']->fresh()->availableKg())->toBe(1000.0);
});

it('keeps the existing approval and posting controls after reel handoff', function () {
    $fx = reelReconciliationFixture();
    $reelService = app(ReelInventoryService::class);
    $service = app(StockReconciliationService::class);

    $reelService->recordMeasurement($fx['reels'][0], 490, 'Scale count');
    $reelService->recordMeasurement($fx['reels'][1], 500, 'Scale count');

    $reconciliation = $service->createFromReelMeasurements($fx['batch']);

    expect($fx['batch']->fresh()->availableKg())->toBe(1000.0);

    $submitted = $service->submit($reconciliation);
    expect($submitted->status)->toBe(StockReconciliation::STATUS_SUBMITTED)
        ->and($fx['batch']->fresh()->availableKg())->toBe(1000.0);

    $approved = $service->approve($submitted->fresh());
    expect($approved->status)->toBe(StockReconciliation::STATUS_APPROVED)
        ->and($fx['batch']->fresh()->availableKg())->toBe(1000.0);

    $service->post($approved->fresh());

    expect($fx['batch']->fresh()->availableKg())->toBe(990.0)
        ->and($reconciliation->fresh()->status)
        ->toBe(StockReconciliation::STATUS_POSTED);
});
