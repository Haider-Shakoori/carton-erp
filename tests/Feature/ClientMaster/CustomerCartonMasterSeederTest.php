<?php

use App\Models\Account;
use App\Models\BOM;
use App\Models\BOMItem;
use App\Models\Currency;
use App\Models\FinishedGoodSpecification;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\ProductionMaterialConsumption;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Services\ProductionQuantityService;
use Database\Seeders\ClientCartonOpeningStockSeeder;
use Database\Seeders\ClientCartonRawMaterialSeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\CustomerCartonSizeSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

function clientCartonApprovedRawMaterialNames(): array
{
    return collect(ClientCartonRawMaterialSeeder::MATERIALS)
        ->pluck('name')
        ->values()
        ->all();
}

it('keeps the raw-material master limited to the exact ten materials supplied by the client', function () {
    $expected = [
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

    expect(clientCartonApprovedRawMaterialNames())->toBe($expected);

    $this->seed(ProductSeeder::class);

    $actual = Product::query()
        ->where('type', Product::TYPE_RAW_MATERIAL)
        ->orderBy('name')
        ->pluck('name')
        ->all();

    $sortedExpected = $expected;
    sort($sortedExpected);

    expect($actual)->toBe($sortedExpected)
        ->not->toContain('Kraft Paper 120 GSM')
        ->not->toContain('Printing Ink - Cyan')
        ->not->toContain('Starch Adhesive')
        ->not->toContain('Plastic Strapping')
        ->not->toContain('Machine Oil');
});

it('imports 171 distinct finished goods with customer-free display names and reference-only historical prices', function () {
    $this->seed(CustomerCartonSizeSeeder::class);

    $specifications = FinishedGoodSpecification::query()
        ->importedClientCartons()
        ->with(['product', 'customer'])
        ->get();

    expect(
        Account::query()
            ->where('account_type', Account::TYPE_CUSTOMER)
            ->count()
    )->toBe(37)
        ->and($specifications)->toHaveCount(171)
        ->and($specifications->pluck('product_id')->unique())->toHaveCount(171)
        ->and($specifications->pluck('product.name')->unique())->toHaveCount(171)
        ->and(
            FinishedGoodSpecification::importedClientCartons()
                ->where('unit_price', '!=', 0)
                ->count()
        )->toBe(0)
        ->and(
            FinishedGoodSpecification::importedClientCartons()
                ->whereNotNull('historical_rate_note')
                ->count()
        )->toBeGreaterThan(0);

    foreach ($specifications as $specification) {
        expect($specification->product->name)->toStartWith('Carton')
            ->and($specification->product->name)
            ->not->toStartWith($specification->customer->name.' -');
    }
});

it('keeps duplicate customer carton sizes as distinct neutral variants', function () {
    $this->seed(CustomerCartonSizeSeeder::class);

    $cnp = FinishedGoodSpecification::query()
        ->importedClientCartons()
        ->whereHas('customer', fn ($q) => $q->where('name', 'CNP'))
        ->with('product')
        ->get();

    $duplicateSizedVariants = $cnp
        ->filter(fn ($spec) =>
            (float) $spec->length === 480.0
            && (float) $spec->width === 340.0
            && (float) $spec->height === 300.0
        );

    expect($duplicateSizedVariants)->toHaveCount(3)
        ->and($duplicateSizedVariants->pluck('product_id')->unique())->toHaveCount(3)
        ->and($duplicateSizedVariants->pluck('product.name')->unique())->toHaveCount(3);

    foreach ($duplicateSizedVariants as $variant) {
        expect($variant->product->name)->toStartWith('Carton');
    }
});

it('creates one safe draft BOM for every imported finished good', function () {
    $this->seed(DatabaseSeeder::class);

    $specifications = FinishedGoodSpecification::query()
        ->importedClientCartons()
        ->with('product.boms')
        ->get();

    $eligible = $specifications->filter(
        fn ($spec) =>
            (float) ($spec->length ?? 0) > 0
            && (float) ($spec->width ?? 0) > 0
            && (float) ($spec->height ?? 0) > 0
            && in_array((int) ($spec->ply ?? 0), [3, 5], true)
    );

    expect($specifications)->toHaveCount(171)
        ->and($eligible)->toHaveCount(130)
        ->and($specifications->filter(fn ($spec) => $spec->product->boms->isEmpty()))
        ->toHaveCount(0);

    $seededBoms = BOM::query()
        ->where('code', 'like', 'BOM-CLIENT-%')
        ->with('items.material')
        ->get();

    $reviewRequired = $seededBoms
        ->filter(fn ($bom) => str_starts_with((string) $bom->description, '[REVIEW REQUIRED]'));

    $calculated = $seededBoms
        ->reject(fn ($bom) => str_starts_with((string) $bom->description, '[REVIEW REQUIRED]'));

    expect($seededBoms)->toHaveCount(171)
        ->and($calculated)->toHaveCount(130)
        ->and($reviewRequired)->toHaveCount(41);

    foreach ($seededBoms as $bom) {
        expect($bom->status)->toBe('draft')
            ->and((bool) $bom->is_active)->toBeFalse()
            ->and($bom->items->isNotEmpty())->toBeTrue();

        $materialNames = $bom->items
            ->pluck('material.name')
            ->filter()
            ->unique()
            ->values()
            ->all();

        expect(array_diff(
            $materialNames,
            clientCartonApprovedRawMaterialNames()
        ))->toBe([]);
    }

    foreach ($calculated as $bom) {
        expect((float) $bom->selling_price_afn)->toBeGreaterThan(0)
            ->and((float) $bom->total_material_cost_usd)->toBeGreaterThan(0);

        foreach ($bom->items as $item) {
            expect((float) $item->quantity)->toBeGreaterThan(0)
                ->and((float) $item->cost_per_unit_usd)->toBeGreaterThan(0)
                ->and((float) $item->cost_per_unit_afn)->toBeGreaterThan(0)
                ->and((float) $item->total_cost_usd)->toBeGreaterThan(0)
                ->and(abs(
                    (float) $item->quantity
                    - (float) $item->calculateStockRequirement(1, false)
                ))->toBeLessThan(0.000001);
        }
    }

    $approved = clientCartonApprovedRawMaterialNames();
    sort($approved);

    foreach ($reviewRequired as $bom) {
        $names = $bom->items
            ->pluck('material.name')
            ->sort()
            ->values()
            ->all();

        expect((float) $bom->selling_price_afn)->toBe(0.0)
            ->and($bom->items)->toHaveCount(10)
            ->and($names)->toBe($approved)
            ->and($bom->items->filter(fn ($item) => (float) $item->quantity !== 0.0))
            ->toHaveCount(0)
            ->and($bom->items->filter(fn ($item) => (float) $item->cost_per_unit_usd <= 0.0))
            ->toHaveCount(0);
    }
});

it('seeds one arrived opening-stock purchase with all ten client materials available for production', function () {
    $this->seed(DatabaseSeeder::class);

    $purchase = Purchase::query()
        ->where('purchase_no', ClientCartonOpeningStockSeeder::PURCHASE_NO)
        ->with(['items.product', 'currency'])
        ->firstOrFail();

    expect($purchase->status)->toBe('arrived')
        ->and($purchase->currency->code)->toBe('USD')
        ->and($purchase->items)->toHaveCount(10)
        ->and((float) $purchase->usd_subtotal)->toBeGreaterThan(0);

    $actualNames = $purchase->items
        ->pluck('product.name')
        ->sort()
        ->values()
        ->all();

    $expectedNames = clientCartonApprovedRawMaterialNames();
    sort($expectedNames);

    expect($actualNames)->toBe($expectedNames);

    foreach ($purchase->items as $item) {
        expect($item->availableInventoryQuantity())->toBeGreaterThan(0)
            ->and($item->landedCostPerInventoryUnitUsd())->toBeGreaterThan(0);

        if ($item->product->unit === 'roll') {
            expect($item->inventoryCostBasisUnit())->toBe('kg')
                ->and((float) $item->kg_per_roll)->toBe(500.0)
                ->and($item->availableKg())->toBeGreaterThan(0);
        } else {
            expect($item->inventoryCostBasisUnit())->toBe('kg');
        }
    }
});

it('consumes seeded opening stock through the production FIFO landed-cost flow', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::factory()->create();
    Auth::login($user);

    $bom = BOM::query()
        ->where('code', 'like', 'BOM-CLIENT-%')
        ->where('description', 'not like', '[REVIEW REQUIRED]%')
        ->with('items.material')
        ->firstOrFail();

    $openingPurchase = Purchase::query()
        ->where('purchase_no', ClientCartonOpeningStockSeeder::PURCHASE_NO)
        ->firstOrFail();

    $openingBatchIds = PurchaseItem::query()
        ->where('purchase_id', $openingPurchase->id)
        ->pluck('id')
        ->all();

    $before = PurchaseItem::query()
        ->whereIn('id', $openingBatchIds)
        ->get()
        ->keyBy('id')
        ->map(fn (PurchaseItem $item) => [
            'available' => $item->availableInventoryQuantity(),
            'landed_cost_usd' => $item->landedCostPerInventoryUnitUsd(),
        ]);

    $order = ProductionOrder::create([
        'order_number' => 'OPENING-STOCK-FIFO-001',
        'product_id' => $bom->product_id,
        'bom_id' => $bom->id,
        'quantity_ordered' => 1,
        'status' => ProductionOrder::STATUS_PENDING,
        'created_by' => $user->id,
        'start_date' => today()->toDateString(),
    ]);

    $result = app(ProductionQuantityService::class)->start($order, null, 1);

    $consumptions = ProductionMaterialConsumption::query()
        ->where('production_order_id', $order->id)
        ->get();

    $expectedMaterialIds = $bom->items
        ->filter(fn ($item) => (float) $item->calculateStockRequirement(1, true) > 0)
        ->pluck('material_id')
        ->map(fn ($id) => (int) $id)
        ->unique()
        ->sort()
        ->values()
        ->all();

    $actualMaterialIds = $consumptions
        ->pluck('material_id')
        ->map(fn ($id) => (int) $id)
        ->unique()
        ->sort()
        ->values()
        ->all();

    expect($order->fresh()->status)->toBe(ProductionOrder::STATUS_IN_PROGRESS)
        ->and((float) $result['material_cost_usd'])->toBeGreaterThan(0)
        ->and($consumptions->isNotEmpty())->toBeTrue()
        ->and($actualMaterialIds)->toBe($expectedMaterialIds)
        ->and($consumptions->pluck('purchase_item_id')->diff($openingBatchIds)->count())->toBe(0);

    foreach ($consumptions as $consumption) {
        $batch = PurchaseItem::query()->findOrFail($consumption->purchase_item_id);
        $beforeRow = $before->get($batch->id);

        expect($beforeRow)->not->toBeNull()
            ->and(abs(
                (float) $consumption->cost_per_unit_usd
                - (float) $beforeRow['landed_cost_usd']
            ))->toBeLessThan(0.000001)
            ->and(abs(
                (float) $consumption->total_cost_usd
                - ((float) $consumption->actual_quantity * (float) $consumption->cost_per_unit_usd)
            ))->toBeLessThan(0.000001)
            ->and($batch->availableInventoryQuantity())
            ->toBeLessThan((float) $beforeRow['available']);
    }
});

