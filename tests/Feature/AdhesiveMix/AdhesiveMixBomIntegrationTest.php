<?php

use App\Http\Controllers\Admin\BOMController;
use App\Http\Controllers\Admin\ProductionOrderController;
use App\Http\Controllers\Admin\PurchaseItemController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\SaleController;
use App\Models\Account;
use App\Models\BOM;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\User;
use App\Services\AdhesiveMixCalculator;
use App\Services\ProductionQuantityService;
use App\Services\SaleProfitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

function adhesiveBomRequest(string $uri, string $method, array $data = []): Request
{
    $request = Request::create($uri, $method, $data);
    app()->instance('request', $request);

    return $request;
}

function adhesiveBomFixture(): array
{
    $user = User::factory()->create(['name' => 'Adhesive QA Admin']);
    Auth::login($user);

    $usd = Currency::create([
        'name' => 'US Dollar',
        'code' => 'USD',
        'symbol' => '$',
        'country' => 'United States',
        'exchange_rate' => 1,
        'is_default' => true,
        'is_active' => true,
    ]);

    $afn = Currency::create([
        'name' => 'Afghan Afghani',
        'code' => 'AFN',
        'symbol' => '؋',
        'country' => 'Afghanistan',
        'exchange_rate' => 66,
        'is_default' => false,
        'is_active' => true,
    ]);

    $supplier = Account::create([
        'name' => 'Adhesive QA Supplier',
        'code' => 'SUP-ADH-001',
        'account_type' => 'supplier',
        'is_active' => true,
        'created_by' => $user->id,
    ]);

    $customer = Account::create([
        'name' => 'Adhesive QA Customer',
        'code' => 'CUS-ADH-001',
        'account_type' => 'customer',
        'is_active' => true,
        'created_by' => $user->id,
    ]);

    $paperCategory = Category::create([
        'name' => 'Adhesive QA Paper',
        'description' => 'QA paper for adhesive BOM tests.',
        'is_active' => true,
    ]);

    $mixCategory = Category::create([
        'name' => 'Adhesive QA Mixing',
        'description' => 'QA mixing materials for adhesive BOM tests.',
        'is_active' => true,
    ]);

    $kraft = Product::create([
        'name' => 'QA Kraft Liner',
        'unit' => 'roll',
        'default_kg_per_roll' => 100,
        'min_stock_alert' => 5,
        'category_id' => $paperCategory->id,
        'type' => Product::TYPE_RAW_MATERIAL,
        'description' => 'QA kraft liner roll.',
        'is_active' => true,
    ]);

    $mixing = [];
    $mixingNames = [
        'corn_flour' => 'Corn Flour',
        'seligate' => 'Seligate (Glue)',
        'caustic_soda' => 'Caustic Soda',
        'borax' => 'Borax',
    ];

    foreach ($mixingNames as $key => $name) {
        $mixing[$key] = Product::create([
            'name' => $name,
            'unit' => 'kg',
            'min_stock_alert' => 10,
            'category_id' => $mixCategory->id,
            'type' => Product::TYPE_RAW_MATERIAL,
            'description' => 'QA mixing material.',
            'is_active' => true,
        ]);
    }

    $finished = Product::create([
        'name' => 'QA Adhesive Carton',
        'unit' => 'pcs',
        'min_stock_alert' => 100,
        'category_id' => $paperCategory->id,
        'type' => Product::TYPE_FINISHED_GOOD,
        'description' => 'QA finished carton for adhesive BOM tests.',
        'is_active' => true,
    ]);

    $poController = new PurchaseOrderController();
    $poController->store(adhesiveBomRequest('/admin/purchase-orders', 'POST', [
        'purchase_no' => 'PO-ADH-20260920-001',
        'supplier_id' => $supplier->id,
        'currency_id' => $usd->id,
    ]));

    $purchase = Purchase::where('purchase_no', 'PO-ADH-20260920-001')->firstOrFail();
    $purchase->update([
        'exchange_rate' => 1,
        'purchase_date' => '2026-09-19',
    ]);

    $itemController = new PurchaseItemController();
    $itemController->store(adhesiveBomRequest('/admin/purchase-items', 'POST', [
        'purchase_id' => $purchase->id,
        'product_id' => $kraft->id,
        'qty' => 100,
        'unit_price' => 100,
        'rate' => 1,
        'unit' => 'roll',
        'kg_per_roll' => 100,
        'batch_no' => 'ADH-KRAFT-001',
    ]));

    $unitPrices = [
        'corn_flour' => 2.5,
        'seligate' => 3.0,
        'caustic_soda' => 4.0,
        'borax' => 5.0,
    ];

    foreach ($mixing as $key => $material) {
        $itemController->store(adhesiveBomRequest('/admin/purchase-items', 'POST', [
            'purchase_id' => $purchase->id,
            'product_id' => $material->id,
            'qty' => 500,
            'unit_price' => $unitPrices[$key],
            'rate' => 1,
            'unit' => 'kg',
            'batch_no' => 'ADH-' . strtoupper($key),
        ]));
    }

    $poController->updateStatus(
        adhesiveBomRequest('/admin/purchase-orders/'.$purchase->id.'/status', 'PATCH', ['status' => 'shipping']),
        $purchase->id
    );
    $poController->updateStatus(
        adhesiveBomRequest('/admin/purchase-orders/'.$purchase->id.'/status', 'PATCH', ['status' => 'arrived']),
        $purchase->id
    );

    return compact('user', 'usd', 'afn', 'kraft', 'mixing', 'finished', 'purchase', 'customer');
}

