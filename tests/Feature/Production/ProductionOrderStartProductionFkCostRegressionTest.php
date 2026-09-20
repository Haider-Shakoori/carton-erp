<?php

use App\Http\Controllers\Admin\ProductionOrderController;
use App\Models\ProductionOrder;
use App\Models\ProductionMaterialConsumption;
use App\Models\ProductionOrderMaterial;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Focused regression for the Start Production FK + costing fix.
 *
 * Guards exactly the reported runtime failures:
 *  1. production_material_consumptions.sale_id must be a real sales.id or NULL
 *     for standalone production orders — NEVER 0 (Integrity constraint 1452).
 *  2. AFN cost fields must carry USD * authoritative exchange rate
 *     (linked Sale.exchange_rate, else the default currency rate), and must not
 *     store raw USD values when the batch/purchase is USD-denominated.
 *  3. Double Start must be idempotent (status guard) and deduct stock once.
 *  4. The legacy BOM-template fallback (no frozen snapshot) still works.
 */
function startProductionFixtures(): array
{
    $user = User::factory()->create();
    $now = now();

    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'FK-Cost Category',
        'slug' => 'fk-cost-category',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $materialId = DB::table('products')->insertGetId([
        'name' => 'Kraft Paper FK',
        'slug' => 'kraft-paper-fk',
        'unit' => 'roll',
        'default_kg_per_roll' => 50,
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $finishedProductId = DB::table('products')->insertGetId([
        'name' => 'FK Test Box',
        'slug' => 'fk-test-box',
        'unit' => 'box',
        'category_id' => $categoryId,
        'type' => 'finished_good',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $bomId = DB::table('boms')->insertGetId([
        'name' => 'FK-Cost BOM',
        'code' => 'FK-COST-BOM',
        'product_id' => $finishedProductId,
        'status' => 'active',
        'is_active' => 1,
        'created_by' => $user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // Both BOM template rows reference the SAME raw material. Each legacy row is
    // forced to a 0.16375 kg/unit pre-wastage requirement via the stock
    // consumption override (SQLite test schema has no carton_3d dimension
    // columns), so with 5% wastage each row yields 0.17194 kg — identical to the
    // runtime case.
    $itemFields = [
        'bom_id' => $bomId,
        'material_id' => $materialId,
        'quantity' => 1,
        'unit' => 'roll',
        'wastage_percentage' => 5,
        'cost_per_unit_usd' => 0.78,
        'stock_consumption_override' => 0.16375,
        'stock_consumption_unit' => 'kg',
        'created_at' => $now,
        'updated_at' => $now,
    ];
    DB::table('bom_items')->insert($itemFields);
    $itemFields['cost_per_unit_usd'] = 0.16;
    DB::table('bom_items')->insert($itemFields);

    $afnId = DB::table('currencies')->insertGetId([
        'name' => 'Afghani',
        'code' => 'AFN',
        'symbol' => '؋',
        'exchange_rate' => 66,
        'is_default' => true,
        'is_active' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    DB::table('currencies')->insertGetId([
        'name' => 'US Dollar',
        'code' => 'USD',
        'symbol' => '$',
        'exchange_rate' => 1,
        'is_default' => false,
        'is_active' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $customerId = DB::table('accounts')->insertGetId([
        'name' => 'FK-Cost Customer',
        'code' => 'FK-COST-CUS',
        'account_type' => 'customer',
        'is_active' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $supplierId = DB::table('accounts')->insertGetId([
        'name' => 'FK-Cost Supplier',
        'code' => 'FK-COST-SUP',
        'account_type' => 'supplier',
        'is_active' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // Arrived roll batch with a real kg basis. USD-denominated (rate/exchange
    // rate = 1) so AFN conversion MUST come from the default currency / sale,
    // not from the purchase row. This is what proves the AFN costing fix.
    $purchaseId = DB::table('purchases')->insertGetId([
        'purchase_no' => 'FK-COST-PURCHASE',
        'supplier_id' => $supplierId,
        'currency_id' => $afnId,
        'status' => 'arrived',
        'exchange_rate' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $purchaseItemId = DB::table('purchase_items')->insertGetId([
        'purchase_id' => $purchaseId,
        'product_id' => $materialId,
        'purchase_currency_id' => $afnId,
        'unit' => 'roll',
        'qty' => 12000,
        'qty_sold' => 0,
        'qty_returned' => 0,
        'qty_wasted' => 0,
        'qty_available' => 12000,
        'kg_per_roll' => 2.0,
        'qty_kg' => 24000,
        'qty_kg_available' => 24000,
        'total_weight_kg' => 24000,
        'rate' => 1,
        'usd_total' => 10993.2,
        'usd_cost_per_item' => 0.0,
        'landed_cost_per_kg' => 0.45805,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $saleId = DB::table('sales')->insertGetId([
        'sale_no' => 'FK-COST-SALE',
        'customer_id' => $customerId,
        'currency_id' => $afnId,
        'status' => 'confirmed',
        'is_produced' => 0,
        'exchange_rate' => 66,
        'grand_total' => 660,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('sale_items')->insertGetId([
        'sale_id' => $saleId,
        'product_id' => $finishedProductId,
        'bom_id' => $bomId,
        'sale_currency_id' => $afnId,
        'qty' => 1,
        'total' => 660,
        'total_cost_usd' => 2,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return compact(
        'user',
        'materialId',
        'finishedProductId',
        'bomId',
        'saleId',
        'purchaseItemId'
    );
}

function makeOrder(string $number, array $fx, ?int $linkSaleId = null): ProductionOrder
{
    $order = ProductionOrder::create([
        'order_number' => $number,
        'product_id' => $fx['finishedProductId'],
        'bom_id' => $fx['bomId'],
        'quantity_ordered' => 1,
        'status' => 'pending',
        'created_by' => $fx['user']->id,
        'start_date' => today()->toDateString(),
    ]);

    if ($linkSaleId) {
        Sale::where('id', $linkSaleId)->update(['production_order_id' => $order->id]);
    }

    return $order;
}

function addSnapshotRows(ProductionOrder $order, int $materialId): void
{
    ProductionOrderMaterial::create([
        'production_order_id' => $order->id,
        'product_id' => $materialId,
        'required_quantity' => 0.8597,
        'available_quantity' => 24000,
        'shortage_quantity' => 0,
        'unit' => 'kg',
        'cost_per_unit' => 0.4581,
        'total_cost' => 0.3938,
        'consumed_quantity' => 0,
    ]);
    ProductionOrderMaterial::create([
        'production_order_id' => $order->id,
        'product_id' => $materialId,
        'required_quantity' => 0.1719,
        'available_quantity' => 24000,
        'shortage_quantity' => 0,
        'unit' => 'kg',
        'cost_per_unit' => 0.4581,
        'total_cost' => 0.0788,
        'consumed_quantity' => 0,
    ]);
}

it('starts a standalone production order with sale_id=NULL and correct AFN costing', function () {
    $fx = startProductionFixtures();
    Auth::login($fx['user']);

    $order = makeOrder('FK-STANDALONE-001', $fx);
    addSnapshotRows($order, $fx['materialId']);

    $response = (new ProductionOrderController())->startProduction($order);

    expect($response->getSession()->get('success'))->not->toBeNull();

    $order->refresh();
    expect($order->status)->toBe('in_progress')
        ->and(abs((float) $order->total_material_cost - 0.4725))->toBeLessThan(0.001)
        ->and(abs((float) $order->total_cost - 0.4725))->toBeLessThan(0.001);

    $pmc = ProductionMaterialConsumption::where('production_order_id', $order->id)->get();
    expect($pmc->count())->toBeGreaterThanOrEqual(1);

    $record = $pmc->first();
    expect($record->sale_id)->toBeNull()
        ->and($record->sale_item_id)->toBeNull()
        ->and(abs((float) $record->actual_quantity - 1.0316))->toBeLessThan(0.001)
        ->and($record->unit)->toBe('kg')
        ->and(abs((float) $record->cost_per_unit_usd - 0.45805))->toBeLessThan(0.0001)
        ->and(abs((float) $record->cost_per_unit_afn - 30.23130))->toBeLessThan(0.001)
        ->and(abs((float) $record->total_cost_usd - 0.4725))->toBeLessThan(0.001)
        ->and(abs((float) $record->total_cost_afn - 31.1866))->toBeLessThan(0.01);

    $batch = PurchaseItem::find($fx['purchaseItemId']);
    expect(abs((float) $batch->qty_kg_available - 23998.9684))->toBeLessThan(0.001)
        ->and(abs((float) $batch->qty_kg_used - 1.0316))->toBeLessThan(0.001);

    // Frozen snapshot must be untouched.
    foreach ($order->materials as $m) {
        expect((float) $m->consumed_quantity)->toBe(0.0);
    }
});

it('links a sale-linked production order to the real sale id in the PMC record', function () {
    $fx = startProductionFixtures();
    Auth::login($fx['user']);

    $order = makeOrder('FK-LINKED-001', $fx, $fx['saleId']);

    (new ProductionOrderController())->startProduction($order);

    $pmc = ProductionMaterialConsumption::where('production_order_id', $order->id)->get();
    expect($pmc->count())->toBeGreaterThanOrEqual(1)
        ->and($pmc->first()->sale_id)->toBe($fx['saleId']);

    $order->refresh();
    expect($order->status)->toBe('in_progress');
});

it('blocks a second start and does not deduct stock twice', function () {
    $fx = startProductionFixtures();
    Auth::login($fx['user']);

    $order = makeOrder('FK-DOUBLE-001', $fx);
    addSnapshotRows($order, $fx['materialId']);

    (new ProductionOrderController())->startProduction($order);
    $batchBefore = PurchaseItem::find($fx['purchaseItemId'])->qty_kg_available;

    $response = (new ProductionOrderController())->startProduction($order->fresh());

    expect($response->getSession()->get('error'))->toContain('pending');

    $order->refresh();
    expect($order->status)->toBe('in_progress')
        ->and(ProductionMaterialConsumption::where('production_order_id', $order->id)->count())->toBe(1)
        ->and(abs((float) PurchaseItem::find($fx['purchaseItemId'])->qty_kg_available - (float) $batchBefore))->toBeLessThan(0.0001);
});

it('keeps the legacy BOM-template fallback working when no frozen snapshot exists', function () {
    $fx = startProductionFixtures();
    Auth::login($fx['user']);

    $order = makeOrder('FK-LEGACY-001', $fx);

    (new ProductionOrderController())->startProduction($order);

    $order->refresh();
    expect($order->status)->toBe('in_progress')
        ->and($order->materials->count())->toBe(0);

    $pmc = ProductionMaterialConsumption::where('production_order_id', $order->id)->get();
    expect($pmc->count())->toBeGreaterThanOrEqual(1);

    // Legacy template: 2 × 0.17194 kg (5% wastage, carton_3d) = 0.34388 kg.
    $totalActual = (float) $pmc->sum('actual_quantity');
    expect(abs($totalActual - 0.34388))->toBeLessThan(0.002)
        ->and($pmc->first()->sale_id)->toBeNull();
});

it('completes production from operator-entered output and actual material consumption', function () {
    $fx = startProductionFixtures();
    Auth::login($fx['user']);

    $order = makeOrder('FK-ACTUAL-COMPLETE-001', $fx);
    addSnapshotRows($order, $fx['materialId']);

    (new ProductionOrderController())->startProduction($order);
    $order->refresh();

    $request = \Illuminate\Http\Request::create(
        '/admin/production-orders/'.$order->id.'/complete',
        'POST',
        [
            'quantity_manufactured' => 1.10,
            'quantity_produced' => 1.00,
            'quantity_rejected' => 0.10,
            'materials' => [[
                'material_id' => $fx['materialId'],
                'actual_quantity' => 1.20,
                'wastage_quantity' => 0.05,
                'unit' => 'kg',
            ]],
        ]
    );

    $response = (new ProductionOrderController())->completeProduction($order, $request);

    expect($response->getSession()->get('success'))
        ->toContain('1.10 manufactured')
        ->toContain('1.00 good/usable')
        ->toContain('0.10 rejected');

    $order->refresh();
    $consumptions = ProductionMaterialConsumption::where('production_order_id', $order->id)->get();
    $batch = PurchaseItem::findOrFail($fx['purchaseItemId']);

    expect($order->status)->toBe('completed')
        ->and((float) $order->quantity_manufactured)->toBe(1.10)
        ->and((float) $order->quantity_produced)->toBe(1.00)
        ->and((float) $order->quantity_rejected)->toBe(0.10)
        ->and(abs((float) $order->yield_percentage - 90.9090909))->toBeLessThan(0.001)
        ->and(abs((float) $consumptions->sum('actual_quantity') - 1.20))->toBeLessThan(0.0001)
        ->and(abs((float) $consumptions->sum('wastage_quantity') - 0.05))->toBeLessThan(0.0001)
        ->and(abs((float) $batch->qty_kg_available - 23998.80))->toBeLessThan(0.001);
});

it('restores unused FIFO stock when operator-entered actual material use is below the start allocation', function () {
    $fx = startProductionFixtures();
    Auth::login($fx['user']);

    $order = makeOrder('FK-ACTUAL-RESTORE-001', $fx);
    addSnapshotRows($order, $fx['materialId']);

    (new ProductionOrderController())->startProduction($order);
    $order->refresh();

    $request = \Illuminate\Http\Request::create(
        '/admin/production-orders/'.$order->id.'/complete',
        'POST',
        [
            'quantity_manufactured' => 1.00,
            'quantity_produced' => 0.95,
            'quantity_rejected' => 0.05,
            'materials' => [[
                'material_id' => $fx['materialId'],
                'actual_quantity' => 0.80,
                'wastage_quantity' => 0.02,
                'unit' => 'kg',
            ]],
        ]
    );

    (new ProductionOrderController())->completeProduction($order, $request);

    $consumptions = ProductionMaterialConsumption::where('production_order_id', $order->id)->get();
    $batch = PurchaseItem::findOrFail($fx['purchaseItemId']);

    expect(abs((float) $consumptions->sum('actual_quantity') - 0.80))->toBeLessThan(0.0001)
        ->and(abs((float) $consumptions->sum('wastage_quantity') - 0.02))->toBeLessThan(0.0001)
        ->and(abs((float) $batch->qty_kg_available - 23999.20))->toBeLessThan(0.001);
});

it('rejects completion when manufactured quantity does not equal good plus rejected', function () {
    $fx = startProductionFixtures();
    Auth::login($fx['user']);

    $order = makeOrder('FK-ACTUAL-VALIDATION-001', $fx);
    addSnapshotRows($order, $fx['materialId']);
    (new ProductionOrderController())->startProduction($order);

    $request = \Illuminate\Http\Request::create(
        '/admin/production-orders/'.$order->id.'/complete',
        'POST',
        [
            'quantity_manufactured' => 1.20,
            'quantity_produced' => 1.00,
            'quantity_rejected' => 0.10,
            'materials' => [[
                'material_id' => $fx['materialId'],
                'actual_quantity' => 1.0316,
                'wastage_quantity' => 0,
                'unit' => 'kg',
            ]],
        ]
    );

    expect(fn () => (new ProductionOrderController())->completeProduction($order->fresh(), $request))
        ->toThrow(\Illuminate\Validation\ValidationException::class);

    expect($order->fresh()->status)->toBe('in_progress');
});
