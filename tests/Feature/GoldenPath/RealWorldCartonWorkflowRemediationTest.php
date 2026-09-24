<?php

use App\Http\Controllers\Admin\BOMController;
use App\Http\Controllers\Admin\PurchaseExpenseController;
use App\Http\Controllers\Admin\PurchaseItemController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\ProductionOrderController;
use App\Http\Controllers\Admin\TransactionsController;
use App\Models\Account;
use App\Models\BOM;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

function rwRequest(string $uri, string $method, array $data = []): Request
{
    $request = Request::create($uri, $method, $data);
    app()->instance('request', $request);
    return $request;
}

function rwBaseFixture(): array
{
    $user = User::factory()->create([
        'name' => 'Real World QA Admin',
        'email' => 'realworld.qa@example.test',
    ]);
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
        'name' => 'Zhejiang Golden Paper Co., Ltd.',
        'code' => 'SUP-RW-001',
        'account_type' => 'supplier',
        'company' => 'Zhejiang Golden Paper Co., Ltd.',
        'address' => 'Zhejiang, China',
        'is_active' => true,
        'created_by' => $user->id,
    ]);

    $freightAgent = Account::create([
        'name' => 'Silk Road Freight Services',
        'code' => 'AGT-RW-FREIGHT',
        'account_type' => 'agent',
        'address' => 'Kabul, Afghanistan',
        'is_active' => true,
        'created_by' => $user->id,
    ]);

    $customsAgent = Account::create([
        'name' => 'Kabul Customs & Inland Transport',
        'code' => 'AGT-RW-CUSTOMS',
        'account_type' => 'agent',
        'address' => 'Kabul, Afghanistan',
        'is_active' => true,
        'created_by' => $user->id,
    ]);

    $customer = Account::create([
        'name' => 'Aryana Pharmaceutical Industries',
        'code' => 'CUS-RW-001',
        'account_type' => 'customer',
        'address' => 'Kabul Industrial Park, Afghanistan',
        'is_active' => true,
        'created_by' => $user->id,
    ]);

    $paperCategory = Category::create([
        'name' => 'Real World Paper Materials',
        'description' => 'QA-only paper materials for end-to-end carton costing.',
        'is_active' => true,
    ]);

    $finishedCategory = Category::create([
        'name' => 'Real World Finished Cartons',
        'description' => 'QA-only finished cartons.',
        'is_active' => true,
    ]);

    $kraft = Product::create([
        'name' => 'Kraft Liner 150 GSM - QA',
        'unit' => 'roll',
        'default_kg_per_roll' => 320,
        'min_stock_alert' => 5,
        'category_id' => $paperCategory->id,
        'type' => Product::TYPE_RAW_MATERIAL,
        'description' => 'Imported kraft liner for outer/inner carton layers.',
        'is_active' => true,
    ]);

    $fluting = Product::create([
        'name' => 'Fluting Paper 120 GSM - QA',
        'unit' => 'roll',
        'default_kg_per_roll' => 300,
        'min_stock_alert' => 5,
        'category_id' => $paperCategory->id,
        'type' => Product::TYPE_RAW_MATERIAL,
        'description' => 'Imported corrugating medium.',
        'is_active' => true,
    ]);

    $finished = Product::create([
        'name' => '120ml Syrup Carton - 5 Layer - QA',
        'unit' => 'pcs',
        'min_stock_alert' => 100,
        'category_id' => $finishedCategory->id,
        'type' => Product::TYPE_FINISHED_GOOD,
        'description' => 'Real-world QA finished carton for 120ml syrup packs.',
        'is_active' => true,
    ]);

    return compact(
        'user', 'usd', 'afn', 'supplier', 'freightAgent', 'customsAgent',
        'customer', 'kraft', 'fluting', 'finished'
    );
}

function rwCreatePurchaseFlow(): array
{
    $fx = rwBaseFixture();

    $poController = new PurchaseOrderController();
    $poController->store(rwRequest('/admin/purchase-orders', 'POST', [
        'purchase_no' => 'PO-RW-20260919-001',
        'supplier_id' => $fx['supplier']->id,
        'currency_id' => $fx['usd']->id,
    ]));

    $purchase = Purchase::where('purchase_no', 'PO-RW-20260919-001')->firstOrFail();
    $purchase->update([
        'exchange_rate' => 1,
        'purchase_date' => '2026-09-01',
        'notes' => '40 kraft rolls + 50 fluting rolls imported from China.',
    ]);

    $itemController = new PurchaseItemController();

    $kraftResponse = $itemController->store(rwRequest('/admin/purchase-items', 'POST', [
        'purchase_id' => $purchase->id,
        'product_id' => $fx['kraft']->id,
        'qty' => 40,
        'unit_price' => 292.50,
        'rate' => 1,
        'remarks' => 'Kraft liner 150 GSM, export grade',
        'batch_no' => 'CN-KRAFT-150-0901',
        'unit' => 'roll',
        'kg_per_roll' => 320,
    ]));
    expect($kraftResponse->getStatusCode())->toBe(200);

    $flutingResponse = $itemController->store(rwRequest('/admin/purchase-items', 'POST', [
        'purchase_id' => $purchase->id,
        'product_id' => $fx['fluting']->id,
        'qty' => 50,
        'unit_price' => 238.00,
        'rate' => 1,
        'remarks' => 'Fluting paper 120 GSM',
        'batch_no' => 'CN-FLUTE-120-0901',
        'unit' => 'roll',
        'kg_per_roll' => 300,
    ]));
    expect($flutingResponse->getStatusCode())->toBe(200);

    $expenseController = new PurchaseExpenseController();

    $freightResponse = $expenseController->store(rwRequest('/admin/purchase-expenses', 'POST', [
        'purchase_id' => $purchase->id,
        'agent_id' => $fx['freightAgent']->id,
        'currency_id' => $fx['usd']->id,
        'amount' => 1500,
        'rate' => 1,
        'description' => 'China to Afghanistan freight allocation',
    ]));
    expect($freightResponse->getStatusCode())->toBe(200);

    $customsResponse = $expenseController->store(rwRequest('/admin/purchase-expenses', 'POST', [
        'purchase_id' => $purchase->id,
        'agent_id' => $fx['customsAgent']->id,
        'currency_id' => $fx['afn']->id,
        'amount' => 56100,
        'rate' => 66,
        'description' => 'Customs clearance and Kabul inland transport',
    ]));
    expect($customsResponse->getStatusCode())->toBe(200);

    $poController->updateStatus(
        rwRequest('/admin/purchase-orders/'.$purchase->id.'/status', 'PATCH', ['status' => 'shipping']),
        $purchase->id
    );
    $poController->updateStatus(
        rwRequest('/admin/purchase-orders/'.$purchase->id.'/status', 'PATCH', ['status' => 'arrived']),
        $purchase->id
    );

    $purchase->refresh();
    $kraftItem = PurchaseItem::where('purchase_id', $purchase->id)
        ->where('product_id', $fx['kraft']->id)->firstOrFail();
    $flutingItem = PurchaseItem::where('purchase_id', $purchase->id)
        ->where('product_id', $fx['fluting']->id)->firstOrFail();

    return array_merge($fx, compact('purchase', 'kraftItem', 'flutingItem'));
}

