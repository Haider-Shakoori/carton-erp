<?php

use App\Models\Currency;
use App\Models\ProfitDistribution;
use App\Models\ProfitDistributionItem;
use App\Models\Shareholder;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ProfitSharingService;
use App\Services\SaleProfitService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Isolate approval/ledger operations from production costing. This deliberately
 * stubs the already-calculated period figures; inventory/FIFO formulas have
 * their separate material-consumption and financial regression suites.
 */
function shareholderReleaseService(int $missingActualCosts = 0): ProfitSharingService
{
    $service = Mockery::mock(ProfitSharingService::class, [app(SaleProfitService::class)])
        ->makePartial();

    $service->shouldReceive('calculatePeriodProfit')->andReturn([
        'total_profit' => 101.01,
        'estimated_sales_count' => $missingActualCosts,
        'actual_sales_count' => $missingActualCosts > 0 ? 0 : 1,
    ]);

    return $service;
}

function shareholderReleaseFixture(): User
{
    test()->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    // Do not rely on the initial factory dataset containing shareholder masters.
    Shareholder::query()->where('is_active', true)->update(['is_active' => false]);
    foreach ([['name' => 'Owner A', 'share_percentage' => 60], ['name' => 'Owner B', 'share_percentage' => 40]] as $data) {
        Shareholder::create([
            'name' => $data['name'],
            'code' => 'QA-SH-'.(int) $data['share_percentage'],
            'share_percentage' => $data['share_percentage'],
            'is_active' => true,
            'created_by' => $user->id,
        ]);
    }
    Currency::query()->where('is_default', true)->firstOrFail();
    return $user;
}

it('rejects payout when any confirmed sale is still using estimated rather than actual production cost', function () {
    $user = shareholderReleaseFixture();

    expect(fn () => shareholderReleaseService(1)
        ->distributeProfitLoss('2026-09-01', '2026-09-30', 'QA blocked', $user->id))
        ->toThrow(RuntimeException::class, 'lack actual production costs');

    expect(ProfitDistribution::query()->count())->toBe(0)
        ->and(ProfitDistributionItem::query()->count())->toBe(0)
        ->and(Transaction::query()->where('table_name', 'profit_distributions')->count())->toBe(0);
});

it('allocates exact rounded shares as unpaid noncash ledger credits and rejects overlapping distribution periods', function () {
    $user = shareholderReleaseFixture();
    $service = shareholderReleaseService();

    $distribution = $service->distributeProfitLoss('2026-09-01', '2026-09-30', 'QA actual costs', $user->id);

    expect($distribution->status)->toBe(ProfitDistribution::STATUS_DISTRIBUTED)
        ->and((float) $distribution->total_profit)->toBe(101.01)
        ->and((float) $distribution->items->sum('amount'))->toBe(101.01)
        ->and($distribution->items)->toHaveCount(2)
        ->and($distribution->items->every(fn ($item) => $item->status === ProfitDistributionItem::STATUS_PENDING))->toBeTrue()
        ->and($distribution->items->every(fn ($item) => $item->payment_date === null))->toBeTrue();

    $ledgerCredits = Transaction::query()
        ->where('table_name', 'profit_distributions')
        ->where('table_row_id', $distribution->id)
        ->get();

    expect($ledgerCredits)->toHaveCount(2)
        ->and($ledgerCredits->every(fn ($tx) => ! $tx->is_cash && $tx->transaction_type === 'credit'))->toBeTrue()
        ->and(round((float) $ledgerCredits->sum('amount'), 2))->toBe(101.01)
        ->and(round((float) Shareholder::query()->where('is_active', true)->get()->sum('total_distributed'), 2))->toBe(101.01);

    expect(fn () => $service->distributeProfitLoss('2026-09-15', '2026-10-15', null, $user->id))
        ->toThrow(RuntimeException::class, 'already been processed');

    expect(ProfitDistribution::query()->count())->toBe(1)
        ->and(Transaction::query()->where('table_name', 'profit_distributions')->count())->toBe(2);
});
