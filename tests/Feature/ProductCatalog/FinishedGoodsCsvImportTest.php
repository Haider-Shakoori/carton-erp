<?php

use App\Http\Middleware\CheckPermissionWithFeedback;
use App\Models\Category;
use App\Models\FinishedGoodSpecification;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function finishedGoodsCsvFile(array $rows): UploadedFile
{
    $stream = fopen('php://temp', 'w+');
    fputcsv($stream, ['product_id','sku','name','category','weight_g','length_mm','width_mm','height_mm','ply','flute_type','is_active']);
    foreach ($rows as $row) {
        fputcsv($stream, $row);
    }
    rewind($stream);
    $content = stream_get_contents($stream);
    fclose($stream);
    return UploadedFile::fake()->createWithContent('finished-goods.csv', $content);
}

it('creates a measured carton with specification and updates it by SKU idempotently', function () {
    $user = User::factory()->create();
    $category = Category::create(['name' => 'CSV Cartons', 'is_active' => true]);

    $rows = [['', 'CARTON-001', 'One measured carton', $category->name, '245.50', '450', '300', '200', '5', 'B', '1']];
    $request = fn () => $this->withoutMiddleware(CheckPermissionWithFeedback::class)->actingAs($user)
        ->post(route('admin.products.import.store'), ['csv' => finishedGoodsCsvFile($rows)]);

    $request()->assertRedirect(route('admin.products.import.index'));
    $product = Product::where('sku', 'CARTON-001')->firstOrFail();
    expect($product->type)->toBe(Product::TYPE_FINISHED_GOOD)
        ->and((float) $product->finished_weight_g)->toBe(245.5)
        ->and($product->unit)->toBe('pcs');

    $spec = FinishedGoodSpecification::where('source_key', 'finished-good-csv:'.$product->id)->firstOrFail();
    expect((int) $spec->ply)->toBe(5)
        ->and((float) $spec->length)->toBe(450.0)
        ->and((float) $spec->weight)->toBe(245.5);

    $request()->assertRedirect();
    expect(Product::where('sku', 'CARTON-001')->count())->toBe(1)
        ->and(FinishedGoodSpecification::where('source_key', 'finished-good-csv:'.$product->id)->count())->toBe(1);
});

it('updates an existing legacy carton by product id without replacing a client source specification', function () {
    $user = User::factory()->create();
    $category = Category::create(['name' => 'CSV Legacy Category', 'is_active' => true]);
    $product = Product::create(['name'=>'Legacy Carton', 'category_id'=>$category->id,
        'unit'=>'pcs', 'type'=>Product::TYPE_FINISHED_GOOD, 'is_active'=>true]);
    $legacy = FinishedGoodSpecification::create([
        'product_id'=>$product->id, 'source_key'=>'client-carton-order:legacy-001', 'weight'=>90,
    ]);

    $this->withoutMiddleware(CheckPermissionWithFeedback::class)->actingAs($user)
        ->post(route('admin.products.import.store'), ['csv'=>finishedGoodsCsvFile([
            [$product->id,'LEGACY-001','Legacy Carton',$category->name,'110','','','','','',''],
        ])])->assertRedirect();

    expect((float) $product->fresh()->finished_weight_g)->toBe(110.0)
        ->and($product->fresh()->sku)->toBe('LEGACY-001')
        ->and((float) $legacy->fresh()->weight)->toBe(90.0)
        ->and(FinishedGoodSpecification::where('product_id',$product->id)->count())->toBe(2);
});

it('rolls back the full import if a later row is invalid', function () {
    $user = User::factory()->create();
    $category = Category::create(['name' => 'CSV Valid Category', 'is_active' => true]);
    $response = $this->withoutMiddleware(CheckPermissionWithFeedback::class)->actingAs($user)
        ->post(route('admin.products.import.store'), ['csv'=>finishedGoodsCsvFile([
            ['', 'SAFE-001','Good carton',$category->name,'250','','','','','','1'],
            ['', 'BAD-001','Bad carton','Missing category','250','','','','','','1'],
        ])]);

    $response->assertSessionHasErrors('csv');
    expect(Product::where('sku','SAFE-001')->exists())->toBeFalse()
        ->and(FinishedGoodSpecification::where('source_key','like','finished-good-csv:%')->count())->toBe(0);
});

it('never converts a raw material into a finished good via CSV', function () {
    $user = User::factory()->create();
    $category = Category::create(['name' => 'CSV Materials', 'is_active' => true]);
    $raw = Product::create(['name'=>'Liner', 'category_id'=>$category->id,
        'unit'=>'kg', 'type'=>Product::TYPE_RAW_MATERIAL, 'is_active'=>true]);

    $this->withoutMiddleware(CheckPermissionWithFeedback::class)->actingAs($user)
        ->post(route('admin.products.import.store'), ['csv'=>finishedGoodsCsvFile([
            [$raw->id,'LINER-001','Liner',$category->name,'200','','','','','',''],
        ])])->assertSessionHasErrors('csv');
    expect($raw->fresh()->type)->toBe(Product::TYPE_RAW_MATERIAL);
});

it('provides CSV template and displays weight on the catalog', function () {
    $user = User::factory()->create();
    $category = Category::create(['name' => 'CSV UI Category', 'is_active' => true]);
    Product::create(['name'=>'Measured UI carton','category_id'=>$category->id,
        'unit'=>'pcs','type'=>Product::TYPE_FINISHED_GOOD,'is_active'=>true,'finished_weight_g'=>325]);

    $this->withoutMiddleware(CheckPermissionWithFeedback::class)->actingAs($user)
        ->get(route('admin.products.import.template'))->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=finished-goods-template.csv');

    $this->withoutMiddleware(CheckPermissionWithFeedback::class)->actingAs($user)
        ->get(route('admin.products.index'))->assertOk()
        ->assertSee('Import finished goods CSV')
        ->assertSee('325.00 g');
});

it('flags missing BOM without changing inventory', function () {
    $user = User::factory()->create();
    $category = Category::create(['name'=>'CSV Audit Category', 'is_active'=>true]);
    Product::create(['name'=>'Audit carton','category_id'=>$category->id,
        'unit'=>'pcs','type'=>Product::TYPE_FINISHED_GOOD,'is_active'=>true,'finished_weight_g'=>210]);

    $this->withoutMiddleware(CheckPermissionWithFeedback::class)->actingAs($user)
        ->get(route('admin.products.weight-audit'))->assertOk()
        ->assertSee('Audit carton')->assertSee('Missing BOM');
});