function rwCreateBom(array $fx): BOM
{
    $kraftLanded = $fx['kraftItem']->landedCostPerKg();
    $flutingLanded = $fx['flutingItem']->landedCostPerKg();

    $controller = new BOMController();
    $response = $controller->store(rwRequest('/admin/bom', 'POST', [
        'name' => '120ml Syrup Carton 5-Layer - Real World QA',
        'product_id' => $fx['finished']->id,
        'description' => 'Real-world 5-layer carton costing scenario based on arrived imported paper stock.',
        'status' => 'active',
        'is_active' => 1,
        'work_percentage' => 40,
        'profit_margin_percentage' => 15,
        'exchange_rate' => 66,
        'items' => [
            [
                'material_id' => $fx['kraft']->id,
                'quantity' => 1,
                'unit' => 'kg',
                'wastage_percentage' => 5,
                'cost_per_unit_usd' => $kraftLanded,
                'purchase_currency' => 'USD',
                'purchase_currency_id' => $fx['usd']->id,
                'formula_type' => 'carton_3d',
                'is_formula_based' => 1,
                'length_inch' => 17.32,
                'width_inch' => 15.75,
                'height_inch' => 12.20,
                'paper_gsm' => 150,
                'layers' => 2,
                'multiplication_layer' => 2,
                'per_gram_rate' => 1,
                'formula_constant' => 1550000,
                'work_percentage' => 40,
                'print' => 0,
                'notes' => 'Two kraft liner layers',
            ],
            [
                'material_id' => $fx['fluting']->id,
                'quantity' => 1,
                'unit' => 'kg',
                'wastage_percentage' => 5,
                'cost_per_unit_usd' => $flutingLanded,
                'purchase_currency' => 'USD',
                'purchase_currency_id' => $fx['usd']->id,
                'formula_type' => 'carton_3d',
                'is_formula_based' => 1,
                'length_inch' => 17.32,
                'width_inch' => 15.75,
                'height_inch' => 12.20,
                'paper_gsm' => 120,
                'layers' => 3,
                'multiplication_layer' => 3,
                'per_gram_rate' => 1,
                'formula_constant' => 1550000,
                'work_percentage' => 40,
                'print' => 0,
                'notes' => 'Three fluting layers',
            ],
        ],
    ]));

    expect($response->getStatusCode())->toBe(302);

    $bom = BOM::where('product_id', $fx['finished']->id)
        ->where('name', '120ml Syrup Carton 5-Layer - Real World QA')
        ->with('items')
        ->first();

    if (!$bom) {
        throw new RuntimeException('BOM store failed: '.($response->getSession()->get('error') ?? 'unknown error'));
    }

    return $bom;
}


function rwCreateSaleWithBom(array $fx, BOM $bom, string $saleNo): Sale
{
    $saleController = new SaleController();
    $saleController->store(rwRequest('/admin/sales', 'POST', [
        'sale_no' => $saleNo,
        'customer_id' => $fx['customer']->id,
        'currency_id' => $fx['afn']->id,
        'sale_date' => '2026-09-19',
        'exchange_rate' => 66,
        'shipping_address' => 'Kabul Industrial Park, Afghanistan',
        'notes' => 'QA order for 100 pieces of 120ml syrup cartons.',
    ]));

    $sale = Sale::where('sale_no', $saleNo)->firstOrFail();

    $response = $saleController->addItem(rwRequest('/admin/sales/add-item', 'POST', [
        'sale_id' => $sale->id,
        'items' => [[
            'bom_id' => $bom->id,
            'qty' => 100,
            'remarks' => '100 pcs production order from the canonical BOM',
        ]],
    ]));

    $payload = $response->getData(true);

    expect($response->getStatusCode())->toBe(200)
        ->and($payload['success'])->toBeTrue()
        ->and((float) $payload['sale_total'])->toBeGreaterThan(0);

    $sale = $sale->fresh(['items', 'currency']);
    expect($sale->items)->toHaveCount(1)
        ->and((float) $sale->grand_total)->toBeGreaterThan(0);

    return $sale;
}

it('runs a real-world purchase order through arrival and produces usable stock with landed kg costing', function () {
    $fx = rwCreatePurchaseFlow();

    expect((float) $fx['purchase']->usd_subtotal)->toBe(23600.0)
        ->and((float) $fx['purchase']->usd_expense_total)->toBe(2350.0)
        ->and((float) $fx['purchase']->usd_grand_total)->toBe(25950.0)
        ->and($fx['purchase']->status)->toBe('arrived')
        ->and($fx['purchase']->arrival_date)->not->toBeNull()
        ->and((float) $fx['kraftItem']->qty_available)->toBe(40.0)
        ->and((float) $fx['kraftItem']->qty_kg_available)->toBe(12800.0)
        ->and((float) $fx['flutingItem']->qty_available)->toBe(50.0)
        ->and((float) $fx['flutingItem']->qty_kg_available)->toBe(15000.0)
        ->and($fx['kraftItem']->landedCostPerKg())->toBeGreaterThan(0.9)
        ->and($fx['flutingItem']->landedCostPerKg())->toBeGreaterThan(0.8);

    $supplierTransaction = Transaction::where('table_name', 'purchases')
        ->where('table_row_id', $fx['purchase']->id)
        ->where('type', 'purchase')
        ->firstOrFail();

    expect((float) $supplierTransaction->amount)->toBe(23600.0)
        ->and($supplierTransaction->transaction_type)->toBe('credit')
        ->and((int) $supplierTransaction->currency_id)->toBe((int) $fx['usd']->id);
});

it('keeps mixed-currency purchase grand totals in one coherent purchase currency', function () {
    $fx = rwCreatePurchaseFlow();

    // PO currency is USD. The AFN customs expense is 56,100 / 66 = USD 850.
    // Therefore the coherent purchase-currency grand total is USD 25,950.
    expect((float) $fx['purchase']->grand_total)->toBe(25950.0);
});

it('returns the real landed USD per kg as latest material cost for roll-based stock', function () {
    $fx = rwCreatePurchaseFlow();

    $response = (new BOMController())->getMaterialCost($fx['kraft']->id);
    $payload = $response->getData(true);

    $expectedLandedPerKg = $fx['kraftItem']->landedCostPerKg();

    expect($payload['success'])->toBeTrue()
        ->and((float) $payload['data']['latest_cost_usd'])
        ->toBeGreaterThan(0)
        ->and(abs((float) $payload['data']['latest_cost_usd'] - $expectedLandedPerKg))
        ->toBeLessThan(0.0001);
});

