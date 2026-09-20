<?php

use App\Models\StockReconciliation;
use App\Services\StockReconciliationService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function stockReconciliationFixture(): array
{
    $user = User::factory()->create();
    Auth::login($user);
    $now = now();

    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Cycle Count Raw Materials',
        'slug' => 'cycle-count-raw-materials',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $paperId = DB::table('products')->insertGetId([
        'name' => 'Cycle Count Kraft Roll',
        'slug' => 'cycle-count-kraft-roll',
        'unit' => 'roll',
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $cornId = DB::table('products')->insertGetId([
        'name' => 'Cycle Count Corn Flour',
        'slug' => 'cycle-count-corn-flour',
        'unit' => 'kg',
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $currencyId = DB::table('currencies')->insertGetId([
        'name' => 'US Dollar',
        'code' => 'USD',
        'symbol' => '$',
        'exchange_rate' => 1,
        'is_default' => 1,
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $supplierId = DB::table('accounts')->insertGetId([
        'name' => 'Cycle Count Supplier',
        'code' => 'CYCLE-SUP',
        'account_type' => 'supplier',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $purchaseId = DB::table('purchases')->insertGetId([
        'purchase_no' => 'PO-CYCLE-001',
        'supplier_id' => $supplierId,
        'currency_id' => $currencyId,
        'status' => 'arrived',
        'exchange_rate' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $paperBatchId = DB::table('purchase_items')->insertGetId([
        'purchase_id' => $purchaseId,
        'product_id' => $paperId,
        'purchase_currency_id' => $currencyId,
        'qty' => 10,
        'qty_available' => 10,
        'qty_sold' => 0,
        'qty_used' => 0,
        'qty_wasted' => 0,
        'unit' => 'roll',
        'kg_per_roll' => 100,
        'qty_kg' => 1000,
        'qty_kg_available' => 1000,
        'qty_kg_sold' => 0,
        'qty_kg_used' => 0,
        'qty_kg_wasted' => 0,
        'total_weight_kg' => 1000,
        'landed_cost_per_kg' => 0.90,
        'batch_no' => 'KRAFT-001',
        'usd_total' => 900,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $cornBatchId = DB::table('purchase_items')->insertGetId([
        'purchase_id' => $purchaseId,
        'product_id' => $cornId,
        'purchase_currency_id' => $currencyId,
        'qty' => 50,
        'qty_available' => 50,
        'qty_sold' => 0,
        'qty_used' => 0,
        'qty_wasted' => 0,
        'unit' => 'kg',
        'kg_per_roll' => 0,
        'qty_kg' => 0,
        'qty_kg_available' => 0,
        'qty_kg_sold' => 0,
        'qty_kg_used' => 0,
        'qty_kg_wasted' => 0,
        'total_weight_kg' => 0,
        'cost_per_unit' => 0.40,
        'batch_no' => 'CORN-001',
        'usd_total' => 20,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return compact('user', 'paperId', 'cornId', 'paperBatchId', 'cornBatchId');
}

it('creates a frozen batch-level snapshot using kg for roll stock and native units otherwise', function () {
    $fx = stockReconciliationFixture();

    $reconciliation = app(StockReconciliationService::class)
        ->createSnapshot(today()->toDateString(), 'Weekly cycle count');

    expect($reconciliation->status)->toBe(StockReconciliation::STATUS_COUNTING)
        ->and($reconciliation->items)->toHaveCount(2);

    $paper = $reconciliation->items->firstWhere('purchase_item_id', $fx['paperBatchId']);
    $corn = $reconciliation->items->firstWhere('purchase_item_id', $fx['cornBatchId']);

    expect($paper)->not->toBeNull()
        ->and($paper->inventory_unit)->toBe('kg')
        ->and((float) $paper->system_quantity)->toBe(1000.0)
        ->and((float) $paper->cost_per_unit_usd)->toBe(0.90)
        ->and($corn)->not->toBeNull()
        ->and($corn->inventory_unit)->toBe('kg')
        ->and((float) $corn->system_quantity)->toBe(50.0)
        ->and((float) $corn->cost_per_unit_usd)->toBe(0.40);
});

it('saves physical counts and submits without changing FIFO batch stock', function () {
    $fx = stockReconciliationFixture();
    $service = app(StockReconciliationService::class);

    $reconciliation = $service->createSnapshot();
    $paper = $reconciliation->items->firstWhere('purchase_item_id', $fx['paperBatchId']);
    $corn = $reconciliation->items->firstWhere('purchase_item_id', $fx['cornBatchId']);

    $paperBefore = DB::table('purchase_items')->where('id', $fx['paperBatchId'])->first();
    $cornBefore = DB::table('purchase_items')->where('id', $fx['cornBatchId'])->first();

    $service->updateCount($reconciliation, $paper, 995.0);
    $service->updateCount($reconciliation, $corn, 50.0);

    expect(fn () => $service->submit($reconciliation))
        ->toThrow(RuntimeException::class, 'A variance reason is required');

    $service->updateCount(
        $reconciliation,
        $paper->fresh(),
        995.0,
        'reel_weight_difference',
        'Warehouse scale count'
    );

    $submitted = $service->submit($reconciliation);

    expect($submitted->status)->toBe(StockReconciliation::STATUS_SUBMITTED)
        ->and((float) $submitted->items->firstWhere('purchase_item_id', $fx['paperBatchId'])->variance_quantity)->toBe(-5.0)
        ->and((float) $submitted->items->firstWhere('purchase_item_id', $fx['paperBatchId'])->variance_value_usd)->toBe(-4.5);

    $paperAfter = DB::table('purchase_items')->where('id', $fx['paperBatchId'])->first();
    $cornAfter = DB::table('purchase_items')->where('id', $fx['cornBatchId'])->first();

    expect((float) $paperAfter->qty_kg_available)->toBe((float) $paperBefore->qty_kg_available)
        ->and((float) $paperAfter->qty_available)->toBe((float) $paperBefore->qty_available)
        ->and((float) $cornAfter->qty_available)->toBe((float) $cornBefore->qty_available);
});

it('does not allow editing a submitted reconciliation', function () {
    stockReconciliationFixture();
    $service = app(StockReconciliationService::class);

    $reconciliation = $service->createSnapshot();

    foreach ($reconciliation->items as $item) {
        $service->updateCount(
            $reconciliation,
            $item,
            (float) $item->system_quantity
        );
    }

    $service->submit($reconciliation);

    expect(fn () => $service->updateCount(
        $reconciliation->fresh(),
        $reconciliation->items->first()->fresh(),
        1
    ))->toThrow(RuntimeException::class, 'Only a reconciliation that is still being counted can be edited');
});
