<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\ProductionMaterialConsumption;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function batch04Indexes(string $table): array
{
    return collect(DB::select("PRAGMA index_list('{$table}')"))
        ->mapWithKeys(function ($index) {
            $columns = collect(DB::select("PRAGMA index_info('{$index->name}')"))
                ->sortBy('seqno')
                ->pluck('name')
                ->values()
                ->all();

            return [$index->name => $columns];
        })
        ->all();
}

function batch04Fixture(): array
{
    $user = User::factory()->create();
    $account = Account::create([
        'name' => 'Batch 04 Account',
        'code' => 'B04-ACCOUNT',
        'account_type' => 'customer',
        'is_active' => true,
    ]);
    $currency = Currency::create([
        'name' => 'Batch 04 Currency',
        'code' => 'B04',
        'symbol' => 'B',
        'exchange_rate' => 1,
        'is_active' => true,
    ]);
    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Batch 04 Category',
        'slug' => 'batch-04-category',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $productId = DB::table('products')->insertGetId([
        'name' => 'Batch 04 Product',
        'slug' => 'batch-04-product',
        'category_id' => $categoryId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $bomId = DB::table('boms')->insertGetId([
        'name' => 'Batch 04 BOM',
        'code' => 'BOM-B04',
        'product_id' => $productId,
        'status' => 'active',
        'created_by' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return compact('user', 'account', 'currency', 'productId', 'bomId');
}

it('creates only the proven Batch 04 indexes with exact column order and safe names', function () {
    $expected = [
        'sales' => [
            'sales_cust_stat_date_idx' => ['customer_id', 'status', 'created_at'],
        ],
        'transactions' => [
            'txn_account_curr_stat_date_idx' => ['account_id', 'currency_id', 'status', 'created_at'],
            'txn_reference_idx' => ['table_name', 'table_row_id'],
        ],
        'exchanges' => [
            'exchange_report_idx' => ['customer_account_id', 'base_currency_id', 'target_currency_id', 'created_at'],
        ],
        'remittances' => [
            'remittance_report_idx' => ['account_id', 'currency_id', 'status', 'created_at'],
        ],
    ];

    foreach ($expected as $table => $indexes) {
        $actual = batch04Indexes($table);
        foreach ($indexes as $name => $columns) {
            expect($name)->not->toBeEmpty()
                ->and(strlen($name))->toBeLessThan(64)
                ->and($actual)->toHaveKey($name)
                ->and($actual[$name])->toBe($columns)
                ->and(collect(array_keys($actual))->filter(fn ($index) => $index === $name))->toHaveCount(1);
        }
    }

    expect(batch04Indexes('purchases'))->not->toHaveKey('purch_supp_stat_date_idx')
        ->and(batch04Indexes('purchase_items'))->not->toHaveKey('purchase_items_prod_date_idx')
        ->and(batch04Indexes('production_material_consumptions'))->not->toHaveKey('pmc_order_material_idx')
        ->and(batch04Indexes('activity_log'))->not->toHaveKey('activity_causer_event_date_idx');
});

it('preserves representative writes and relationships on indexed and related core tables', function () {
    $fixture = batch04Fixture();
    $now = now();

    $purchase = Purchase::create([
        'purchase_no' => 'PO-B04',
        'supplier_id' => $fixture['account']->id,
        'currency_id' => $fixture['currency']->id,
        'status' => 'arrived',
    ]);
    $purchaseItem = PurchaseItem::create([
        'purchase_id' => $purchase->id,
        'product_id' => $fixture['productId'],
        'purchase_currency_id' => $fixture['currency']->id,
        'qty' => 10,
    ]);
    $sale = Sale::create([
        'sale_no' => 'SO-B04',
        'customer_id' => $fixture['account']->id,
        'currency_id' => $fixture['currency']->id,
        'status' => 'confirmed',
    ]);
    $saleItem = SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $fixture['productId'],
        'purchase_item_id' => $purchaseItem->id,
        'sale_currency_id' => $fixture['currency']->id,
        'qty' => 1,
    ]);
    $productionOrderId = DB::table('production_orders')->insertGetId([
        'order_number' => 'PROD-B04',
        'product_id' => $fixture['productId'],
        'bom_id' => $fixture['bomId'],
        'quantity_ordered' => 1,
        'created_by' => $fixture['user']->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $transaction = Transaction::create([
        'table_name' => 'sales',
        'table_row_id' => $sale->id,
        'sale_id' => $sale->id,
        'account_id' => $fixture['account']->id,
        'currency_id' => $fixture['currency']->id,
        'amount' => 100,
        'transaction_type' => 'credit',
        'status' => 'active',
        'created_by' => $fixture['user']->id,
    ]);
    $consumption = ProductionMaterialConsumption::create([
        'production_order_id' => $productionOrderId,
        'sale_id' => $sale->id,
        'sale_item_id' => $saleItem->id,
        'material_id' => $fixture['productId'],
        'purchase_item_id' => $purchaseItem->id,
        'created_by' => $fixture['user']->id,
        'planned_quantity' => 1,
        'actual_quantity' => 1,
    ]);

    expect($purchaseItem->purchase->is($purchase))->toBeTrue()
        ->and($saleItem->sale->is($sale))->toBeTrue()
        ->and($transaction->account->is($fixture['account']))->toBeTrue()
        ->and($transaction->currency->is($fixture['currency']))->toBeTrue()
        ->and($consumption->productionOrder->id)->toBe($productionOrderId)
        ->and($consumption->material->id)->toBe($fixture['productId'])
        ->and($consumption->purchaseItem->is($purchaseItem))->toBeTrue();
});

it('removes only Batch 04 indexes in down and can apply them again without duplicates', function () {
    $migration = require database_path(
        'migrations/2026_07_30_120000_add_performance_indexes_batch_04.php'
    );
    $batch04 = [
        'sales' => ['sales_cust_stat_date_idx'],
        'transactions' => ['txn_account_curr_stat_date_idx', 'txn_reference_idx'],
        'exchanges' => ['exchange_report_idx'],
        'remittances' => ['remittance_report_idx'],
    ];
    $expectedAfterDown = [];
    foreach ($batch04 as $table => $names) {
        $expectedAfterDown[$table] = array_diff_key(
            batch04Indexes($table),
            array_flip($names)
        );
    }

    $migration->down();

    foreach ($batch04 as $table => $names) {
        $indexes = batch04Indexes($table);
        foreach ($names as $name) {
            expect($indexes)->not->toHaveKey($name);
        }
        expect($indexes)->toBe($expectedAfterDown[$table]);
    }

    $migration->up();

    foreach ($batch04 as $table => $names) {
        $indexes = batch04Indexes($table);
        foreach ($names as $name) {
            expect($indexes)->toHaveKey($name)
                ->and(collect(array_keys($indexes))->filter(fn ($index) => $index === $name))->toHaveCount(1);
        }
    }
});