it('creates a real 5-layer BOM and reconciles saved material cost with physical kg formula', function () {
    $fx = rwCreatePurchaseFlow();
    $bom = rwCreateBom($fx);

    expect($bom->status)->toBe('active')
        ->and($bom->is_active)->toBeTrue()
        ->and($bom->items)->toHaveCount(2);

    $kraftBom = $bom->items->firstWhere('material_id', $fx['kraft']->id);
    $flutingBom = $bom->items->firstWhere('material_id', $fx['fluting']->id);

    $kraftKgWithWaste = $kraftBom->calculateStockKgPerUnit() * 1.05;
    $flutingKgWithWaste = $flutingBom->calculateStockKgPerUnit() * 1.05;

    $expectedKraftCost = $kraftKgWithWaste * $fx['kraftItem']->landedCostPerKg();
    $expectedFlutingCost = $flutingKgWithWaste * $fx['flutingItem']->landedCostPerKg();
    $expectedMaterialCost = $expectedKraftCost + $expectedFlutingCost;

    $summary = app(\App\Services\BOMCostingService::class)->summarize($bom);

    expect($kraftKgWithWaste)->toBeGreaterThan(0.4)
        ->and($flutingKgWithWaste)->toBeGreaterThan(0.49)
        ->and(abs((float) $kraftBom->cost_per_unit_usd - $fx['kraftItem']->landedCostPerKg()))
        ->toBeLessThan(0.0001)
        ->and(abs((float) $flutingBom->cost_per_unit_usd - $fx['flutingItem']->landedCostPerKg()))
        ->toBeLessThan(0.0001)
        ->and(abs((float) $kraftBom->per_gram_rate - ($fx['kraftItem']->landedCostPerKg() * 66)))
        ->toBeLessThan(0.0001)
        ->and(abs((float) $flutingBom->per_gram_rate - ($fx['flutingItem']->landedCostPerKg() * 66)))
        ->toBeLessThan(0.0001)
        ->and(abs((float) $bom->total_material_cost_usd - $expectedMaterialCost))
        ->toBeLessThan(0.0001)
        ->and(abs((float) $bom->selling_price_afn - (float) $summary['selling_price_afn']))
        ->toBeLessThan(0.0001)
        ->and((float) $bom->selling_price_afn)->toBeGreaterThan((float) $bom->total_cost_afn);
});

it('keeps BOM pricing and sale quotation pricing consistent for the same 100-carton order', function () {
    $fx = rwCreatePurchaseFlow();
    $bom = rwCreateBom($fx);
    $sale = rwCreateSaleWithBom($fx, $bom, 'SO-RW-PRICE-001');

    $saleItem = $sale->items->firstOrFail();
    $expectedUnitAfn = (float) $bom->selling_price_afn;

    expect(abs((float) $saleItem->unit_price - $expectedUnitAfn))
        ->toBeLessThan(0.01)
        ->and(abs(
            (float) $saleItem->cost_per_unit_usd
            - (float) $bom->total_material_cost_usd
        ))->toBeLessThan(0.0001);
});

it('continues real imported stock through sale confirmation, production creation, FIFO start, and completion', function () {
    $fx = rwCreatePurchaseFlow();
    $bom = rwCreateBom($fx);
    $sale = rwCreateSaleWithBom($fx, $bom, 'SO-RW-PROD-001');

    $controller = new SaleController();
    $confirmResponse = $controller->confirmSale(
        rwRequest('/admin/sales/'.$sale->id.'/confirm', 'POST', [
            'discount_amount' => 0,
            'advance_payment' => 500,
            'notes' => 'Customer paid AFN 500 advance.',
            'start_production' => 1,
        ]),
        $sale->id
    );
    $confirmPayload = $confirmResponse->getData(true);

    expect($confirmPayload['success'])->toBeTrue();

    $sale->refresh();
    expect($sale->status)->toBe('confirmed')
        ->and($sale->production_order_id)->not->toBeNull()
        ->and((float) $sale->advance_payment)->toBe(500.0);

    $production = $sale->productionOrder()->firstOrFail();
    expect($production->status)->toBe('pending')
        ->and($production->materials()->count())->toBe(2);

    $kraftKgBefore = (float) $fx['kraftItem']->fresh()->qty_kg_available;
    $flutingKgBefore = (float) $fx['flutingItem']->fresh()->qty_kg_available;

    $startResponse = (new ProductionOrderController())->startProduction($production);
    $production->refresh();

    expect($startResponse->getSession()->get('success'))->not->toBeNull()
        ->and($production->status)->toBe('in_progress');

    $kraftAfter = $fx['kraftItem']->fresh();
    $flutingAfter = $fx['flutingItem']->fresh();

    expect((float) $kraftAfter->qty_kg_available)->toBeLessThan($kraftKgBefore)
        ->and((float) $flutingAfter->qty_kg_available)->toBeLessThan($flutingKgBefore);

    $consumptions = DB::table('production_material_consumptions')
        ->where('production_order_id', $production->id)
        ->get();

    expect($consumptions->count())->toBeGreaterThanOrEqual(2)
        ->and((float) $consumptions->sum('total_cost_usd'))->toBeGreaterThan(0);

    (new ProductionOrderController())->completeProduction($production);
    $production->refresh();
    $sale->refresh();

    expect($production->status)->toBe('completed')
        ->and((float) $production->quantity_produced)->toBe((float) $production->quantity_ordered)
        ->and((bool) $sale->is_produced)->toBeTrue();
});

it('reconciles actual FIFO production cost and profit after completion', function () {
    $fx = rwCreatePurchaseFlow();
    $bom = rwCreateBom($fx);
    $sale = rwCreateSaleWithBom($fx, $bom, 'SO-RW-PROFIT-001');

    $confirm = (new SaleController())->confirmSale(
        rwRequest('/admin/sales/'.$sale->id.'/confirm', 'POST', [
            'discount_amount' => 0,
            'advance_payment' => 0,
            'start_production' => 1,
        ]),
        $sale->id
    );
    expect($confirm->getData(true)['success'])->toBeTrue();

    $sale->refresh();
    $production = $sale->productionOrder()->firstOrFail();

    $start = (new ProductionOrderController())->startProduction($production);
    expect($start->getSession()->get('success'))->not->toBeNull();

    (new ProductionOrderController())->completeProduction($production);
    $sale->refresh();

    $summary = app(\App\Services\SaleProfitService::class)->calculate($sale);
    $bomSummary = app(\App\Services\BOMCostingService::class)->summarize($bom);
    $consumedUsd = (float) DB::table('production_material_consumptions')
        ->where('production_order_id', $production->id)
        ->sum('total_cost_usd');

    expect($summary['actual_available'])->toBeTrue()
        ->and(abs((float) $summary['actual_material_cost_usd'] - $consumedUsd))->toBeLessThan(0.01)
        ->and(abs((float) $summary['actual_production_cost_usd'] - $consumedUsd))->toBeLessThan(0.01)
        ->and(abs(
            (float) $summary['standard_work_profit_afn']
            - ((float) $bomSummary['standard_work_profit_afn'] * 100)
        ))->toBeLessThan(0.01)
        ->and(abs(
            (float) $summary['actual_profit_afn']
            - ((float) $summary['gross_sales_afn'] - (float) $summary['actual_production_cost_afn'])
        ))->toBeLessThan(0.01);
});

