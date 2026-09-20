<?php

use App\Models\BoardProfile;
use App\Models\BoardProfileLayer;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\BoardProfileSeeder;
use Database\Seeders\ClientCartonRawMaterialSeeder;
use Illuminate\Support\Facades\DB;

/**
 * Board Profile foundation (Batch 1).
 *
 * The profile is a manufacturing recipe and the client source of truth is
 * 125 GSM x 5 and 145 GSM x 1, evaluated with:
 *   PaperKg = ReelLength x ReelHeight x GSM x MultiplicationLayer / 1,550,000
 */
function boardProfileFixtures(): array
{
    $user = User::factory()->create();

    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Board Profile Category',
        'slug' => 'board-profile-category',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    foreach (['Test Liner', 'Fluting', 'Kraft Liner', 'Semi Kraft', 'White Liner', 'Box Board'] as $name) {
        DB::table('products')->insert([
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)) . '-' . uniqid(),
            'unit' => 'roll',
            'category_id' => $categoryId,
            'type' => 'raw_material',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    return ['user' => $user];
}

it('seeds the default client board profile with the verified 125x5 and 145x1 recipe', function () {
    boardProfileFixtures();

    $this->seed(ClientCartonRawMaterialSeeder::class);
    $this->seed(BoardProfileSeeder::class);

    $profile = BoardProfile::where('code', 'STD-5PLY-125-145')->with('layers')->first();

    expect($profile)->not->toBeNull()
        ->and($profile->is_active)->toBeTrue()
        ->and((int) $profile->ply)->toBe(5)
        ->and($profile->layers)->toHaveCount(2);

    $first = $profile->layers->firstWhere('gsm', 125);
    $second = $profile->layers->firstWhere('gsm', 145);

    expect((float) $first->multiplication_layer)->toBe(5.0)
        ->and((float) $second->multiplication_layer)->toBe(1.0);
});

it('resolves the client profile layer paper kg exactly with the verified formula', function () {
    boardProfileFixtures();
    $this->seed(ClientCartonRawMaterialSeeder::class);
    $this->seed(BoardProfileSeeder::class);

    $profile = BoardProfile::where('code', 'STD-5PLY-125-145')->with('layers')->firstOrFail();

    $reelLength = 51.244;  // ((11.811 + 11.811) * 2) + 4 for a 30 cm cube
    $reelHeight = 24.622;  // 11.811 + 11.811 + 1

    $layer125 = $profile->layers->firstWhere('gsm', 125);
    $layer145 = $profile->layers->firstWhere('gsm', 145);

    $expected125 = $reelLength * $reelHeight * 125 * 5 / 1550000;
    $expected145 = $reelLength * $reelHeight * 145 * 1 / 1550000;

    expect($layer125->paperKgPerUnit($reelLength, $reelHeight))->toEqualWithDelta($expected125, 0.0000000001)
        ->and($layer145->paperKgPerUnit($reelLength, $reelHeight))->toEqualWithDelta($expected145, 0.0000000001)
        ->and($expected145)->toBeGreaterThan(0);
});

it('does not overwrite existing profile layers when the seeder runs again', function () {
    boardProfileFixtures();
    $this->seed(ClientCartonRawMaterialSeeder::class);
    $this->seed(BoardProfileSeeder::class);

    $profile = BoardProfile::where('code', 'STD-5PLY-125-145')->firstOrFail();
    $layer = $profile->layers()->first();
    $layer->update(['gsm' => 999]);

    $this->seed(BoardProfileSeeder::class);

    expect((int) $profile->fresh()->layers()->count())->toBe(2)
        ->and((int) $profile->fresh()->layers()->first()->gsm)->toBe(999);
});

it('treats an inactive profile as not usable', function () {
    boardProfileFixtures();

    $profile = BoardProfile::create([
        'name' => 'Retired Profile',
        'code' => 'RETIRED-5PLY',
        'ply' => 5,
        'wastage_percentage' => 5,
        'is_active' => false,
        'version' => '1.0',
    ]);

    expect($profile->isUsable())->toBeFalse()
        ->and(BoardProfile::active()->whereKey($profile->id)->exists())->toBeFalse();
});

it('freezes profile version and layer data in the snapshot and is unaffected by later edits', function () {
    boardProfileFixtures();
    $this->seed(ClientCartonRawMaterialSeeder::class);
    $this->seed(BoardProfileSeeder::class);

    $profile = BoardProfile::where('code', 'STD-5PLY-125-145')->with('layers')->firstOrFail();
    $snapshot = $profile->snapshot();

    expect($snapshot['version'])->toBe('1.0')
        ->and($snapshot['layers'])->toHaveCount(2)
        ->and($snapshot['layers'][0]['gsm'])->toBe(125)
        ->and((float) $snapshot['layers'][0]['multiplication_layer'])->toBe(5.0)
        ->and($snapshot['layers'][1]['gsm'])->toBe(145)
        ->and((float) $snapshot['layers'][1]['multiplication_layer'])->toBe(1.0);

    $profile->update(['version' => '2.0', 'wastage_percentage' => 8]);
    $profile->layers()->first()->update(['gsm' => 200]);

    expect($snapshot['version'])->toBe('1.0')
        ->and($snapshot['wastage_percentage'])->toBe(5.0)
        ->and($snapshot['layers'][0]['gsm'])->toBe(125);
});

it('keeps the board profile layer model resolvable through its relationships', function () {
    boardProfileFixtures();

    $profile = BoardProfile::create([
        'name' => 'Relationship Profile',
        'code' => 'REL-5PLY',
        'ply' => 5,
        'is_active' => true,
    ]);

    $fluting = Product::where('name', 'Fluting')->firstOrFail();

    $layer = BoardProfileLayer::create([
        'board_profile_id' => $profile->id,
        'sort_order' => 0,
        'role' => 'flute',
        'component_type' => 'paper',
        'material_id' => $fluting->id,
        'gsm' => 125,
        'multiplication_layer' => 5,
    ]);

    expect($layer->boardProfile->is($profile))->toBeTrue()
        ->and($layer->material->is($fluting))->toBeTrue();
});