function adhesiveBomPayload(array $fx, string $name, float $paperGsm = 125, float $dimension = 11.81): array
{
    $calculator = app(AdhesiveMixCalculator::class);

    $items = [[
        'material_id' => $fx['kraft']->id,
        'quantity' => 1,
        'unit' => 'kg',
        'wastage_percentage' => 5,
        'cost_per_unit_usd' => 1.0,
        'purchase_currency' => 'USD',
        'purchase_currency_id' => $fx['usd']->id,
        'formula_type' => 'carton_3d',
        'is_formula_based' => 1,
        'length_inch' => $dimension,
        'width_inch' => $dimension,
        'height_inch' => $dimension,
        'paper_gsm' => $paperGsm,
        'multiplication_layer' => 5,
        'per_gram_rate' => 1,
        'formula_constant' => 1550000,
        'work_percentage' => 40,
        'print' => 0,
        'notes' => 'Paper layer',
    ]];

    $prices = [
        'corn_flour' => 2.5,
        'seligate' => 3.0,
        'caustic_soda' => 4.0,
        'borax' => 5.0,
    ];

    foreach ($fx['mixing'] as $key => $material) {
        $items[] = [
            'material_id' => $material->id,
            'quantity' => $calculator->perCartonKg($dimension, $dimension, $dimension, $key),
            'unit' => 'kg',
            'wastage_percentage' => 5,
            'cost_per_unit_usd' => $prices[$key],
            'purchase_currency' => 'USD',
            'purchase_currency_id' => $fx['usd']->id,
            'formula_type' => 'adhesive_mix',
            'is_formula_based' => 1,
            'length_inch' => $dimension,
            'width_inch' => $dimension,
            'height_inch' => $dimension,
            'recipe_key' => $key,
            'glue_lines' => 4,
            'dry_glue_gsm_per_line' => 5.5,
            'glue_wastage_percentage' => 5,
            'adhesive_solids_percentage' => 35,
            'work_percentage' => 40,
            'print' => 0,
            'notes' => 'Dimension based adhesive mix',
        ];
    }

    return [
        'name' => $name,
        'product_id' => $fx['finished']->id,
        'description' => 'Adhesive mix BOM integration test.',
        'work_percentage' => 40,
        'profit_margin_percentage' => 0,
        'exchange_rate' => 1,
        'items' => $items,
        'is_active' => 1,
        'status' => 'active',
    ];
}

function adhesiveStoreBom(array $fx, string $name, float $paperGsm = 125, float $dimension = 11.81): BOM
{
    $response = (new BOMController())->store(
        adhesiveBomRequest('/admin/bom', 'POST', adhesiveBomPayload($fx, $name, $paperGsm, $dimension))
    );

    expect($response->getStatusCode())->toBe(302);

    return BOM::where('name', $name)->with('items')->firstOrFail();
}

it('creates a BOM with paper and dimension-driven adhesive rows', function () {
    $fx = adhesiveBomFixture();
    $bom = adhesiveStoreBom($fx, 'QA Adhesive BOM');

    expect($bom->items)->toHaveCount(5);

    $calculator = app(AdhesiveMixCalculator::class);

    foreach ($fx['mixing'] as $key => $material) {
        $adhesiveItem = $bom->items->firstWhere('material_id', $material->id);

        expect($adhesiveItem)->not->toBeNull()
            ->and($adhesiveItem->formula_type)->toBe('adhesive_mix')
            ->and((bool) $adhesiveItem->is_formula_based)->toBeTrue()
            ->and((float) $adhesiveItem->wastage_percentage)->toBe(0.0)
            ->and($adhesiveItem->unit)->toBe('kg')
            ->and($adhesiveItem->formula_data['recipe_key'] ?? null)->toBe($key)
            ->and((float) ($adhesiveItem->formula_data['glue_lines'] ?? 0))->toBe(4.0)
            ->and((float) ($adhesiveItem->formula_data['adhesive_solids_percentage'] ?? 0))->toBe(35.0);

        $expected = $calculator->perCartonKg(11.81, 11.81, 11.81, $key);

        expect($adhesiveItem->calculateStockKgPerUnit())->toEqualWithDelta($expected, 0.00000001)
            ->and($adhesiveItem->calculateStockRequirement(1, true))
            ->toEqualWithDelta($adhesiveItem->calculateStockRequirement(1, false), 0.00000001);
    }
});

