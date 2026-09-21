<?php

use App\Models\Account;
use App\Models\BOM;
use App\Models\BOMItem;
use App\Models\FinishedGoodSpecification;
use App\Models\Product;
use Database\Seeders\ClientCartonRawMaterialSeeder;
use Database\Seeders\CustomerCartonBomSeeder;
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

    // The old broad ProductSeeder is a compatibility wrapper only and must
    // never reintroduce demo paper, ink, adhesive, packaging or machine items.
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

it('imports the client customer carton master without promoting historical workbook rates into live prices', function () {
    $this->seed(CustomerCartonSizeSeeder::class);

    expect(
        Account::query()
            ->where('account_type', Account::TYPE_CUSTOMER)
            ->count()
    )->toBe(37)
        ->and(FinishedGoodSpecification::importedClientCartons()->count())
        ->toBe(171)
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
});

it('keeps duplicate customer carton names as distinct source variants', function () {
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
});

it('creates draft technical BOMs only for customer cartons with complete dimensions and supported ply', function () {
    $this->seed(DatabaseSeeder::class);

    $specifications = FinishedGoodSpecification::query()
        ->importedClientCartons()
        ->get();

    $eligible = $specifications->filter(
        fn ($spec) =>
            (float) ($spec->length ?? 0) > 0
            && (float) ($spec->width ?? 0) > 0
            && (float) ($spec->height ?? 0) > 0
            && in_array((int) ($spec->ply ?? 0), [3, 5], true)
    );

    expect($specifications)->toHaveCount(171)
        ->and($eligible)->toHaveCount(130);

    $seededBoms = BOM::query()
        ->where('code', 'like', 'BOM-CLIENT-%')
        ->with('items.material')
        ->get();

    expect($seededBoms)->toHaveCount(130);

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
});

it('uses the verified client 125x5 plus 145x1 paper formula for seeded five-ply carton BOMs', function () {
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

it('adds the four client mixing materials to every automatically generated carton BOM', function () {
    $this->seed(DatabaseSeeder::class);

    $bom = BOM::query()
        ->where('code', 'like', 'BOM-CLIENT-%')
        ->with('items.material')
        ->firstOrFail();

    $adhesiveNames = $bom->items
        ->where('component_type', 'adhesive')
        ->pluck('material.name')
        ->sort()
        ->values()
        ->all();

    $expected = [
        'Borax',
        'Caustic Soda',
        'Corn Flour',
        'Seligate (Glue)',
    ];
    sort($expected);

    expect($adhesiveNames)->toBe($expected);
});

it('is idempotent across the full client master and BOM seeding flow', function () {
    $this->seed(DatabaseSeeder::class);

    $before = [
        'customers' => Account::where('account_type', Account::TYPE_CUSTOMER)->count(),
        'specifications' => FinishedGoodSpecification::importedClientCartons()->count(),
        'products' => Product::count(),
        'raw_materials' => Product::where('type', Product::TYPE_RAW_MATERIAL)->count(),
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
        'boms' => BOM::where('code', 'like', 'BOM-CLIENT-%')->count(),
        'bom_items' => BOMItem::whereHas(
            'bom',
            fn ($q) => $q->where('code', 'like', 'BOM-CLIENT-%')
        )->count(),
    ];

    expect($after)->toBe($before)
        ->and($after['raw_materials'])->toBe(10)
        ->and($after['specifications'])->toBe(171)
        ->and($after['boms'])->toBe(130);
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
