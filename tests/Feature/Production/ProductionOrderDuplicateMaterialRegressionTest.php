<?php

use App\Http\Controllers\Admin\ProductionOrderController;
use App\Models\ProductionOrder;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Focused regression for the "one production order, two manual-BOM rows,
 * SAME product_id, DIFFERENT physical requirements" case.
 *
 * Guards exactly the reported bug: the Production Order SHOW page must render
 * the frozen production_order_materials snapshot (row identity, kg quantity,
 * unit, landed USD/kg and row cost preserved independently) and must never
 * rebuild planned values from the saved BOM template (which collapsed both
 * rows to layers=1 / unit=roll / legacy USD/roll costs).
 */
function duplicateMaterialFixtures(): array
{
    $user = User::factory()->create();
    $now = now();

    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Pf-Same-Mat Category',
        'slug' => 'pf-same-mat-category',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // Raw material tracked in rolls with a kg basis (store resolution uses it).
    $materialId = DB::table('products')->insertGetId([
        'name' => 'Kraft Paper Test',
        'slug' => 'kraft-paper-test',
        'unit' => 'roll',
        'default_kg_per_roll' => 50,
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $finishedProductId = DB::table('products')->insertGetId([
        'name' => 'Test Box',
        'slug' => 'test-box',
        'unit' => 'box',
        'category_id' => $categoryId,
        'type' => 'finished_good',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $bomId = DB::table('boms')->insertGetId([
        'name' => 'PF Same-Mat BOM',
        'code' => 'PF-SAME-MAT-BOM',
        'product_id' => $finishedProductId,
        'status' => 'active',
        'is_active' => 1,
        'created_by' => $user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // Both BOM template rows reference the SAME raw material product.
    DB::table('bom_items')->insertGetId([
        'bom_id' => $bomId,
        'material_id' => $materialId,
        'quantity' => 1,
        'unit' => 'roll',
        'wastage_percentage' => 5,
        'cost_per_unit_usd' => 0.78, // legacy USD/roll rate (must NOT reach Show)
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    DB::table('bom_items')->insertGetId([
        'bom_id' => $bomId,
        'material_id' => $materialId,
        'quantity' => 1,
        'unit' => 'roll',
        'wastage_percentage' => 5,
        'cost_per_unit_usd' => 0.16, // legacy USD/roll rate (must NOT reach Show)
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
        'name' => 'PF Same-Mat Customer',
        'code' => 'PF-SAME-MAT-CUS',
        'account_type' => 'customer',
        'is_active' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $supplierId = DB::table('accounts')->insertGetId([
        'name' => 'PF Same-Mat Supplier',
        'code' => 'PF-SAME-MAT-SUP',
        'account_type' => 'supplier',
        'is_active' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $saleId = DB::table('sales')->insertGetId([
        'sale_no' => 'PF-SAME-MAT-SALE',
        'customer_id' => $customerId,
        'currency_id' => $currencyId,
        'status' => 'confirmed',
        'is_produced' => 0,
        'exchange_rate' => 66,
        'grand_total' => 660,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // Manual-BOM quotation snapshot: TWO rows, SAME material, DIFFERENT layers.
    // Row 1: 5 layers ~ 0.8597 kg. Row 2: 1 layer ~ 0.1719 kg.
    $snapshot = [
        [
            'material_id' => $materialId,
            'material_name' => 'Kraft Paper Test',
            'length' => 17.32,
            'width' => 15.75,
            'height' => 12.2,
            'paper_gsm' => 125,
            'per_gram_rate' => 40,
            'multiplication_layer' => 5,
            'formula_constant' => 1550000,
            'work_percentage' => 40,
            'wastage' => 5,
            'print_cost' => 0,
        ],
        [
            'material_id' => $materialId,
            'material_name' => 'Kraft Paper Test',
            'length' => 17.32,
            'width' => 15.75,
            'height' => 12.2,
            'paper_gsm' => 125,
            'per_gram_rate' => 52,
            'multiplication_layer' => 1,
            'formula_constant' => 1550000,
            'work_percentage' => 40,
            'wastage' => 5,
            'print_cost' => 2,
        ],
    ];

    $saleItemId = DB::table('sale_items')->insertGetId([
        'sale_id' => $saleId,
        'product_id' => $finishedProductId,
        'bom_id' => $bomId,
        'sale_currency_id' => $currencyId,
        'qty' => 1,
        'total' => 660,
        'total_cost_usd' => 2,
        'manual_bom_snapshot' => json_encode($snapshot),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // Arrived roll batch with a real kg basis (kg_per_roll = 2.0).
    // 12000 rolls x 2.0 = 24000 kg available. Landed cost 0.45805 USD/kg.
    $purchaseId = DB::table('purchases')->insertGetId([
        'purchase_no' => 'PF-SAME-MAT-PURCHASE',
        'supplier_id' => $supplierId,
        'currency_id' => $currencyId,
        'status' => 'arrived',
        'exchange_rate' => 66,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    DB::table('purchase_items')->insertGetId([
        'purchase_id' => $purchaseId,
        'product_id' => $materialId,
        'purchase_currency_id' => $currencyId,
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
        'rate' => 66,
        'usd_total' => 10993.2,
        'usd_cost_per_item' => 0.9161,
        'landed_cost_per_kg' => 0.45805,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return compact(
        'user',
        'saleId',
        'saleItemId',
        'bomId',
        'finishedProductId'
    );
}

it('persists two same-material manual-BOM rows independently and renders both on Show', function () {
    $fx = duplicateMaterialFixtures();
    Auth::login($fx['user']);

    $request = Request::create('/admin/production-orders', 'POST', [
        'product_id' => $fx['finishedProductId'],
        'bom_id' => $fx['bomId'],
        'sale_id' => $fx['saleId'],
        'quantity_ordered' => 1,
        'start_date' => today()->toDateString(),
    ]);
    app()->instance('request', $request);

    $controller = new ProductionOrderController();
    $controller->store($request);

    // ─── PERSISTENCE: two independent rows, same product, kg, distinct qty ───
    $sale = Sale::findOrFail($fx['saleId']);
    $order = ProductionOrder::findOrFail($sale->production_order_id);

    expect($order->materials->count())->toBe(2)
        ->and($order->materials->pluck('product_id')->unique()->count())->toBe(1)
        ->and($order->materials->pluck('unit')->unique()->all())->toBe(['kg'])
        ->and($order->materials->pluck('required_quantity')->map(fn ($v) => (float) $v)->sort()->values()->all())->toBe([0.1719, 0.8597]);

    $row1 = $order->materials->first(fn ($m) => abs((float) $m->required_quantity - 0.8597) < 0.0001);
    $row2 = $order->materials->first(fn ($m) => abs((float) $m->required_quantity - 0.1719) < 0.0001);

    expect($row1)->not->toBeNull()
        ->and($row2)->not->toBeNull()
        ->and(abs((float) $row1->cost_per_unit - 0.45805))->toBeLessThan(0.0001)
        ->and(abs((float) $row1->total_cost - 0.3938))->toBeLessThan(0.0001)
        ->and(abs((float) $row2->total_cost - 0.0788))->toBeLessThan(0.0001);

    // ─── SHOW: renders the frozen snapshot, not the BOM template ───
    $response = $controller->show($order->load([
        'product', 'bom', 'bom.items.material', 'materials.product', 'createdBy', 'approvedBy',
    ]));
    $html = $response->render();

    // Both independent rows with their own quantities/units/costs.
    expect($html)->toContain('0.8597')
        ->toContain('0.1719')
        ->toContain('$0.4581')
        ->toContain('؋25.99')
        ->toContain('؋5.20')
        ->toContain('؋31.19');

    // The legacy BOM-template collapse values must never appear:
    // two 0.1719 roll rows at $0.78 / $0.16 => 8.85 / 1.82 AFN.
    expect(str_contains($html, '8.85'))->toBeFalse()
        ->and(str_contains($html, '1.82'))->toBeFalse()
        ->and(str_contains($html, '$0.7800'))->toBeFalse()
        ->and(str_contains($html, '$0.1600'))->toBeFalse();

    // Material Status section shows both planned rows.
    expect(substr_count($html, 'Kraft Paper Test'))->toBeGreaterThanOrEqual(2);
});