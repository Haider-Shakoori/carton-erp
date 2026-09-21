<?php

use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockVarianceInvestigation;
use App\Models\User;
use App\Services\InventoryPreventionIntelligenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function preventionIntelligenceFixture(): array
{
    $user = User::factory()->create(['is_active' => true]);
    Auth::login($user);
    $now = now();

    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Prevention Intelligence Materials',
        'slug' => 'prevention-intelligence-materials',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $materialA = DB::table('products')->insertGetId([
        'name' => 'Prevention Kraft Paper',
        'slug' => 'prevention-kraft-paper',
        'unit' => 'kg',
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $materialB = DB::table('products')->insertGetId([
        'name' => 'Prevention Corn Flour',
        'slug' => 'prevention-corn-flour',
        'unit' => 'kg',
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $currencyId = DB::table('currencies')->insertGetId([
        'name' => 'Prevention USD',
        'code' => 'PVUSD',
        'symbol' => '$',
        'exchange_rate' => 1,
        'is_default' => 0,
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $supplierId = DB::table('accounts')->insertGetId([
        'name' => 'Prevention Supplier',
        'code' => 'PREV-SUP',
        'account_type' => 'supplier',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $purchaseId = DB::table('purchases')->insertGetId([
        'purchase_no' => 'PO-PREV-001',
        'supplier_id' => $supplierId,
        'currency_id' => $currencyId,
        'status' => 'arrived',
        'exchange_rate' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $batchA = DB::table('purchase_items')->insertGetId([
        'purchase_id' => $purchaseId,
        'product_id' => $materialA,
        'purchase_currency_id' => $currencyId,
        'qty' => 1000,
        'qty_available' => 1000,
        'qty_sold' => 0,
        'qty_used' => 0,
        'qty_wasted' => 0,
        'unit' => 'kg',
        'cost_per_unit' => 1,
        'usd_total' => 1000,
        'batch_no' => 'PREV-A',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $batchB = DB::table('purchase_items')->insertGetId([
        'purchase_id' => $purchaseId,
        'product_id' => $materialB,
        'purchase_currency_id' => $currencyId,
        'qty' => 500,
        'qty_available' => 500,
        'qty_sold' => 0,
        'qty_used' => 0,
        'qty_wasted' => 0,
        'unit' => 'kg',
        'cost_per_unit' => 1,
        'usd_total' => 500,
        'batch_no' => 'PREV-B',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return compact(
        'user',
        'materialA',
        'materialB',
        'batchA',
        'batchB'
    );
}

function createPreventionVarianceCase(
    array $fx,
    int $productId,
    int $batchId,
    int $daysAgo,
    string $rootCause,
    string $status = StockVarianceInvestigation::STATUS_RESOLVED,
    ?int $resolvedDaysAgo = null,
    float $adjustmentQuantity = -10,
    float $costPerUnit = 1
): StockVarianceInvestigation {
    static $sequence = 1;

    $adjustmentDate = now()->subDays($daysAgo);
    $adjustment = StockAdjustment::create([
        'adjustment_no' => 'SA-PREV-'.str_pad((string) $sequence++, 4, '0', STR_PAD_LEFT),
        'stock_reconciliation_id' => null,
        'adjustment_date' => $adjustmentDate->toDateString(),
        'type' => 'reconciliation',
        'status' => 'posted',
        'notes' => 'Prevention intelligence fixture',
        'created_by' => $fx['user']->id,
        'posted_by' => $fx['user']->id,
        'posted_at' => $adjustmentDate,
    ]);

    $line = StockAdjustmentItem::create([
        'stock_adjustment_id' => $adjustment->id,
        'product_id' => $productId,
        'purchase_item_id' => $batchId,
        'inventory_unit' => 'kg',
        'before_quantity' => 100,
        'adjustment_quantity' => $adjustmentQuantity,
        'after_quantity' => 100 + $adjustmentQuantity,
        'cost_per_unit_usd' => $costPerUnit,
        'adjustment_value_usd' => $adjustmentQuantity * $costPerUnit,
        'reason_code' => 'unknown',
        'notes' => 'Prevention intelligence variance',
    ]);

    $resolvedAt = null;
    if ($status === StockVarianceInvestigation::STATUS_RESOLVED) {
        $resolvedAt = now()->subDays($resolvedDaysAgo ?? max($daysAgo - 2, 0));
    }

    return StockVarianceInvestigation::create([
        'stock_adjustment_item_id' => $line->id,
        'status' => $status,
        'assigned_to' => $fx['user']->id,
        'due_date' => $adjustmentDate->copy()->addDays(7)->toDateString(),
        'root_cause_code' => $rootCause,
        'root_cause_details' => 'Confirmed '.$rootCause.' during investigation.',
        'investigation_notes' => 'Fixture investigation notes.',
        'corrective_action' => 'Fixture corrective action for '.$rootCause.'.',
        'resolution_notes' => $resolvedAt
            ? 'Fixture resolution completed.'
            : null,
        'opened_by' => $fx['user']->id,
        'opened_at' => $adjustmentDate,
        'started_at' => $adjustmentDate,
        'resolved_by' => $resolvedAt ? $fx['user']->id : null,
        'resolved_at' => $resolvedAt,
    ]);
}

it('detects a recurring material and root-cause combination at the configured threshold', function () {
    $fx = preventionIntelligenceFixture();

    createPreventionVarianceCase(
        $fx,
        $fx['materialA'],
        $fx['batchA'],
        80,
        'counting_error',
        resolvedDaysAgo: 75
    );
    createPreventionVarianceCase(
        $fx,
        $fx['materialA'],
        $fx['batchA'],
        50,
        'counting_error',
        resolvedDaysAgo: 45
    );
    createPreventionVarianceCase(
        $fx,
        $fx['materialA'],
        $fx['batchA'],
        20,
        'counting_error',
        StockVarianceInvestigation::STATUS_INVESTIGATING
    );

    // Same material, different root cause must remain a separate pattern.
    createPreventionVarianceCase(
        $fx,
        $fx['materialA'],
        $fx['batchA'],
        10,
        'data_entry_error',
        StockVarianceInvestigation::STATUS_INVESTIGATING
    );

    $analysis = app(InventoryPreventionIntelligenceService::class)->analyze(
        now()->subDays(120)->toDateString(),
        today()->toDateString()
    );

    $pattern = $analysis['patterns']->firstWhere(
        'root_cause_code',
        'counting_error'
    );

    expect($pattern)->not->toBeNull()
        ->and($pattern['product_id'])->toBe($fx['materialA'])
        ->and($pattern['occurrences'])->toBe(3)
        ->and($pattern['is_recurring'])->toBeTrue()
        ->and($pattern['active_cases'])->toBe(1)
        ->and($analysis['summary']['recurring_patterns'])->toBe(1)
        ->and($analysis['flags']->where(
            'type',
            'recurring_root_cause'
        )->count())->toBe(1);
});

it('keeps different materials and different root causes as distinct prevention patterns', function () {
    $fx = preventionIntelligenceFixture();

    createPreventionVarianceCase(
        $fx,
        $fx['materialA'],
        $fx['batchA'],
        40,
        'data_entry_error',
        StockVarianceInvestigation::STATUS_INVESTIGATING
    );
    createPreventionVarianceCase(
        $fx,
        $fx['materialB'],
        $fx['batchB'],
        35,
        'data_entry_error',
        StockVarianceInvestigation::STATUS_INVESTIGATING
    );
    createPreventionVarianceCase(
        $fx,
        $fx['materialA'],
        $fx['batchA'],
        30,
        'counting_error',
        StockVarianceInvestigation::STATUS_INVESTIGATING
    );

    $analysis = app(InventoryPreventionIntelligenceService::class)->analyze(
        now()->subDays(90)->toDateString(),
        today()->toDateString()
    );

    expect($analysis['patterns'])->toHaveCount(3)
        ->and($analysis['summary']['recurring_patterns'])->toBe(0)
        ->and($analysis['material_hotspots'])->toHaveCount(2);
});

it('marks a completed post-resolution window with no later variance as no recurrence observed', function () {
    $fx = preventionIntelligenceFixture();

    $case = createPreventionVarianceCase(
        $fx,
        $fx['materialA'],
        $fx['batchA'],
        70,
        'measurement_scale_error',
        resolvedDaysAgo: 60
    );

    $analysis = app(InventoryPreventionIntelligenceService::class)->analyze(
        now()->subDays(120)->toDateString(),
        today()->toDateString(),
        $fx['materialA']
    );

    $row = $analysis['effectiveness']->firstWhere(
        'investigation_id',
        $case->id
    );

    expect($row)->not->toBeNull()
        ->and($row['post_window_complete'])->toBeTrue()
        ->and($row['pre_lines'])->toBe(1)
        ->and($row['post_lines'])->toBe(0)
        ->and($row['confirmed_recurrences'])->toBe(0)
        ->and($row['signal'])->toBe('no_recurrence');
});

it('raises a critical flag when the same root cause returns after corrective action', function () {
    $fx = preventionIntelligenceFixture();

    $first = createPreventionVarianceCase(
        $fx,
        $fx['materialA'],
        $fx['batchA'],
        80,
        'production_recording_gap',
        resolvedDaysAgo: 60
    );

    createPreventionVarianceCase(
        $fx,
        $fx['materialA'],
        $fx['batchA'],
        40,
        'production_recording_gap',
        StockVarianceInvestigation::STATUS_INVESTIGATING
    );

    $analysis = app(InventoryPreventionIntelligenceService::class)->analyze(
        now()->subDays(120)->toDateString(),
        today()->toDateString(),
        $fx['materialA']
    );

    $row = $analysis['effectiveness']->firstWhere(
        'investigation_id',
        $first->id
    );

    expect($row)->not->toBeNull()
        ->and($row['confirmed_recurrences'])->toBe(1)
        ->and($row['signal'])->toBe('recurrent')
        ->and($analysis['summary']['post_corrective_recurrences'])->toBe(1)
        ->and($analysis['flags']->where(
            'type',
            'post_corrective_recurrence'
        )->where('severity', 'critical')->count())->toBe(1);
});

it('keeps recent resolutions in monitoring until the post window is complete', function () {
    $fx = preventionIntelligenceFixture();

    $case = createPreventionVarianceCase(
        $fx,
        $fx['materialB'],
        $fx['batchB'],
        12,
        'warehouse_handling_damage',
        resolvedDaysAgo: 5
    );

    $analysis = app(InventoryPreventionIntelligenceService::class)->analyze(
        now()->subDays(60)->toDateString(),
        today()->toDateString(),
        $fx['materialB']
    );

    $row = $analysis['effectiveness']->firstWhere(
        'investigation_id',
        $case->id
    );

    expect($row)->not->toBeNull()
        ->and($row['post_window_complete'])->toBeFalse()
        ->and($row['signal'])->toBe('monitoring');
});