it('applies a linked customer payment to the exact confirmed sale and reduces its due balance', function () {
    $fx = rwCreatePurchaseFlow();
    $bom = rwCreateBom($fx);
    $sale = rwCreateSaleWithBom($fx, $bom, 'SO-RW-PAY-001');

    $confirmResponse = (new SaleController())->confirmSale(
        rwRequest('/admin/sales/'.$sale->id.'/confirm', 'POST', [
            'discount_amount' => 0,
            'advance_payment' => 500,
            'start_production' => 0,
        ]),
        $sale->id
    );
    expect($confirmResponse->getData(true)['success'])->toBeTrue();

    $sale->refresh();
    $dueBefore = (float) $sale->due_amount;
    expect($dueBefore)->toBeGreaterThan(1000);

    $paymentRequest = rwRequest('/admin/transactions', 'POST', [
        'account_id' => $fx['customer']->id,
        'currency_id' => $fx['afn']->id,
        'amount' => 1000,
        'transaction_type' => 'credit',
        'sale_id' => $sale->id,
        'description' => 'Linked QA customer payment against SO-RW-PAY-001',
    ]);
    $paymentRequest->headers->set('X-Requested-With', 'XMLHttpRequest');

    $paymentResponse = (new TransactionsController())->store($paymentRequest);
    expect($paymentResponse->getStatusCode())->toBe(200);

    $sale->refresh();

    expect(abs((float) $sale->due_amount - ($dueBefore - 1000)))->toBeLessThan(0.01)
        ->and((float) $sale->advance_payment)->toBe(1500.0)
        ->and(Transaction::where('table_name', 'sales')
            ->where('table_row_id', $sale->id)
            ->where('transaction_type', 'credit')
            ->where('amount', 1000)
            ->exists())->toBeTrue();
});

it('completes below the customer order, restores unused raw material, and invoices only actual output', function () {
    $fx = rwCreatePurchaseFlow();
    $bom = rwCreateBom($fx);
    $sale = rwCreateSaleWithBom($fx, $bom, 'SO-RW-ACTUAL-UNDER-001');

    $confirm = (new SaleController())->confirmSale(
        rwRequest('/admin/sales/'.$sale->id.'/confirm', 'POST', [
            'discount_amount' => 0,
            'advance_payment' => 0,
            'start_production' => 1,
        ]),
        $sale->id
    );
    expect($confirm->getData(true)['success'])->toBeTrue();

    $sale->refresh();
    $production = $sale->productionOrder()->firstOrFail();

    $start = (new ProductionOrderController())->startProduction($production);
    expect($start->getSession()->get('success'))->not->toBeNull();

    $production->refresh();
    $sale->refresh();

    $consumedBefore = (float) DB::table('production_material_consumptions')
        ->where('production_order_id', $production->id)
        ->sum('actual_quantity');
    $kraftAfterStart = (float) $fx['kraftItem']->fresh()->qty_kg_available;
    $flutingAfterStart = (float) $fx['flutingItem']->fresh()->qty_kg_available;

    $underMaterials = DB::table('production_material_consumptions')
        ->where('production_order_id', $production->id)
        ->selectRaw('material_id, MAX(unit) AS unit, SUM(actual_quantity) AS actual_quantity')
        ->groupBy('material_id')
        ->get()
        ->map(fn ($row) => [
            'material_id' => (int) $row->material_id,
            'actual_quantity' => (float) $row->actual_quantity * 0.8,
            'wastage_quantity' => 0,
            'unit' => $row->unit,
        ])
        ->values()
        ->all();

    $completeRequest = rwRequest(
        '/admin/production-orders/'.$production->id.'/complete',
        'POST',
        [
            'quantity_manufactured' => 80,
            'quantity_produced' => 80,
            'quantity_rejected' => 0,
            'materials' => $underMaterials,
        ]
    );
    $complete = (new ProductionOrderController())->completeProduction($production, $completeRequest);
    expect($complete->getSession()->get('success'))->toContain('80.00 manufactured');

    $production->refresh();
    $sale->refresh()->load(['items', 'currency']);

    $saleItem = $sale->items->firstOrFail();
    $consumedAfter = (float) DB::table('production_material_consumptions')
        ->where('production_order_id', $production->id)
        ->sum('actual_quantity');

    expect((float) $production->quantity_ordered)->toBe(100.0)
        ->and((float) $production->quantity_produced)->toBe(80.0)
        ->and($production->status)->toBe('completed')
        ->and($production->is_completed)->toBeTrue()
        ->and($consumedAfter)->toBeLessThan($consumedBefore)
        ->and(abs($consumedAfter - ($consumedBefore * 0.8)))->toBeLessThan(0.01)
        ->and((float) $fx['kraftItem']->fresh()->qty_kg_available)->toBeGreaterThan($kraftAfterStart)
        ->and((float) $fx['flutingItem']->fresh()->qty_kg_available)->toBeGreaterThan($flutingAfterStart)
        ->and((float) $saleItem->ordered_qty)->toBe(100.0)
        ->and((float) $saleItem->qty)->toBe(80.0)
        ->and(abs((float) $saleItem->total - ((float) $saleItem->unit_price * 80)))->toBeLessThan(0.01)
        ->and(abs((float) $sale->grand_total - (float) $saleItem->total))->toBeLessThan(0.01);

    $invoiceDebit = Transaction::query()
        ->where('type', 'sale')
        ->where('table_name', 'sales')
        ->where('table_row_id', $sale->id)
        ->where('transaction_type', 'debit')
        ->where('is_cash', false)
        ->firstOrFail();

    expect(abs((float) $invoiceDebit->amount - (float) $sale->grand_total))->toBeLessThan(0.01);

    $profit = app(\App\Services\SaleProfitService::class)->calculate($sale);
    expect(abs((float) $profit['gross_sales_afn'] - (float) $sale->grand_total))->toBeLessThan(0.01);
});

