<?php

use App\Http\Controllers\Admin\BOMController;
use App\Models\BOM;
use App\Models\User;
use App\Services\BOMGovernanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function enterpriseBomFixture(): array
{
    $user = User::factory()->create();
    $now = now();

    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Enterprise BOM Category',
        'slug' => 'enterprise-bom-category',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $materialId = DB::table('products')->insertGetId([
        'name' => 'Enterprise Test Liner',
        'slug' => 'enterprise-test-liner',
        'unit' => 'kg',
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $finishedId = DB::table('products')->insertGetId([
        'name' => 'Enterprise Carton',
        'slug' => 'enterprise-carton',
        'unit' => 'pcs',
        'category_id' => $categoryId,
        'type' => 'finished_good',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $bomId = DB::table('boms')->insertGetId([
        'name' => 'Enterprise BOM',
        'code' => 'BOM-ENTERPRISE',
        'product_id' => $finishedId,
        'version' => '1.0',
        'revision_sequence' => 1,
        'status' => 'active',
        'is_active' => 1,
        'effective_from' => now()->subMonth()->toDateString(),
        'created_by' => $user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('bom_items')->insert([
        'bom_id' => $bomId,
        'material_id' => $materialId,
        'quantity' => 1.25,
        'unit' => 'kg',
        'wastage_percentage' => 5,
        'cost_per_unit_usd' => 0.9,
        'total_cost_usd' => 1.18125,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return [$user, BOM::query()->findOrFail($bomId)];
}

it('creates a draft BOM revision without mutating the approved source', function () {
    [$user, $source] = enterpriseBomFixture();

    $revision = app(BOMGovernanceService::class)->createRevision($source, $user);

    expect($revision->id)->not->toBe($source->id)
        ->and($revision->supersedes_bom_id)->toBe($source->id)
        ->and($revision->revision_sequence)->toBe(2)
        ->and($revision->version)->toBe('R2')
        ->and($revision->status)->toBe('draft')
        ->and($revision->is_active)->toBeFalse()
        ->and($revision->items)->toHaveCount(1)
        ->and((float) $revision->items->first()->quantity)->toBe(1.25);

    $source->refresh();

    expect($source->status)->toBe('active')
        ->and($source->effective_to)->toBeNull();
});

it('approves and locks the new revision while archiving historical BOM state', function () {
    [$user, $source] = enterpriseBomFixture();

    $revision = app(BOMGovernanceService::class)->createRevision($source, $user);
    $effective = now()->addDay()->toDateString();

    $approved = app(BOMGovernanceService::class)
        ->approveRevision($revision, $user, $effective);

    expect($approved->status)->toBe('active')
        ->and($approved->is_active)->toBeTrue()
        ->and($approved->locked_at)->not->toBeNull()
        ->and($approved->approved_by)->toBe($user->id)
        ->and($approved->effective_from->toDateString())->toBe($effective);

    $source->refresh();

    expect($source->status)->toBe('archived')
        ->and($source->is_active)->toBeFalse()
        ->and($source->locked_at)->not->toBeNull()
        ->and($source->effective_to->toDateString())
        ->toBe(now()->toDateString());

    $this->actingAs($user);

    $response = app(BOMController::class)->edit($approved);

    expect($response->getTargetUrl())->toContain('/bom/'.$approved->id)
        ->and(session('error'))->toContain('locked');
});
