<?php

use App\Models\StockReconciliation;
use App\Models\PurchaseItem;
use App\Models\StockAdjustment;
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


it('approves and posts signed batch adjustments with an immutable ledger', function () {
    $fx = stockReconciliationFixture();
    $service = app(StockReconciliationService::class);

    $reconciliation = $service->createSnapshot();
    $paper = $reconciliation->items->firstWhere('purchase_item_id', $fx['paperBatchId']);
    $corn = $reconciliation->items->firstWhere('purchase_item_id', $fx['cornBatchId']);

    $service->updateCount($reconciliation, $paper, 990, 'reel_weight_difference');
    $service->updateCount($reconciliation, $corn, 55, 'material_found');

    $service->submit($reconciliation);
    $approved = $service->approve($reconciliation->fresh());

    expect($approved->status)->toBe(StockReconciliation::STATUS_APPROVED)
        ->and((float) PurchaseItem::find($fx['paperBatchId'])->qty_kg_available)->toBe(1000.0)
        ->and((float) PurchaseItem::find($fx['cornBatchId'])->qty_available)->toBe(50.0);

    $adjustment = $service->post($approved);

    $paperBatch = PurchaseItem::findOrFail($fx['paperBatchId']);
    $cornBatch = PurchaseItem::findOrFail($fx['cornBatchId']);
    $reconciliation->refresh();

    expect($reconciliation->status)->toBe(StockReconciliation::STATUS_POSTED)
        ->and((float) $paperBatch->qty_kg_adjusted)->toBe(-10.0)
        ->and((float) $paperBatch->qty_kg_available)->toBe(990.0)
        ->and((float) $cornBatch->qty_adjusted)->toBe(5.0)
        ->and((float) $cornBatch->qty_available)->toBe(55.0)
        ->and($adjustment->items)->toHaveCount(2);

    $paperLine = $adjustment->items->firstWhere('purchase_item_id', $fx['paperBatchId']);
    $cornLine = $adjustment->items->firstWhere('purchase_item_id', $fx['cornBatchId']);

    expect((float) $paperLine->before_quantity)->toBe(1000.0)
        ->and((float) $paperLine->adjustment_quantity)->toBe(-10.0)
        ->and((float) $paperLine->after_quantity)->toBe(990.0)
        ->and((float) $paperLine->adjustment_value_usd)->toBe(-9.0)
        ->and((float) $cornLine->before_quantity)->toBe(50.0)
        ->and((float) $cornLine->adjustment_quantity)->toBe(5.0)
        ->and((float) $cornLine->after_quantity)->toBe(55.0)
        ->and(StockAdjustment::where('stock_reconciliation_id', $reconciliation->id)->count())->toBe(1);

    expect(fn () => $service->post($reconciliation->fresh()))
        ->toThrow(RuntimeException::class);
});

it('preserves legitimate stock movements after the snapshot by applying variance additively', function () {
    $fx = stockReconciliationFixture();
    $service = app(StockReconciliationService::class);

    $reconciliation = $service->createSnapshot();
    $paper = $reconciliation->items->firstWhere('purchase_item_id', $fx['paperBatchId']);
    $corn = $reconciliation->items->firstWhere('purchase_item_id', $fx['cornBatchId']);

    // Physical count at snapshot time found 10 kg less paper.
    $service->updateCount($reconciliation, $paper, 990, 'reel_weight_difference');
    $service->updateCount($reconciliation, $corn, 50);

    $service->submit($reconciliation);
    $service->approve($reconciliation->fresh());

    // A legitimate 100 kg production movement happens after counting but before posting.
    $paperBatch = PurchaseItem::findOrFail($fx['paperBatchId']);
    $paperBatch->qty_kg_used = 100;
    $paperBatch->save();

    expect((float) $paperBatch->fresh()->qty_kg_available)->toBe(900.0);

    $adjustment = $service->post($reconciliation->fresh());
    $paperBatch->refresh();
    $line = $adjustment->items->firstWhere('purchase_item_id', $fx['paperBatchId']);

    // Current 900 - original count variance 10 = 890. The 100 kg movement is preserved.
    expect((float) $paperBatch->qty_kg_available)->toBe(890.0)
        ->and((float) $line->before_quantity)->toBe(900.0)
        ->and((float) $line->adjustment_quantity)->toBe(-10.0)
        ->and((float) $line->after_quantity)->toBe(890.0);
});

it('blocks posting when an old negative variance would make the current batch negative', function () {
    $fx = stockReconciliationFixture();
    $service = app(StockReconciliationService::class);

    $reconciliation = $service->createSnapshot();
    $paper = $reconciliation->items->firstWhere('purchase_item_id', $fx['paperBatchId']);
    $corn = $reconciliation->items->firstWhere('purchase_item_id', $fx['cornBatchId']);

    $service->updateCount($reconciliation, $paper, 0, 'reel_weight_difference');
    $service->updateCount($reconciliation, $corn, 50);
    $service->submit($reconciliation);
    $service->approve($reconciliation->fresh());

    // Leave only 500 kg after legitimate production movement. Applying the
    // snapshot variance of -1000 kg would be invalid.
    $paperBatch = PurchaseItem::findOrFail($fx['paperBatchId']);
    $paperBatch->qty_kg_used = 500;
    $paperBatch->save();

    expect(fn () => $service->post($reconciliation->fresh()))
        ->toThrow(RuntimeException::class, 'would make batch');

    expect($reconciliation->fresh()->status)->toBe(StockReconciliation::STATUS_APPROVED)
        ->and((float) PurchaseItem::find($fx['paperBatchId'])->qty_kg_available)->toBe(500.0)
        ->and(StockAdjustment::where('stock_reconciliation_id', $reconciliation->id)->count())->toBe(0);
});

it('rejects a submitted reconciliation without changing inventory', function () {
    $fx = stockReconciliationFixture();
    $service = app(StockReconciliationService::class);

    $reconciliation = $service->createSnapshot();
    foreach ($reconciliation->items as $item) {
        $service->updateCount($reconciliation, $item, (float) $item->system_quantity);
    }
    $service->submit($reconciliation);

    $rejected = $service->reject($reconciliation->fresh(), 'Warehouse requested a recount.');

    expect($rejected->status)->toBe(StockReconciliation::STATUS_REJECTED)
        ->and($rejected->rejection_reason)->toBe('Warehouse requested a recount.')
        ->and((float) PurchaseItem::find($fx['paperBatchId'])->qty_kg_available)->toBe(1000.0)
        ->and((float) PurchaseItem::find($fx['cornBatchId'])->qty_available)->toBe(50.0);
});


it('cancels only an in-progress count without changing inventory', function () {
    $fx = stockReconciliationFixture();
    $service = app(StockReconciliationService::class);

    $reconciliation = $service->createSnapshot();
    $cancelled = $service->cancel($reconciliation);

    expect($cancelled->status)->toBe(StockReconciliation::STATUS_CANCELLED)
        ->and((float) PurchaseItem::find($fx['paperBatchId'])->qty_kg_available)->toBe(1000.0)
        ->and((float) PurchaseItem::find($fx['cornBatchId'])->qty_available)->toBe(50.0);

    expect(fn () => $service->cancel($cancelled->fresh()))
        ->toThrow(RuntimeException::class, 'still being counted');
});
