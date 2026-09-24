<?php

use App\Models\Product;
use Database\Seeders\ClientCartonRawMaterialSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

/**
 * The carton raw-material and optional finishing baseline must deploy
 * idempotently: rerunning the seeder never creates duplicates.
 */
it('deploys the 11 carton and finishing materials idempotently', function () {
    $names = collect(ClientCartonRawMaterialSeeder::MATERIALS)->pluck('name')->all();

    expect($names)->toHaveCount(11);

    // The migration already deployed them; run the seeder twice more to prove
    // idempotency.
    $this->seed(ClientCartonRawMaterialSeeder::class);
    $this->seed(ClientCartonRawMaterialSeeder::class);

    $products = Product::whereIn('name', $names)->get();

    expect($products)->toHaveCount(11);

    foreach (ClientCartonRawMaterialSeeder::MATERIALS as $material) {
        $matches = $products->where('name', $material['name']);

        expect($matches)->toHaveCount(1);

        $product = $matches->first();

        expect($product->unit)->toBe($material['unit'])
            ->and($product->type)->toBe('raw_material')
            ->and((int) $product->min_stock_alert)->toBe($material['min_stock'])
            ->and((bool) $product->is_active)->toBeTrue();

        $expectedCategory = match ($material['category']) {
            'paper' => 'Paper Materials',
            'mixing' => 'Mixing Materials',
            'finishing' => 'Finishing Materials',
        };
        $categoryName = DB::table('categories')->where('id', $product->category_id)->value('name');

        expect($categoryName)->toBe($expectedCategory);
    }

    $paperNames = ['Test Liner', 'Fluting', 'Kraft Liner', 'Semi Kraft', 'White Liner', 'Box Board'];
    $mixingNames = ['Seligate (Glue)', 'Corn Flour', 'Borax', 'Caustic Soda'];
    $finishingNames = ['Lamination Plastic'];

    foreach ($paperNames as $name) {
        expect($products->firstWhere('name', $name)->unit)->toBe('roll');
    }

    foreach ($mixingNames as $name) {
        expect($products->firstWhere('name', $name)->unit)->toBe('kg');
    }

    foreach ($finishingNames as $name) {
        expect($products->firstWhere('name', $name)->unit)->toBe('kg');
    }
});

it('keeps the broad product seeder disabled while registering the client seeder', function () {
    $source = file_get_contents(database_path('seeders/DatabaseSeeder.php'));

    expect($source)
        ->toContain('ClientCartonRawMaterialSeeder::class')
        ->toContain('// $this->call(ProductSeeder::class);');
});

it('runs as part of the full DatabaseSeeder without duplicating materials', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $names = collect(ClientCartonRawMaterialSeeder::MATERIALS)->pluck('name')->all();

    foreach ($names as $name) {
        expect(Product::where('name', $name)->count())->toBe(1);
    }
});

it('aligns an unreferenced same-name legacy product to the client baseline', function () {
    $paperCategoryId = DB::table('categories')->where('name', 'Paper Materials')->value('id');
    $now = now();

    DB::table('products')->where('name', 'Test Liner')->delete();

    $legacyId = DB::table('products')->insertGetId([
        'name' => 'Test Liner',
        'slug' => 'legacy-test-liner',
        'unit' => 'piece',
        'type' => 'finished_good',
        'category_id' => $paperCategoryId,
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $this->seed(ClientCartonRawMaterialSeeder::class);

    $product = Product::where('name', 'Test Liner')->first();

    expect(Product::where('name', 'Test Liner')->count())->toBe(1)
        ->and($product->id)->toBe($legacyId)
        ->and($product->unit)->toBe('roll')
        ->and($product->type)->toBe('raw_material')
        ->and((int) $product->min_stock_alert)->toBe(10);
});

it('does not alter a same-name product that is already referenced', function () {
    $paperCategoryId = DB::table('categories')->where('name', 'Paper Materials')->value('id');
    $finished = Product::where('name', 'Box Board')->firstOrFail();
    $now = now();

    DB::table('products')->where('name', 'Test Liner')->delete();

    $legacyId = DB::table('products')->insertGetId([
        'name' => 'Test Liner',
        'slug' => 'referenced-test-liner',
        'unit' => 'piece',
        'type' => 'finished_good',
        'category_id' => $paperCategoryId,
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $bomId = DB::table('boms')->insertGetId([
        'name' => 'Legacy Referencing BOM',
        'code' => 'BOM-LEGACY-REF',
        'product_id' => $finished->id,
        'status' => 'active',
        'is_active' => 1,
        'created_by' => App\Models\User::factory()->create()->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('bom_items')->insert([
        'bom_id' => $bomId,
        'material_id' => $legacyId,
        'quantity' => 1,
        'unit' => 'piece',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $this->seed(ClientCartonRawMaterialSeeder::class);

    $product = Product::find($legacyId);

    expect($product->unit)->toBe('piece')
        ->and($product->type)->toBe('finished_good');
});
