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

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData(true)['success'])->toBeTrue();

    $sale = $sale->fresh(['items', 'currency']);
    expect($sale->items)->toHaveCount(1);

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
