<?php

use App\Models\BOM;
use App\Models\User;
use App\Services\BOMCostingService;
use App\Services\LaminationAddonService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('derives an order-specific laminated BOM without mutating the master recipe', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::factory()->create();

    $source = BOM::query()
        ->where('code', 'like', 'BOM-CLIENT-%')
        ->where('description', 'not like', '[REVIEW REQUIRED]%')
        ->with('items.material')
        ->get()
        ->first(fn (BOM $bom) => $bom->items->contains(
            fn ($item) => (float) ($item->reel_length_inch ?? 0) > 0
                && (float) ($item->reel_height_inch ?? 0) > 0
        ));

    expect($source)->not->toBeNull();

    $sourceId = $source->id;
    $sourceItemCount = $source->items->count();
    $sourcePrice = (float) app(BOMCostingService::class)->summarize($source)['selling_price_afn'];

    $derived = app(LaminationAddonService::class)->derive($source, $user);
    $lamination = $derived->items->firstWhere('notes', 'lamination');

    $source->refresh()->load('items.material');

    expect($derived->id)->not->toBe($sourceId)
        ->and($derived->supersedes_bom_id)->toBe($sourceId)
        ->and($derived->status)->toBe('draft')
        ->and((bool) $derived->is_active)->toBeFalse()
        ->and($source->items)->toHaveCount($sourceItemCount)
        ->and($source->items->firstWhere('notes', 'lamination'))->toBeNull()
        ->and($derived->items)->toHaveCount($sourceItemCount + 1)
        ->and($lamination)->not->toBeNull()
        ->and($lamination->material->name)->toBe('Lamination Plastic')
        ->and($lamination->formula_type)->toBe('fixed_rate')
        ->and((bool) $lamination->apply_work_percentage)->toBeFalse()
        ->and((float) $lamination->calculateStockRequirement(100, true))->toBeGreaterThan(0)
        ->and((float) app(BOMCostingService::class)->summarize($derived)['selling_price_afn'])
        ->toBeGreaterThan($sourcePrice);
});