it('uses the verified client 125x5 plus 145x1 paper formula for calculated five-ply carton BOMs', function () {
    $this->seed(DatabaseSeeder::class);

    $fivePlySpec = FinishedGoodSpecification::query()
        ->importedClientCartons()
        ->where('ply', 5)
        ->whereNotNull('length')
        ->whereNotNull('width')
        ->whereNotNull('height')
        ->firstOrFail();

    $bom = BOM::query()
        ->where('product_id', $fivePlySpec->product_id)
        ->where('code', 'like', 'BOM-CLIENT-%')
        ->with('items.material')
        ->firstOrFail();

    expect((string) $bom->description)->not->toStartWith('[REVIEW REQUIRED]');

    $paperRows = $bom->items
        ->where('component_type', 'paper')
        ->values();

    expect($paperRows)->toHaveCount(2);

    $row125 = $paperRows->firstWhere('paper_gsm', 125);
    $row145 = $paperRows->firstWhere('paper_gsm', 145);

    expect($row125)->not->toBeNull()
        ->and((int) $row125->multiplication_layer)->toBe(5)
        ->and($row125->material->name)->toBe('Fluting')
        ->and($row145)->not->toBeNull()
        ->and((int) $row145->multiplication_layer)->toBe(1)
        ->and($row145->material->name)->toBe('Kraft Liner');
});