it('completes above the customer order, consumes extra FIFO stock, and expands the final invoice', function () {
    $fx = rwCreatePurchaseFlow();
    $bom = rwCreateBom($fx);
    $sale = rwCreateSaleWithBom($fx, $bom, 'SO-RW-ACTUAL-OVER-001');

    $confirm = (new SaleController())->confirmSale(
        rwRequest('/admin/sales/'.$sale->id.'/confirm', 'POST', [
            'discount_amount' => 0,
            'advance_payment' => 0,
            'start_production' => 1,
        ]),
        $sale->id
    );
    expect($confirm->getData(true)['success'])->toBeTrue();

    $sale->refresh();
    $production = $sale->productionOrder()->firstOrFail();

    $start = (new ProductionOrderController())->startProduction($production);
    expect($start->getSession()->get('success'))->not->toBeNull();

    $consumedBefore = (float) DB::table('production_material_consumptions')
        ->where('production_order_id', $production->id)
        ->sum('actual_quantity');
    $kraftAfterStart = (float) $fx['kraftItem']->fresh()->qty_kg_available;
    $flutingAfterStart = (float) $fx['flutingItem']->fresh()->qty_kg_available;

    $overMaterials = DB::table('production_material_consumptions')
        ->where('production_order_id', $production->id)
        ->selectRaw('material_id, MAX(unit) AS unit, SUM(actual_quantity) AS actual_quantity')
        ->groupBy('material_id')
        ->get()
        ->map(fn ($row) => [
            'material_id' => (int) $row->material_id,
            'actual_quantity' => (float) $row->actual_quantity * 1.2,
            'wastage_quantity' => 0,
            'unit' => $row->unit,
        ])
        ->values()
        ->all();

    $completeRequest = rwRequest(
        '/admin/production-orders/'.$production->id.'/complete',
        'POST',
        [
            'quantity_manufactured' => 120,
            'quantity_produced' => 120,
            'quantity_rejected' => 0,
            'materials' => $overMaterials,
        ]
    );
    $complete = (new ProductionOrderController())->completeProduction($production, $completeRequest);
    expect($complete->getSession()->get('success'))->toContain('120.00 manufactured');

    $production->refresh();
    $sale->refresh()->load('items');

    $saleItem = $sale->items->firstOrFail();
    $consumedAfter = (float) DB::table('production_material_consumptions')
        ->where('production_order_id', $production->id)
        ->sum('actual_quantity');

    expect((float) $production->quantity_ordered)->toBe(100.0)
        ->and((float) $production->quantity_produced)->toBe(120.0)
        ->and($consumedAfter)->toBeGreaterThan($consumedBefore)
        ->and(abs($consumedAfter - ($consumedBefore * 1.2)))->toBeLessThan(0.01)
        ->and((float) $fx['kraftItem']->fresh()->qty_kg_available)->toBeLessThan($kraftAfterStart)
        ->and((float) $fx['flutingItem']->fresh()->qty_kg_available)->toBeLessThan($flutingAfterStart)
        ->and((float) $saleItem->ordered_qty)->toBe(100.0)
        ->and((float) $saleItem->qty)->toBe(120.0)
        ->and(abs((float) $saleItem->total - ((float) $saleItem->unit_price * 120)))->toBeLessThan(0.01)
        ->and(abs((float) $sale->grand_total - (float) $saleItem->total))->toBeLessThan(0.01);

    $invoiceDebit = Transaction::query()
        ->where('type', 'sale')
        ->where('table_name', 'sales')
        ->where('table_row_id', $sale->id)
        ->where('transaction_type', 'debit')
        ->where('is_cash', false)
        ->firstOrFail();

    expect(abs((float) $invoiceDebit->amount - (float) $sale->grand_total))->toBeLessThan(0.01);
});

it('keeps roll-paper consumption formula-authoritative when browser values try to exceed plan', function () {
    $fx = rwCreatePurchaseFlow();
    $bom = rwCreateBom($fx);
    $sale = rwCreateSaleWithBom($fx, $bom, 'SO-RW-MATERIAL-OVERRUN-001');

    $confirm = (new SaleController())->confirmSale(
        rwRequest('/admin/sales/'.$sale->id.'/confirm', 'POST', [
            'discount_amount' => 0,
            'advance_payment' => 0,
            'start_production' => 1,
        ]),
        $sale->id
    );

    expect($confirm->getData(true)['success'])->toBeTrue();

    $sale->refresh();
    $production = $sale->productionOrder()->firstOrFail();

    $start = (new ProductionOrderController())->startProduction($production);
    expect($start->getSession()->get('success'))->not->toBeNull();

    $sale->refresh()->load(['items', 'currency']);
    $before = app(\App\Services\SaleProfitService::class)->calculate($sale);
    $revenueBefore = (float) $sale->grand_total;

    // Roll paper is deliberately formula-authoritative: a browser/operator value
    // cannot inflate or reduce paper usage because large reels are not weighed per
    // job. Measured overrides are reserved for eligible non-roll formula materials.
    $browserMaterials = DB::table('production_material_consumptions')
        ->where('production_order_id', $production->id)
        ->selectRaw('material_id, MAX(unit) AS unit, SUM(actual_quantity) AS actual_quantity')
        ->groupBy('material_id')
        ->get()
        ->map(fn ($row) => [
            'material_id' => (int) $row->material_id,
            'actual_quantity' => (float) $row->actual_quantity * 1.20,
            'wastage_quantity' => 0,
            'unit' => $row->unit,
        ])
        ->values()
        ->all();

    $complete = (new ProductionOrderController())->completeProduction(
        $production,
        rwRequest(
            '/admin/production-orders/'.$production->id.'/complete',
            'POST',
            [
                'quantity_manufactured' => 100,
                'quantity_produced' => 100,
                'quantity_rejected' => 0,
                'materials' => $browserMaterials,
            ]
        )
    );

    expect($complete->getSession()->get('success'))->toContain('100.00 manufactured');

    $sale->refresh()->load(['items', 'currency']);
    $after = app(\App\Services\SaleProfitService::class)->calculate($sale);
    $saleItem = $sale->items->firstOrFail();

    $actualMaterialUsd = (float) DB::table('production_material_consumptions')
        ->where('production_order_id', $production->id)
        ->sum('total_cost_usd');

    expect(abs((float) $sale->grand_total - $revenueBefore))->toBeLessThan(0.01)
        ->and(abs((float) $after['actual_material_cost_usd'] - (float) $before['actual_material_cost_usd']))
        ->toBeLessThan(0.01)
        ->and(abs((float) $after['actual_production_cost_afn'] - (float) $before['actual_production_cost_afn']))
        ->toBeLessThan(0.01)
        ->and(abs((float) $after['actual_profit_afn'] - (float) $before['actual_profit_afn']))
        ->toBeLessThan(0.01)
        ->and(abs((float) $saleItem->total_cost_usd - $actualMaterialUsd))
        ->toBeLessThan(0.01)
        ->and(abs(
            (float) $after['actual_profit_afn']
            - ((float) $after['gross_sales_afn'] - (float) $after['actual_production_cost_afn'])
        ))->toBeLessThan(0.01);
});

