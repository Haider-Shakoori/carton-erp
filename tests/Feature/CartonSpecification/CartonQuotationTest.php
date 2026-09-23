<?php

use App\Http\Controllers\Admin\CartonQuotationController;
use App\Http\Controllers\Admin\SaleController;
use App\Models\Account;
use App\Models\BoardProfile;
use App\Models\BoardProfileLayer;
use App\Models\BOM;
use App\Models\Currency;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\CartonSpecificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Simple quotation flow (Batch 5) and specification/price freeze (Batch 6).
 */
function cqRequest(string $uri, string $method, array $data = []): Request
{
    $request = Request::create($uri, $method, $data);
    app()->instance('request', $request);

    return $request;
}

function cqFixture(): array
{
    $user = User::factory()->create();
    Auth::login($user);

    $usd = Currency::create([
        'name' => 'US Dollar',
        'code' => 'USD',
        'symbol' => '$',
        'exchange_rate' => 1,
        'is_default' => true,
        'is_active' => true,
    ]);

    $afn = Currency::create([
        'name' => 'Afghan Afghani',
        'code' => 'AFN',
        'symbol' => '؋',
        'exchange_rate' => 66,
        'is_default' => false,
        'is_active' => true,
    ]);

    $customer = Account::create([
        'name' => 'Quick Quote Customer',
        'code' => 'CUS-QQ-001',
        'account_type' => 'customer',
        'is_active' => true,
        'created_by' => $user->id,
    ]);

    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Quick Quote Finished',
        'slug' => 'quick-quote-finished-' . uniqid(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $finished = Product::create([
        'name' => 'Quick Quote Carton - QA',
        'unit' => 'pcs',
        'category_id' => $categoryId,
        'type' => Product::TYPE_FINISHED_GOOD,
        'is_active' => true,
    ]);

    $materialIds = [];
    foreach (['Fluting', 'Kraft Liner', 'Corn Flour', 'Seligate (Glue)', 'Caustic Soda', 'Borax'] as $name) {
        $materialIds[$name] = (int) Product::where('name', $name)->value('id');
    }

    $profile = BoardProfile::create([
        'name' => 'Quick Quote 5 Ply 125/145',
        'code' => 'QQ-5PLY-' . strtoupper(uniqid()),
        'ply' => 5,
        'flute_type' => 'BC',
        'wastage_percentage' => 5,
        'is_active' => true,
        'version' => '1.0',
    ]);

    BoardProfileLayer::create([
        'board_profile_id' => $profile->id,
        'sort_order' => 0,
        'role' => 'generic_client_formula',
        'component_type' => 'paper',
        'material_id' => $materialIds['Fluting'],
        'gsm' => 125,
        'multiplication_layer' => 5,
        'commercial_work_enabled' => true,
    ]);

    BoardProfileLayer::create([
        'board_profile_id' => $profile->id,
        'sort_order' => 1,
        'role' => 'generic_client_formula',
        'component_type' => 'paper',
        'material_id' => $materialIds['Kraft Liner'],
        'gsm' => 145,
        'multiplication_layer' => 1,
        'commercial_work_enabled' => true,
    ]);

    $purchaseId = DB::table('purchases')->insertGetId([
        'purchase_no' => 'PO-QQ-' . strtoupper(uniqid()),
        'currency_id' => $usd->id,
        'status' => 'arrived',
        'purchase_date' => '2026-09-01',
        'arrival_date' => '2026-09-05',
        'exchange_rate' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $landed = [
        'Fluting' => 1.0,
        'Kraft Liner' => 1.2,
        'Corn Flour' => 0.5,
        'Seligate (Glue)' => 0.6,
        'Caustic Soda' => 0.4,
        'Borax' => 1.0,
    ];

    foreach ($landed as $name => $cost) {
        $isKg = in_array($name, ['Corn Flour', 'Seligate (Glue)', 'Caustic Soda', 'Borax'], true);

        DB::table('purchase_items')->insert([
            'purchase_id' => $purchaseId,
            'product_id' => $materialIds[$name],
            'purchase_currency_id' => $usd->id,
            'qty' => $isKg ? 2000 : 20,
            'qty_available' => $isKg ? 2000 : 20,
            'unit' => $isKg ? 'kg' : 'roll',
            'kg_per_roll' => $isKg ? null : 1000,
            'qty_kg_available' => 200000,
            'qty_sold' => 0,
            'qty_returned' => 0,
            'qty_wasted' => 0,
            'landed_cost_per_kg' => $cost,
            'usd_total' => $cost * 20000,
            'usd_cost_per_item' => $cost,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $sale = Sale::create([
        'sale_no' => 'SALE-QQ-' . strtoupper(uniqid()),
        'customer_id' => $customer->id,
        'currency_id' => $afn->id,
        'status' => 'draft',
        'exchange_rate' => 66,
        'sale_date' => '2026-09-20',
    ]);

    return [
        'user' => $user,
        'usd' => $usd,
        'afn' => $afn,
        'customer' => $customer,
        'finished' => $finished,
        'profile' => $profile,
        'materials' => $materialIds,
        'sale' => $sale,
    ];
}

function cqSpecPayload(array $fx, array $overrides = []): array
{
    return array_merge([
        'product_id' => $fx['finished']->id,
        'box_style' => 'RSC',
        'length' => 30,
        'width' => 30,
        'height' => 30,
        'dimension_unit' => 'cm',
        'board_profile_id' => $fx['profile']->id,
        'ply' => 5,
        'printing_option' => 'none',
        'quantity' => 100,
    ], $overrides);
}

it('exposes board profiles and carton options to the simple quotation form', function () {
    $fx = cqFixture();
    $controller = app(CartonQuotationController::class);

    $response = $controller->options(cqRequest('/admin/sales/carton-spec/options', 'GET'));
    $data = $response->getData(true)['data'];

    $profileIds = collect($data['profiles'])->pluck('id')->map(fn ($id) => (int) $id)->all();

    expect($data['box_styles'][0]['value'])->toBe('RSC')
        ->and($data['units'])->toContain('cm')
        ->and($data['units'])->toContain('inch')
        ->and($data['flutes'])->not->toBeEmpty()
        ->and($profileIds)->toContain((int) $fx['profile']->id);
});

it('previews a carton specification without persisting anything', function () {
    $fx = cqFixture();
    $controller = app(CartonQuotationController::class);

    $before = [SaleItem::count(), BOM::count()];

    $response = $controller->calculate(
        cqRequest('/admin/sales/'.$fx['sale']->id.'/carton-spec/calculate', 'POST', cqSpecPayload($fx)),
        $fx['sale']
    );

    $data = $response->getData(true);

    expect($response->getStatusCode())->toBe(200)
        ->and($data['success'])->toBeTrue()
        ->and($data['data']['paper']['physical_kg_total'])->toBeGreaterThan(0)
        ->and($data['data']['adhesive']['kg_total'])->toBeGreaterThan(0)
        ->and($data['data']['commercial']['selling_price_afn'])->toBeGreaterThan(0)
        ->and($data['data']['shortages']['has_shortage'])->toBeFalse()
        ->and($data['data']['rows'])->toHaveCount(6)
        ->and([SaleItem::count(), BOM::count()])->toBe($before);
});

it('adds a frozen carton specification and technical BOM to a draft sale', function () {
    $fx = cqFixture();
    $controller = app(CartonQuotationController::class);

    $response = $controller->add(
        cqRequest('/admin/sales/'.$fx['sale']->id.'/carton-spec/add', 'POST', cqSpecPayload($fx, [
            'quantity' => 100,
            'quotation_description' => '30x30x30 cm 5 ply printed carton',
        ])),
        $fx['sale']
    );

    $data = $response->getData(true);

    expect($response->getStatusCode())->toBe(200)
        ->and($data['success'])->toBeTrue();

    $item = SaleItem::findOrFail($data['data']['sale_item_id']);
    $snapshot = $item->carton_spec_snapshot;

    expect($snapshot)->toBeArray()
        ->and($snapshot['status'])->toBe('quotation')
        ->and($snapshot['box_style'])->toBe('RSC')
        ->and($snapshot['ply'])->toBe(5)
        ->and($snapshot['dimensions']['unit'])->toBe('cm')
        ->and($snapshot['board_profile']['id'])->toBe((int) $fx['profile']->id)
        ->and($snapshot['board_profile']['version'])->toBe('1.0')
        ->and($snapshot['board_profile']['layers'])->toHaveCount(2)
        ->and($snapshot['rows'])->toHaveCount(6)
        ->and($snapshot['commercial']['paper_basis_afn_per_unit'])->toBeGreaterThan(0)
        ->and($snapshot['commercial']['work_profit_afn_per_unit'])->toBeGreaterThan(0)
        ->and($snapshot['accepted']['unit_price'])->toBeGreaterThan(0)
        ->and((float) $snapshot['accepted']['quantity'])->toBe(100.0)
        ->and($item->bom->items)->toHaveCount(6)
        ->and($item->bom->items->where('component_type', 'adhesive'))->toHaveCount(4)
        ->and((float) $item->total)->toBeGreaterThan(0);
});

it('previews several carton sizes with standard price and per-size override', function () {
    $fx = cqFixture();
    $controller = app(CartonQuotationController::class);

    $payload = [
        'product_id' => $fx['finished']->id,
        'box_style' => 'RSC',
        'dimension_unit' => 'cm',
        'board_profile_id' => $fx['profile']->id,
        'ply' => 5,
        'printing_option' => 'none',
        'wastage_percentage' => 5,
        'work_percentage' => 40,
        'sizes' => [
            [
                'name' => '120ml',
                'length' => 20,
                'width' => 15,
                'height' => 12,
                'quantity' => 1000,
            ],
            [
                'name' => '250ml',
                'length' => 30,
                'width' => 20,
                'height' => 15,
                'quantity' => 500,
                'quoted_unit_price' => 75,
            ],
        ],
    ];

    $response = $controller->calculateMany(
        cqRequest('/admin/sales/'.$fx['sale']->id.'/carton-spec/calculate-many', 'POST', $payload),
        $fx['sale']
    );

    $data = $response->getData(true);

    expect($response->getStatusCode())->toBe(200)
        ->and($data['success'])->toBeTrue()
        ->and($data['data']['previews'])->toHaveCount(2)
        ->and($data['data']['previews'][0]['size_name'])->toBe('120ml')
        ->and((float) $data['data']['previews'][0]['standard_unit_price'])->toBeGreaterThan(0)
        ->and((float) $data['data']['previews'][0]['price_override'])->toEqualWithDelta(0, 0.0001)
        ->and($data['data']['previews'][1]['size_name'])->toBe('250ml')
        ->and((float) $data['data']['previews'][1]['effective_unit_price'])->toBe(75.0)
        ->and((float) $data['data']['previews'][1]['price_override'])
        ->toEqualWithDelta(
            75.0 - (float) $data['data']['previews'][1]['standard_unit_price'],
            0.0001
        );
});

it('adds several size-specific BOM lines to one quotation transactionally', function () {
    $fx = cqFixture();
    $controller = app(CartonQuotationController::class);

    $payload = [
        'product_id' => $fx['finished']->id,
        'box_style' => 'RSC',
        'dimension_unit' => 'cm',
        'board_profile_id' => $fx['profile']->id,
        'ply' => 5,
        'printing_option' => 'none',
        'wastage_percentage' => 5,
        'work_percentage' => 40,
        'sizes' => [
            [
                'name' => '120ml',
                'length' => 20,
                'width' => 15,
                'height' => 12,
                'quantity' => 1000,
                'description' => '120ml customer carton',
            ],
            [
                'name' => '250ml',
                'length' => 30,
                'width' => 20,
                'height' => 15,
                'quantity' => 500,
                'quoted_unit_price' => 75,
                'description' => '250ml customer carton',
            ],
        ],
    ];

    $response = $controller->addMany(
        cqRequest('/admin/sales/'.$fx['sale']->id.'/carton-spec/add-many', 'POST', $payload),
        $fx['sale']
    );

    $data = $response->getData(true);
    $items = SaleItem::query()->where('sale_id', $fx['sale']->id)->orderBy('id')->get();

    expect($response->getStatusCode())->toBe(200)
        ->and($data['success'])->toBeTrue()
        ->and($data['data']['items'])->toHaveCount(2)
        ->and($items)->toHaveCount(2)
        ->and($items[0]->bom->name)->toBe('120ml')
        ->and($items[0]->price_adjustment_type)->toBe('none')
        ->and($items[0]->quotation_description)->toBe('120ml customer carton')
        ->and($items[1]->bom->name)->toBe('250ml')
        ->and($items[1]->price_adjustment_type)->toBe('manual')
        ->and((float) $items[1]->unit_price)->toBe(75.0)
        ->and((float) $items[1]->base_price)->toBeGreaterThan(0)
        ->and((float) $data['data']['items'][1]['price_override'])
        ->toEqualWithDelta(75.0 - (float) $items[1]->base_price, 0.0001);
});

it('updates the correct invoice line and sale total from actual quantity on a multi-size sale', function () {
    $fx = cqFixture();
    $controller = app(CartonQuotationController::class);

    $payload = [
        'product_id' => $fx['finished']->id,
        'box_style' => 'RSC',
        'dimension_unit' => 'cm',
        'board_profile_id' => $fx['profile']->id,
        'ply' => 5,
        'printing_option' => 'none',
        'wastage_percentage' => 5,
        'work_percentage' => 40,
        'sizes' => [
            [
                'name' => 'Invoice Size A',
                'length' => 20,
                'width' => 15,
                'height' => 12,
                'quantity' => 100,
            ],
            [
                'name' => 'Invoice Size B',
                'length' => 30,
                'width' => 20,
                'height' => 15,
                'quantity' => 50,
                'quoted_unit_price' => 75,
            ],
        ],
    ];

    $response = $controller->addMany(
        cqRequest('/admin/sales/'.$fx['sale']->id.'/carton-spec/add-many', 'POST', $payload),
        $fx['sale']
    );
    expect($response->getData(true)['success'])->toBeTrue();

    $sale = $fx['sale']->fresh(['items']);
    $items = $sale->items->sortBy('id')->values();
    $first = $items[0];
    $second = $items[1];

    app(\App\Services\ProductionService::class)->createProductionFromSale($sale);

    $orders = \App\Models\ProductionOrder::query()
        ->where('sale_id', $sale->id)
        ->orderBy('id')
        ->get();

    expect($orders)->toHaveCount(2)
        ->and((int) $orders[0]->sale_item_id)->toBe((int) $first->id)
        ->and((int) $orders[1]->sale_item_id)->toBe((int) $second->id)
        ->and((int) $sale->fresh()->production_order_id)->toBe((int) $orders[0]->id);

    $secondOrder = $orders[1];
    $quantityService = app(\App\Services\ProductionQuantityService::class);

    $quantityService->start($secondOrder, null, 50);
    $completed = $quantityService->complete(
        $secondOrder->fresh(),
        40,
        null,
        40,
        0,
        null
    );

    $sale = $sale->fresh(['items']);
    $first = $sale->items->firstWhere('id', $first->id);
    $second = $sale->items->firstWhere('id', $second->id);

    $expectedSecondTotal = (float) $second->unit_price * 40;
    $expectedGrandTotal = (float) $first->total + $expectedSecondTotal;

    expect((float) $second->ordered_qty)->toBe(50.0)
        ->and((float) $second->qty)->toBe(40.0)
        ->and((float) $second->total)->toEqualWithDelta($expectedSecondTotal, 0.01)
        ->and((float) $first->ordered_qty)->toBe(100.0)
        ->and((float) $first->qty)->toBe(100.0)
        ->and((float) $sale->subtotal)->toEqualWithDelta($expectedGrandTotal, 0.01)
        ->and((float) $sale->grand_total)->toEqualWithDelta($expectedGrandTotal, 0.01)
        ->and((float) data_get($completed, 'invoice.invoice_quantity'))->toBe(40.0)
        ->and((float) data_get($completed, 'invoice.grand_total'))->toEqualWithDelta($expectedGrandTotal, 0.01)
        ->and((bool) $sale->is_produced)->toBeFalse();

    $invoiceTransaction = \App\Models\Transaction::query()
        ->where('type', 'sale')
        ->where('table_name', 'sales')
        ->where('table_row_id', $sale->id)
        ->where('transaction_type', 'debit')
        ->where('status', 'active')
        ->firstOrFail();

    expect((float) $invoiceTransaction->amount)
        ->toEqualWithDelta($expectedGrandTotal, 0.01);
});

it('freezes the accepted specification at confirmation and ignores later profile and rate changes', function () {
    $fx = cqFixture();
    $controller = app(CartonQuotationController::class);

    $controller->add(
        cqRequest('/admin/sales/'.$fx['sale']->id.'/carton-spec/add', 'POST', cqSpecPayload($fx)),
        $fx['sale']
    );

    $item = SaleItem::where('sale_id', $fx['sale']->id)->firstOrFail();
    $frozenUnitPrice = (float) $item->unit_price;
    $frozenTotal = (float) $item->total;
    $frozenSnapshotPrice = (float) $item->carton_spec_snapshot['accepted']['unit_price'];
    $service = app(CartonSpecificationService::class);

    $saleController = app(SaleController::class);
    $response = $saleController->confirmSale(
        cqRequest('/admin/sales/'.$fx['sale']->id.'/confirm', 'POST', [
            'discount_amount' => 0,
            'advance_payment' => 0,
        ]),
        $fx['sale']->id
    );

    expect($response->isRedirect() || $response->getStatusCode() === 200)->toBeTrue();

    $sale = $fx['sale']->fresh(['items']);
    $item = $sale->items->first();
    $snapshot = $item->carton_spec_snapshot;

    expect($sale->status)->toBe('confirmed')
        ->and($snapshot['status'])->toBe('accepted')
        ->and($snapshot['frozen_at'])->not->toBeNull()
        ->and((float) $snapshot['accepted']['unit_price'])->toBe($frozenSnapshotPrice)
        ->and((float) $item->unit_price)->toBe($frozenUnitPrice)
        ->and((float) $item->total)->toBe($frozenTotal);

    // Change the board profile, the config defaults and landed rates AFTER the
    // order was accepted. The frozen order must not move.
    $fx['profile']->update(['wastage_percentage' => 25]);
    $fx['profile']->layers()->where('gsm', 125)->update(['gsm' => 400]);
    DB::table('purchase_items')
        ->where('product_id', $fx['materials']['Fluting'])
        ->update(['landed_cost_per_kg' => 9.99]);

    $sale->refresh()->load('items');
    $item = $sale->items->first();

    expect((float) $item->unit_price)->toBe($frozenUnitPrice)
        ->and((float) $item->carton_spec_snapshot['accepted']['unit_price'])->toBe($frozenSnapshotPrice);

    $requirements = $service->requirementsFromSnapshot($item->carton_spec_snapshot, (float) $item->qty);
    $fluting = collect($requirements)->firstWhere('material_id', $fx['materials']['Fluting']);

    // The frozen 125 GSM x 5 recipe (66 AFN/kg landed) still drives production
    // planning, not the changed 400 GSM profile at 9.99 USD/kg.
    expect((float) $fluting['cost_per_unit_usd'])->toEqualWithDelta(1.0, 0.0001)
        ->and((float) $fluting['required_quantity'])->toBeGreaterThan(0);

    // The frozen snapshot still carries the original GSM layer data.
    expect($item->carton_spec_snapshot['board_profile']['layers'][0]['gsm'])->toBe(125)
        ->and($item->carton_spec_snapshot['board_profile']['version'])->toBe('1.0');
});

it('keeps legacy sale items without a carton snapshot safe at confirmation', function () {
    $fx = cqFixture();
    $sale = $fx['sale'];

    $item = SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $fx['finished']->id,
        'qty' => 1,
        'ordered_qty' => 1,
        'unit_price' => 10,
        'final_price' => 10,
        'total' => 10,
        'rate' => 66,
        'usd_unit_price' => 10 / 66,
        'usd_total' => 10 / 66,
        'cost_per_unit_usd' => 5,
        'total_cost_usd' => 5,
    ]);

    $saleController = app(SaleController::class);
    $saleController->confirmSale(
        cqRequest('/admin/sales/'.$sale->id.'/confirm', 'POST', [
            'discount_amount' => 0,
            'advance_payment' => 0,
        ]),
        $sale->id
    );

    $item->refresh();

    expect($item->carton_spec_snapshot)->toBeNull()
        ->and((float) $item->unit_price)->toBe(10.0)
        ->and($sale->fresh()->status)->toBe('confirmed');
});

it('locks the simple quotation panel and the advanced technical BOM section in the UI', function () {
    $source = file_get_contents(resource_path('views/admin/sales/show.blade.php'));

    expect($source)
        ->toContain('Quick Carton Quotation')
        ->toContain('id="csProduct"')
        ->toContain('id="csBoardProfile"')
        ->toContain('id="csFlute"')
        ->toContain('id="csPrinting"')
        ->toContain('id="csQuantity"')
        ->toContain('Advanced / Technical BOM')
        ->toContain('Quote Multiple Sizes')
        ->toContain('id="csMultiSizeRows"')
        ->toContain('id="csCalculateManyBtn"')
        ->toContain('id="csAddManyBtn"')
        ->toContain('Standard Price')
        ->toContain('Price Override')
        ->toContain('Customer Price')
        ->toContain('carton-spec.add');
});

it('creates production from the frozen specification without duplicate material deduction', function () {
    $fx = cqFixture();
    $controller = app(CartonQuotationController::class);

    $controller->add(
        cqRequest('/admin/sales/'.$fx['sale']->id.'/carton-spec/add', 'POST', cqSpecPayload($fx, ['quantity' => 100])),
        $fx['sale']
    );

    $saleController = app(SaleController::class);
    $saleController->confirmSale(
        cqRequest('/admin/sales/'.$fx['sale']->id.'/confirm', 'POST', []),
        $fx['sale']->id
    );

    $sale = $fx['sale']->fresh(['items']);
    $item = $sale->items->first();
    $snapshot = $item->carton_spec_snapshot;

    // Change the live profile AFTER acceptance: production must still consume
    // the frozen 125x5 + 145x1 recipe.
    $fx['profile']->layers()->where('gsm', 125)->update(['gsm' => 400]);

    $productionService = app(\App\Services\ProductionService::class);
    $order = $productionService->createProductionFromSale($sale);

    expect($order)->not->toBeNull()
        ->and((float) $order->quantity_ordered)->toBe(100.0)
        ->and($order->materials)->toHaveCount(6);

    $materialIds = $order->materials->pluck('product_id')->map(fn ($id) => (int) $id)->all();
    expect(count($materialIds))->toBe(count(array_unique($materialIds)));

    $service = app(CartonSpecificationService::class);
    $expected = collect($service->requirementsFromSnapshot($snapshot, 100))->keyBy('material_id');

    foreach ($order->materials as $orderMaterial) {
        $expectedRow = $expected->get((int) $orderMaterial->product_id);
        expect($expectedRow)->not->toBeNull()
            ->and((float) $orderMaterial->required_quantity)
            ->toEqualWithDelta((float) $expectedRow['required_quantity'], 0.01);
    }

    // The frozen 125 GSM paper plan follows the snapshot, not the live 400 GSM.
    $flutingRow = $order->materials->firstWhere('product_id', $fx['materials']['Fluting']);
    $frozenFluting = $expected->get((int) $fx['materials']['Fluting']);
    expect((float) $flutingRow->required_quantity)
        ->toEqualWithDelta((float) $frozenFluting['required_quantity'], 0.01);

    // FIFO start and completion consume the frozen plan.
    $quantityService = app(\App\Services\ProductionQuantityService::class);
    $start = $quantityService->start($order, $sale, 100);
    $completed = $quantityService->complete($order->fresh(), 100, $sale);

    $consumptions = \App\Models\ProductionMaterialConsumption::where('production_order_id', $order->id)->get();
    $consumedByMaterial = $consumptions->groupBy('material_id')->map->sum('actual_quantity');

    expect($start['allocation_quantity'])->toBe(100.0)
        ->and($completed['actual_quantity'])->toBe(100.0)
        ->and($consumptions)->not->toBeEmpty();

    foreach ($expected as $materialId => $expectedRow) {
        expect((float) $consumedByMaterial[$materialId])
            ->toEqualWithDelta((float) $expectedRow['required_quantity'], 0.05);
    }

    // Invoice uses the frozen accepted unit price for the actual output.
    $sale->refresh()->load('items');
    $invoiceItem = $sale->items->first();

    expect((float) $invoiceItem->unit_price)->toBe((float) $snapshot['accepted']['unit_price'])
        ->and((float) $invoiceItem->qty)->toBe(100.0)
        ->and((float) $sale->grand_total)->toEqualWithDelta((float) $snapshot['accepted']['unit_price'] * 100, 0.01);
});

it('detects a frozen-specification shortage without blocking the production order', function () {
    $fx = cqFixture();
    $controller = app(CartonQuotationController::class);

    $controller->add(
        cqRequest('/admin/sales/'.$fx['sale']->id.'/carton-spec/add', 'POST', cqSpecPayload($fx, ['quantity' => 100])),
        $fx['sale']
    );

    // MySQL carries a BEFORE UPDATE trigger that recomputes qty_available from
    // qty - sold - used - wasted, so zero the stock through qty_wasted too.
    DB::table('purchase_items')
        ->where('product_id', $fx['materials']['Borax'])
        ->update(['qty_wasted' => 2000, 'qty_available' => 0]);

    $sale = $fx['sale']->fresh(['items']);
    $order = app(\App\Services\ProductionService::class)->createProductionFromSale($sale);

    $borax = $order->materials->firstWhere('product_id', $fx['materials']['Borax']);

    expect($borax)->not->toBeNull()
        ->and((float) $borax->shortage_quantity)->toBeGreaterThan(0);
});

it('keeps the frozen landed-rate basis in historical sale profit after rate changes', function () {
    $fx = cqFixture();
    $controller = app(CartonQuotationController::class);

    $controller->add(
        cqRequest('/admin/sales/'.$fx['sale']->id.'/carton-spec/add', 'POST', cqSpecPayload($fx, ['quantity' => 100])),
        $fx['sale']
    );

    app(SaleController::class)->confirmSale(
        cqRequest('/admin/sales/'.$fx['sale']->id.'/confirm', 'POST', []),
        $fx['sale']->id
    );

    $profitService = app(\App\Services\SaleProfitService::class);
    $before = $profitService->calculate($fx['sale']->fresh(['items', 'currency']));

    // Today's landed rates and live board profile change after acceptance.
    DB::table('purchase_items')->update(['landed_cost_per_kg' => 50.0]);
    $fx['profile']->layers()->update(['gsm' => 999]);

    $after = $profitService->calculate($fx['sale']->fresh(['items', 'currency']));

    expect((float) $after['quotation_bom_cost_afn'])
        ->toEqualWithDelta((float) $before['quotation_bom_cost_afn'], 0.01)
        ->and((float) $after['estimated_material_cost_afn'])
        ->toEqualWithDelta((float) $before['estimated_material_cost_afn'], 0.01)
        ->and((float) $after['estimated_cost_afn'])
        ->toEqualWithDelta((float) $before['estimated_cost_afn'], 0.01);
});

it('reports planned versus actual variance from real consumption records', function () {
    $fx = cqFixture();
    $controller = app(CartonQuotationController::class);

    $controller->add(
        cqRequest('/admin/sales/'.$fx['sale']->id.'/carton-spec/add', 'POST', cqSpecPayload($fx, ['quantity' => 100])),
        $fx['sale']
    );

    app(SaleController::class)->confirmSale(
        cqRequest('/admin/sales/'.$fx['sale']->id.'/confirm', 'POST', []),
        $fx['sale']->id
    );

    $sale = $fx['sale']->fresh(['items']);
    $order = app(\App\Services\ProductionService::class)->createProductionFromSale($sale);

    $quantityService = app(\App\Services\ProductionQuantityService::class);
    $quantityService->start($order, $sale, 100);

    // Produce less than planned: consumption must reconcile down to the real
    // output and unused raw material must be restored.
    $quantityService->complete($order->fresh(), 80, $sale);

    $varianceService = app(\App\Services\ProductionVarianceService::class);
    $variance = $varianceService->forSale($sale->fresh(['items', 'currency']));

    expect($variance['has_actual'])->toBeTrue()
        ->and($variance['materials'])->toHaveCount(6);

    $fluting = collect($variance['materials'])->firstWhere('material_id', $fx['materials']['Fluting']);

    expect($fluting)->not->toBeNull()
        ->and((float) $fluting['planned_quantity'])->toBeGreaterThan(0)
        ->and((float) $fluting['actual_quantity'])->toBeGreaterThan(0)
        ->and((float) $fluting['variance_quantity'])->toBeLessThan(0)
        ->and($fluting['indicator'])->toBe('favorable')
        ->and((float) $fluting['planned_wastage_quantity'])->toBeGreaterThan(0)
        ->and((float) $fluting['actual_wastage_quantity'])->toBeGreaterThanOrEqual(0);

    $summary = $variance['summary'];

    expect($summary['realized_profit_usd'])->not->toBeNull()
        ->and($summary['realized_profit_afn'])->not->toBeNull()
        ->and($summary['actual_production_cost_usd'])->toBeGreaterThan(0)
        ->and($summary['realized_margin_percentage'])->not->toBeNull();

    // Order-level report uses the same real consumption records.
    $orderVariance = $varianceService->forProductionOrder($order->fresh());
    $orderFluting = collect($orderVariance['materials'])->firstWhere('material_id', $fx['materials']['Fluting']);

    expect((float) $orderFluting['actual_quantity'])
        ->toEqualWithDelta((float) $fluting['actual_quantity'], 0.001)
        ->and($orderVariance['has_actual'])->toBeTrue();
});
