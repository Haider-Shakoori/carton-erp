<?php

use App\Models\BOM;
use App\Models\Currency;
use App\Models\ProfitDistribution;
use App\Models\ProfitDistributionItem;
use App\Models\Shareholder;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FactoryReleaseAuditService;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('reports a clean isolated database without authorizing physical go-live', function () {
    $result = app(FactoryReleaseAuditService::class)->report();

    expect($result['status'])->toBe('ready_for_manual_signoff')
        ->and($result['automatic_release_approval'])->toBeFalse()
        ->and($result['counts'])->toBe([
            'review_required_boms' => 0,
            'legacy_non_cash_marked_paid' => 0,
            'confirmed_sales_without_actual_consumption' => 0,
            'overlapping_processed_distributions' => 0,
        ]);

    $this->artisan('erp:release-audit', ['--fail-on-risk' => true])->assertExitCode(0);
});

it('reports incomplete BOM recipes without changing production inventory', function () {
    $user = User::factory()->create();
    $now = now();
    $category = DB::table('categories')->insertGetId([
        'name' => 'QA imported client cartons', 'slug' => 'qa-imported-carton',
        'created_at' => $now, 'updated_at' => $now,
    ]);
    $product = DB::table('products')->insertGetId([
        'name' => 'QA carton missing engineering evidence',
        'slug' => 'qa-carton-missing-evidence',
        'category_id' => $category, 'unit' => 'pcs', 'type' => 'finished_good',
        'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
    ]);
    $bom = DB::table('boms')->insertGetId([
        'name' => 'Client imported draft', 'code' => 'BOM-QA-REVIEW',
        'product_id' => $product, 'version' => '1.0-review',
        'status' => 'draft', 'is_active' => false,
        'description' => '[REVIEW REQUIRED] Missing verified 7-ply profile.',
        'created_by' => $user->id, 'created_at' => $now, 'updated_at' => $now,
    ]);

    $audit = app(FactoryReleaseAuditService::class)->report();

    expect($audit['status'])->toBe('review_required')
        ->and($audit['counts']['review_required_boms'])->toBe(1)
        ->and($audit['review_required_boms'][0]['bom_id'])->toBe($bom)
        ->and(BOM::query()->findOrFail($bom)->status)->toBe('draft');

    $this->artisan('erp:release-audit', ['--fail-on-risk' => true])->assertExitCode(1);
});

it('detects historical paid flags with non-cash ledger credits without auto-rewriting accounts', function () {
    $this->seed(CurrencySeeder::class);
    $currency = Currency::where('is_default', true)->firstOrFail();
    $user = User::factory()->create();
    $shareholder = Shareholder::create([
        'name' => 'Historical Shareholder',
        'code' => 'QA-HIST-001', 'share_percentage' => 100,
        'is_active' => true, 'created_by' => $user->id,
    ]);
    $distribution = ProfitDistribution::create([
        'distribution_number' => 'PD-QA-HISTORY-0001',
        'period_start' => '2026-07-01', 'period_end' => '2026-07-31',
        'distribution_date' => '2026-08-01', 'total_profit' => 75,
        'distributed_amount' => 75, 'remaining_amount' => 0,
        'status' => ProfitDistribution::STATUS_DISTRIBUTED,
        'created_by' => $user->id,
    ]);
    $transaction = Transaction::create([
        'table_name' => 'profit_distributions', 'table_row_id' => $distribution->id,
        'type' => 'adjustment', 'account_id' => null,
        'shareholder_id' => $shareholder->id,
        'currency_id' => $currency->id, 'amount' => 75,
        'transaction_type' => 'credit', 'is_cash' => false,
        'exchange_rate' => 1, 'description' => 'Historical non-cash credit',
        'status' => 'active', 'created_by' => $user->id, 'is_visible' => true,
    ]);
    $item = ProfitDistributionItem::create([
        'distribution_id' => $distribution->id,
        'shareholder_id' => $shareholder->id,
        'share_percentage' => 100, 'amount' => 75,
        'status' => ProfitDistributionItem::STATUS_PAID,
        'transaction_id' => $transaction->id,
        'payment_date' => '2026-08-01',
    ]);

    $audit = app(FactoryReleaseAuditService::class)->report();

    expect($audit['counts']['legacy_non_cash_marked_paid'])->toBe(1)
        ->and($audit['legacy_non_cash_marked_paid'][0]['distribution_item_id'])->toBe($item->id)
        ->and($item->fresh()->status)->toBe(ProfitDistributionItem::STATUS_PAID)
        ->and($transaction->fresh()->is_cash)->toBeFalsy();
});


it('prioritizes an unapproved engineering BOM over a missing gross carton weight in the UI audit', function () {
    $finished = new \App\Models\Product();
    $finished->finished_weight_g = null;
    $unapproved = new \App\Models\BOM();
    $unapproved->description = '[REVIEW REQUIRED] 7-ply paper recipe missing';

    $assessment = app(\App\Services\FinishedGoodWeightAuditService::class)
        ->assess($finished, $unapproved);

    expect($assessment['status'])->toBe('BOM review required')
        ->and($assessment['measured_g'])->toBeNull()
        ->and($assessment['paper_g'])->toBeNull();
});
