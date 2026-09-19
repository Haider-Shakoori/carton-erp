<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Models\Currency;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(Tests\TestCase::class);

beforeEach(function () {
    if (!Schema::hasTable('migrations')) {
        Schema::create('migrations', function ($table) {
            $table->increments('id');
            $table->string('migration');
            $table->integer('batch');
        });
        DB::table('migrations')->insert([
            'migration' => '2026_07_29_000001_create_production_material_consumptions_table',
            'batch' => 1,
        ]);
        Artisan::call('migrate', ['--force' => true]);
    }

    Schema::disableForeignKeyConstraints();
    foreach ([
        'transactions', 'sale_return_items', 'sale_returns', 'sale_items',
        'sales', 'accounts', 'currencies', 'users',
    ] as $table) {
        DB::table($table)->delete();
    }
    Schema::enableForeignKeyConstraints();
});

function batch02Currency(string $code, float $rate, bool $default = false): Currency
{
    return Currency::create([
        'name' => $code.' Currency',
        'code' => $code,
        'symbol' => $code === 'AFN' ? '؋' : '$',
        'exchange_rate' => $rate,
        'is_default' => $default,
        'is_active' => true,
    ]);
}

function batch02Account(string $type, string $code): int
{
    return DB::table('accounts')->insertGetId([
        'name' => $code,
        'code' => $code,
        'account_type' => $type,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function batch02Sale(
    int $customerId,
    int $currencyId,
    Carbon $createdAt,
    string $number,
    array $items
): array {
    $saleId = DB::table('sales')->insertGetId([
        'sale_no' => $number,
        'customer_id' => $customerId,
        'currency_id' => $currencyId,
        'status' => 'confirmed',
        'discount_total' => collect($items)->sum('discount'),
        'grand_total' => collect($items)->sum('total'),
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);

    $itemIds = [];
    foreach ($items as $item) {
        $itemIds[] = DB::table('sale_items')->insertGetId([
            'sale_id' => $saleId,
            'sale_currency_id' => $currencyId,
            'qty' => 1,
            'total' => $item['total'],
            'discount' => $item['discount'] ?? 0,
            'total_cost_usd' => $item['cost'],
            'profit_usd' => $item['total'] - $item['cost'],
            'profit_afn' => ($item['total'] - $item['cost']) * 66,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    return [$saleId, $itemIds];
}

function batch02Return(
    int $saleId,
    int $saleItemId,
    int $customerId,
    int $currencyId,
    Carbon $createdAt,
    string $number,
    float $total,
    float $cost
): void {
    $returnId = DB::table('sale_returns')->insertGetId([
        'sale_id' => $saleId,
        'return_no' => $number,
        'customer_id' => $customerId,
        'currency_id' => $currencyId,
        'status' => 'processed',
        'grand_total' => $total,
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);
    DB::table('sale_return_items')->insert([
        'sale_return_id' => $returnId,
        'sale_id' => $saleId,
        'sale_item_id' => $saleItemId,
        'qty_returned' => 1,
        'total' => $total,
        'total_cost_usd' => $cost,
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);
}

function batch02Chart(string $type, string $period = 'daily'): array
{
    $response = app(DashboardController::class)->getChartData(
        Request::create('/', 'GET', compact('type', 'period'))
    );

    expect($response->getStatusCode())->toBe(200);

    return $response->getData(true);
}

beforeEach(function () {
    Carbon::setTestNow('2026-07-29 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('captures the existing financially sensitive profit chart formula and boundaries', function () {
    $user = User::factory()->create();
    $usd = batch02Currency('USD', 1);
    $afn = batch02Currency('AFN', 66, true);
    $customer = batch02Account('customer', 'CUS-B02');
    $expense = batch02Account('expense', 'EXP-B02');

    [$afnSale] = batch02Sale($customer, $afn->id, Carbon::today(), 'SO-B02-AFN', [
        ['total' => 100, 'cost' => 1],
        ['total' => 50, 'cost' => 1, 'discount' => 10],
    ]);
    [$usdSale, $usdItems] = batch02Sale($customer, $usd->id, Carbon::today()->subDay(), 'SO-B02-USD', [
        ['total' => 100, 'cost' => 80],
    ]);
    batch02Return($usdSale, $usdItems[0], $customer, $usd->id, Carbon::today()->subDay(), 'SR-B02-PART', 25, 20);

    [$fullSale, $fullItems] = batch02Sale($customer, $afn->id, Carbon::today()->subDays(2), 'SO-B02-FULL', [
        ['total' => 40, 'cost' => 10],
    ]);
    batch02Return($fullSale, $fullItems[0], $customer, $afn->id, Carbon::today()->subDays(2), 'SR-B02-FULL', 40, 10);

    batch02Sale($customer, $afn->id, Carbon::today()->subDays(29), 'SO-B02-BOUNDARY', [
        ['total' => 90, 'cost' => 50, 'discount' => 10],
    ]);

    DB::table('transactions')->insert([
        [
            'account_id' => $expense,
            'currency_id' => $afn->id,
            'amount' => 10000,
            'transaction_type' => 'debit',
            'status' => 'active',
            'created_by' => $user->id,
            'created_at' => Carbon::today(),
            'updated_at' => Carbon::today(),
        ],
        [
            'account_id' => $expense,
            'currency_id' => $usd->id,
            'amount' => 15,
            'transaction_type' => 'debit',
            'status' => 'active',
            'created_by' => $user->id,
            'created_at' => Carbon::today()->subDay(),
            'updated_at' => Carbon::today()->subDay(),
        ],
    ]);

    $data = batch02Chart('profit')['data'];

    expect($data)->toHaveCount(30)
        ->and($data[0])->toBe([
            'label' => 'Jun 30',
            'revenue' => 90,
            'profit' => -3210,
            'usd_revenue' => 0,
            'afn_revenue' => 90,
        ])
        ->and($data[27]['profit'])->toBe(0)
        ->and($data[28])->toBe([
            'label' => 'Jul 28',
            'revenue' => 4950,
            'profit' => 0,
            'usd_revenue' => 75,
            'afn_revenue' => 0,
        ])
        ->and($data[29])->toBe([
            'label' => 'Jul 29',
            'revenue' => 150,
            'profit' => -9982,
            'usd_revenue' => 0,
            'afn_revenue' => 150,
        ]);
});

it('captures sales expense customer and empty chart response contracts', function () {
    $user = User::factory()->create();
    $usd = batch02Currency('USD', 1);
    $afn = batch02Currency('AFN', 66, true);
    $buyer = batch02Account('customer', 'CUS-B02-BUYER');
    batch02Account('customer', 'CUS-B02-NONE');
    $expense = batch02Account('expense', 'EXP-B02-CHART');

    batch02Sale($buyer, $usd->id, Carbon::today(), 'SO-B02-CHART', [
        ['total' => 12.34, 'cost' => 2.34],
    ]);
    DB::table('transactions')->insert([
        'account_id' => $expense,
        'currency_id' => $afn->id,
        'amount' => 7.89,
        'transaction_type' => 'debit',
        'status' => 'active',
        'created_by' => $user->id,
        'created_at' => Carbon::today(),
        'updated_at' => Carbon::today(),
    ]);

    $sales = batch02Chart('sales')['data'];
    $expenses = batch02Chart('expenses')['data'];
    $customers = batch02Chart('customers', 'monthly')['data'];

    expect($sales)->toHaveCount(30)
        ->and($sales[29])->toBe([
            'label' => 'Jul 29',
            'usd' => 12.34,
            'afn' => 0,
            'revenue' => 814.4399999999999,
            'profit' => 660,
            'usd_revenue' => 12.34,
            'afn_revenue' => 0,
        ])
        ->and($expenses[29])->toBe([
            'label' => 'Jul 29',
            'usd' => 0,
            'afn' => 7.89,
            'total' => 7.89,
        ])
        ->and($customers)->toBe([
            'labels' => ['Has Purchases', 'No Purchases'],
            'values' => [1, 1],
            'colors' => ['#10b981', '#6b7280'],
        ]);
});

it('preserves all supported chart ranges and numeric JSON types for empty data', function () {
    batch02Currency('USD', 1);
    batch02Currency('AFN', 66, true);

    foreach (['sales', 'profit', 'expenses'] as $type) {
        foreach (['daily' => 30, 'weekly' => 12, 'monthly' => 12, 'yearly' => 5] as $period => $count) {
            $data = batch02Chart($type, $period)['data'];

            expect($data)->toHaveCount($count)
                ->and($data[0]['label'])->toBeString();

            foreach (array_diff_key($data[0], ['label' => true]) as $value) {
                expect($value)->toBeInt();
            }
        }
    }
});

it('captures statistics totals percentages response keys and numeric types', function () {
    batch02Currency('USD', 1);
    batch02Currency('AFN', 66, true);

    $response = app(DashboardController::class)->getStats(
        Request::create('/', 'GET', ['timeframe' => 'month'])
    );
    $payload = $response->getData(true);

    expect($response->getStatusCode())->toBe(200)
        ->and($payload['success'])->toBeTrue()
        ->and(array_keys($payload['data']))->toBe([
            'default_currency', 'sales', 'cogs', 'gross_profit', 'expenses',
            'net_profit', 'inventory', 'receivables', 'payables', 'customers',
            'suppliers', 'agents', 'saraf', 'currency_balances', 'timeframe', 'alerts',
        ])
        ->and($payload['data']['sales'])->toBe([
            'usd' => 0, 'afn' => 0, 'total' => 0, 'growth' => 0, 'prev_total' => 0,
        ])
        ->and($payload['data']['net_profit']['afn'])->toBeInt()
        ->and($payload['data']['customers']['total'])->toBeInt();
});

it('keeps dashboard query counts fixed across chart bucket ranges', function () {
    batch02Currency('USD', 1);
    batch02Currency('AFN', 66, true);

    $queryCount = function (callable $callback): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    };

    foreach (['daily', 'weekly', 'monthly', 'yearly'] as $period) {
        expect($queryCount(fn () => batch02Chart('sales', $period)))->toBeLessThanOrEqual(3)
            ->and($queryCount(fn () => batch02Chart('profit', $period)))->toBeLessThanOrEqual(5)
            ->and($queryCount(fn () => batch02Chart('expenses', $period)))->toBeLessThanOrEqual(3);
    }

    expect($queryCount(fn () => batch02Chart('customers', 'monthly')))->toBe(1);
});
