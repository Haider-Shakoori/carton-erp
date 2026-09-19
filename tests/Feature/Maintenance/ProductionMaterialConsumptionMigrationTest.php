<?php

use App\Models\ProductionMaterialConsumption;
use App\Models\Sale;
use App\Models\User;
use App\Services\ProductionCostRecorder;
use App\Services\SaleProfitService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function migrationFix01Fixtures(): array
{
    $user = User::factory()->create();
    $now = now();

    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Migration Fix Category',
        'slug' => 'migration-fix-category',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $materialId = DB::table('products')->insertGetId([
        'name' => 'Migration Fix Material',
        'slug' => 'migration-fix-material',
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $bomId = DB::table('boms')->insertGetId([
        'name' => 'Migration Fix BOM',
        'code' => 'MIG-FIX-BOM',
        'product_id' => $materialId,
        'status' => 'active',
        'created_by' => $user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $productionOrderId = DB::table('production_orders')->insertGetId([
        'order_number' => 'MIG-FIX-PO',
        'product_id' => $materialId,
        'bom_id' => $bomId,
        'quantity_ordered' => 10,
        'created_by' => $user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $currencyId = DB::table('currencies')->insertGetId([
        'name' => 'Afghani',
        'code' => 'AFN',
        'symbol' => '؋',
        'exchange_rate' => 66,
        'is_default' => true,
        'is_active' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $customerId = DB::table('accounts')->insertGetId([
        'name' => 'Migration Fix Customer',
        'code' => 'MIG-FIX-CUS',
        'account_type' => 'customer',
        'is_active' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $supplierId = DB::table('accounts')->insertGetId([
        'name' => 'Migration Fix Supplier',
        'code' => 'MIG-FIX-SUP',
        'account_type' => 'supplier',
        'is_active' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $saleId = DB::table('sales')->insertGetId([
        'sale_no' => 'MIG-FIX-SALE',
        'customer_id' => $customerId,
        'production_order_id' => $productionOrderId,
        'currency_id' => $currencyId,
        'status' => 'confirmed',
        'exchange_rate' => 66,
        'grand_total' => 660,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $saleItemId = DB::table('sale_items')->insertGetId([
        'sale_id' => $saleId,
        'product_id' => $materialId,
        'sale_currency_id' => $currencyId,
        'qty' => 1,
        'total' => 660,
        'total_cost_usd' => 2,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $purchaseId = DB::table('purchases')->insertGetId([
        'purchase_no' => 'MIG-FIX-PURCHASE',
        'supplier_id' => $supplierId,
        'currency_id' => $currencyId,
        'status' => 'arrived',
        'exchange_rate' => 66,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $purchaseItemId = DB::table('purchase_items')->insertGetId([
        'purchase_id' => $purchaseId,
        'product_id' => $materialId,
        'purchase_currency_id' => $currencyId,
        'qty' => 10,
        'qty_sold' => 0,
        'qty_returned' => 0,
        'qty_wasted' => 0,
        'qty_available' => 10,
        'rate' => 66,
        'usd_total' => 20,
        'usd_cost_per_item' => 2,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return compact(
        'user',
        'materialId',
        'productionOrderId',
        'saleId',
        'saleItemId',
        'purchaseItemId'
    );
}

it('creates the canonical consumption schema exactly once with safe names', function () {
    expect(Schema::hasTable('production_material_consumptions'))->toBeTrue()
        ->and(Schema::hasColumns('production_material_consumptions', [
            'id',
            'production_order_id',
            'sale_id',
            'sale_item_id',
            'material_id',
            'purchase_item_id',
            'created_by',
            'planned_quantity',
            'actual_quantity',
            'wastage_quantity',
            'unit',
            'cost_per_unit_usd',
            'cost_per_unit_afn',
            'total_cost_usd',
            'total_cost_afn',
            'wastage_cost_usd',
            'wastage_cost_afn',
            'consumed_at',
            'created_at',
            'updated_at',
        ]))->toBeTrue();

    $matchingTables = collect(DB::select(
        "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'production_material_consumptions'"
    ));
    expect($matchingTables)->toHaveCount(1);

    $indexes = collect(Schema::getIndexes('production_material_consumptions'));
    $indexNames = $indexes->pluck('name')->filter()->values();
    expect($indexNames)->toContain(
        'pmc_prod_order_idx',
        'pmc_sale_idx',
        'pmc_sale_item_idx',
        'pmc_material_idx',
        'pmc_purchase_item_idx',
        'pmc_consumed_at_idx',
        'pmc_sale_prod_idx',
        'pmc_material_purchase_idx'
    );
    $indexNames->each(fn ($name) => expect(strlen($name))->toBeLessThan(64));

    $foreignKeys = collect(Schema::getForeignKeys('production_material_consumptions'));
    expect($foreignKeys->pluck('foreign_table')->sort()->values()->all())->toBe([
        'production_orders',
        'products',
        'purchase_items',
        'sale_items',
        'sales',
        'users',
    ]);
});

it('preserves nullable fields defaults and production order cost columns', function () {
    $columns = collect(Schema::getColumns('production_material_consumptions'))->keyBy('name');

    foreach ([
        'planned_quantity',
        'actual_quantity',
        'wastage_quantity',
        'cost_per_unit_usd',
        'cost_per_unit_afn',
        'total_cost_usd',
        'total_cost_afn',
        'wastage_cost_usd',
        'wastage_cost_afn',
    ] as $column) {
        expect(trim((string) $columns[$column]['default'], "'"))->toBe('0');
    }

    foreach (['sale_id', 'sale_item_id', 'purchase_item_id', 'created_by', 'unit', 'consumed_at'] as $column) {
        expect($columns[$column]['nullable'])->toBeTrue();
    }

    expect(Schema::hasColumns('production_orders', [
        'actual_labour_cost_afn',
        'actual_overhead_cost_afn',
        'other_direct_cost_afn',
    ]))->toBeTrue();
});

it('creates a representative model record and loads every relationship', function () {
    $fixture = migrationFix01Fixtures();

    $record = ProductionMaterialConsumption::create([
        'production_order_id' => $fixture['productionOrderId'],
        'sale_id' => $fixture['saleId'],
        'sale_item_id' => $fixture['saleItemId'],
        'material_id' => $fixture['materialId'],
        'purchase_item_id' => $fixture['purchaseItemId'],
        'planned_quantity' => 1,
        'actual_quantity' => 1,
        'wastage_quantity' => 0,
        'unit' => 'kg',
        'cost_per_unit_usd' => 2,
        'cost_per_unit_afn' => 132,
        'total_cost_usd' => 2,
        'total_cost_afn' => 132,
        'wastage_cost_usd' => 0,
        'wastage_cost_afn' => 0,
        'consumed_at' => now(),
        'created_by' => $fixture['user']->id,
    ]);
    $record->load(['productionOrder', 'sale', 'saleItem', 'material', 'purchaseItem']);

    expect($record->exists)->toBeTrue()
        ->and($record->productionOrder->id)->toBe($fixture['productionOrderId'])
        ->and($record->sale->id)->toBe($fixture['saleId'])
        ->and($record->saleItem->id)->toBe($fixture['saleItemId'])
        ->and($record->material->id)->toBe($fixture['materialId'])
        ->and($record->purchaseItem->id)->toBe($fixture['purchaseItemId']);

    $profit = app(SaleProfitService::class)->calculate(Sale::findOrFail($fixture['saleId']));
    expect($profit['actual_available'])->toBeTrue();
});

it('allows the production cost recorder to write all required cost fields', function () {
    $fixture = migrationFix01Fixtures();
    Auth::login($fixture['user']);

    $record = app(ProductionCostRecorder::class)->consumeBatch(
        productionOrderId: $fixture['productionOrderId'],
        saleId: $fixture['saleId'],
        saleItemId: $fixture['saleItemId'],
        materialId: $fixture['materialId'],
        purchaseItemId: $fixture['purchaseItemId'],
        actualQuantity: 1,
        plannedQuantity: 1,
        wastageQuantity: 0,
        unit: 'kg'
    );

    expect($record)->toBeInstanceOf(ProductionMaterialConsumption::class)
        ->and($record->cost_per_unit_usd)->toBe('2.000000')
        ->and($record->cost_per_unit_afn)->toBe('132.000000')
        ->and($record->total_cost_usd)->toBe('2.0000')
        ->and($record->total_cost_afn)->toBe('132.0000');
});

it('runs the later alignment migration repeatedly without rebuilding or losing rows', function () {
    $fixture = migrationFix01Fixtures();
    $record = ProductionMaterialConsumption::create([
        'production_order_id' => $fixture['productionOrderId'],
        'sale_id' => $fixture['saleId'],
        'sale_item_id' => $fixture['saleItemId'],
        'material_id' => $fixture['materialId'],
        'purchase_item_id' => $fixture['purchaseItemId'],
        'actual_quantity' => 1,
    ]);

    $migrationPath = database_path(
        'migrations/2026_07_29_000001_create_production_material_consumptions_table.php'
    );
    $firstRun = require $migrationPath;
    $firstRun->up();
    $secondRun = require $migrationPath;
    $secondRun->up();

    expect(ProductionMaterialConsumption::query()->count())->toBe(1)
        ->and(ProductionMaterialConsumption::find($record->id)?->actual_quantity)->toBe('1.0000')
        ->and(Schema::hasTable('production_material_consumptions'))->toBeTrue();
});
