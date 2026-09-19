<?php

use App\Http\Controllers\Admin\ReportsController;
use App\Models\Account;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

function batch03Account(string $code, bool $active = true): Account
{
    return Account::create([
        'name' => "Account {$code}",
        'code' => $code,
        'account_type' => 'customer',
        'is_active' => $active,
    ]);
}

function batch03Currency(string $code, string $symbol): Currency
{
    return Currency::create([
        'name' => "{$code} Currency",
        'code' => $code,
        'symbol' => $symbol,
        'exchange_rate' => 1,
        'is_active' => true,
    ]);
}

function batch03Request(
    string $method,
    array $filters = [],
    string $controller = ReportsController::class
): array
{
    $exchange = $method === 'exchangeData';
    $columns = $exchange
        ? ['DT_RowIndex', 'customer', 'base_currency', 'target_currency', 'amount', 'rate', 'received', 'profit', 'created_at']
        : ['DT_RowIndex', 'customer', 'currency', 'amount', 'status', 'date'];
    $names = $exchange
        ? ['DT_RowIndex', 'customerAccount.name', 'baseCurrency.code', 'targetCurrency.code', 'base_amount', 'rate', 'target_amount', 'target_profit', 'created_at']
        : ['DT_RowIndex', 'account.name', 'currency.code', 'amount', 'status', 'created_at'];

    $parameters = array_merge([
        'draw' => 7,
        'start' => 0,
        'length' => 10,
        'search' => ['value' => '', 'regex' => false],
        'order' => [['column' => count($columns) - 1, 'dir' => 'desc']],
        'columns' => array_map(
            fn ($data, $index) => [
                'data' => $data,
                'name' => $names[$index],
                'searchable' => false,
                'orderable' => $index !== 0,
                'search' => ['value' => '', 'regex' => false],
            ],
            $columns,
            array_keys($columns)
        ),
    ], $filters);

    $request = Request::create('/batch-03', 'GET', $parameters);
    $previousRequest = app('request');
    app()->instance('request', $request);
    $response = app($controller)->{$method}($request);
    app()->instance('request', $previousRequest);

    expect($response->getStatusCode())->toBe(200);

    return $response->getData(true);
}