it('recalculates adhesive when dimensions change but not when only paper gsm changes', function () {
    $fx = adhesiveBomFixture();

    $base = adhesiveStoreBom($fx, 'QA Adhesive Base', 125, 11.81);
    $gsmChanged = adhesiveStoreBom($fx, 'QA Adhesive Gsm Changed', 145, 11.81);
    $larger = adhesiveStoreBom($fx, 'QA Adhesive Larger', 125, 20);

    $cornFlourId = $fx['mixing']['corn_flour']->id;

    $baseKg = $base->items->firstWhere('material_id', $cornFlourId)->calculateStockKgPerUnit();
    $gsmChangedKg = $gsmChanged->items->firstWhere('material_id', $cornFlourId)->calculateStockKgPerUnit();
    $largerKg = $larger->items->firstWhere('material_id', $cornFlourId)->calculateStockKgPerUnit();

    // Only paper GSM changed: adhesive must be identical.
    expect($gsmChangedKg)->toEqualWithDelta($baseKg, 0.00000001);

    // Larger dimensions: adhesive must grow.
    expect($largerKg)->toBeGreaterThan($baseKg);
});

it('reprices the updated BOM from landed cost and keeps the adhesive snapshot', function () {
    $fx = adhesiveBomFixture();
    $bom = adhesiveStoreBom($fx, 'QA Adhesive Edit BOM', 125, 11.81);

    // Build the edit payload from the saved rows (ids included) and change the
    // carton dimensions. Corrupt the paper price to prove the update flow
    // reprices every row from the latest arrived landed cost.
    $payload = adhesiveBomPayload($fx, $bom->name, 125, 15.0);
    foreach ($payload['items'] as $index => &$item) {
        $existing = $bom->items->firstWhere('material_id', $item['material_id']);
        $item['id'] = $existing->id;
    }
    unset($item);
    $payload['items'][0]['cost_per_unit_usd'] = 0.68869;

    $response = (new BOMController())->update(
        adhesiveBomRequest('/admin/bom/'.$bom->id, 'PUT', $payload),
        $bom
    );

    expect($response->getStatusCode())->toBe(302);

    $updated = BOM::with('items')->findOrFail($bom->id);
    $calculator = app(AdhesiveMixCalculator::class);

    $paperItem = $updated->items->firstWhere('material_id', $fx['kraft']->id);
    $adhesiveItem = $updated->items->firstWhere('material_id', $fx['mixing']['corn_flour']->id);

    // The corrupted posted price is replaced by the arrived landed cost (1.00 USD/kg).
    expect((float) $paperItem->cost_per_unit_usd)->toEqualWithDelta(1.0, 0.000001);

    // Adhesive follows the new dimensions, keeps wastage at zero and keeps its
    // snapshot parameters.
    expect((float) $adhesiveItem->height_inch)->toBe(15.0)
        ->and((float) $adhesiveItem->wastage_percentage)->toBe(0.0)
        ->and($adhesiveItem->formula_data['recipe_key'] ?? null)->toBe('corn_flour')
        ->and($adhesiveItem->calculateStockKgPerUnit())
        ->toEqualWithDelta($calculator->perCartonKg(15.0, 15.0, 15.0, 'corn_flour'), 0.00000001)
        ->and((float) $adhesiveItem->cost_per_unit_usd)->toEqualWithDelta(2.5, 0.000001);

    expect((float) $updated->selling_price_afn)->toBeGreaterThan(0);
});

function adhesiveSaleAndProduction(array $fx, BOM $bom, int $qty = 100): array
{
    $saleNo = 'SO-ADH-'.uniqid();
    $saleController = new SaleController();
    $saleController->store(adhesiveBomRequest('/admin/sales', 'POST', [
        'sale_no' => $saleNo,
        'customer_id' => $fx['customer']->id,
        'currency_id' => $fx['afn']->id,
        'sale_date' => '2026-09-20',
        'exchange_rate' => 66,
        'notes' => 'Adhesive integration test sale.',
    ]));

    $sale = Sale::where('sale_no', $saleNo)->firstOrFail();

    $add = $saleController->addItemWithBOM(adhesiveBomRequest('/admin/sales/add-item-with-bom', 'POST', [
        'sale_id' => $sale->id,
        'bom_id' => $bom->id,
        'product_id' => $fx['finished']->id,
        'qty' => $qty,
        'exchange_rate' => 66,
        'currency_code' => 'AFN',
        'pricing_mode' => 'saved',
        'quoted_unit_price' => (float) $bom->selling_price_afn,
        'quotation_description' => 'Dimension-based adhesive integration test',
    ]));
    expect($add->getData(true)['success'])->toBeTrue();

    $confirm = $saleController->confirmSale(adhesiveBomRequest('/admin/sales/'.$sale->id.'/confirm', 'POST', [
        'discount_amount' => 0,
        'advance_payment' => 0,
        'start_production' => 1,
    ]), $sale->id);
    expect($confirm->getData(true)['success'])->toBeTrue();

    $sale->refresh();

    return [$sale, $sale->productionOrder()->firstOrFail()];
}

