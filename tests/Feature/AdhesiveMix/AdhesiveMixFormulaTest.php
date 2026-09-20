<?php

use App\Models\BOM;
use App\Models\BOMItem;
use App\Services\AdhesiveMixCalculator;
use App\Services\BOMCostingService;
use Illuminate\Support\Facades\DB;

/**
 * Dimension-driven adhesive (mixing material) formula.
 *
 * Reference: 30 cm × 30 cm × 30 cm = 11.811 in cube.
 *   ReelLength = 51.244 in, ReelHeight = 24.622 in, BoardArea ≈ 0.8140 m²
 *   Dry glue (incl. 5%) ≈ 18.8 g, wet glue at 35% solids ≈ 53.7 g
 *   Corn Flour ≈ 14.24 g, Seligate ≈ 10.15 g, Caustic Soda ≈ 0.645 g, Borax ≈ 0.199 g
 */
function adhesiveReferenceDimensions(): array
{
    return [
        'length' => 30 / 2.54,
        'width' => 30 / 2.54,
        'height' => 30 / 2.54,
    ];
}

it('matches the 30x30x30 cm reference adhesive quantities', function () {
    $calculator = app(AdhesiveMixCalculator::class);
    $dims = adhesiveReferenceDimensions();

    $expectedGrams = [
        'corn_flour' => 14.24,
        'seligate' => 10.15,
        'caustic_soda' => 0.645,
        'borax' => 0.199,
    ];

    $reference = $calculator->breakdown($dims['length'], $dims['width'], $dims['height'], 'corn_flour');

    expect($reference['reel_length_inch'])->toBeGreaterThan(51.24)
        ->and($reference['reel_length_inch'])->toBeLessThan(51.25)
        ->and($reference['reel_height_inch'])->toBeGreaterThan(24.62)
        ->and($reference['reel_height_inch'])->toBeLessThan(24.63)
        ->and($reference['board_area_m2'])->toBeGreaterThan(0.8139)
        ->and($reference['board_area_m2'])->toBeLessThan(0.8141)
        ->and($reference['dry_glue_with_wastage_grams'])->toBeGreaterThan(18.79)
        ->and($reference['dry_glue_with_wastage_grams'])->toBeLessThan(18.81)
        ->and($reference['wet_glue_kg'])->toBeGreaterThan(0.05371)
        ->and($reference['wet_glue_kg'])->toBeLessThan(0.05373);

    foreach ($expectedGrams as $recipeKey => $expectedGramsValue) {
        $grams = $calculator->perCartonKg(
            $dims['length'],
            $dims['width'],
            $dims['height'],
            $recipeKey
        ) * 1000;

        expect(abs($grams - $expectedGramsValue))->toBeLessThan(0.01);
    }
});

it('increases adhesive consumption as carton dimensions grow', function () {
    $calculator = app(AdhesiveMixCalculator::class);

    $small = $calculator->perCartonKg(8, 8, 8, 'corn_flour');
    $reference = $calculator->perCartonKg(11.811, 11.811, 11.811, 'corn_flour');
    $large = $calculator->perCartonKg(20, 20, 20, 'corn_flour');

    expect($small)->toBeGreaterThan(0)
        ->and($large)->toBeGreaterThan($reference)
        ->and($reference)->toBeGreaterThan($small);
});

it('returns zero adhesive for invalid or zero dimensions', function () {
    $calculator = app(AdhesiveMixCalculator::class);

    expect($calculator->perCartonKg(0, 10, 10, 'corn_flour'))->toBe(0.0)
        ->and($calculator->perCartonKg(10, 0, 10, 'corn_flour'))->toBe(0.0)
        ->and($calculator->perCartonKg(10, 10, 0, 'corn_flour'))->toBe(0.0)
        ->and($calculator->perCartonKg(-5, 10, 10, 'corn_flour'))->toBe(0.0)
        ->and($calculator->boardAreaM2(0, 0, 0))->toBe(0.0);
});

it('scales adhesive with glue lines, dry glue gsm, wastage, solids and recipe share', function () {
    $calculator = app(AdhesiveMixCalculator::class);
    $dims = [11.811, 11.811, 11.811];

    $base = $calculator->perCartonKg($dims[0], $dims[1], $dims[2], 'corn_flour');

    // Doubling the glue lines doubles dry glue => doubles the ingredient.
    $doubleLines = $calculator->perCartonKg($dims[0], $dims[1], $dims[2], 'corn_flour', ['glue_lines' => 8]);
    expect(abs($doubleLines - ($base * 2)))->toBeLessThan(0.0000001);

    // Doubling the dry glue GSM also doubles the ingredient.
    $doubleGsm = $calculator->perCartonKg($dims[0], $dims[1], $dims[2], 'corn_flour', ['dry_glue_gsm_per_line' => 11]);
    expect(abs($doubleGsm - ($base * 2)))->toBeLessThan(0.0000001);

    // Doubling glue wastage scales by (110 / 105) exactly.
    $doubleWastage = $calculator->perCartonKg($dims[0], $dims[1], $dims[2], 'corn_flour', ['glue_wastage_percentage' => 10]);
    expect(abs($doubleWastage - ($base * (110 / 105))))->toBeLessThan(0.0000001);

    // Halving the solids percentage doubles the wet glue.
    $halfSolids = $calculator->perCartonKg($dims[0], $dims[1], $dims[2], 'corn_flour', ['adhesive_solids_percentage' => 17.5]);
    expect(abs($halfSolids - ($base * 2)))->toBeLessThan(0.0000001);

    // Recipe override replaces the configured share.
    $boraxBase = $calculator->perCartonKg($dims[0], $dims[1], $dims[2], 'borax');
    $boraxDoubled = $calculator->perCartonKg($dims[0], $dims[1], $dims[2], 'borax', ['recipe_percentage' => 0.0074]);
    expect(abs($boraxDoubled - ($boraxBase * 2)))->toBeLessThan(0.0000001);
});

