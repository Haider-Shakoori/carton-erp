<?php

use App\Models\PurchaseItem;
use App\Models\PurchaseItemReel;
use App\Models\User;
use App\Http\Controllers\Admin\ReelInventoryController;
use App\Services\ReelInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function reelMeasurementReadinessFixture(): array
{
    $user = User::factory()->create(['is_active' => true]);
    Auth::login($user);
    $now = now();

    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Measurement Readiness Paper',
        'slug' => 'measurement-readiness-paper',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $materialId = DB::table('products')->insertGetId([
        'name' => 'Measurement Readiness Kraft',
        'slug' => 'measurement-readiness-kraft',
        'unit' => 'roll',
        'default_kg_per_roll' => 500,
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $currencyId = DB::table('currencies')->insertGetId([
        'name' => 'Measurement Readiness USD',
        'code' => 'MRUSD',
        'symbol' => '$',
        'exchange_rate' => 1,
        'is_default' => 1,
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $supplierId = DB::table('accounts')->insertGetId([
        'name' => 'Measurement Readiness Supplier',
        'code' => 'MR-SUP',
        'account_type' => 'supplier',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $purchaseId = DB::table('purchases')->insertGetId([
        'purchase_no' => 'PO-MR-001',
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
        'batch_no' => 'MR-BATCH-001',
    ]);

    $service = app(ReelInventoryService::class);
    $reels = $service->initializeBatch($batch);

    return compact('user', 'batch', 'reels', 'service');
}

it('marks a newly tracked batch as weighing incomplete until every active reel is measured', function () {
    $fx = reelMeasurementReadinessFixture();

    $state = $fx['service']->measurementReadiness($fx['batch']->fresh());

    expect($state['state'])->toBe('incomplete')
        ->and($state['active_count'])->toBe(2)
        ->and($state['fresh_count'])->toBe(0)
        ->and($state['unmeasured_count'])->toBe(2)
        ->and($state['stale_count'])->toBe(0)
        ->and($state['complete'])->toBeFalse()
        ->and($state['needs_reconciliation'])->toBeFalse()
        ->and($state['can_rebaseline'])->toBeFalse();

    $fx['service']->recordMeasurement($fx['reels'][0], 500, 'First reel');

    $state = $fx['service']->measurementReadiness($fx['batch']->fresh());

    expect($state['state'])->toBe('incomplete')
        ->and($state['fresh_count'])->toBe(1)
        ->and($state['unmeasured_count'])->toBe(1);
});

it('marks a complete fresh weighing as aligned when it matches authoritative stock', function () {
    $fx = reelMeasurementReadinessFixture();

    foreach ($fx['reels'] as $reel) {
        $fx['service']->recordMeasurement($reel, 500, 'Matched physical count');
    }

    $state = $fx['service']->measurementReadiness($fx['batch']->fresh());

    expect($state['state'])->toBe('aligned')
        ->and($state['label'])->toBe('Fresh & aligned')
        ->and($state['fresh_count'])->toBe(2)
        ->and($state['complete'])->toBeTrue()
        ->and($state['aligned'])->toBeTrue()
        ->and($state['needs_reconciliation'])->toBeFalse()
        ->and($state['can_rebaseline'])->toBeTrue()
        ->and((float) $state['measured_total_kg'])->toBe(1000.0)
        ->and((float) $state['variance_kg'])->toBe(0.0);
});

it('marks fresh physical variance as ready for controlled reconciliation', function () {
    $fx = reelMeasurementReadinessFixture();

    $fx['service']->recordMeasurement($fx['reels'][0], 480, 'Short reel');
    $fx['service']->recordMeasurement($fx['reels'][1], 500, 'Full reel');

    $summary = $fx['service']->summary($fx['batch']->fresh());
    $state = $summary['measurement_readiness'];

    expect($state['state'])->toBe('reconcile')
        ->and($state['label'])->toBe('Ready to reconcile')
        ->and($state['complete'])->toBeTrue()
        ->and($state['aligned'])->toBeFalse()
        ->and($state['needs_reconciliation'])->toBeTrue()
        ->and($state['can_rebaseline'])->toBeFalse()
        ->and((float) $state['measured_total_kg'])->toBe(980.0)
        ->and((float) $state['variance_kg'])->toBe(-20.0);
});

it('invalidates previously fresh measurements after a later stock movement', function () {
    $fx = reelMeasurementReadinessFixture();

    foreach ($fx['reels'] as $reel) {
        $fx['service']->recordMeasurement($reel, 490, 'Before stock movement');
    }

    DB::table('purchase_items')
        ->where('id', $fx['batch']->id)
        ->update(['updated_at' => now()->addMinute()]);

    $state = $fx['service']->measurementReadiness($fx['batch']->fresh());

    expect($state['state'])->toBe('stale')
        ->and($state['fresh_count'])->toBe(0)
        ->and($state['stale_count'])->toBe(2)
        ->and($state['unmeasured_count'])->toBe(0)
        ->and($state['complete'])->toBeFalse()
        ->and($state['needs_reconciliation'])->toBeFalse()
        ->and($state['can_rebaseline'])->toBeFalse();
});

it('does not require an already consumed zero-weight reel to block current weighing readiness', function () {
    $fx = reelMeasurementReadinessFixture();

    $consumed = $fx['reels'][0]->fresh();
    $consumed->update([
        'system_remaining_weight_kg' => 0,
        'status' => PurchaseItemReel::STATUS_CONSUMED,
        'depleted_at' => now(),
    ]);

    $batch = $fx['batch']->fresh();
    $batch->qty_kg_used = 500;
    $batch->qty_used = 1;
    $batch->save();

    $active = $fx['reels'][1]->fresh();
    $fx['service']->recordMeasurement($active, 500, 'Only active reel');

    $state = $fx['service']->measurementReadiness($batch->fresh());

    expect($state['state'])->toBe('aligned')
        ->and($state['active_count'])->toBe(1)
        ->and($state['fresh_count'])->toBe(1)
        ->and($state['unmeasured_count'])->toBe(0)
        ->and($state['stale_count'])->toBe(0)
        ->and((float) $state['measured_total_kg'])->toBe(500.0)
        ->and((float) $state['batch_available_kg'])->toBe(500.0);
});

it('renders measurement readiness on the reel batch workspace', function () {
    $fx = reelMeasurementReadinessFixture();

    $view = app(ReelInventoryController::class)
        ->show($fx['batch']->fresh());
    $html = $view->render();

    expect($html)
        ->toContain('Measurement Readiness: Weighing incomplete')
        ->toContain('2 of 2 active reel(s) still need a measurement.');
});
