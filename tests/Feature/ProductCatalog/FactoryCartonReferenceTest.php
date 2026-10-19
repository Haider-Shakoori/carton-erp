<?php

use App\Models\Category;
use App\Models\FinishedGoodSpecification;
use App\Models\Product;
use App\Services\FactoryCartonReferenceService;
use App\Services\FinishedGoodWeightAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeFactoryReferenceCarton(int $ply = 5, string $sourceKey = 'client-carton-order:fixture'): Product
{
    $category = Category::firstOrCreate(['name' => 'Factory calibration tests'], ['is_active' => true]);

    $product = Product::create([
        'name' => '53 x 27 x 27 test',
        'category_id' => $category->id,
        'unit' => 'pcs',
        'type' => Product::TYPE_FINISHED_GOOD,
        'is_active' => true,
    ]);

    FinishedGoodSpecification::create([
        'product_id' => $product->id,
        'source_key' => $sourceKey,
        'length' => 530,
        'width' => 270,
        'height' => 270,
        'ply' => $ply,
    ]);

    return $product;
}

it('matches the client factory net weight only for exact carton size and verified ply', function () {
    $product = makeFactoryReferenceCarton();

    $ref = app(FactoryCartonReferenceService::class)->match($product);

    expect($ref['net_weight_g'])->toBe(590)
        ->and($ref['ply'])->toBe(5);

    $audit = app(FinishedGoodWeightAuditService::class)->assess($product, null);

    expect($audit['measured_g'])->toBe(590.0)
        ->and($audit['measured_source'])->toBe('factory_sheet_reference')
        ->and($audit['status'])->toBe('Missing BOM')
        ->and($product->fresh()->finished_weight_g)->toBeNull();
});

it('never overwrites an operator measured product value', function () {
    $product = makeFactoryReferenceCarton();
    $product->update(['finished_weight_g' => 612.5]);

    $audit = app(FinishedGoodWeightAuditService::class)->assess($product->fresh(), null);

    expect($audit['measured_g'])->toBe(612.5)
        ->and($audit['measured_source'])->toBe('product')
        ->and((float) $product->fresh()->finished_weight_g)->toBe(612.5);
});

it('does not apply factory sheet references to CSV-specific products', function () {
    $product = makeFactoryReferenceCarton(5, 'finished-good-csv:123');
    $audit = app(FinishedGoodWeightAuditService::class)->assess($product, null);

    expect($audit['measured_g'])->toBeNull()
        ->and($audit['status'])->toBe('Missing weight');
});

it('refuses an unknown ply or a changed physical dimension', function () {
    $product = makeFactoryReferenceCarton(7);
    expect(app(FactoryCartonReferenceService::class)->match($product))->toBeNull();

    $spec = $product->finishedGoodSpecifications()->firstOrFail();
    $spec->update(['ply' => 5, 'length' => 531]);
    $product->unsetRelation('finishedGoodSpecifications');

    expect(app(FactoryCartonReferenceService::class)->match($product))->toBeNull();
});