it('treats adhesive stock requirements as already-wastage-inclusive', function () {
    $user = App\Models\User::factory()->create();
    $now = now();

    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Adhesive Formula Category',
        'slug' => 'adhesive-formula-category',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $materialId = DB::table('products')->insertGetId([
        'name' => 'Corn Flour',
        'slug' => 'corn-flour',
        'unit' => 'kg',
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $finishedId = DB::table('products')->insertGetId([
        'name' => 'Adhesive Formula Box',
        'slug' => 'adhesive-formula-box',
        'unit' => 'pcs',
        'category_id' => $categoryId,
        'type' => 'finished_good',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $bomId = DB::table('boms')->insertGetId([
        'name' => 'Adhesive Formula BOM',
        'code' => 'BOM-ADH-FORMULA',
        'product_id' => $finishedId,
        'status' => 'active',
        'is_active' => 1,
        'created_by' => $user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $item = BOMItem::create([
        'bom_id' => $bomId,
        'material_id' => $materialId,
        'quantity' => 1,
        'unit' => 'kg',
        'wastage_percentage' => 5, // must be ignored for adhesive rows
        'is_formula_based' => true,
        'formula_type' => 'adhesive_mix',
        'length_inch' => 11.811,
        'width_inch' => 11.811,
        'height_inch' => 11.811,
        'formula_data' => [
            'recipe_key' => 'corn_flour',
            'recipe_percentage' => 0.265,
            'glue_lines' => 4,
            'dry_glue_gsm_per_line' => 5.5,
            'glue_wastage_percentage' => 5,
            'adhesive_solids_percentage' => 35,
            'sq_inch_to_m2' => 0.00064516,
        ],
    ]);

    $perUnit = $item->calculateStockKgPerUnit();

    expect($perUnit)->toBeGreaterThan(0)
        ->and($item->calculateStockRequirement(1, true))->toEqualWithDelta($perUnit, 0.000000001)
        ->and($item->calculateStockRequirement(1, false))->toEqualWithDelta($perUnit, 0.000000001)
        ->and($item->calculateStockRequirement(100, true))->toEqualWithDelta($perUnit * 100, 0.000000001);
});

it('excludes adhesive rows from the commercial work profit in the BOM summary', function () {
    $user = App\Models\User::factory()->create();
    $now = now();

    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Adhesive Summary Category',
        'slug' => 'adhesive-summary-category',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $paperId = DB::table('products')->insertGetId([
        'name' => 'Summary Kraft',
        'slug' => 'summary-kraft',
        'unit' => 'roll',
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $glueId = DB::table('products')->insertGetId([
        'name' => 'Summary Borax',
        'slug' => 'summary-borax',
        'unit' => 'kg',
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $finishedId = DB::table('products')->insertGetId([
        'name' => 'Adhesive Summary Box',
        'slug' => 'adhesive-summary-box',
        'unit' => 'pcs',
        'category_id' => $categoryId,
        'type' => 'finished_good',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $bom = BOM::create([
        'name' => 'Adhesive Summary BOM',
        'code' => 'BOM-ADH-SUMMARY',
        'product_id' => $finishedId,
        'status' => 'active',
        'is_active' => true,
        'work_percentage' => 40,
        'profit_margin_percentage' => 0,
        'exchange_rate' => 66,
        'created_by' => $user->id,
    ]);

    BOMItem::create([
        'bom_id' => $bom->id,
        'material_id' => $paperId,
        'quantity' => 1,
        'unit' => 'kg',
        'wastage_percentage' => 5,
        'is_formula_based' => true,
        'formula_type' => 'carton_3d',
        'length_inch' => 11.811,
        'width_inch' => 11.811,
        'height_inch' => 11.811,
        'paper_gsm' => 125,
        'multiplication_layer' => 1,
        'formula_constant' => 1550000,
        'per_gram_rate' => 60,
        'cost_per_unit_usd' => 1.0,
        'cost_per_unit_afn' => 66.0,
    ]);

    BOMItem::create([
        'bom_id' => $bom->id,
        'material_id' => $glueId,
        'quantity' => 1,
        'unit' => 'kg',
        'wastage_percentage' => 0,
        'is_formula_based' => true,
        'formula_type' => 'adhesive_mix',
        'length_inch' => 11.811,
        'width_inch' => 11.811,
        'height_inch' => 11.811,
        'cost_per_unit_usd' => 2.0,
        'cost_per_unit_afn' => 132.0,
        'formula_data' => ['recipe_key' => 'borax'],
    ]);

    $bom = $bom->fresh('items');
    $summary = app(BOMCostingService::class)->summarize($bom);

    $paperItem = $bom->items->firstWhere('material_id', $paperId);
    $glueItem = $bom->items->firstWhere('material_id', $glueId);

    $paperBaseAfn = $paperItem->calculateStockRequirement(1, false) * 66.0;
    $glueBaseAfn = $glueItem->calculateStockRequirement(1, false) * 132.0;

    // Paper still earns the 40% work; adhesive only contributes material cost.
    expect($summary['standard_work_profit_afn'])->toEqualWithDelta($paperBaseAfn * 0.40, 0.0001)
        ->and($summary['base_material_cost_afn'])->toEqualWithDelta($paperBaseAfn + $glueBaseAfn, 0.0001);
});
