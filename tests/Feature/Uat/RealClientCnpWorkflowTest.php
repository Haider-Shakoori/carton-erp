<?php

use App\Http\Controllers\Admin\ProductionOrderController;
use App\Http\Controllers\Admin\PurchaseExpenseController;
use App\Http\Controllers\Admin\PurchaseItemController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\TransactionsController;
use App\Models\Account;
use App\Models\BOM;
use App\Models\Currency;
use App\Models\FinishedGoodSpecification;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\BoardProfileSeeder;
use Database\Seeders\ClientCartonRawMaterialSeeder;
use Database\Seeders\CustomerCartonBomSeeder;
use Database\Seeders\CustomerCartonSizeSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

function cnpUatRequest(string $uri, string $method, array $data = []): Request
{
    $request = Request::create($uri, $method, $data);
    app()->instance('request', $request);

    return $request;
}

function cnpUatCreateCurrency(
    string $name,
    string $code,
    string $symbol,
    float $exchangeRate,
    bool $isDefault
): Currency {
    return Currency::create([
        'name' => $name,
        'code' => $code,
        'symbol' => $symbol,
        'country' => $code === 'AFN' ? 'Afghanistan' : 'United States',
        'exchange_rate' => $exchangeRate,
        'is_default' => $isDefault,
        'is_active' => true,
    ]);
}

function cnpUatCreateAccount(
    string $name,
    string $code,
    string $type,
    int $createdBy
): Account {
    return Account::create([
        'name' => $name,
        'code' => $code,
        'account_type' => $type,
        'address' => 'Kabul, Afghanistan',
        'is_active' => true,
        'created_by' => $createdBy,
    ]);
}

