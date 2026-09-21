<?php

use App\Models\PurchaseItem;
use App\Models\ProductionMaterialConsumption;
use App\Models\StockReconciliation;
use App\Models\User;
use App\Services\InventoryControlAnalysisService;
use App\Services\StockCycleCountPlanningService;
use App\Services\StockReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function stockPlanningAnalysisFixture(): array
{
    $user = User::factory()->create();
    Auth::login($user);
    $now = now();

    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Planning Analysis Materials',
        'slug' => 'planning-analysis-materials',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $materialA = DB::table('products')->insertGetId([
        'name' => 'Planner Material A',
        'slug' => 'planner-material-a',
        'unit' => 'kg',
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $materialB = DB::table('products')->insertGetId([
        'name' => 'Planner Material B',
        'slug' => 'planner-material-b',
        'unit' => 'kg',
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $materialC = DB::table('products')->insertGetId([
        'name' => 'Planner Material C',
        'slug' => 'planner-material-c',
        'unit' => 'kg',
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $finishedProductId = DB::table('products')->insertGetId([
        'name' => 'Planner Finished Carton',
        'slug' => 'planner-finished-carton',
        'unit' => 'piece',
        'category_id' => $categoryId,
        'type' => 'finished_good',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $currencyId = DB::table('currencies')->insertGetId([
        'name' => 'Planner USD',
        'code' => 'PUSD',
        'symbol' => '$',
        'exchange_rate' => 1,
        'is_default' => 0,
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $supplierId = DB::table('accounts')->insertGetId([
        'name' => 'Planner Supplier',
        'code' => 'PLAN-SUP',
        'account_type' => 'supplier',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $purchaseId = DB::table('purchases')->insertGetId([
        'purchase_no' => 'PO-PLAN-001',
        'supplier_id' => $supplierId,
        'currency_id' => $currencyId,
        'status' => 'arrived',
        'exchange_rate' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $batchIds = [];
    foreach ([
        $materialA => ['qty' => 800, 'batch' => 'PLAN-A'],
        $materialB => ['qty' => 150, 'batch' => 'PLAN-B'],
        $materialC => ['qty' => 50, 'batch' => 'PLAN-C'],
    ] as $productId => $row) {
        $batchIds[$productId] = DB::table('purchase_items')->insertGetId([
            'purchase_id' => $purchaseId,
            'product_id' => $productId,
            'purchase_currency_id' => $currencyId,
            'qty' => $row['qty'],
            'qty_available' => $row['qty'],
            'qty_sold' => 0,
            'qty_used' => 0,
            'qty_wasted' => 0,
            'unit' => 'kg',
            'cost_per_unit' => 1,
            'usd_unit_price' => 1,
            'usd_total' => $row['qty'],
            'batch_no' => $row['batch'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    $bomId = DB::table('boms')->insertGetId([
        'name' => 'Planner BOM',
        'code' => 'PLAN-BOM',
        'product_id' => $finishedProductId,
        'status' => 'active',
        'created_by' => $user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return compact(
        'user',
        'materialA',
        'materialB',
        'materialC',
        'finishedProductId',
        'batchIds',
        'bomId'
    );
}

it('classifies current inventory by ABC value and creates a targeted cycle count', function () {
    $fx = stockPlanningAnalysisFixture();

    $planner = app(StockCycleCountPlanningService::class);
    $plan = $planner->plan('2026-09-21');

    $rows = $plan['rows']->keyBy('product_id');

    expect($rows[$fx['materialA']]['abc_class'])->toBe('A')
        ->and($rows[$fx['materialB']]['abc_class'])->toBe('B')
        ->and($rows[$fx['materialC']]['abc_class'])->toBe('C')
        ->and($plan['summary']['inventory_value_usd'])->toBe(1000.0)
        ->and($plan['summary']['due_materials'])->toBe(3);

    $service = app(StockReconciliationService::class);
    $reconciliation = $service->createSnapshot(
        '2026-09-21',
        'Targeted B/C count',
        [$fx['materialB'], $fx['materialC']]
    );

    expect($reconciliation->items->pluck('product_id')->unique()->sort()->values()->all())
        ->toBe(collect([$fx['materialB'], $fx['materialC']])->sort()->values()->all());

    foreach ($reconciliation->items as $item) {
        $service->updateCount($reconciliation, $item, (float) $item->system_quantity);
    }

    $service->submit($reconciliation);
    $service->approve($reconciliation->fresh());
    $service->post($reconciliation->fresh());

    $nextPlan = $planner->plan('2026-09-22');
    $nextRows = $nextPlan['rows']->keyBy('product_id');

    expect($nextRows[$fx['materialA']]['is_due'])->toBeTrue()
        ->and($nextRows[$fx['materialB']]['is_due'])->toBeFalse()
        ->and($nextRows[$fx['materialC']]['is_due'])->toBeFalse()
        ->and($nextRows[$fx['materialB']]['last_count_date'])->toBe('2026-09-21');
});

it('aggregates posted reconciliation shortage and surplus into variance trends', function () {
    $fx = stockPlanningAnalysisFixture();
    $service = app(StockReconciliationService::class);

    $shortage = $service->createSnapshot(
        '2026-09-01',
        'Shortage count',
        [$fx['materialA']]
    );
    $shortageItem = $shortage->items->first();
    $service->updateCount($shortage, $shortageItem, 790, 'measurement_difference');
    $service->submit($shortage);
    $service->approve($shortage->fresh());
    $service->post($shortage->fresh());

    $surplus = $service->createSnapshot(
        '2026-09-10',
        'Surplus count',
        [$fx['materialB']]
    );
    $surplusItem = $surplus->items->first();
    $service->updateCount($surplus, $surplusItem, 155, 'material_found');
    $service->submit($surplus);
    $service->approve($surplus->fresh());
    $service->post($surplus->fresh());

    $trend = app(InventoryControlAnalysisService::class)
        ->varianceTrend('2026-09-01', '2026-09-30', 'month');

    expect($trend['summary']['reconciliations'])->toBe(2)
        ->and($trend['summary']['shortage_value_usd'])->toBe(10.0)
        ->and($trend['summary']['surplus_value_usd'])->toBe(5.0)
        ->and($trend['summary']['net_value_usd'])->toBe(-5.0)
        ->and($trend['summary']['absolute_value_usd'])->toBe(15.0)
        ->and($trend['periods'])->toHaveCount(1)
        ->and($trend['periods']->first()['label'])->toBe('Sep 2026');
});

it('keeps production variance separate from the later physical inventory difference', function () {
    $fx = stockPlanningAnalysisFixture();
    $now = now();

    $productionOrderId = DB::table('production_orders')->insertGetId([
        'order_number' => 'PROD-CONTROL-001',
        'product_id' => $fx['finishedProductId'],
        'bom_id' => $fx['bomId'],
        'quantity_ordered' => 100,
        'quantity_produced' => 100,
        'status' => 'completed',
        'start_date' => '2026-09-05',
        'completion_date' => '2026-09-06',
        'created_by' => $fx['user']->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('production_order_materials')->insert([
        'production_order_id' => $productionOrderId,
        'product_id' => $fx['materialA'],
        'required_quantity' => 10,
        'available_quantity' => 800,
        'shortage_quantity' => 0,
        'unit' => 'kg',
        'cost_per_unit' => 1,
        'total_cost' => 10,
        'consumed_quantity' => 12,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    ProductionMaterialConsumption::create([
        'production_order_id' => $productionOrderId,
        'material_id' => $fx['materialA'],
        'purchase_item_id' => $fx['batchIds'][$fx['materialA']],
        'created_by' => $fx['user']->id,
        'planned_quantity' => 10,
        'actual_quantity' => 12,
        'wastage_quantity' => 1,
        'unit' => 'kg',
        'cost_per_unit_usd' => 1,
        'total_cost_usd' => 12,
        'consumed_at' => '2026-09-06 12:00:00',
    ]);

    $batch = PurchaseItem::findOrFail($fx['batchIds'][$fx['materialA']]);
    $batch->qty_used = 12;
    $batch->save();

    expect((float) $batch->fresh()->qty_available)->toBe(788.0);

    $service = app(StockReconciliationService::class);
    $reconciliation = $service->createSnapshot(
        '2026-09-07',
        'Physical verification after production',
        [$fx['materialA']]
    );
    $item = $reconciliation->items->first();

    expect((float) $item->system_quantity)->toBe(788.0);

    $service->updateCount(
        $reconciliation,
        $item,
        786,
        'measurement_difference'
    );
    $service->submit($reconciliation);
    $service->approve($reconciliation->fresh());
    $service->post($reconciliation->fresh());

    $analysis = app(InventoryControlAnalysisService::class)
        ->controlAnalysis('2026-09-01', '2026-09-30', $fx['materialA']);

    expect($analysis['rows'])->toHaveCount(1);

    $row = $analysis['rows']->first();

    expect($row['planned_quantity'])->toBe(10.0)
        ->and($row['actual_quantity'])->toBe(12.0)
        ->and($row['production_variance_quantity'])->toBe(2.0)
        ->and($row['production_wastage_quantity'])->toBe(1.0)
        ->and($row['system_quantity_at_count'])->toBe(788.0)
        ->and($row['physical_quantity'])->toBe(786.0)
        ->and($row['physical_variance_quantity'])->toBe(-2.0)
        ->and($row['posted_adjustment_quantity'])->toBe(-2.0)
        ->and($row['posted_adjustment_value_usd'])->toBe(-2.0)
        ->and($row['control_signal'])->toBe('inventory_variance')
        ->and($analysis['summary']['planned_material_cost_usd'])->toBe(10.0)
        ->and($analysis['summary']['actual_material_cost_usd'])->toBe(12.0)
        ->and($analysis['summary']['production_cost_variance_usd'])->toBe(2.0)
        ->and($analysis['summary']['reconciliation_absolute_value_usd'])->toBe(2.0);
});
