<?php

use App\Http\Controllers\Admin\StockReconciliationController;
use App\Models\PurchaseItem;
use App\Models\StockVarianceInvestigation;
use App\Models\User;
use App\Services\StockReconciliationService;
use App\Services\StockVarianceInvestigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function varianceInvestigationFixture(): array
{
    $user = User::factory()->create(['is_active' => true]);
    Auth::login($user);
    $now = now();

    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Investigation Raw Materials',
        'slug' => 'investigation-raw-materials',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $materialId = DB::table('products')->insertGetId([
        'name' => 'Investigation Corn Flour',
        'slug' => 'investigation-corn-flour',
        'unit' => 'kg',
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $currencyId = DB::table('currencies')->insertGetId([
        'name' => 'Investigation USD',
        'code' => 'IUSD',
        'symbol' => '$',
        'exchange_rate' => 1,
        'is_default' => 0,
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $supplierId = DB::table('accounts')->insertGetId([
        'name' => 'Investigation Supplier',
        'code' => 'INV-SUP',
        'account_type' => 'supplier',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $purchaseId = DB::table('purchases')->insertGetId([
        'purchase_no' => 'PO-INV-001',
        'supplier_id' => $supplierId,
        'currency_id' => $currencyId,
        'status' => 'arrived',
        'exchange_rate' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $batchId = DB::table('purchase_items')->insertGetId([
        'purchase_id' => $purchaseId,
        'product_id' => $materialId,
        'purchase_currency_id' => $currencyId,
        'qty' => 50,
        'qty_available' => 50,
        'qty_sold' => 0,
        'qty_used' => 0,
        'qty_wasted' => 0,
        'unit' => 'kg',
        'cost_per_unit' => 1,
        'usd_total' => 50,
        'batch_no' => 'INV-CORN-001',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return compact('user', 'materialId', 'batchId');
}

function postInvestigationVariance(string $reason = 'unknown'): array
{
    $fx = varianceInvestigationFixture();
    $service = app(StockReconciliationService::class);

    $reconciliation = $service->createSnapshot(
        today()->toDateString(),
        'Variance investigation fixture',
        [$fx['materialId']]
    );
    $item = $reconciliation->items->first();

    $service->updateCount(
        $reconciliation,
        $item,
        40,
        $reason,
        '10 kg physical shortage'
    );
    $service->submit($reconciliation);
    $service->approve($reconciliation->fresh());
    $adjustment = $service->post($reconciliation->fresh());

    return $fx + compact('reconciliation', 'adjustment');
}

it('creates the investigation schema and auto-opens unknown posted variances', function () {
    expect(Schema::hasTable('stock_variance_investigations'))->toBeTrue()
        ->and(Schema::hasTable('stock_variance_investigation_events'))->toBeTrue();

    $fx = postInvestigationVariance('unknown');
    $line = $fx['adjustment']->items->first()->fresh(['investigation.events']);

    expect((float) PurchaseItem::findOrFail($fx['batchId'])->qty_available)->toBe(40.0)
        ->and($line->reason_code)->toBe('unknown')
        ->and($line->investigation)->not->toBeNull()
        ->and($line->investigation->status)->toBe(StockVarianceInvestigation::STATUS_OPEN)
        ->and($line->investigation->events)->toHaveCount(1)
        ->and($line->investigation->events->first()->event_type)->toBe('opened')
        ->and($line->investigation->due_date?->toDateString())
        ->toBe(now()->addDays(7)->toDateString());
});

it('does not auto-open ordinary variance reasons but allows a manual investigation', function () {
    $fx = postInvestigationVariance('measurement_difference');
    $line = $fx['adjustment']->items->first()->fresh(['investigation']);

    expect($line->investigation)->toBeNull();

    $owner = User::factory()->create(['is_active' => true]);
    $case = app(StockVarianceInvestigationService::class)->openForItem(
        $line->load('adjustment'),
        $owner->id,
        'Management requested a root-cause review.'
    );

    expect($case->status)->toBe(StockVarianceInvestigation::STATUS_INVESTIGATING)
        ->and((int) $case->assigned_to)->toBe((int) $owner->id)
        ->and($case->events)->toHaveCount(1)
        ->and($case->events->first()->event_type)->toBe('opened');
});

it('tracks assignment root cause corrective action and immutable investigation events', function () {
    $fx = postInvestigationVariance('unknown');
    $case = $fx['adjustment']->items->first()->investigation;
    $owner = User::factory()->create(['is_active' => true]);

    $updated = app(StockVarianceInvestigationService::class)->update($case, [
        'assigned_to' => $owner->id,
        'due_date' => now()->addDays(3)->toDateString(),
        'root_cause_code' => 'data_entry_error',
        'root_cause_details' => 'Warehouse issue quantity was entered against the wrong batch.',
        'investigation_notes' => 'Batch labels and issue sheet were compared.',
        'corrective_action' => 'Require batch barcode verification before posting material issue.',
        'event_note' => 'Initial root-cause review completed.',
    ]);

    expect($updated->status)->toBe(StockVarianceInvestigation::STATUS_INVESTIGATING)
        ->and((int) $updated->assigned_to)->toBe((int) $owner->id)
        ->and($updated->root_cause_code)->toBe('data_entry_error')
        ->and($updated->events)->toHaveCount(2)
        ->and($updated->events->last()->event_type)->toBe('updated')
        ->and($updated->events->last()->metadata['after']['assigned_to'])->toBe($owner->id);
});

it('requires documented corrective closure and removes resolved cases from unresolved reporting', function () {
    $fx = postInvestigationVariance('unknown');
    $case = $fx['adjustment']->items->first()->investigation;
    $service = app(StockVarianceInvestigationService::class);

    expect(fn () => $service->resolve($case, [
        'root_cause_code' => 'counting_error',
        'root_cause_details' => 'Count was taken before the final pallet was included.',
        'corrective_action' => '',
        'resolution_notes' => 'Count procedure reviewed.',
    ]))->toThrow(RuntimeException::class, 'corrective action');

    $resolved = $service->resolve($case->fresh(), [
        'root_cause_code' => 'counting_error',
        'root_cause_details' => 'Count was taken before the final pallet was included.',
        'corrective_action' => 'Warehouse lead must sign the zone-complete checklist before count submission.',
        'resolution_notes' => 'Procedure updated and warehouse staff briefed.',
    ]);

    expect($resolved->status)->toBe(StockVarianceInvestigation::STATUS_RESOLVED)
        ->and($resolved->resolved_by)->toBe(Auth::id())
        ->and($resolved->resolved_at)->not->toBeNull()
        ->and($resolved->events->last()->event_type)->toBe('resolved')
        ->and($fx['adjustment']->items->first()->fresh()->reason_code)->toBe('unknown');

    $request = Request::create(
        '/admin/stock-reconciliations/report',
        'GET',
        ['unresolved' => 1]
    );
    $view = app(StockReconciliationController::class)->report($request);
    $data = $view->getData();

    expect($data['summary']['unresolved_lines'])->toBe(0)
        ->and($data['rows']->total())->toBe(0);

    expect(fn () => $service->update($resolved->fresh(), [
        'investigation_notes' => 'Attempt to alter a closed case.',
    ]))->toThrow(RuntimeException::class, 'read-only');
});

it('calculates aging and overdue state without modifying the original stock adjustment', function () {
    $fx = postInvestigationVariance('unknown');
    $case = $fx['adjustment']->items->first()->investigation;

    DB::table('stock_variance_investigations')
        ->where('id', $case->id)
        ->update([
            'opened_at' => now()->subDays(35),
            'due_date' => now()->subDays(20)->toDateString(),
            'updated_at' => now(),
        ]);

    $aged = $case->fresh();

    expect($aged->age_days)->toBeGreaterThanOrEqual(35)
        ->and($aged->aging_bucket)->toBe('31+ days')
        ->and($aged->is_overdue)->toBeTrue()
        ->and($fx['adjustment']->items->first()->fresh()->adjustment_quantity)->toBe('-10.000000');
});