function batch03Fixtures(): array
{
    $user = User::factory()->create();
    $customer = batch03Account('CUS-B03');
    $otherCustomer = batch03Account('CUS-B03-OTHER');
    $office = batch03Account('OFF-B03');
    $inactive = batch03Account('CUS-B03-INACTIVE', false);
    $usd = batch03Currency('USD', '$');
    $afn = batch03Currency('AFN', '؋');
    $eur = batch03Currency('EUR', '€');

    $exchangeRows = [
        [$customer, $usd, $afn, '10.10', '66.123456', '667.85', '7.25', '2026-07-01 09:00:00'],
        [$otherCustomer, $usd, $eur, '20.20', '0.900000', '18.18', null, '2026-07-02 09:00:00'],
        [$customer, $afn, $usd, '330.00', '0.015000', '5.00', '-0.25', '2026-07-03 09:00:00'],
        [$inactive, $usd, $afn, '999.00', '1.000000', '999.00', '999.00', '2026-07-04 09:00:00'],
    ];

    foreach ($exchangeRows as [$account, $base, $target, $amount, $rate, $received, $profit, $date]) {
        DB::table('exchanges')->insert([
            'customer_account_id' => $account->id,
            'office_account_id' => $office->id,
            'base_currency_id' => $base->id,
            'target_currency_id' => $target->id,
            'base_amount' => $amount,
            'rate' => $rate,
            'target_amount' => $received,
            'target_profit' => $profit,
            'destination' => 'account',
            'is_withdrawn' => false,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }

    $remittanceRows = [
        [$customer, $usd, '12.34', 'pending', '2026-07-01 10:00:00'],
        [$otherCustomer, $usd, '0.01', 'processed', '2026-07-02 10:00:00'],
        [$customer, $afn, '500.55', 'processed', '2026-07-03 10:00:00'],
        [$inactive, $usd, '999.00', 'pending', '2026-07-04 10:00:00'],
    ];

    foreach ($remittanceRows as [$account, $currency, $amount, $status, $date]) {
        DB::table('remittances')->insert([
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'amount' => $amount,
            'bank_name' => 'Batch 03 Bank',
            'account_holder' => 'Batch 03 Holder',
            'bank_account_number' => 'B03-'.$account->id.'-'.$currency->id,
            'status' => $status,
            'created_by' => $user->id,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }

    return compact('customer', 'otherCustomer', 'office', 'inactive', 'usd', 'afn', 'eur');
}

it('preserves exchange report response totals filters pagination ordering and precision', function () {
    $fixtures = batch03Fixtures();

    $all = batch03Request('exchangeData');
    expect(array_keys($all))->toBe([
        'draw', 'recordsTotal', 'recordsFiltered', 'data', 'totals_by_currency', 'disableOrdering', 'queries', 'input',
    ])
        ->and($all['draw'])->toBe(7)
        ->and($all['recordsTotal'])->toBe(3)
        ->and($all['recordsFiltered'])->toBe(3)
        ->and($all['data'])->toHaveCount(3)
        ->and($all['data'][0]['base_currency'])->toBe('AFN')
        ->and((float) $all['data'][0]['rate'])->toBe(0.015)
        ->and($all['totals_by_currency'])->toBe([
            'AFN' => ['amount' => '330.00', 'received' => '5.00', 'profit' => '-0.25', 'symbol' => '؋', 'profit_symbol' => '$'],
            'USD' => ['amount' => '30.30', 'received' => '686.03', 'profit' => '7.25', 'symbol' => '$', 'profit_symbol' => '€'],
        ]);

    $filtered = batch03Request('exchangeData', [
        'base_currency_id' => $fixtures['usd']->id,
        'customer_account_id' => $fixtures['customer']->id,
        'date_range' => '2026-07-01 - 2026-07-01',
    ]);
    expect($filtered['recordsFiltered'])->toBe(1)
        ->and($filtered['data'][0]['amount'])->toBe('10.10')
        ->and($filtered['data'][0]['received'])->toBe('667.85')
        ->and($filtered['totals_by_currency']['USD']['profit'])->toBe('7.25');

    $page = batch03Request('exchangeData', ['start' => 1, 'length' => 1]);
    expect($page['recordsFiltered'])->toBe(3)
        ->and($page['data'])->toHaveCount(1)
        ->and($page['data'][0]['amount'])->toBe('20.20');

    $empty = batch03Request('exchangeData', ['base_currency_id' => 999999]);
    expect($empty['recordsFiltered'])->toBe(0)
        ->and($empty['data'])->toBe([])
        ->and($empty['totals_by_currency'])->toBe([]);
});

it('preserves remittance report response totals filters pagination ordering and numeric types', function () {
    $fixtures = batch03Fixtures();

    $all = batch03Request('remittanceData');
    expect($all['draw'])->toBe(7)
        ->and($all['recordsTotal'])->toBe(3)
        ->and($all['recordsFiltered'])->toBe(3)
        ->and($all['data'])->toHaveCount(3)
        ->and($all['data'][0]['currency'])->toBe('AFN')
        ->and($all['data'][0]['amount'])->toBe('500.55')
        ->and($all['totals_by_currency'])->toBe([
            'AFN' => ['amount' => '500.55', 'count' => 1, 'symbol' => '؋'],
            'USD' => ['amount' => '12.35', 'count' => 2, 'symbol' => '$'],
        ])
        ->and($all['totals_by_currency']['USD']['count'])->toBeInt();

    $filtered = batch03Request('remittanceData', [
        'account_id' => $fixtures['customer']->id,
        'currency_id' => $fixtures['afn']->id,
        'status' => 'processed',
        'date_range' => '2026-07-03 - 2026-07-03',
    ]);
    expect($filtered['recordsFiltered'])->toBe(1)
        ->and($filtered['data'][0]['status'])->toBe('Processed')
        ->and($filtered['totals_by_currency']['AFN']['amount'])->toBe('500.55');

    $page = batch03Request('remittanceData', ['start' => 1, 'length' => 1]);
    expect($page['recordsFiltered'])->toBe(3)
        ->and($page['data'])->toHaveCount(1)
        ->and($page['data'][0]['amount'])->toBe('0.01');

    $empty = batch03Request('remittanceData', ['status' => 'cancelled']);
    expect($empty['recordsFiltered'])->toBe(0)
        ->and($empty['data'])->toBe([])
        ->and($empty['totals_by_currency'])->toBe([]);
});

it('keeps the optimized full-dataset report query budgets portable', function () {
    batch03Fixtures();

    DB::flushQueryLog();
    DB::enableQueryLog();
    $exchange = batch03Request('exchangeData');
    $exchangeQueries = DB::getQueryLog();

    DB::flushQueryLog();
    $remittance = batch03Request('remittanceData');
    $remittanceQueries = DB::getQueryLog();
    DB::disableQueryLog();

    // Response semantics are covered by the focused tests above. These budgets
    // protect the optimized implementation without depending on an uncommitted
    // local storage/app/optimization-backup copy.
    expect($exchange['recordsTotal'])->toBe(3)
        ->and($remittance['recordsTotal'])->toBe(3)
        ->and($exchangeQueries)->toHaveCount(7)
        ->and($remittanceQueries)->toHaveCount(5);
});