it('starts partial production when raw material cannot support the full ordered quantity', function () {
    $fx = rwCreatePurchaseFlow();
    $bom = rwCreateBom($fx);
    $sale = rwCreateSaleWithBom($fx, $bom, 'SO-RW-ACTUAL-LIMITED-001');

    $confirm = (new SaleController())->confirmSale(
        rwRequest('/admin/sales/'.$sale->id.'/confirm', 'POST', [
            'discount_amount' => 0,
            'advance_payment' => 0,
            'start_production' => 1,
        ]),
        $sale->id
    );
    expect($confirm->getData(true)['success'])->toBeTrue();

    $sale->refresh();
    $production = $sale->productionOrder()->with('materials')->firstOrFail();

    $kraftRequirement = (float) $production->materials
        ->firstWhere('product_id', $fx['kraft']->id)
        ->required_quantity;
    $flutingRequirement = (float) $production->materials
        ->firstWhere('product_id', $fx['fluting']->id)
        ->required_quantity;

    // Limit both materials to exactly 60% of the 100-unit production plan.
    // PurchaseItem derives availability from the used counters on save, so
    // simulate genuine prior consumption rather than writing availability
    // directly.
    foreach ([
        [$fx['kraftItem'], $kraftRequirement * 0.60],
        [$fx['flutingItem'], $flutingRequirement * 0.60],
    ] as [$batch, $kgAvailable]) {
        $batch = $batch->fresh();

        $totalKg = (float) $batch->total_weight_kg;
        $usedKg = max($totalKg - $kgAvailable, 0);

        $batch->qty_kg_used = $usedKg;
        $batch->qty_used = $usedKg / max((float) $batch->kg_per_roll, 0.000001);
        $batch->save();

        expect(abs((float) $batch->fresh()->qty_kg_available - $kgAvailable))
            ->toBeLessThan(0.01);
    }

    $result = app(\App\Services\ProductionQuantityService::class)
        ->start($production->fresh(), $sale, 60.0);

    expect($result['partial_start'])->toBeTrue()
        ->and(abs((float) $result['planned_quantity'] - 60.0))->toBeLessThan(0.01)
        ->and(abs((float) $result['allocation_quantity'] - 60.0))->toBeLessThan(0.01);

    $production->refresh();
    expect($production->status)->toBe('in_progress');

    $consumedAtStart = (float) DB::table('production_material_consumptions')
        ->where('production_order_id', $production->id)
        ->sum('actual_quantity');

    $complete = app(\App\Services\ProductionQuantityService::class)
        ->complete($production, 58.0, $sale);

    $production->refresh();
    $sale->refresh()->load('items');

    $consumedAtEnd = (float) DB::table('production_material_consumptions')
        ->where('production_order_id', $production->id)
        ->sum('actual_quantity');

    $saleItem = $sale->items->firstOrFail();

    expect((float) $production->quantity_ordered)->toBe(100.0)
        ->and((float) $production->quantity_produced)->toBe(58.0)
        ->and($production->status)->toBe('completed')
        ->and($consumedAtEnd)->toBeLessThan($consumedAtStart)
        ->and(abs($consumedAtEnd - ($consumedAtStart * (58 / 60))))->toBeLessThan(0.01)
        ->and((float) $saleItem->ordered_qty)->toBe(100.0)
        ->and((float) $saleItem->qty)->toBe(58.0)
        ->and((float) data_get($complete, 'invoice.invoice_quantity'))->toBe(58.0);
});

it('calculates raw material and start cost from a manually entered production quantity below the customer order', function () {
    $fx = rwCreatePurchaseFlow();
    $bom = rwCreateBom($fx);
    $sale = rwCreateSaleWithBom($fx, $bom, 'SO-RW-PLAN-UNDER-001');

    $confirm = (new SaleController())->confirmSale(
        rwRequest('/admin/sales/'.$sale->id.'/confirm', 'POST', [
            'discount_amount' => 0,
            'advance_payment' => 0,
            'start_production' => 1,
        ]),
        $sale->id
    );
    expect($confirm->getData(true)['success'])->toBeTrue();

    $sale->refresh();
    $production = $sale->productionOrder()->firstOrFail();

    $expected = collect(
        app(\App\Services\ProductionQuantityService::class)
            ->requirementsForQuantity($production, 80.0)
    )->sum('quantity');

    $request = rwRequest(
        '/admin/production-orders/'.$production->id.'/start',
        'POST',
        ['quantity_planned' => 80]
    );
    $response = (new ProductionOrderController())->startProduction($production, $request);

    $production->refresh();
    $consumed = (float) DB::table('production_material_consumptions')
        ->where('production_order_id', $production->id)
        ->sum('actual_quantity');

    expect($response->getSession()->get('success'))->toContain('80.00')
        ->and((float) $production->quantity_ordered)->toBe(100.0)
        ->and((float) $production->quantity_planned)->toBe(80.0)
        ->and($production->status)->toBe('in_progress')
        ->and(abs($consumed - $expected))->toBeLessThan(0.01)
        ->and((float) $production->total_material_cost)->toBeGreaterThan(0);
});

it('allows a manually entered production quantity above the customer order when raw material supports it', function () {
    $fx = rwCreatePurchaseFlow();
    $bom = rwCreateBom($fx);
    $sale = rwCreateSaleWithBom($fx, $bom, 'SO-RW-PLAN-OVER-001');

    $confirm = (new SaleController())->confirmSale(
        rwRequest('/admin/sales/'.$sale->id.'/confirm', 'POST', [
            'discount_amount' => 0,
            'advance_payment' => 0,
            'start_production' => 1,
        ]),
        $sale->id
    );
    expect($confirm->getData(true)['success'])->toBeTrue();

    $sale->refresh();
    $production = $sale->productionOrder()->firstOrFail();

    $expected = collect(
        app(\App\Services\ProductionQuantityService::class)
            ->requirementsForQuantity($production, 120.0)
    )->sum('quantity');

    $result = app(\App\Services\ProductionQuantityService::class)
        ->start($production, $sale, 120.0);

    $production->refresh();

    $consumed = (float) DB::table('production_material_consumptions')
        ->where('production_order_id', $production->id)
        ->sum('actual_quantity');

    expect($result['over_order_start'])->toBeTrue()
        ->and((float) $production->quantity_ordered)->toBe(100.0)
        ->and((float) $production->quantity_planned)->toBe(120.0)
        ->and(abs($consumed - $expected))->toBeLessThan(0.01);
});

it('rejects a planned production quantity that current raw material cannot support', function () {
    $fx = rwCreatePurchaseFlow();
    $bom = rwCreateBom($fx);
    $sale = rwCreateSaleWithBom($fx, $bom, 'SO-RW-PLAN-LIMIT-001');

    $confirm = (new SaleController())->confirmSale(
        rwRequest('/admin/sales/'.$sale->id.'/confirm', 'POST', [
            'discount_amount' => 0,
            'advance_payment' => 0,
            'start_production' => 1,
        ]),
        $sale->id
    );
    expect($confirm->getData(true)['success'])->toBeTrue();

    $sale->refresh();
    $production = $sale->productionOrder()->firstOrFail();
    $service = app(\App\Services\ProductionQuantityService::class);
    $max = $service->maxProducibleQuantity($production);

    expect(fn () => $service->start($production, $sale, $max + 10))
        ->toThrow(\RuntimeException::class, 'exceeds');

    $production->refresh();

    expect($production->status)->toBe('pending')
        ->and($production->quantity_planned)->toBeNull()
        ->and(DB::table('production_material_consumptions')
            ->where('production_order_id', $production->id)
            ->count())->toBe(0);
});

