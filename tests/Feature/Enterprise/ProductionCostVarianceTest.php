<?php

use App\Models\ProductionOrder;
use App\Models\User;
use App\Services\ProductionVarianceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('separates material usage rate and conversion variances from real actual cost', function () {
    $user = User::factory()->create();
    $now = now();

    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Variance Category',
        'slug' => 'variance-category',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $materialId = DB::table('products')->insertGetId([
        'name' => 'Variance Test Liner',
        'slug' => 'variance-test-liner',
        'unit' => 'kg',
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $finishedId = DB::table('products')->insertGetId([
        'name' => 'Variance Carton',
        'slug' => 'variance-carton',
        'unit' => 'pcs',
        'category_id' => $categoryId,
        'type' => 'finished_good',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $bomId = DB::table('boms')->insertGetId([
        'name' => 'Variance BOM',
        'code' => 'BOM-VARIANCE',
        'product_id' => $finishedId,
        'status' => 'active',
        'is_active' => 1,
        'created_by' => $user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $orderId = DB::table('production_orders')->insertGetId([
        'order_number' => 'PROD-VARIANCE',
        'product_id' => $finishedId,
        'bom_id' => $bomId,
        'quantity_ordered' => 100,
        'quantity_planned' => 100,
        'quantity_manufactured' => 100,
        'quantity_produced' => 95,
        'quantity_rejected' => 5,
        'status' => 'completed',
        'total_material_cost' => 30,
        'total_labor_cost' => 6,
        'total_overhead_cost' => 6,
        'total_cost' => 42,
        'created_by' => $user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('production_order_materials')->insert([
        'production_order_id' => $orderId,
        'product_id' => $materialId,
        'required_quantity' => 10,
        'unit' => 'kg',
        'cost_per_unit' => 2,
        'total_cost' => 20,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('production_material_consumptions')->insert([
        'production_order_id' => $orderId,
        'material_id' => $materialId,
        'planned_quantity' => 10,
        'actual_quantity' => 12,
        'wastage_quantity' => 1,
        'unit' => 'kg',
        'cost_per_unit_usd' => 2.5,
        'total_cost_usd' => 30,
        'consumed_at' => $now,
        'created_by' => $user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('production_order_events')->insert([
        'production_order_id' => $orderId,
        'event_type' => 'completion_snapshot',
        'from_status' => 'in_progress',
        'to_status' => 'completed',
        'metadata' => json_encode([
            'order' => [
                'total_labor_cost' => 5,
                'total_overhead_cost' => 5,
            ],
        ]),
        'user_id' => $user->id,
        'created_at' => $now,
    ]);

    $report = app(ProductionVarianceService::class)
        ->forProductionOrder(ProductionOrder::query()->findOrFail($orderId));

    $row = $report['materials'][0];
    $summary = $report['summary'];

    expect($row['planned_quantity'])->toBe(10.0)
        ->and($row['actual_quantity'])->toBe(12.0)
        ->and($row['planned_cost_per_unit_usd'])->toBe(2.0)
        ->and($row['actual_cost_per_unit_usd'])->toBe(2.5)
        ->and($row['usage_variance_usd'])->toBe(4.0)
        ->and($row['rate_variance_usd'])->toBe(6.0)
        ->and($row['cost_variance_usd'])->toBe(10.0)
        ->and($summary['material_usage_variance_usd'])->toBe(4.0)
        ->and($summary['material_rate_variance_usd'])->toBe(6.0)
        ->and($summary['material_cost_variance_usd'])->toBe(10.0)
        ->and($summary['conversion_cost_variance_usd'])->toBe(2.0)
        ->and($summary['total_production_cost_variance_usd'])->toBe(12.0)
        ->and($report['output']['yield_percentage'])->toBe(95.0);
});