it('runs the seeded CNP 5-ply carton through real-client purchase, sale, production actuals, payment, variance, and profit UAT', function () {
    $user = User::factory()->create([
        'name' => 'Real Client UAT Admin',
        'email' => 'real-client-uat@example.test',
    ]);
    Auth::login($user);

    // Seed the exact client master-data chain used by DatabaseSeeder. The CI
    // fresh-install stage separately verifies DatabaseSeeder itself; invoking
    // PermissionsSeeder inside RefreshDatabase would invalidate MySQL's nested
    // test savepoint before this UAT starts.
    $this->seed([
        ClientCartonRawMaterialSeeder::class,
        BoardProfileSeeder::class,
        CustomerCartonSizeSeeder::class,
        CustomerCartonBomSeeder::class,
    ]);

    $usd = cnpUatCreateCurrency('US Dollar', 'USD', '$', 1, true);
    $afn = cnpUatCreateCurrency('Afghan Afghani', 'AFN', '؋', 66, false);

    $supplier = cnpUatCreateAccount(
        'Real Client UAT Paper & Chemical Supplier',
        'SUP-CNP-UAT-001',
        Account::TYPE_SUPPLIER,
        $user->id
    );
    $freightAgent = cnpUatCreateAccount(
        'Real Client UAT Freight Agent',
        'AGT-CNP-UAT-FREIGHT',
        'agent',
        $user->id
    );
    $customsAgent = cnpUatCreateAccount(
        'Real Client UAT Customs Agent',
        'AGT-CNP-UAT-CUSTOMS',
        'agent',
        $user->id
    );

    // This is an actual row from the supplied client workbook:
    // CNP / Cure Net pharma / 44.5 x 39.5 x 30.5 cm / 200ml / 72pcs / 5-layer.
    $specification = FinishedGoodSpecification::query()
        ->importedClientCartons()
        ->where('source_row', 6)
        ->whereHas('customer', fn ($q) => $q->where('name', 'CNP'))
        ->with(['customer', 'product'])
        ->firstOrFail();

    expect($specification->source_size_raw)->toBe('(44.5*39.5*30.5)cm')
        ->and((float) $specification->length)->toBe(445.0)
        ->and((float) $specification->width)->toBe(395.0)
        ->and((float) $specification->height)->toBe(305.0)
        ->and((int) $specification->ply)->toBe(5)
        ->and((int) $specification->pieces_per_carton)->toBe(72)
        ->and($specification->historical_rate_note)->toContain('58');

    $bom = BOM::query()
        ->where('product_id', $specification->product_id)
        ->where('code', 'like', 'BOM-CLIENT-%')
        ->with('items.material')
        ->firstOrFail();

    expect($bom->status)->toBe('draft')
        ->and((bool) $bom->is_active)->toBeFalse();

    $expectedMaterials = [
        'Fluting',
        'Kraft Liner',
        'Seligate (Glue)',
        'Corn Flour',
        'Borax',
        'Caustic Soda',
    ];

    $bomMaterialNames = $bom->items
        ->pluck('material.name')
        ->sort()
        ->values()
        ->all();
    $sortedExpectedMaterials = $expectedMaterials;
    sort($sortedExpectedMaterials);

    expect($bomMaterialNames)->toBe($sortedExpectedMaterials);

    // Purchase the exact six materials the selected seeded BOM uses.
    $purchaseController = new PurchaseOrderController();
    $purchaseController->store(cnpUatRequest('/admin/purchase-orders', 'POST', [
        'purchase_no' => 'PO-CNP-UAT-001',
        'supplier_id' => $supplier->id,
        'currency_id' => $usd->id,
    ]));

    $purchase = Purchase::where('purchase_no', 'PO-CNP-UAT-001')->firstOrFail();
    $purchase->update([
        'exchange_rate' => 1,
        'purchase_date' => '2026-09-21',
        'notes' => 'Real-client UAT stock for the seeded CNP 5-ply carton.',
    ]);

    $materialPurchasePlan = [
        'Fluting' => [
            'qty' => 20,
            'unit_price' => 425,
            'unit' => 'roll',
            'kg_per_roll' => 500,
            'batch_no' => 'CNP-UAT-FLUTING-001',
        ],
        'Kraft Liner' => [
            'qty' => 20,
            'unit_price' => 465,
            'unit' => 'roll',
            'kg_per_roll' => 500,
            'batch_no' => 'CNP-UAT-KRAFT-001',
        ],
        'Seligate (Glue)' => [
            'qty' => 500,
            'unit_price' => 1.30,
            'unit' => 'kg',
            'batch_no' => 'CNP-UAT-GLUE-001',
        ],
        'Corn Flour' => [
            'qty' => 1000,
            'unit_price' => 0.55,
            'unit' => 'kg',
            'batch_no' => 'CNP-UAT-CORN-001',
        ],
        'Borax' => [
            'qty' => 250,
            'unit_price' => 1.65,
            'unit' => 'kg',
            'batch_no' => 'CNP-UAT-BORAX-001',
        ],
        'Caustic Soda' => [
            'qty' => 250,
            'unit_price' => 1.75,
            'unit' => 'kg',
            'batch_no' => 'CNP-UAT-CAUSTIC-001',
        ],
    ];

    $itemController = new PurchaseItemController();

    foreach ($materialPurchasePlan as $materialName => $plan) {
        $material = Product::query()
            ->where('name', $materialName)
            ->where('type', Product::TYPE_RAW_MATERIAL)
            ->firstOrFail();

        $payload = [
            'purchase_id' => $purchase->id,
            'product_id' => $material->id,
            'qty' => $plan['qty'],
            'unit_price' => $plan['unit_price'],
            'rate' => 1,
            'remarks' => 'Real-client UAT material for seeded CNP carton',
            'batch_no' => $plan['batch_no'],
            'unit' => $plan['unit'],
        ];

        if ($plan['unit'] === 'roll') {
            $payload['kg_per_roll'] = $plan['kg_per_roll'];
        }

        $response = $itemController->store(
            cnpUatRequest('/admin/purchase-items', 'POST', $payload)
        );

        expect($response->getStatusCode())->toBe(200)
            ->and($response->getData(true)['success'])->toBeTrue();
    }

    $expenseController = new PurchaseExpenseController();

    $freight = $expenseController->store(cnpUatRequest('/admin/purchase-expenses', 'POST', [
        'purchase_id' => $purchase->id,
        'agent_id' => $freightAgent->id,
        'currency_id' => $usd->id,
        'amount' => 1200,
        'rate' => 1,
        'description' => 'China freight for CNP real-client UAT materials',
    ]));
    expect($freight->getStatusCode())->toBe(200);

    $customs = $expenseController->store(cnpUatRequest('/admin/purchase-expenses', 'POST', [
        'purchase_id' => $purchase->id,
        'agent_id' => $customsAgent->id,
        'currency_id' => $afn->id,
        'amount' => 33000,
        'rate' => 66,
        'description' => 'Customs and inland transport for CNP real-client UAT materials',
    ]));
    expect($customs->getStatusCode())->toBe(200);

    $purchaseController->updateStatus(
        cnpUatRequest('/admin/purchase-orders/'.$purchase->id.'/status', 'PATCH', ['status' => 'shipping']),
        $purchase->id
    );
    $purchaseController->updateStatus(
        cnpUatRequest('/admin/purchase-orders/'.$purchase->id.'/status', 'PATCH', ['status' => 'arrived']),
        $purchase->id
    );

    $purchase->refresh();

    expect($purchase->status)->toBe('arrived')
        ->and($purchase->arrival_date)->not->toBeNull()
        ->and($purchase->items()->count())->toBe(6)
        ->and((float) $purchase->usd_expense_total)->toBeGreaterThan(1600);

    foreach ($expectedMaterials as $materialName) {
        $material = Product::where('name', $materialName)->firstOrFail();
        $batch = PurchaseItem::query()
            ->where('purchase_id', $purchase->id)
            ->where('product_id', $material->id)
            ->firstOrFail();

        expect($batch->landedCostPerInventoryUnitUsd())->toBeGreaterThan(0)
            ->and($batch->availableInventoryQuantity())->toBeGreaterThan(0);
    }

    // The seeded BOM intentionally starts draft/zero-priced. Once real landed
    // inventory exists, refresh it from inventory and activate it for quoting.
    $bom->exchange_rate = 66;
    $bom->saveQuietly();

    $bom = app(\App\Services\BOMCostingService::class)
        ->refreshBomMaterialCosts($bom);

    $bom->forceFill([
        'status' => 'active',
        'is_active' => true,
    ])->save();

    $bom->refresh()->load('items.material');

    expect((float) $bom->total_material_cost_usd)->toBeGreaterThan(0)
        ->and((float) $bom->selling_price_afn)->toBeGreaterThan(0)
        ->and($bom->status)->toBe('active')
        ->and((bool) $bom->is_active)->toBeTrue();

    // Create the real-client sale from the seeded CNP customer + seeded CNP BOM.
    $saleController = new SaleController();
    $saleController->store(cnpUatRequest('/admin/sales', 'POST', [
        'sale_no' => 'SO-CNP-UAT-001',
        'customer_id' => $specification->customer_id,
        'currency_id' => $afn->id,
        'sale_date' => '2026-09-21',
        'exchange_rate' => 66,
        'shipping_address' => 'CNP, Kabul, Afghanistan',
        'notes' => 'Real-client UAT: CNP 44.5x39.5x30.5cm 200ml/72pcs 5-ply.',
    ]));

    $sale = Sale::where('sale_no', 'SO-CNP-UAT-001')->firstOrFail();

    $addItem = $saleController->addItem(cnpUatRequest('/admin/sales/add-item', 'POST', [
        'sale_id' => $sale->id,
        'items' => [[
            'bom_id' => $bom->id,
            'qty' => 100,
            'remarks' => '100 cartons - seeded client CNP specification',
        ]],
    ]));

    expect($addItem->getStatusCode())->toBe(200)
        ->and($addItem->getData(true)['success'])->toBeTrue();

    $sale->refresh()->load(['items', 'currency']);

    $saleItem = $sale->items->firstOrFail();

    expect((float) $saleItem->ordered_qty)->toBe(100.0)
        ->and((float) $saleItem->qty)->toBe(100.0)
        ->and(abs((float) $saleItem->unit_price - (float) $bom->selling_price_afn))
        ->toBeLessThan(0.01)
        ->and((float) $sale->grand_total)->toBeGreaterThan(0);

    $confirm = $saleController->confirmSale(
        cnpUatRequest('/admin/sales/'.$sale->id.'/confirm', 'POST', [
            'discount_amount' => 0,
            'advance_payment' => 500,
            'notes' => 'AFN 500 customer advance before production.',
            'start_production' => 1,
        ]),
        $sale->id
    );

    expect($confirm->getData(true)['success'])->toBeTrue();

    $sale->refresh();
    $production = $sale->productionOrder()->with('materials')->firstOrFail();

    expect($sale->status)->toBe('confirmed')
        ->and((float) $sale->advance_payment)->toBe(500.0)
        ->and($production->status)->toBe('pending')
        ->and($production->materials)->toHaveCount(6);

    // Start the exact 100-carton production plan. This creates provisional FIFO
    // consumption from the arrived purchase batches.
    $start = (new ProductionOrderController())->startProduction(
        $production,
        cnpUatRequest(
            '/admin/production-orders/'.$production->id.'/start',
            'POST',
            ['quantity_planned' => 100]
        )
    );

    expect($start->getSession()->get('success'))->toContain('100.00');

    $production->refresh();

    $atStart = DB::table('production_material_consumptions')
        ->where('production_order_id', $production->id)
        ->selectRaw('material_id, MAX(unit) AS unit, SUM(actual_quantity) AS actual_quantity')
        ->groupBy('material_id')
        ->get();

    expect($production->status)->toBe('in_progress')
        ->and($atStart)->toHaveCount(6)
        ->and((float) $atStart->sum('actual_quantity'))->toBeGreaterThan(0);

    // Simulate shop-floor actuals entered in the improved completion screen:
    // paper slightly over plan, mixing materials slightly under plan, and waste
    // classified inside actual consumption.
    $paperNames = ['Fluting', 'Kraft Liner'];

    $actualMaterials = $atStart->map(function ($row) use ($paperNames) {
        $material = Product::findOrFail((int) $row->material_id);
        $isPaper = in_array($material->name, $paperNames, true);
        $multiplier = $isPaper ? 1.02 : 0.98;
        $actual = (float) $row->actual_quantity * $multiplier;
        $waste = $actual * ($isPaper ? 0.015 : 0.005);

        return [
            'material_id' => (int) $row->material_id,
            'actual_quantity' => round($actual, 6),
            'wastage_quantity' => round($waste, 6),
            // Standard roll-paper consumption is always system-calculated.
            // Adhesive/mixing rows only accept a shop-floor override when the
            // operator explicitly enables Measured Actual in the completion UI.
            'use_measured_actual' => $isPaper ? 0 : 1,
            'unit' => (string) $row->unit,
        ];
    })->values()->all();

    $complete = (new ProductionOrderController())->completeProduction(
        $production,
        cnpUatRequest(
            '/admin/production-orders/'.$production->id.'/complete',
            'POST',
            [
                'quantity_manufactured' => 100,
                'quantity_produced' => 98,
                'quantity_rejected' => 2,
                'materials' => $actualMaterials,
            ]
        )
    );

    expect($complete->getSession()->get('success'))
        ->toContain('100.00 manufactured')
        ->toContain('98.00 good')
        ->toContain('2.00 rejected');

    $production->refresh();
    $sale->refresh()->load(['items', 'currency']);

    $saleItem = $sale->items->firstOrFail();

    expect($production->status)->toBe('completed')
        ->and((float) $production->quantity_manufactured)->toBe(100.0)
        ->and((float) $production->quantity_produced)->toBe(98.0)
        ->and((float) $production->quantity_rejected)->toBe(2.0)
        ->and((bool) $sale->is_produced)->toBeTrue()
        ->and((float) $saleItem->ordered_qty)->toBe(100.0)
        ->and((float) $saleItem->qty)->toBe(98.0)
        ->and(abs((float) $sale->grand_total - ((float) $saleItem->unit_price * 98)))
        ->toBeLessThan(0.01);

    $actualRows = DB::table('production_material_consumptions')
        ->where('production_order_id', $production->id)
        ->selectRaw('material_id, MAX(unit) AS unit, SUM(actual_quantity) AS actual_quantity, SUM(wastage_quantity) AS wastage_quantity, SUM(total_cost_usd) AS total_cost_usd')
        ->groupBy('material_id')
        ->get();

    expect($actualRows)->toHaveCount(6)
        ->and((float) $actualRows->sum('actual_quantity'))->toBeGreaterThan(0)
        ->and((float) $actualRows->sum('wastage_quantity'))->toBeGreaterThan(0)
        ->and((float) $actualRows->sum('total_cost_usd'))->toBeGreaterThan(0);

    foreach ($actualMaterials as $declared) {
        $persisted = $actualRows->firstWhere('material_id', $declared['material_id']);
        $material = Product::findOrFail((int) $declared['material_id']);
        $isPaper = in_array($material->name, $paperNames, true);

        expect($persisted)->not->toBeNull();

        if ($isPaper) {
            $standard = $atStart->firstWhere('material_id', $declared['material_id']);

            expect($standard)->not->toBeNull()
                ->and(abs((float) $persisted->actual_quantity - (float) $standard->actual_quantity))
                ->toBeLessThan(0.001)
                ->and((float) $persisted->wastage_quantity)
                ->toBe(0.0);
        } else {
            expect(abs((float) $persisted->actual_quantity - (float) $declared['actual_quantity']))
                ->toBeLessThan(0.001)
                ->and(abs((float) $persisted->wastage_quantity - (float) $declared['wastage_quantity']))
                ->toBeLessThan(0.001);
        }
    }

    $variance = app(\App\Services\ProductionVarianceService::class)
        ->forProductionOrder($production);

    expect($variance['has_actual'])->toBeTrue()
        ->and((float) data_get($variance, 'output.planned_quantity'))->toBe(100.0)
        ->and((float) data_get($variance, 'output.manufactured_quantity'))->toBe(100.0)
        ->and((float) data_get($variance, 'output.good_quantity'))->toBe(98.0)
        ->and((float) data_get($variance, 'output.rejected_quantity'))->toBe(2.0)
        ->and($variance['materials'])->toHaveCount(6)
        // Roll paper stayed on the standard formula, so the typed +2% browser
        // value must not create a positive variance. The measured mixing rows
        // were explicitly opted in at -2%, so they must create a negative one.
        ->and(collect($variance['materials'])->contains(
            fn ($row) => (float) $row['variance_quantity'] > 0.000001
        ))->toBeFalse()
        ->and(collect($variance['materials'])->contains(
            fn ($row) => (float) $row['variance_quantity'] < -0.000001
        ))->toBeTrue();

    $profit = app(\App\Services\SaleProfitService::class)->calculate($sale);

    expect($profit['actual_available'])->toBeTrue()
        ->and((float) $profit['actual_material_cost_usd'])->toBeGreaterThan(0)
        ->and((float) $profit['actual_production_cost_afn'])->toBeGreaterThan(0)
        ->and(abs(
            (float) $profit['actual_profit_afn']
            - ((float) $profit['gross_sales_afn'] - (float) $profit['actual_production_cost_afn'])
        ))->toBeLessThan(0.01);

    // Finish the accounting flow by settling the final produced invoice exactly.
    $sale->refresh();
    $dueBeforePayment = (float) $sale->due_amount;

    expect($dueBeforePayment)->toBeGreaterThan(0);

    $paymentRequest = cnpUatRequest('/admin/transactions', 'POST', [
        'account_id' => $specification->customer_id,
        'currency_id' => $afn->id,
        'amount' => $dueBeforePayment,
        'transaction_type' => 'credit',
        'sale_id' => $sale->id,
        'description' => 'Full settlement of CNP real-client UAT sale',
    ]);
    $paymentRequest->headers->set('X-Requested-With', 'XMLHttpRequest');

    $payment = (new TransactionsController())->store($paymentRequest);

    expect($payment->getStatusCode())->toBe(200)
        ->and($payment->getData(true)['success'])->toBeTrue();

    $sale->refresh();

    expect(abs((float) $sale->due_amount))->toBeLessThan(0.01)
        ->and(abs((float) $sale->advance_payment - (float) $sale->grand_total))
        ->toBeLessThan(0.01)
        ->and(Transaction::query()
            ->where('table_name', 'sales')
            ->where('table_row_id', $sale->id)
            ->where('transaction_type', 'credit')
            ->where('amount', $dueBeforePayment)
            ->exists())->toBeTrue();

    // Final inventory/accounting sanity: all six source batches remain valid
    // and non-negative after actual consumption was reconciled.
    foreach ($expectedMaterials as $materialName) {
        $material = Product::where('name', $materialName)->firstOrFail();
        $batch = PurchaseItem::query()
            ->where('purchase_id', $purchase->id)
            ->where('product_id', $material->id)
            ->firstOrFail();

        expect($batch->availableInventoryQuantity())->toBeGreaterThanOrEqual(0);
    }
});