it('accepts a manual selling price and quotation description without changing physical BOM cost', function () {
    $fx = rwCreatePurchaseFlow();
    $bom = rwCreateBom($fx);

    $controller = new SaleController();
    $controller->store(rwRequest('/admin/sales', 'POST', [
        'sale_no' => 'SO-RW-MANUAL-PRICE-001',
        'customer_id' => $fx['customer']->id,
        'currency_id' => $fx['afn']->id,
        'sale_date' => '2026-09-20',
        'exchange_rate' => 66,
    ]));

    $sale = Sale::where('sale_no', 'SO-RW-MANUAL-PRICE-001')->firstOrFail();
    $bomSummary = app(\App\Services\BOMCostingService::class)->summarize($bom);

    $response = $controller->addItemWithBOM(rwRequest('/admin/sales/add-item-with-bom', 'POST', [
        'sale_id' => $sale->id,
        'bom_id' => $bom->id,
        'product_id' => $fx['finished']->id,
        'qty' => 100,
        'exchange_rate' => 66,
        'currency_code' => 'AFN',
        'pricing_mode' => 'saved',
        'quoted_unit_price' => (float) $bom->selling_price_afn,
        'manual_unit_price' => 125.50,
        'quotation_description' => '120ml printed syrup carton, customer artwork revision B',
    ]));

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData(true)['success'])->toBeTrue();

    $item = $sale->fresh('items')->items->firstOrFail();

    expect((float) $item->unit_price)->toBe(125.5)
        ->and((float) $item->total)->toBe(12550.0)
        ->and($item->price_adjustment_type)->toBe('manual')
        ->and($item->quotation_description)->toBe('120ml printed syrup carton, customer artwork revision B')
        ->and(abs((float) $item->cost_per_unit_usd - (float) $bomSummary['physical_production_cost_usd']))
        ->toBeLessThan(0.0001);

    $physicalCostBefore = (float) $item->total_cost_usd;

    $manualUpdate = $controller->updateManualPrice(
        rwRequest('/admin/sales/item/'.$item->id.'/manual-price', 'PATCH', [
            'unit_price' => 130.75,
        ]),
        $item->fresh()
    );

    expect($manualUpdate->getData(true)['success'])->toBeTrue();

    $item->refresh();
    $sale->refresh();

    expect((float) $item->unit_price)->toBe(130.75)
        ->and((float) $item->total)->toBe(13075.0)
        ->and((float) $item->total_cost_usd)->toBe($physicalCostBefore)
        ->and((float) $sale->grand_total)->toBe(13075.0);

    // Production-create commercial preview: the configured Standard Work /
    // Profit is the base profit component. Manual selling price changes are a
    // separate labelled Price Override adjustment, never production cost.
    // The manual adjustment is measured from the frozen quotation price
    // stored on the sale line, not from a later/recomputed BOM summary.
    $systemPrice = (float) ($item->original_unit_price ?: $item->base_price);
    $expectedOverrideAfn = (130.75 - $systemPrice) * 100;

    $profitBreakdown = app(\App\Services\SaleProfitService::class)
        ->calculate($sale->fresh(['items.bom.items', 'currency']));

    expect((float) $profitBreakdown['standard_work_profit_afn'])->toBeGreaterThan(0)
        ->and($profitBreakdown['has_manual_price_override'])->toBeTrue()
        ->and(abs((float) $profitBreakdown['price_override_afn'] - $expectedOverrideAfn))->toBeLessThan(0.01)
        ->and(abs(
            (float) $profitBreakdown['commercial_profit_afn']
            - ((float) $profitBreakdown['standard_work_profit_afn'] + $expectedOverrideAfn)
        ))->toBeLessThan(0.01);

    $preview = (new ProductionOrderController())->getProductionMaterials(
        rwRequest('/admin/production-orders/materials', 'POST', [
            'sale_id' => $sale->id,
            'quantity' => 100,
        ])
    )->getData(true);

    expect($preview['success'])->toBeTrue()
        ->and((float) data_get($preview, 'summary.standard_work_profit_afn'))->toBeGreaterThan(0)
        ->and((bool) data_get($preview, 'summary.has_manual_price_override'))->toBeTrue()
        ->and(abs((float) data_get($preview, 'summary.price_override_afn') - $expectedOverrideAfn))->toBeLessThan(0.01);

    $productionCreateView = file_get_contents(resource_path('views/admin/production-orders/create.blade.php'));
    expect($productionCreateView)
        ->toContain('Standard Work / Profit')
        ->toContain('Price Override — Commercial')
        ->not->toContain('Expected Profit / Loss');

    $clearOverride = $controller->updateManualPrice(
        rwRequest('/admin/sales/item/'.$item->id.'/manual-price', 'PATCH', []),
        $item->fresh()
    );

    expect($clearOverride->getData(true)['success'])->toBeTrue();

    $item->refresh();
    $sale->refresh();
    expect(abs((float) $item->unit_price - $systemPrice))->toBeLessThan(0.0001)
        ->and($item->price_adjustment_type)->toBe('none')
        ->and((float) $item->total_cost_usd)->toBe($physicalCostBefore)
        ->and(abs((float) $sale->grand_total - ($systemPrice * 100)))->toBeLessThan(0.01);
});

it('creates a gate pass from the final produced invoice quantity and customer-facing line description on delivery', function () {
    $fx = rwCreatePurchaseFlow();
    $bom = rwCreateBom($fx);

    $controller = new SaleController();
    $controller->store(rwRequest('/admin/sales', 'POST', [
        'sale_no' => 'SO-RW-GATEPASS-001',
        'customer_id' => $fx['customer']->id,
        'currency_id' => $fx['afn']->id,
        'sale_date' => '2026-09-20',
        'exchange_rate' => 66,
    ]));

    $sale = Sale::where('sale_no', 'SO-RW-GATEPASS-001')->firstOrFail();

    $add = $controller->addItemWithBOM(rwRequest('/admin/sales/add-item-with-bom', 'POST', [
        'sale_id' => $sale->id,
        'bom_id' => $bom->id,
        'product_id' => $fx['finished']->id,
        'qty' => 100,
        'exchange_rate' => 66,
        'currency_code' => 'AFN',
        'pricing_mode' => 'saved',
        'manual_unit_price' => 110,
        'quotation_description' => 'Finished 120ml syrup cartons - blue print',
    ]));
    expect($add->getData(true)['success'])->toBeTrue();

    $confirm = $controller->confirmSale(
        rwRequest('/admin/sales/'.$sale->id.'/confirm', 'POST', [
            'discount_amount' => 0,
            'advance_payment' => 0,
            'start_production' => 1,
        ]),
        $sale->id
    );
    expect($confirm->getData(true)['success'])->toBeTrue();

    $sale->refresh();
    $production = $sale->productionOrder()->firstOrFail();

    app(\App\Services\ProductionQuantityService::class)
        ->start($production, $sale, 90.0);

    app(\App\Services\ProductionQuantityService::class)
        ->complete($production->fresh(), 86.0, $sale->fresh());

    $delivery = $controller->deliver($sale->id);
    expect($delivery->getSession()->get('success'))->toContain('Gate Pass');

    $sale->refresh()->load(['items', 'gatePass.items']);

    expect($sale->status)->toBe('delivered')
        ->and($sale->gatePass)->not->toBeNull()
        ->and($sale->gatePass->items)->toHaveCount(1)
        ->and((float) $sale->gatePass->items->first()->quantity)->toBe(86.0)
        ->and($sale->gatePass->items->first()->item_name)->toBe($fx['finished']->name)
        ->and($sale->gatePass->items->first()->description)->toBe('Finished 120ml syrup cartons - blue print')
        ->and((float) $sale->items->first()->qty)->toBe(86.0);
});