it('flows dimension-driven adhesive through requirements, FIFO and actual production cost', function () {
    $fx = adhesiveBomFixture();
    $bom = adhesiveStoreBom($fx, 'QA Adhesive Production BOM');
    [$sale, $production] = adhesiveSaleAndProduction($fx, $bom, 100);

    $calculator = app(AdhesiveMixCalculator::class);
    $unitPrices = [
        'corn_flour' => 2.5,
        'seligate' => 3.0,
        'caustic_soda' => 4.0,
        'borax' => 5.0,
    ];

    // 1. All four ingredients appear in the production requirements with the
    //    exact dimension-derived kg and no double wastage.
    $requirements = collect(app(ProductionQuantityService::class)->requirementsForQuantity($production, 100));

    foreach ($fx['mixing'] as $key => $material) {
        $row = $requirements->firstWhere('material_id', $material->id);
        $expectedKg = $calculator->perCartonKg(11.81, 11.81, 11.81, $key) * 100;

        expect($row)->not->toBeNull()
            ->and((float) $row['quantity'])->toEqualWithDelta($expectedKg, 0.0001)
            ->and((float) $row['planned_quantity'])->toEqualWithDelta($expectedKg, 0.0001)
            ->and((float) $row['wastage_quantity'])->toEqualWithDelta(0.0, 0.000001)
            ->and($row['unit'])->toBe('kg');
    }

    // 2. Start production: FIFO consumes the exact kg in one row per material
    //    at the landed purchase cost.
    $start = (new ProductionOrderController())->startProduction($production);
    $production->refresh();

    expect($start->getSession()->get('success'))->not->toBeNull()
        ->and($production->status)->toBe('in_progress');

    $consumptions = DB::table('production_material_consumptions')
        ->where('production_order_id', $production->id)
        ->get();

    $adhesiveTotalUsd = 0.0;

    foreach ($fx['mixing'] as $key => $material) {
        $rows = $consumptions->where('material_id', $material->id)->values();
        expect($rows)->toHaveCount(1);

        $consumption = $rows->first();
        $expectedKg = $calculator->perCartonKg(11.81, 11.81, 11.81, $key) * 100;

        expect((float) $consumption->actual_quantity)->toEqualWithDelta($expectedKg, 0.001)
            ->and((float) $consumption->cost_per_unit_usd)->toEqualWithDelta($unitPrices[$key], 0.0001)
            ->and((float) $consumption->total_cost_usd)->toEqualWithDelta($expectedKg * $unitPrices[$key], 0.001)
            ->and(strtolower((string) $consumption->unit))->toBe('kg');

        $adhesiveTotalUsd += (float) $consumption->total_cost_usd;
    }

    // 3. Adhesive costs are part of the actual (FIFO) production cost.
    $summary = app(SaleProfitService::class)->calculate($sale->fresh());

    expect($summary['actual_available'])->toBeTrue()
        ->and($summary['actual_material_cost_usd'])->toBeGreaterThanOrEqual($adhesiveTotalUsd - 0.01)
        ->and($adhesiveTotalUsd)->toBeGreaterThan(0);
});

it('detects an adhesive stock shortage before production starts', function () {
    $fx = adhesiveBomFixture();
    $bom = adhesiveStoreBom($fx, 'QA Adhesive Shortage BOM');
    [$sale, $production] = adhesiveSaleAndProduction($fx, $bom, 100);

    // Leave only 0.5 kg of corn flour available: the adhesive mix is now the
    // binding shortage for any meaningful production quantity.
    $batch = PurchaseItem::where('product_id', $fx['mixing']['corn_flour']->id)->firstOrFail();
    $batch->qty_used = (float) $batch->qty - 0.5;
    $batch->save();

    $service = app(ProductionQuantityService::class);
    $max = $service->maxProducibleQuantity($production->fresh());

    expect($max)->toBeGreaterThan(0);

    expect(fn () => $service->start($production->fresh(), $sale, $max + 10))
        ->toThrow(\RuntimeException::class);

    expect(DB::table('production_material_consumptions')
        ->where('production_order_id', $production->id)
        ->count())->toBe(0);
});
