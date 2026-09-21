<?php

use App\Models\Account;
use App\Models\BOM;
use App\Models\BOMItem;
use App\Models\Currency;
use App\Models\FinishedGoodSpecification;
use App\Models\Product;
use Database\Seeders\ClientCartonRawMaterialSeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\CustomerCartonSizeSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
            ->and((float) $bom->selling_price_afn)->toBe(0.0)
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

    $approved = clientCartonApprovedRawMaterialNames();
    sort($approved);

    foreach ($reviewRequired as $bom) {
        $names = $bom->items
            ->pluck('material.name')
            ->sort()
            ->values()
            ->all();

        expect($bom->items)->toHaveCount(10)
            ->and($names)->toBe($approved)
            ->and($bom->items->filter(fn ($item) => (float) $item->quantity !== 0.0))
            ->toHaveCount(0);
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
    ];

    expect($after)->toBe($before)
        ->and($after['raw_materials'])->toBe(10)
        ->and($after['finished_goods'])->toBe(171)
        ->and($after['specifications'])->toBe(171)
        ->and($after['boms'])->toBe(171);
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
