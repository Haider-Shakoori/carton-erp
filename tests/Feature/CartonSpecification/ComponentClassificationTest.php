<?php

use App\Models\BOM;
use App\Models\BOMItem;
use App\Services\BOMCostingService;
use Illuminate\Support\Facades\DB;

/**
 * Explicit commercial/physical classification of BOM rows (Batch 4).
 *
 * The client 40% Standard Work / Profit belongs to the paper commercial
 * basis only. Adhesive/mixing rows are physical material cost only: they
 * still feed stock, FIFO, landed production cost and realized profit.
 */
function classificationBom(): array
{
    $user = App\Models\User::factory()->create();
    $now = now();

    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Classification Category',
        'slug' => 'classification-category-' . uniqid(),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $paperId = DB::table('products')->insertGetId([
        'name' => 'Classification Kraft',
        'slug' => 'classification-kraft-' . uniqid(),
        'unit' => 'roll',
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $glueId = DB::table('products')->insertGetId([
        'name' => 'Classification Borax',
        'slug' => 'classification-borax-' . uniqid(),
        'unit' => 'kg',
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $finishedId = DB::table('products')->insertGetId([
        'name' => 'Classification Box',
        'slug' => 'classification-box-' . uniqid(),
        'unit' => 'pcs',
        'category_id' => $categoryId,
        'type' => 'finished_good',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $bom = BOM::create([
        'name' => 'Classification BOM ' . uniqid(),
        'code' => 'BOM-CLASS-' . strtoupper(uniqid()),
        'product_id' => $finishedId,
        'status' => 'active',
        'is_active' => true,
        'work_percentage' => 40,
        'profit_margin_percentage' => 0,
        'exchange_rate' => 66,
        'created_by' => $user->id,
    ]);

    return [$bom, $paperId, $glueId];
}

function classificationPaperItem(BOM $bom, int $materialId, array $overrides = []): BOMItem
{
    return BOMItem::create(array_merge([
        'bom_id' => $bom->id,
        'material_id' => $materialId,
        'quantity' => 1,
        'unit' => 'kg',
        'wastage_percentage' => 0,
        'is_formula_based' => true,
        'formula_type' => 'carton_3d',
        'length_inch' => 11.811,
        'width_inch' => 11.811,
        'height_inch' => 11.811,
        'paper_gsm' => 125,
        'multiplication_layer' => 5,
        'formula_constant' => 1550000,
        'per_gram_rate' => 60,
        'cost_per_unit_usd' => 1.0,
        'cost_per_unit_afn' => 66.0,
        'work_percentage' => 40,
    ], $overrides));
}

function classificationAdhesiveItem(BOM $bom, int $materialId, array $overrides = []): BOMItem
{
    return BOMItem::create(array_merge([
        'bom_id' => $bom->id,
        'material_id' => $materialId,
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
        'work_percentage' => 0,
    ], $overrides));
}

it('applies the client 40% work only to paper rows and never to adhesive rows', function () {
    [$bom, $paperId, $glueId] = classificationBom();

    $paper = classificationPaperItem($bom, $paperId, [
        'component_type' => 'paper',
        'apply_work_percentage' => true,
    ]);
    $glue = classificationAdhesiveItem($bom, $glueId, [
        'component_type' => 'adhesive',
        'apply_work_percentage' => false,
    ]);

    $summary = app(BOMCostingService::class)->summarize($bom->fresh('items'));

    $paperBase = $paper->calculateStockRequirement(1, false) * 66.0;
    $glueBase = $glue->calculateStockRequirement(1, false) * 132.0;

    expect($summary['standard_work_profit_afn'])->toEqualWithDelta($paperBase * 0.40, 0.0001)
        ->and($summary['base_material_cost_afn'])->toEqualWithDelta($paperBase + $glueBase, 0.0001)
        ->and($summary['physical_material_cost_afn'])->toEqualWithDelta(
            $paper->calculateStockRequirement(1, true) * 66.0
            + $glue->calculateStockRequirement(1, true) * 132.0,
            0.0001
        );
});

it('preserves the legacy inference for rows without an explicit classification', function () {
    [$bom, $paperId, $glueId] = classificationBom();

    $paper = classificationPaperItem($bom, $paperId);
    $glue = classificationAdhesiveItem($bom, $glueId);

    expect($paper->appliesWorkProfit())->toBeTrue()
        ->and($glue->appliesWorkProfit())->toBeFalse()
        ->and($paper->resolvedComponentType())->toBe('paper')
        ->and($glue->resolvedComponentType())->toBe('adhesive');
});

it('honours an explicit work opt-out on a paper row and an explicit opt-in on an auxiliary row', function () {
    [$bom, $paperId, $glueId] = classificationBom();

    classificationPaperItem($bom, $paperId, ['apply_work_percentage' => false]);
    classificationAdhesiveItem($bom, $glueId, [
        'component_type' => 'auxiliary',
        'apply_work_percentage' => true,
        'work_percentage' => 40,
    ]);

    $summary = app(BOMCostingService::class)->summarize($bom->fresh('items'));

    $glueBase = $bom->fresh('items')->items
        ->firstWhere('material_id', $glueId)
        ->calculateStockRequirement(1, false) * 132.0;

    expect($summary['standard_work_profit_afn'])->toEqualWithDelta($glueBase * 0.40, 0.0001);
});

it('still counts adhesive rows in physical production cost and expected profit', function () {
    [$bom, $paperId, $glueId] = classificationBom();

    $paper = classificationPaperItem($bom, $paperId);
    $glue = classificationAdhesiveItem($bom, $glueId);

    $summary = app(BOMCostingService::class)->summarize($bom->fresh('items'));

    $expectedPhysical = $paper->calculateStockRequirement(1, true) * 66.0
        + $glue->calculateStockRequirement(1, true) * 132.0;

    expect($summary['physical_production_cost_afn'])->toEqualWithDelta($expectedPhysical, 0.0001)
        ->and($summary['expected_profit_afn'])->toEqualWithDelta(
            $summary['selling_price_afn'] - $expectedPhysical,
            0.0001
        );
});
