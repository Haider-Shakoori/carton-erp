<?php

use App\Models\PurchaseItem;
use App\Models\User;
use App\Services\ReelBarcodeService;
use App\Services\ReelInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function reelBarcodeFixture(): array
{
    $user = User::factory()->create(['is_active' => true]);
    $now = now();

    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Barcode Reel Paper',
        'slug' => 'barcode-reel-paper',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $materialId = DB::table('products')->insertGetId([
        'name' => 'Barcode Kraft Paper 150 GSM',
        'slug' => 'barcode-kraft-paper-150-gsm',
        'unit' => 'roll',
        'default_kg_per_roll' => 500,
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $currencyId = DB::table('currencies')->insertGetId([
        'name' => 'Barcode USD',
        'code' => 'BQUSD',
        'symbol' => '$',
        'exchange_rate' => 1,
        'is_default' => 1,
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $supplierId = DB::table('accounts')->insertGetId([
        'name' => 'Barcode Reel Supplier',
        'code' => 'BQ-SUP',
        'account_type' => 'supplier',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $purchaseId = DB::table('purchases')->insertGetId([
        'purchase_no' => 'PO-BARCODE-001',
        'supplier_id' => $supplierId,
        'currency_id' => $currencyId,
        'status' => 'arrived',
        'exchange_rate' => 1,
        'purchase_date' => today()->toDateString(),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $batch = PurchaseItem::create([
        'purchase_id' => $purchaseId,
        'product_id' => $materialId,
        'purchase_currency_id' => $currencyId,
        'qty' => 2,
        'unit' => 'roll',
        'kg_per_roll' => 500,
        'unit_price' => 250,
        'usd_unit_price' => 250,
        'usd_total' => 500,
        'usd_expense_per_item' => 0,
        'landed_cost_per_kg' => 0.50,
        'batch_no' => 'BARCODE-BATCH-001',
    ]);

    $reels = app(ReelInventoryService::class)->initializeBatch($batch);

    return compact('user', 'batch', 'reels');
}

it('generates a deterministic scanner key and dependency-free Code 39 SVG', function () {
    $fx = reelBarcodeFixture();
    $reel = $fx['reels']->first();
    $service = app(ReelBarcodeService::class);

    $scanKey = $service->scanKey($reel);
    $svg = $service->barcodeSvg($scanKey);

    expect($scanKey)
        ->toMatch('/^REEL-\d{8,}$/')
        ->and($svg)
        ->toContain('<svg')
        ->toContain('<rect')
        ->toContain('aria-label="Reel barcode"')
        ->and($service->resolve($scanKey)?->id)
        ->toBe($reel->id)
        ->and($service->resolve(strtolower($reel->reel_code))?->id)
        ->toBe($reel->id);
});

it('prints single and batch labels without changing authoritative inventory', function () {
    $fx = reelBarcodeFixture();
    $this->withoutMiddleware();
    $this->actingAs($fx['user']);

    $batch = $fx['batch']->fresh();
    $reel = $fx['reels']->first();
    $beforeBatchKg = $batch->availableKg();
    $beforeReelKg = (float) $reel->system_remaining_weight_kg;

    $barcode = app(ReelBarcodeService::class);
    $singleData = $barcode->labelData($reel);

    expect($singleData['reel_code'])->toBe($reel->reel_code)
        ->and($singleData['scan_key'])->toBe($barcode->scanKey($reel));

    $singleResponse = $this->get(route('admin.stock-reels.label', $reel));

    $singleResponse
        ->assertOk()
        ->assertViewHas('labels', function ($labels) use ($reel): bool {
            return $labels->count() === 1
                && $labels->first()['reel_code'] === $reel->reel_code;
        })
        ->assertSee('Code 39 labels')
        ->assertSee($barcode->scanKey($reel))
        ->assertSee($reel->reel_code);

    $batchResponse = $this->get(
        route('admin.stock-reels.batch-labels', $batch)
    );

    $batchResponse
        ->assertOk()
        ->assertViewHas('labels', function ($labels) use ($fx): bool {
            return $labels->pluck('reel_code')->values()->all() === [
                $fx['reels'][0]->reel_code,
                $fx['reels'][1]->reel_code,
            ];
        })
        ->assertSee('2 reel(s)')
        ->assertSee($fx['reels'][0]->reel_code)
        ->assertSee($fx['reels'][1]->reel_code);

    expect($batch->fresh()->availableKg())->toBe($beforeBatchKg)
        ->and((float) $reel->fresh()->system_remaining_weight_kg)
        ->toBe($beforeReelKg);
});

it('resolves scanner input to the physical reel workspace without mutating stock', function () {
    $fx = reelBarcodeFixture();
    $this->withoutMiddleware();
    $this->actingAs($fx['user']);

    $batch = $fx['batch']->fresh();
    $reel = $fx['reels']->first();
    $service = app(ReelBarcodeService::class);
    $beforeBatchKg = $batch->availableKg();

    $this->get(route('admin.stock-reels.scan', [
        'code' => $service->scanKey($reel),
    ]))
        ->assertRedirect(
            route('admin.stock-reels.show', $batch).'#reel-'.$reel->id
        )
        ->assertSessionHas('success', 'Reel identified: '.$reel->reel_code);

    $this->get(route('admin.stock-reels.scan', [
        'code' => $reel->reel_code,
    ]))
        ->assertRedirect(
            route('admin.stock-reels.show', $batch).'#reel-'.$reel->id
        );

    $this->get(route('admin.stock-reels.scan', [
        'code' => 'REEL-99999999',
    ]))
        ->assertRedirect(route('admin.stock-reels.index'))
        ->assertSessionHas('error');

    expect($batch->fresh()->availableKg())->toBe($beforeBatchKg)
        ->and((float) $reel->fresh()->system_remaining_weight_kg)
        ->toBe(500.0);
});


it('keeps the production completion scanner compatible with printed scanner keys', function () {
    $source = file_get_contents(
        resource_path('views/admin/production-orders/show.blade.php')
    );

    expect($source)
        ->toContain('data-reel-code')
        ->toContain('data-scan-key')
        ->toContain('row.dataset.reelCode === code')
        ->toContain('row.dataset.scanKey === code');
});
