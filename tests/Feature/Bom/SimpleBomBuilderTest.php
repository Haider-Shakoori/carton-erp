<?php

use App\Http\Middleware\CheckPermissionWithFeedback;
use App\Models\BoardProfile;
use App\Models\BoardProfileLayer;
use App\Models\BOM;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function simpleBomBuilderFixtures(): array
{
    $user = User::factory()->create();
    $now = now();

    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Quick BOM Materials',
        'slug' => 'quick-bom-' . uniqid(),
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $materials = [];

    foreach ([
        'Fluting' => 'roll',
        'Kraft Liner' => 'roll',
        'Corn Flour' => 'kg',
        'Seligate (Glue)' => 'kg',
        'Caustic Soda' => 'kg',
        'Borax' => 'kg',
    ] as $name => $unit) {
        $existing = DB::table('products')->where('name', $name)->value('id');

        $materials[$name] = $existing
            ? (int) $existing
            : DB::table('products')->insertGetId([
                'name' => $name,
                'slug' => strtolower(str_replace([' ', '(', ')'], ['-', '', ''], $name)) . '-' . uniqid(),
                'unit' => $unit,
                'category_id' => $categoryId,
                'type' => 'raw_material',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
    }

    $materials['Quick BOM Finished Carton'] = DB::table('products')->insertGetId([
        'name' => 'Quick BOM Finished Carton-' . uniqid(),
        'slug' => 'quick-bom-finished-' . uniqid(),
        'unit' => 'pcs',
        'category_id' => $categoryId,
        'type' => 'finished_good',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $usdId = DB::table('currencies')->where('code', 'USD')->value('id');

    if (! $usdId) {
        $usdId = DB::table('currencies')->insertGetId([
            'name' => 'US Dollar',
            'code' => 'USD',
            'symbol' => '

    $purchaseId = DB::table('purchases')->insertGetId([
        'purchase_no' => 'PO-QUICK-' . strtoupper(uniqid()),
        'currency_id' => $usdId,
        'status' => 'arrived',
        'purchase_date' => '2026-09-01',
        'arrival_date' => '2026-09-02',
        'exchange_rate' => 66,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    foreach ([
        'Fluting' => 1.00,
        'Kraft Liner' => 1.20,
        'Corn Flour' => 0.50,
        'Seligate (Glue)' => 0.60,
        'Caustic Soda' => 0.40,
        'Borax' => 1.00,
    ] as $name => $cost) {
        $isRoll = in_array($name, ['Fluting', 'Kraft Liner'], true);

        DB::table('purchase_items')->insert([
            'purchase_id' => $purchaseId,
            'product_id' => $materials[$name],
            'purchase_currency_id' => $usdId,
            'qty' => $isRoll ? 10 : 1000,
            'qty_available' => $isRoll ? 10 : 1000,
            'unit' => $isRoll ? 'roll' : 'kg',
            'kg_per_roll' => $isRoll ? 900 : null,
            'qty_kg_available' => $isRoll ? 9000 : 1000,
            'qty_sold' => 0,
            'qty_returned' => 0,
            'qty_wasted' => 0,
            'landed_cost_per_kg' => $cost,
            'usd_total' => $cost * 1000,
            'usd_cost_per_item' => $cost,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    $profile = BoardProfile::create([
        'name' => 'Quick Standard 5 Ply',
        'code' => 'QUICK-5PLY-' . strtoupper(uniqid()),
        'ply' => 5,
        'flute_type' => 'BC',
        'wastage_percentage' => 5,
        'is_active' => true,
        'version' => '1.0',
    ]);

    BoardProfileLayer::create([
        'board_profile_id' => $profile->id,
        'sort_order' => 0,
        'role' => 'client_formula',
        'component_type' => 'paper',
        'material_id' => $materials['Fluting'],
        'gsm' => 125,
        'multiplication_layer' => 5,
        'commercial_work_enabled' => true,
    ]);

    BoardProfileLayer::create([
        'board_profile_id' => $profile->id,
        'sort_order' => 1,
        'role' => 'client_formula',
        'component_type' => 'paper',
        'material_id' => $materials['Kraft Liner'],
        'gsm' => 145,
        'multiplication_layer' => 1,
        'commercial_work_enabled' => true,
    ]);

    return compact('user', 'materials', 'profile');
}

function simpleBomPayload(array $fx): array
{
    return [
        'product_id' => $fx['materials']['Quick BOM Finished Carton'],
        'board_profile_id' => $fx['profile']->id,
        'box_style' => 'RSC',
        'dimension_unit' => 'mm',
        'printing_option' => 'none',
        'print_cost_afn' => 0,
        'wastage_percentage' => 5,
        'work_percentage' => 40,
        'profit_margin_percentage' => 0,
        'exchange_rate' => 66,
        'sizes' => [
            [
                'name' => 'Quick 120ml',
                'length' => 300,
                'width' => 180,
                'height' => 150,
            ],
            [
                'name' => 'Quick 250ml',
                'length' => 400,
                'width' => 250,
                'height' => 200,
            ],
        ],
    ];
}

it('renders the quick BOM builder while keeping the advanced builder available', function () {
    $fx = simpleBomBuilderFixtures();

    $response = $this
        ->withoutMiddleware(CheckPermissionWithFeedback::class)
        ->actingAs($fx['user'])
        ->get(route('bom.create'));

    $response
        ->assertOk()
        ->assertSee('Quick BOM Builder')
        ->assertSee('Board Preset')
        ->assertSee('Add Another Size')
        ->assertSee('Advanced BOM Builder')
        ->assertSee('Standard Work / Profit');
});

it('previews multiple carton sizes through the canonical specification engine', function () {
    $fx = simpleBomBuilderFixtures();

    $response = $this
        ->withoutMiddleware(CheckPermissionWithFeedback::class)
        ->actingAs($fx['user'])
        ->postJson(route('bom.simple-preview'), simpleBomPayload($fx));

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data.previews')
        ->assertJsonPath('data.previews.0.name', 'Quick 120ml')
        ->assertJsonPath('data.previews.1.name', 'Quick 250ml');

    expect((float) $response->json('data.previews.0.standard_work_profit_afn'))->toBeGreaterThan(0)
        ->and((float) $response->json('data.previews.0.standard_rate_afn'))->toBeGreaterThan(0)
        ->and((float) $response->json('data.previews.0.material_kg_per_unit'))->toBeGreaterThan(0);
});

it('creates one production-ready BOM per submitted size', function () {
    $fx = simpleBomBuilderFixtures();

    $response = $this
        ->withoutMiddleware(CheckPermissionWithFeedback::class)
        ->actingAs($fx['user'])
        ->postJson(route('bom.simple-store'), simpleBomPayload($fx));

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data.boms');

    expect(BOM::query()->withoutGlobalScope('business_unit')->where('name', 'Quick 120ml')->exists())->toBeTrue()
        ->and(BOM::query()->withoutGlobalScope('business_unit')->where('name', 'Quick 250ml')->exists())->toBeTrue();

    $bom = BOM::query()
        ->withoutGlobalScope('business_unit')
        ->with('items')
        ->where('name', 'Quick 120ml')
        ->firstOrFail();

    expect($bom->items->count())->toBeGreaterThanOrEqual(6)
        ->and((float) $bom->work_percentage)->toBe(40.0)
        ->and((float) $bom->selling_price_afn)->toBeGreaterThan(0);
});
,
            'exchange_rate' => 1,
            'is_default' => 1,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    if (! DB::table('currencies')->where('code', 'AFN')->exists()) {
        DB::table('currencies')->insert([
            'name' => 'Afghan Afghani',
            'code' => 'AFN',
            'symbol' => '؋',
            'exchange_rate' => 66,
            'is_default' => 0,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    $purchaseId = DB::table('purchases')->insertGetId([
        'purchase_no' => 'PO-QUICK-' . strtoupper(uniqid()),
        'currency_id' => $usdId,
        'status' => 'arrived',
        'purchase_date' => '2026-09-01',
        'arrival_date' => '2026-09-02',
        'exchange_rate' => 66,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    foreach ([
        'Fluting' => 1.00,
        'Kraft Liner' => 1.20,
        'Corn Flour' => 0.50,
        'Seligate (Glue)' => 0.60,
        'Caustic Soda' => 0.40,
        'Borax' => 1.00,
    ] as $name => $cost) {
        $isRoll = in_array($name, ['Fluting', 'Kraft Liner'], true);

        DB::table('purchase_items')->insert([
            'purchase_id' => $purchaseId,
            'product_id' => $materials[$name],
            'purchase_currency_id' => $usdId,
            'qty' => $isRoll ? 10 : 1000,
            'qty_available' => $isRoll ? 10 : 1000,
            'unit' => $isRoll ? 'roll' : 'kg',
            'kg_per_roll' => $isRoll ? 900 : null,
            'qty_kg_available' => $isRoll ? 9000 : 1000,
            'qty_sold' => 0,
            'qty_returned' => 0,
            'qty_wasted' => 0,
            'landed_cost_per_kg' => $cost,
            'usd_total' => $cost * 1000,
            'usd_cost_per_item' => $cost,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    $profile = BoardProfile::create([
        'name' => 'Quick Standard 5 Ply',
        'code' => 'QUICK-5PLY-' . strtoupper(uniqid()),
        'ply' => 5,
        'flute_type' => 'BC',
        'wastage_percentage' => 5,
        'is_active' => true,
        'version' => '1.0',
    ]);

    BoardProfileLayer::create([
        'board_profile_id' => $profile->id,
        'sort_order' => 0,
        'role' => 'client_formula',
        'component_type' => 'paper',
        'material_id' => $materials['Fluting'],
        'gsm' => 125,
        'multiplication_layer' => 5,
        'commercial_work_enabled' => true,
    ]);

    BoardProfileLayer::create([
        'board_profile_id' => $profile->id,
        'sort_order' => 1,
        'role' => 'client_formula',
        'component_type' => 'paper',
        'material_id' => $materials['Kraft Liner'],
        'gsm' => 145,
        'multiplication_layer' => 1,
        'commercial_work_enabled' => true,
    ]);

    return compact('user', 'materials', 'profile');
}

function simpleBomPayload(array $fx): array
{
    return [
        'product_id' => $fx['materials']['Quick BOM Finished Carton'],
        'board_profile_id' => $fx['profile']->id,
        'box_style' => 'RSC',
        'dimension_unit' => 'mm',
        'printing_option' => 'none',
        'print_cost_afn' => 0,
        'wastage_percentage' => 5,
        'work_percentage' => 40,
        'profit_margin_percentage' => 0,
        'exchange_rate' => 66,
        'sizes' => [
            [
                'name' => 'Quick 120ml',
                'length' => 300,
                'width' => 180,
                'height' => 150,
            ],
            [
                'name' => 'Quick 250ml',
                'length' => 400,
                'width' => 250,
                'height' => 200,
            ],
        ],
    ];
}

it('renders the quick BOM builder while keeping the advanced builder available', function () {
    $fx = simpleBomBuilderFixtures();

    $response = $this
        ->withoutMiddleware(CheckPermissionWithFeedback::class)
        ->actingAs($fx['user'])
        ->get(route('bom.create'));

    $response
        ->assertOk()
        ->assertSee('Quick BOM Builder')
        ->assertSee('Board Preset')
        ->assertSee('Add Another Size')
        ->assertSee('Advanced BOM Builder')
        ->assertSee('Standard Work / Profit');
});

it('previews multiple carton sizes through the canonical specification engine', function () {
    $fx = simpleBomBuilderFixtures();

    $response = $this
        ->withoutMiddleware(CheckPermissionWithFeedback::class)
        ->actingAs($fx['user'])
        ->postJson(route('bom.simple-preview'), simpleBomPayload($fx));

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data.previews')
        ->assertJsonPath('data.previews.0.name', 'Quick 120ml')
        ->assertJsonPath('data.previews.1.name', 'Quick 250ml');

    expect((float) $response->json('data.previews.0.standard_work_profit_afn'))->toBeGreaterThan(0)
        ->and((float) $response->json('data.previews.0.standard_rate_afn'))->toBeGreaterThan(0)
        ->and((float) $response->json('data.previews.0.material_kg_per_unit'))->toBeGreaterThan(0);
});

it('creates one production-ready BOM per submitted size', function () {
    $fx = simpleBomBuilderFixtures();

    $response = $this
        ->withoutMiddleware(CheckPermissionWithFeedback::class)
        ->actingAs($fx['user'])
        ->postJson(route('bom.simple-store'), simpleBomPayload($fx));

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data.boms');

    expect(BOM::query()->withoutGlobalScope('business_unit')->where('name', 'Quick 120ml')->exists())->toBeTrue()
        ->and(BOM::query()->withoutGlobalScope('business_unit')->where('name', 'Quick 250ml')->exists())->toBeTrue();

    $bom = BOM::query()
        ->withoutGlobalScope('business_unit')
        ->with('items')
        ->where('name', 'Quick 120ml')
        ->firstOrFail();

    expect($bom->items->count())->toBeGreaterThanOrEqual(6)
        ->and((float) $bom->work_percentage)->toBe(40.0)
        ->and((float) $bom->selling_price_afn)->toBeGreaterThan(0);
});
