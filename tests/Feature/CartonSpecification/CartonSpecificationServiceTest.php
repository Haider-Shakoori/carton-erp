<?php

use App\Models\BoardProfile;
use App\Models\BoardProfileLayer;
use App\Models\BOM;
use App\Services\CartonSpecificationService;
use Illuminate\Support\Facades\DB;

/**
 * Carton specification engine (Batches 2 and 3).
 *
 * The 30x30x30 cm example, unit conversion, RSC blank, the client paper
 * source of truth (125x5 + 145x1) and dimension-driven adhesive are all
 * verified through the single canonical service.
 */
function cartonSpecFixtures(): array
{
    $user = App\Models\User::factory()->create();
    $now = now();

    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Spec Category',
        'slug' => 'spec-category-' . uniqid(),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $products = [];

    foreach ([
        'Fluting' => 'roll',
        'Kraft Liner' => 'roll',
        'Corn Flour' => 'kg',
        'Seligate (Glue)' => 'kg',
        'Caustic Soda' => 'kg',
        'Borax' => 'kg',
        'Lamination Plastic' => 'kg',
        'Spec Carton Box' => 'pcs',
    ] as $name => $unit) {
        // The client material migration already deploys the baseline raw
        // materials. Reuse them so a single product exists per name.
        $existing = DB::table('products')->where('name', $name)->value('id');

        if ($existing) {
            $products[$name] = (int) $existing;
            continue;
        }

        $products[$name] = DB::table('products')->insertGetId([
            'name' => $name,
            'slug' => strtolower(str_replace([' ', '(', ')'], ['-', '', ''], $name)) . '-' . uniqid(),
            'unit' => $unit,
            'category_id' => $categoryId,
            'type' => $unit === 'pcs' ? 'finished_good' : 'raw_material',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    $usdId = DB::table('currencies')->where('code', 'USD')->value('id');

    $purchaseId = DB::table('purchases')->insertGetId([
        'purchase_no' => 'PO-SPEC-' . strtoupper(uniqid()),
        'currency_id' => $usdId,
        'status' => 'arrived',
        'purchase_date' => '2026-09-01',
        'arrival_date' => '2026-09-10',
        'exchange_rate' => 66,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $landedCosts = [
        'Fluting' => 1.0,
        'Kraft Liner' => 1.2,
        'Corn Flour' => 0.5,
        'Seligate (Glue)' => 0.6,
        'Caustic Soda' => 0.4,
        'Borax' => 1.0,
        'Lamination Plastic' => 2.25,
    ];

    foreach ($landedCosts as $name => $cost) {
        $isKgOnly = in_array($name, ['Corn Flour', 'Seligate (Glue)', 'Caustic Soda', 'Borax', 'Lamination Plastic'], true);

        DB::table('purchase_items')->insert([
            'purchase_id' => $purchaseId,
            'product_id' => $products[$name],
            'purchase_currency_id' => $usdId,
            'qty' => $isKgOnly ? 1000 : 10,
            'qty_available' => $isKgOnly ? 1000 : 10,
            'unit' => $isKgOnly ? 'kg' : 'roll',
            'kg_per_roll' => $isKgOnly ? null : 1000,
            'qty_kg_available' => 100000,
            'qty_sold' => 0,
            'qty_returned' => 0,
            'qty_wasted' => 0,
            'landed_cost_per_kg' => $cost,
            'usd_total' => $cost * 10000,
            'usd_cost_per_item' => $cost,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    $profile = BoardProfile::create([
        'name' => 'Spec 5 Ply 125/145',
        'code' => 'SPEC-5PLY-' . strtoupper(uniqid()),
        'ply' => 5,
        'flute_type' => 'BC',
        'wastage_percentage' => 5,
        'is_active' => true,
        'version' => '1.0',
    ]);

    BoardProfileLayer::create([
        'board_profile_id' => $profile->id,
        'sort_order' => 0,
        'role' => 'generic_client_formula',
        'component_type' => 'paper',
        'material_id' => $products['Fluting'],
        'gsm' => 125,
        'multiplication_layer' => 5,
        'commercial_work_enabled' => true,
    ]);

    BoardProfileLayer::create([
        'board_profile_id' => $profile->id,
        'sort_order' => 1,
        'role' => 'generic_client_formula',
        'component_type' => 'paper',
        'material_id' => $products['Kraft Liner'],
        'gsm' => 145,
        'multiplication_layer' => 1,
        'commercial_work_enabled' => true,
    ]);

    return [
        'user' => $user,
        'products' => $products,
        'profile' => $profile,
        'landed' => $landedCosts,
    ];
}

function cartonSpecInput(array $fx, array $overrides = []): array
{
    return array_merge([
        'box_style' => 'RSC',
        'length' => 30,
        'width' => 30,
        'height' => 30,
        'dimension_unit' => 'cm',
        'board_profile_id' => $fx['profile']->id,
        'ply' => 5,
        'printing_option' => 'none',
        'quantity' => 1,
        'work_percentage' => 40,
        'profit_margin_percentage' => 0,
        'exchange_rate' => 66,
    ], $overrides);
}

it('resolves an RSC 30x30x30 cm blank with the client reel formula', function () {
    $fx = cartonSpecFixtures();

    $result = app(CartonSpecificationService::class)->calculate(cartonSpecInput($fx));

    expect($result['spec']['box_style'])->toBe('RSC')
        ->and((float) $result['spec']['length_inch'])->toBe(11.81)
        ->and((float) $result['spec']['reel_length_inch'])->toBe(51.24)
        ->and((float) $result['spec']['reel_height_inch'])->toBe(24.62);
});

it('keeps the client paper source of truth 125 x 5 and 145 x 1 exactly', function () {
    $fx = cartonSpecFixtures();
    $service = app(CartonSpecificationService::class);

    $result = $service->calculate(cartonSpecInput($fx));

    $reelLength = 51.24;
    $reelHeight = 24.62;
    $kg125 = $reelLength * $reelHeight * 125 * 5 / 1550000;
    $kg145 = $reelLength * $reelHeight * 145 * 1 / 1550000;

    $flutingRow = collect($result['rows'])->firstWhere('paper_gsm', 125);
    $kraftRow = collect($result['rows'])->firstWhere('paper_gsm', 145);

    expect((float) $flutingRow['kg_per_unit'])->toEqualWithDelta($kg125, 0.0000000001)
        ->and((float) $kraftRow['kg_per_unit'])->toEqualWithDelta($kg145, 0.0000000001)
        ->and((float) $result['paper']['basis_kg_per_unit'])->toEqualWithDelta($kg125 + $kg145, 0.0000001)
        ->and((float) $result['paper']['physical_kg_per_unit'])->toEqualWithDelta(($kg125 + $kg145) * 1.05, 0.0000001);
});

it('treats cm and the equivalent inch dimensions identically', function () {
    $fx = cartonSpecFixtures();
    $service = app(CartonSpecificationService::class);

    $fromCm = $service->calculate(cartonSpecInput($fx));
    $fromInch = $service->calculate(cartonSpecInput($fx, [
        'length' => 11.81,
        'width' => 11.81,
        'height' => 11.81,
        'dimension_unit' => 'inch',
    ]));

    expect((float) $fromCm['spec']['reel_length_inch'])->toBe((float) $fromInch['spec']['reel_length_inch'])
        ->and((float) $fromInch['paper']['basis_kg_per_unit'])->toEqualWithDelta(
            (float) $fromCm['paper']['basis_kg_per_unit'],
            0.0000001
        );
});

it('rejects zero or invalid dimensions and unsupported styles', function () {
    $fx = cartonSpecFixtures();
    $service = app(CartonSpecificationService::class);

    expect(fn () => $service->calculate(cartonSpecInput($fx, ['length' => 0])))
        ->toThrow(RuntimeException::class)
        ->and(fn () => $service->calculate(cartonSpecInput($fx, ['width' => -5])))
        ->toThrow(RuntimeException::class)
        ->and(fn () => $service->calculate(cartonSpecInput($fx, ['height' => null])))
        ->toThrow(RuntimeException::class)
        ->and(fn () => $service->calculate(cartonSpecInput($fx, ['box_style' => 'NONEXISTENT'])))
        ->toThrow(RuntimeException::class)
        ->and(fn () => $service->calculate(cartonSpecInput($fx, ['quantity' => 0])))
        ->toThrow(RuntimeException::class);
});

it('refuses an inactive board profile', function () {
    $fx = cartonSpecFixtures();
    $fx['profile']->update(['is_active' => false]);

    expect(fn () => app(CartonSpecificationService::class)->calculate(cartonSpecInput($fx)))
        ->toThrow(RuntimeException::class);
});

it('scales order totals with quantity while unit values stay fixed', function () {
    $fx = cartonSpecFixtures();
    $service = app(CartonSpecificationService::class);

    $one = $service->calculate(cartonSpecInput($fx, ['quantity' => 1]));
    $ten = $service->calculate(cartonSpecInput($fx, ['quantity' => 10]));

    expect((float) $ten['commercial']['order_total_afn'])->toEqualWithDelta(
        (float) $one['commercial']['selling_price_afn_per_unit'] * 10,
        0.01
    )->and((float) $ten['paper']['physical_kg_total'])->toEqualWithDelta(
        (float) $one['paper']['physical_kg_per_unit'] * 10,
        0.0001
    );
});

it('adds optional lamination from board area and carries it into BOM stock consumption', function () {
    $fx = cartonSpecFixtures();
    $service = app(CartonSpecificationService::class);

    $plain = $service->calculate(cartonSpecInput($fx));
    $laminated = $service->calculate(cartonSpecInput($fx, [
        'lamination_enabled' => true,
        'quantity' => 10,
    ]));

    $areaM2 = (float) $laminated['spec']['board_area_m2'];
    $baseKg = $areaM2 * 20 / 1000;
    $withWasteKg = $baseKg * 1.05;
    $row = collect($laminated['rows'])->firstWhere('role', 'lamination');

    expect($laminated['lamination']['enabled'])->toBeTrue()
        ->and((float) $row['stock_consumption_override'])->toEqualWithDelta($baseKg, 0.00000001)
        ->and((float) $laminated['lamination']['kg_per_unit'])->toEqualWithDelta($withWasteKg, 0.00000001)
        ->and((float) $laminated['lamination']['kg_total'])->toEqualWithDelta($withWasteKg * 10, 0.0000001)
        ->and((float) $laminated['physical']['material_cost_afn_per_unit'])
        ->toBeGreaterThan((float) $plain['physical']['material_cost_afn_per_unit'])
        ->and((float) $laminated['commercial']['selling_price_afn_per_unit'])
        ->toBeGreaterThan((float) $plain['commercial']['selling_price_afn_per_unit']);

    $bom = $service->persistTechnicalBom(
        $laminated,
        $fx['products']['Spec Carton Box'],
        $fx['user']->id
    );
    $laminationItem = $bom->items->firstWhere('notes', 'lamination');

    expect($laminationItem)->not->toBeNull()
        ->and($laminationItem->formula_type)->toBe('fixed_rate')
        ->and((float) $laminationItem->calculateStockRequirement(10, true))
        ->toEqualWithDelta($withWasteKg * 10, 0.0000001);
});

it('does not change adhesive quantity when only paper GSM changes', function () {
    $fx = cartonSpecFixtures();
    $service = app(CartonSpecificationService::class);

    $base = $service->calculate(cartonSpecInput($fx));

    $fx['profile']->layers()->where('gsm', 125)->update(['gsm' => 200]);
    $changed = $service->calculate(cartonSpecInput($fx));

    expect((float) $changed['paper']['basis_kg_per_unit'])
        ->toBeGreaterThan((float) $base['paper']['basis_kg_per_unit'])
        ->and((float) $changed['adhesive']['kg_per_unit'])
        ->toEqualWithDelta((float) $base['adhesive']['kg_per_unit'], 0.0000001);
});

it('grows adhesive consumption with carton dimensions', function () {
    $fx = cartonSpecFixtures();
    $service = app(CartonSpecificationService::class);

    $small = $service->calculate(cartonSpecInput($fx, ['length' => 20, 'width' => 20, 'height' => 20]));
    $large = $service->calculate(cartonSpecInput($fx, ['length' => 50, 'width' => 40, 'height' => 40]));

    expect((float) $large['adhesive']['kg_per_unit'])
        ->toBeGreaterThan((float) $small['adhesive']['kg_per_unit']);
});

it('applies the client 40 percent work only to the paper commercial basis', function () {
    $fx = cartonSpecFixtures();
    $service = app(CartonSpecificationService::class);

    $result = $service->calculate(cartonSpecInput($fx));

    $expectedWork = (float) $result['commercial']['paper_basis_afn_per_unit'] * 0.40;

    expect((float) $result['commercial']['work_profit_afn_per_unit'])->toEqualWithDelta($expectedWork, 0.0001)
        ->and((float) $result['commercial']['net_rate_afn_per_unit'])->toEqualWithDelta(
            (float) $result['commercial']['paper_basis_afn_per_unit'] + $expectedWork,
            0.0001
        );

    // Adhesive still contributes to physical material cost.
    expect((float) $result['physical']['material_cost_afn_per_unit'])
        ->toBeGreaterThan((float) $result['commercial']['paper_basis_afn_per_unit']);
});

it('adds the printing cost once and applies the profit margin on the net rate', function () {
    $fx = cartonSpecFixtures();
    $service = app(CartonSpecificationService::class);

    $result = $service->calculate(cartonSpecInput($fx, [
        'printing_option' => 'single_color',
        'print_cost_afn' => 5,
        'profit_margin_percentage' => 10,
    ]));

    $net = (float) $result['commercial']['paper_basis_afn_per_unit']
        + (float) $result['commercial']['work_profit_afn_per_unit']
        + 5;

    expect((float) $result['commercial']['print_cost_afn_per_unit'])->toBe(5.0)
        ->and((float) $result['commercial']['net_rate_afn_per_unit'])->toEqualWithDelta($net, 0.0001)
        ->and((float) $result['commercial']['selling_price_afn_per_unit'])->toEqualWithDelta($net * 1.10, 0.0001);
});

it('keeps optional flute metadata without altering the RSC paper formula', function () {
    $fx = cartonSpecFixtures();
    $service = app(CartonSpecificationService::class);

    $withoutFlute = $service->calculate(cartonSpecInput($fx, ['flute_type' => null]));
    $withFlute = $service->calculate(cartonSpecInput($fx, ['flute_type' => 'B']));

    expect($withFlute['spec']['flute_type'])->toBe('B')
        ->and((float) $withFlute['paper']['basis_kg_per_unit'])
        ->toEqualWithDelta((float) $withoutFlute['paper']['basis_kg_per_unit'], 0.0000001);
});

it('reports raw material shortages against current stock', function () {
    $fx = cartonSpecFixtures();
    $service = app(CartonSpecificationService::class);

    $result = $service->calculate(cartonSpecInput($fx, ['quantity' => 1000]));

    expect($result['shortages']['has_shortage'])->toBeFalse();

    DB::table('purchase_items')
        ->where('product_id', $fx['products']['Fluting'])
        ->update(['qty_kg_available' => 0]);

    $short = $service->calculate(cartonSpecInput($fx, ['quantity' => 1000]));

    expect($short['shortages']['has_shortage'])->toBeTrue();

    $fluting = collect($short['shortages']['materials'])
        ->firstWhere('material_id', $fx['products']['Fluting']);

    expect((float) $fluting['shortage_quantity'])->toBeGreaterThan(0)
        ->and($fluting['is_available'])->toBeFalse();
});

it('persists a versioned technical BOM whose rows carry component classification', function () {
    $fx = cartonSpecFixtures();
    $service = app(CartonSpecificationService::class);

    $result = $service->calculate(cartonSpecInput($fx, ['quantity' => 10]));
    $bom = $service->persistTechnicalBom($result, $fx['products']['Spec Carton Box'], $fx['user']->id);

    expect($bom)->toBeInstanceOf(BOM::class)
        ->and($bom->status)->toBe('active')
        ->and($bom->items)->toHaveCount(6);

    $paperItems = $bom->items->where('component_type', 'paper');
    $adhesiveItems = $bom->items->where('component_type', 'adhesive');

    expect($paperItems)->toHaveCount(2)
        ->and($adhesiveItems)->toHaveCount(4)
        ->and($paperItems->every(fn ($item) => $item->appliesWorkProfit() === true))->toBeTrue()
        ->and($adhesiveItems->every(fn ($item) => $item->appliesWorkProfit() === false))->toBeTrue();

    $fluting = $bom->items->firstWhere('paper_gsm', 125);
    expect((int) $fluting->multiplication_layer)->toBe(5)
        ->and((float) $fluting->per_gram_rate)->toBe(66.0);
});
