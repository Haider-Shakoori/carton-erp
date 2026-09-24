<?php

use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductionOrderController;
use App\Http\Controllers\Admin\SarafController;
use App\Http\Controllers\Admin\SupplierController;
use App\Models\Account;
use App\Models\Attendance;
use App\Models\Currency;
use App\Models\ProductionOrder;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

uses(RefreshDatabase::class);

function createBatch01Account(string $type, string $code): Account
{
    return Account::create([
        'name' => ucfirst($type).' Test',
        'code' => $code,
        'account_type' => $type,
        'is_active' => true,
    ]);
}

function addBatch01Transactions(Account $account, Currency $currency, User $user): void
{
    $now = now();

    DB::table('transactions')->insert([
        [
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'amount' => 125.50,
            'transaction_type' => 'credit',
            'status' => 'active',
            'created_by' => $user->id,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'amount' => 25.25,
            'transaction_type' => 'debit',
            'status' => 'active',
            'created_by' => $user->id,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'amount' => 999.99,
            'transaction_type' => 'credit',
            'status' => 'cancelled',
            'created_by' => $user->id,
            'created_at' => $now,
            'updated_at' => $now,
        ],
    ]);
}

it('shares one setting instance across nested views in one request scope', function () {
    // CI runs the full suite on top of the seeded baseline. Make this test own
    // the singleton settings row instead of depending on an empty table.
    Setting::query()->delete();
    Setting::create(['company_name' => 'Batch 01 Company']);

    $queries = [];
    DB::listen(function ($query) use (&$queries) {
        if (preg_match('/from\\s+[`"]?settings[`"]?/i', $query->sql)) {
            $queries[] = $query->sql;
        }
    });

    $view = app(AgentController::class)->index(Request::create('/', 'GET'));
    $html = $view->render();

    expect($html)->toContain('Batch 01 Company')
        ->and($queries)->toHaveCount(1);
});

it('preserves the setting fallback when no setting exists', function () {
    Setting::query()->delete();

    $view = View::make('auth.login');
    View::callComposer($view);
    $setting = $view->getData()['setting'];

    expect($setting)->toBeInstanceOf(Setting::class)
        ->and($setting->exists)->toBeFalse()
        ->and($setting->company_name)->toBe(config('app.name', 'ERP'))
        ->and($setting->currency)->toBe('USD');
});

it('preloads exact balances for agent supplier and saraf indexes', function () {
    $user = User::factory()->create();
    $currency = Currency::create([
        'name' => 'US Dollar',
        'code' => 'USD',
        'symbol' => '$',
        'exchange_rate' => 1,
        'is_active' => true,
    ]);

    $cases = [
        [AgentController::class, 'agent', 'AGT-B01', 'agents'],
        [SupplierController::class, 'supplier', 'SUP-B01', 'suppliers'],
        [SarafController::class, 'saraf', 'SAR-B01', 'sarafs'],
    ];

    foreach ($cases as [$controller, $type, $code, $viewKey]) {
        $account = createBatch01Account($type, $code);
        addBatch01Transactions($account, $currency, $user);

        $view = app($controller)->index(Request::create('/', 'GET'));
        $row = $view->getData()[$viewKey]->firstWhere('id', $account->id);

        expect((float) $row->balance)->toBe(100.25);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $html = $view->render();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        expect($html)->toContain('$')
            ->and($html)->toContain('100.25')
            ->and(collect($queries)->filter(
                fn ($query) => preg_match('/from\\s+[`"]?transactions[`"]?/i', $query['query'])
            ))->toHaveCount(0);
    }
});