it('adds the four client mixing materials to every calculated carton BOM', function () {
    $this->seed(DatabaseSeeder::class);

    $calculatedBoms = BOM::query()
        ->where('code', 'like', 'BOM-CLIENT-%')
        ->where('description', 'not like', '[REVIEW REQUIRED]%')
        ->with('items.material')
        ->get();

    expect($calculatedBoms)->toHaveCount(130);

    $expected = [
        'Borax',
        'Caustic Soda',
        'Corn Flour',
        'Seligate (Glue)',
    ];
    sort($expected);

    foreach ($calculatedBoms as $bom) {
        $adhesiveNames = $bom->items
            ->where('component_type', 'adhesive')
            ->pluck('material.name')
            ->sort()
            ->values()
            ->all();

        expect($adhesiveNames)->toBe($expected);
    }
});

it('seeds only USD and AFN as default system currencies', function () {
    $this->seed(CurrencySeeder::class);

    $codes = Currency::query()
        ->orderBy('id')
        ->pluck('code')
        ->values()
        ->all();

    expect($codes)->toBe(['USD', 'AFN'])
        ->not->toContain('CNY');
});

it('keeps USD and AFN only after the full database seed', function () {
    $this->seed(DatabaseSeeder::class);

    expect(
        Currency::query()->orderBy('id')->pluck('code')->values()->all()
    )->toBe(['USD', 'AFN'])
        ->not->toContain('CNY');
});

