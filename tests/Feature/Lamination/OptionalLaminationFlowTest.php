<?php

use App\Models\BOM;
use App\Models\Product;
use App\Models\ProductionMaterialConsumption;
use App\Models\ProductionOrder;
use App\Models\PurchaseItem;
use App\Models\User;
use App\Services\BOMCostingService;
use App\Services\CartonLaminationService;
use App\Services\ProductionQuantityService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

it('creates an order-specific lamination BOM without changing the master BOM and consumes lamination automatically', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::factory()->create();
    Auth::login($user);

    $source = BOM::query()
        ->withoutGlobalScope('business_unit')
        ->where('code', 'like', 'BOM-CLIENT-%')
        ->where('description', 'not like', '[REVIEW REQUIRED]%')
        ->with('items.material')
        ->firstOrFail();

    $lamination = Product::query()
        ->where('name', 'Lamination Plastic')
        ->where('type', Product::TYPE_RAW_MATERIAL)
        ->firstOrFail();

    expect($source->items->where('material_id', $lamination->id))->toHaveCount(0);

    $rate = max((float) $source->getUSDtoAFNRate(), 0.000001);
    $service = app(CartonLaminationService::class);
    $preview = $service->previewForBom($source, $rate);

    $expectedBaseKg = (float) $preview['board_area_m2']
        * (float) config('carton.lamination.film_gsm')
        * (int) config('carton.lamination.sides')
        / 1000;
    $expectedWithWaste = $expectedBaseKg
        * (1 + ((float) config('carton.lamination.wastage_percentage') / 100));

    expect($preview['available'])->toBeTrue()
        ->and(abs((float) $preview['kg_per_unit'] - $expectedBaseKg))->toBeLessThan(0.0000001)
        ->and(abs((float) $preview['kg_with_wastage'] - $expectedWithWaste))->toBeLessThan(0.0000001)
        ->and((float) $preview['selling_price_addition_afn_per_unit'])->toBeGreaterThan(0);

    $sourceSummary = app(BOMCostingService::class)->summarize($source);
    $generated = $service->createOrderSpecificBom($source, $user->id, $rate);
    $orderBom = $generated['bom']->fresh(['items.material']);
    $laminationRow = $orderBom->items->firstWhere('material_id', $lamination->id);

    expect($laminationRow)->not->toBeNull()
        ->and($orderBom->id)->not->toBe($source->id)
        ->and($orderBom->supersedes_bom_id)->toBe($source->id)
        ->and($orderBom->status)->toBe('draft')
        ->and((bool) $orderBom->is_active)->toBeFalse()
        ->and($laminationRow->formula_type)->toBe('fixed_rate')
        ->and((bool) $laminationRow->is_formula_based)->toBeTrue()
        ->and((bool) $laminationRow->apply_work_percentage)->toBeFalse()
        ->and(abs(
            (float) $laminationRow->calculateStockRequirement(10, true)
            - ($expectedWithWaste * 10)
        ))->toBeLessThan(0.00001);

    $source->refresh()->load('items');
    expect($source->items->where('material_id', $lamination->id))->toHaveCount(0);

    $orderSummary = app(BOMCostingService::class)->summarize($orderBom);
    expect((float) $orderSummary['physical_production_cost_usd'])
        ->toBeGreaterThan((float) $sourceSummary['physical_production_cost_usd'])
        ->and((float) $orderSummary['selling_price_afn'])
        ->toBeGreaterThan((float) $sourceSummary['selling_price_afn']);

    $beforeAvailable = PurchaseItem::query()
        ->where('product_id', $lamination->id)
        ->whereHas('purchase', fn ($q) => $q->where('status', 'arrived'))
        ->get()
        ->sum(fn (PurchaseItem $item) => $item->availableInventoryQuantity());

    $productionOrder = ProductionOrder::create([
        'order_number' => 'LAMINATION-AUTO-001',
        'product_id' => $orderBom->product_id,
        'bom_id' => $orderBom->id,
        'quantity_ordered' => 10,
        'status' => ProductionOrder::STATUS_PENDING,
        'created_by' => $user->id,
        'start_date' => today()->toDateString(),
    ]);

    app(ProductionQuantityService::class)->start($productionOrder, null, 10);

    $laminationConsumed = (float) ProductionMaterialConsumption::query()
        ->where('production_order_id', $productionOrder->id)
        ->where('material_id', $lamination->id)
        ->sum('actual_quantity');

    $afterAvailable = PurchaseItem::query()
        ->where('product_id', $lamination->id)
        ->whereHas('purchase', fn ($q) => $q->where('status', 'arrived'))
        ->get()
        ->sum(fn (PurchaseItem $item) => $item->availableInventoryQuantity());

    expect(abs($laminationConsumed - ($expectedWithWaste * 10)))->toBeLessThan(0.001)
        ->and($afterAvailable)->toBeLessThan($beforeAvailable);
});

it('exposes the lamination switches and uses the wider Add Carton drawer', function () {
    $saleView = file_get_contents(resource_path('views/admin/sales/show.blade.php'));
    $modernView = file_get_contents(resource_path('views/admin/sales/partials/modern-show.blade.php'));

    expect($saleView)
        ->toContain('id="laminationEnabled"')
        ->toContain('id="csLaminationEnabled"')
        ->toContain('lamination_enabled:')
        ->toContain('Auto-calculated from carton board area and Lamination Plastic stock.')
        ->and($modernView)
        ->toContain('width:min(1080px,96vw)!important;');
});