it('preloads recent attendance before rendering the detail view', function () {
    $user = User::factory()->create();
    $employeeId = DB::table('employees')->insertGetId([
        'employee_code' => 'EMP-B01',
        'first_name' => 'Batch',
        'last_name' => 'Employee',
        'hire_date' => now()->toDateString(),
        'created_by' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $attendance = Attendance::create([
        'employee_id' => $employeeId,
        'date' => '2026-07-20',
        'status' => 'present',
        'created_by' => $user->id,
    ]);
    Attendance::create([
        'employee_id' => $employeeId,
        'date' => '2026-07-19',
        'status' => 'absent',
        'created_by' => $user->id,
    ]);

    $view = View::make('admin.hr.attendance.show', compact('attendance'));
    View::callComposer($view);

    expect($view->getData()['recent'])->toHaveCount(1)
        ->and($view->getData()['recent']->first()->date->format('Y-m-d'))->toBe('2026-07-19');
});

it('reuses the production order sale loaded by the controller', function () {
    $user = User::factory()->create();
    $currency = Currency::create([
        'name' => 'US Dollar',
        'code' => 'USD',
        'symbol' => '$',
        'exchange_rate' => 1,
        'is_active' => true,
    ]);
    $customer = createBatch01Account('customer', 'CUS-B01-PO');
    $now = now();

    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Batch 01 Category',
        'slug' => 'batch-01-category',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $productId = DB::table('products')->insertGetId([
        'name' => 'Batch 01 Product',
        'slug' => 'batch-01-product',
        'category_id' => $categoryId,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $bomId = DB::table('boms')->insertGetId([
        'name' => 'Batch 01 BOM',
        'code' => 'BOM-B01',
        'product_id' => $productId,
        'status' => 'active',
        'created_by' => $user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $productionOrderId = DB::table('production_orders')->insertGetId([
        'order_number' => 'PO-B01',
        'product_id' => $productId,
        'bom_id' => $bomId,
        'quantity_ordered' => 10,
        'created_by' => $user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    DB::table('sales')->insert([
        'sale_no' => 'SO-B01-PO',
        'customer_id' => $customer->id,
        'production_order_id' => $productionOrderId,
        'currency_id' => $currency->id,
        'status' => 'confirmed',
        'exchange_rate' => 1,
        'grand_total' => 250,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $view = app(ProductionOrderController::class)->show(
        ProductionOrder::findOrFail($productionOrderId)
    );
    $html = $view->render();
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($view->getData()['sale']->sale_no)->toBe('SO-B01-PO')
        ->and($html)->toContain('SO-B01-PO')
        ->and(collect($queries)->filter(
            fn ($query) => preg_match('/from\\s+[`"]?sales[`"]?/i', $query['query'])
        ))->toHaveCount(1);
});

it('preserves recent activity return deductions while using one aggregate query', function () {
    $currency = Currency::create([
        'name' => 'US Dollar',
        'code' => 'USD',
        'symbol' => '$',
        'exchange_rate' => 1,
        'is_active' => true,
    ]);
    $customer = createBatch01Account('customer', 'CUS-B01');
    $now = now();

    $saleId = DB::table('sales')->insertGetId([
        'sale_no' => 'SO-B01',
        'customer_id' => $customer->id,
        'currency_id' => $currency->id,
        'status' => 'confirmed',
        'grand_total' => 125.50,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    DB::table('sale_returns')->insert([
        [
            'sale_id' => $saleId,
            'return_no' => 'SR-B01-1',
            'customer_id' => $customer->id,
            'currency_id' => $currency->id,
            'status' => 'processed',
            'grand_total' => 25.25,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'sale_id' => $saleId,
            'return_no' => 'SR-B01-2',
            'customer_id' => $customer->id,
            'currency_id' => $currency->id,
            'status' => 'draft',
            'grand_total' => 80.00,
            'created_at' => $now,
            'updated_at' => $now,
        ],
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $response = app(DashboardController::class)->getRecentActivity(
        Request::create('/', 'GET', ['type' => 'sales', 'limit' => 10])
    );
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $payload = $response->getData(true);

    expect($response->getStatusCode())->toBe(200)
        ->and($payload['success'])->toBeTrue()
        ->and($payload['data'][0]['amount'])->toBe(100.25)
        ->and($payload['data'][0]['currency'])->toBe('USD')
        ->and($payload['data'][0]['status'])->toBe('confirmed')
        ->and(collect($queries)->filter(
            fn ($query) => str_contains($query['query'], 'sale_returns')
        ))->toHaveCount(1);
});