it('is idempotent across the full client master and all-finished-goods BOM seeding flow', function () {
    $this->seed(DatabaseSeeder::class);

    $before = [
        'customers' => Account::where('account_type', Account::TYPE_CUSTOMER)->count(),
        'specifications' => FinishedGoodSpecification::importedClientCartons()->count(),
        'products' => Product::count(),
        'raw_materials' => Product::where('type', Product::TYPE_RAW_MATERIAL)->count(),
        'finished_goods' => Product::where('type', Product::TYPE_FINISHED_GOOD)->count(),
        'boms' => BOM::where('code', 'like', 'BOM-CLIENT-%')->count(),
        'bom_items' => BOMItem::whereHas(
            'bom',
            fn ($q) => $q->where('code', 'like', 'BOM-CLIENT-%')
        )->count(),
        'opening_purchases' => Purchase::where('purchase_no', ClientCartonOpeningStockSeeder::PURCHASE_NO)->count(),
        'opening_purchase_items' => PurchaseItem::whereHas(
            'purchase',
            fn ($q) => $q->where('purchase_no', ClientCartonOpeningStockSeeder::PURCHASE_NO)
        )->count(),
    ];

    $this->seed(DatabaseSeeder::class);

    $after = [
        'customers' => Account::where('account_type', Account::TYPE_CUSTOMER)->count(),
        'specifications' => FinishedGoodSpecification::importedClientCartons()->count(),
        'products' => Product::count(),
        'raw_materials' => Product::where('type', Product::TYPE_RAW_MATERIAL)->count(),
        'finished_goods' => Product::where('type', Product::TYPE_FINISHED_GOOD)->count(),
        'boms' => BOM::where('code', 'like', 'BOM-CLIENT-%')->count(),
        'bom_items' => BOMItem::whereHas(
            'bom',
            fn ($q) => $q->where('code', 'like', 'BOM-CLIENT-%')
        )->count(),
        'opening_purchases' => Purchase::where('purchase_no', ClientCartonOpeningStockSeeder::PURCHASE_NO)->count(),
        'opening_purchase_items' => PurchaseItem::whereHas(
            'purchase',
            fn ($q) => $q->where('purchase_no', ClientCartonOpeningStockSeeder::PURCHASE_NO)
        )->count(),
    ];

    expect($after)->toBe($before)
        ->and($after['raw_materials'])->toBe(10)
        ->and($after['finished_goods'])->toBe(171)
        ->and($after['specifications'])->toBe(171)
        ->and($after['boms'])->toBe(171)
        ->and($after['opening_purchases'])->toBe(1)
        ->and($after['opening_purchase_items'])->toBe(10);
});

it('preserves operator edits on an already imported carton source row', function () {
    $this->seed(CustomerCartonSizeSeeder::class);

    $spec = FinishedGoodSpecification::query()
        ->importedClientCartons()
        ->with('product')
        ->firstOrFail();

    $productId = $spec->product_id;
    $specificationCount = FinishedGoodSpecification::count();
    $productCount = Product::count();

    $spec->product->update(['name' => 'Operator Renamed Carton']);

    $this->seed(CustomerCartonSizeSeeder::class);

    expect(Product::findOrFail($productId)->name)->toBe('Operator Renamed Carton')
        ->and(FinishedGoodSpecification::count())->toBe($specificationCount)
        ->and(Product::count())->toBe($productCount);
});

it('migrates the old generated customer-prefixed name without touching source identity', function () {
    $this->seed(CustomerCartonSizeSeeder::class);

    $spec = FinishedGoodSpecification::query()
        ->importedClientCartons()
        ->with('product')
        ->orderBy('id')
        ->firstOrFail();

    $productId = $spec->product_id;
    $sourceKey = $spec->source_key;

    // First source row is unique in the old naming scheme.
    $spec->product->update([
        'name' => 'Bless Bee - (44*40*31)cm - 200ml,70pcs',
    ]);

    $this->seed(CustomerCartonSizeSeeder::class);

    $spec->refresh()->load('product');

    expect($spec->source_key)->toBe($sourceKey)
        ->and($spec->product_id)->toBe($productId)
        ->and($spec->product->name)->toStartWith('Carton')
        ->and($spec->product->name)->not->toContain('Bless Bee');
});
