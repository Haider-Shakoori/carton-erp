<?php

use App\Models\FiscalPeriod;
use App\Models\GlAccount;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;

uses(RefreshDatabase::class);

it('posts balanced idempotent double entry journals and financial statements', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $cash = GlAccount::query()->where('code', '1000')->firstOrFail();
    $revenue = GlAccount::query()->where('code', '4000')->firstOrFail();
    $expense = GlAccount::query()->where('code', '5000')->firstOrFail();

    $accounting = app(AccountingService::class);

    $sale = $accounting->postOrReplace(
        rootKey: 'test-sale:1',
        date: now(),
        sourceType: 'test_sale',
        sourceId: 1,
        description: 'Enterprise accounting sale test',
        businessUnitId: null,
        lines: [
            ['account_id' => $cash->id, 'debit' => 100, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 100],
        ],
    );

    $same = $accounting->postOrReplace(
        rootKey: 'test-sale:1',
        date: now(),
        sourceType: 'test_sale',
        sourceId: 1,
        description: 'Enterprise accounting sale test',
        businessUnitId: null,
        lines: [
            ['account_id' => $cash->id, 'debit' => 100, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 100],
        ],
    );

    expect($same->id)->toBe($sale->id)
        ->and((float) $sale->lines->sum('debit_usd'))->toBe(100.0)
        ->and((float) $sale->lines->sum('credit_usd'))->toBe(100.0);

    $accounting->postOrReplace(
        rootKey: 'test-expense:1',
        date: now(),
        sourceType: 'test_expense',
        sourceId: 2,
        description: 'Enterprise accounting expense test',
        businessUnitId: null,
        lines: [
            ['account_id' => $expense->id, 'debit' => 30, 'credit' => 0],
            ['account_id' => $cash->id, 'debit' => 0, 'credit' => 30],
        ],
    );

    $trial = $accounting->trialBalance(now());
    $pnl = $accounting->profitAndLoss(now()->startOfMonth(), now()->endOfMonth());
    $balance = $accounting->balanceSheet(now());
    $cashFlow = $accounting->cashFlow(now()->startOfMonth(), now()->endOfMonth());

    expect(round($trial['debit_usd'], 2))->toBe(130.0)
        ->and(round($trial['credit_usd'], 2))->toBe(130.0)
        ->and(round($pnl['revenue_usd'], 2))->toBe(100.0)
        ->and(round($pnl['expenses_usd'], 2))->toBe(30.0)
        ->and(round($pnl['net_profit_usd'], 2))->toBe(70.0)
        ->and(round($balance['assets_usd'], 2))->toBe(70.0)
        ->and(round($cashFlow['cash_in_usd'], 2))->toBe(100.0)
        ->and(round($cashFlow['cash_out_usd'], 2))->toBe(30.0)
        ->and(round($cashFlow['net_cash_flow_usd'], 2))->toBe(70.0);
});

it('blocks postings into a closed fiscal period', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $period = FiscalPeriod::query()
        ->whereDate('starts_on', '<=', now()->toDateString())
        ->whereDate('ends_on', '>=', now()->toDateString())
        ->firstOrFail();

    $period->update([
        'status' => 'closed',
        'closed_by' => $user->id,
        'closed_at' => now(),
    ]);

    $cash = GlAccount::query()->where('code', '1000')->firstOrFail();
    $revenue = GlAccount::query()->where('code', '4000')->firstOrFail();

    expect(fn () => app(AccountingService::class)->postOrReplace(
        rootKey: 'closed-period-test:1',
        date: now(),
        sourceType: 'test',
        sourceId: 99,
        description: 'Must be blocked',
        businessUnitId: null,
        lines: [
            ['account_id' => $cash->id, 'debit' => 1, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 1],
        ],
    ))->toThrow(RuntimeException::class, 'posting is blocked');
});

it('rejects an unbalanced journal before it can reach the ledger', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $cash = GlAccount::query()->where('code', '1000')->firstOrFail();
    $revenue = GlAccount::query()->where('code', '4000')->firstOrFail();

    expect(fn () => app(AccountingService::class)->postOrReplace(
        rootKey: 'unbalanced-test:1',
        date: now(),
        sourceType: 'test',
        sourceId: 100,
        description: 'Unbalanced entry',
        businessUnitId: null,
        lines: [
            ['account_id' => $cash->id, 'debit' => 10, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 9],
        ],
    ))->toThrow(RuntimeException::class, 'Journal is not balanced');
});