it('keeps printed invoice free of internal BOM remarks and quotation free of BOM details', function () {
    $fx = rwCreatePurchaseFlow();
    $bom = rwCreateBom($fx);
    $sale = rwCreateSaleWithBom($fx, $bom, 'SO-RW-CUSTOMER-DOC-001');

    $item = $sale->items->firstOrFail();
    $item->remarks = 'BOM INTERNAL SECRET '.$bom->code;
    $item->quotation_description = 'Customer-visible carton description';
    $item->save();

    $controller = new SaleController();

    $invoiceHtml = $controller->printInvoice($sale->id)->render();
    $quotationHtml = $controller->quotation($sale->id)->render();

    expect($invoiceHtml)
        ->not->toContain('BOM INTERNAL SECRET')
        ->not->toContain((string) $bom->code)
        ->and($quotationHtml)
        ->toContain('Customer-visible carton description')
        ->not->toContain('BOM INTERNAL SECRET')
        ->not->toContain((string) $bom->code);
});

it('deploys the exact client-approved 3D carton paper and mixing raw materials', function () {
    $required = [
        'Test Liner',
        'Fluting',
        'Kraft Liner',
        'Semi Kraft',
        'White Liner',
        'Box Board',
        'Seligate (Glue)',
        'Corn Flour',
        'Borax',
        'Caustic Soda',
    ];

    $names = Product::query()
        ->whereIn('name', $required)
        ->pluck('name')
        ->all();

    expect($names)->toHaveCount(count($required));

    foreach ($required as $name) {
        expect($names)->toContain($name);
    }
});



it('reconciles the exact invoice line and sale totals for a secondary production order', function () {
    $fx = rwCreatePurchaseFlow();
    $bom = rwCreateBom($fx);
    $sale = rwCreateSaleWithBom($fx, $bom, 'SO-RW-MULTI-PROD-001');

    $firstItem = $sale->items->firstOrFail();

    $addSecond = (new SaleController())->addItem(
        rwRequest('/admin/sales/add-item', 'POST', [
            'sale_id' => $sale->id,
            'items' => [[
                'bom_id' => $bom->id,
                'qty' => 50,
                'remarks' => 'Second sale line for production-link reconciliation QA',
            ]],
        ])
    );

    expect($addSecond->getData(true)['success'])->toBeTrue();

    $sale->refresh()->load('items');
    expect($sale->items)->toHaveCount(2);

    $secondItem = $sale->items->where('id', '!=', $firstItem->id)->firstOrFail();
    $firstTotalBefore = (float) $firstItem->fresh()->total;

    $confirm = (new SaleController())->confirmSale(
        rwRequest('/admin/sales/'.$sale->id.'/confirm', 'POST', [
            'discount_amount' => 0,
            'advance_payment' => 0,
            'start_production' => 1,
        ]),
        $sale->id
    );

    expect($confirm->getData(true)['success'])->toBeTrue();

    $orders = AppModelsProductionOrder::query()
        ->where('sale_id', $sale->id)
        ->orderBy('id')
        ->get();

    expect($orders)->toHaveCount(2)
        ->and($orders->pluck('sale_item_id')->map(fn ($id) => (int) $id)->all())
        ->toContain($firstItem->id, $secondItem->id);

    $secondOrder = $orders->firstWhere('sale_item_id', $secondItem->id);
    expect($secondOrder)->not->toBeNull()
        ->and((int) $sale->fresh()->production_order_id)->not->toBe((int) $secondOrder->id);

    $start = (new ProductionOrderController())->startProduction($secondOrder);
    expect($start->getSession()->get('success'))->not->toBeNull();

    $materialRows = DB::table('production_material_consumptions')
        ->where('production_order_id', $secondOrder->id)
        ->selectRaw('material_id, MAX(unit) AS unit, SUM(actual_quantity) AS actual_quantity')
        ->groupBy('material_id')
        ->get()
        ->map(fn ($row) => [
            'material_id' => (int) $row->material_id,
            'actual_quantity' => (float) $row->actual_quantity,
            'wastage_quantity' => 0,
            'unit' => $row->unit,
        ])
        ->values()
        ->all();

    $complete = (new ProductionOrderController())->completeProduction(
        $secondOrder->fresh(),
        rwRequest(
            '/admin/production-orders/'.$secondOrder->id.'/complete',
            'POST',
            [
                'quantity_manufactured' => 40,
                'quantity_produced' => 40,
                'quantity_rejected' => 0,
                'materials' => $materialRows,
            ]
        )
    );

    expect($complete->getSession()->get('success'))->toContain('40.00 manufactured');

    $sale->refresh()->load('items');
    $firstAfter = $sale->items->firstWhere('id', $firstItem->id);
    $secondAfter = $sale->items->firstWhere('id', $secondItem->id);

    expect((float) $firstAfter->qty)->toBe(100.0)
        ->and((float) $firstAfter->total)->toEqualWithDelta($firstTotalBefore, 0.01)
        ->and((float) $secondAfter->ordered_qty)->toBe(50.0)
        ->and((float) $secondAfter->qty)->toBe(40.0)
        ->and((float) $secondAfter->total)
        ->toEqualWithDelta((float) $secondAfter->unit_price * 40, 0.01)
        ->and((float) $sale->subtotal)
        ->toEqualWithDelta((float) $firstAfter->total + (float) $secondAfter->total, 0.01)
        ->and((float) $sale->grand_total)
        ->toEqualWithDelta(
            (float) $sale->subtotal
            - (float) $sale->discount_total
            + (float) $sale->tax_total
            + (float) $sale->shipping_cost,
            0.01
        )
        ->and((bool) $sale->is_produced)->toBeFalse();

    $invoiceDebit = Transaction::query()
        ->where('type', 'sale')
        ->where('table_name', 'sales')
        ->where('table_row_id', $sale->id)
        ->where('transaction_type', 'debit')
        ->where('is_cash', false)
        ->firstOrFail();

    expect((float) $invoiceDebit->amount)
        ->toEqualWithDelta((float) $sale->grand_total, 0.01);
});
