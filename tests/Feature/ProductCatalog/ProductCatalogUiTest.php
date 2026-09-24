<?php

use App\Http\Middleware\CheckPermissionWithFeedback;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the complete product catalog with the refreshed management UI', function () {
    $user = User::factory()->create();
    $category = Category::create([
        // The seeded ERP already contains the real "Paper Materials" category.
        // Use a test-owned unique name so this remains isolated on seeded CI.
        'name' => 'Catalog Paper Materials ' . uniqid(),
        'description' => 'Raw paper inputs',
        'is_active' => true,
    ]);

    foreach (range(1, 18) as $index) {
        Product::create([
            'name' => sprintf('Catalog Raw Material %02d', $index),
            'category_id' => $category->id,
            'type' => Product::TYPE_RAW_MATERIAL,
            'unit' => 'kg',
            'min_stock_alert' => 10,
            'is_active' => true,
        ]);
    }

    Product::create([
        'name' => 'Catalog Finished Carton',
        'category_id' => $category->id,
        'type' => Product::TYPE_FINISHED_GOOD,
        'unit' => 'piece',
        'is_active' => true,
    ]);

    $response = $this
        ->withoutMiddleware(CheckPermissionWithFeedback::class)
        ->actingAs($user)
        ->get(route('admin.products.index'));

    $response
        ->assertOk()
        ->assertSee('Products &amp; Materials', false)
        ->assertSee('Shared across business units')
        ->assertSee('New catalog item')
        ->assertSee('id="catalogSearch"', false)
        ->assertSee('id="catalogCategoryFilter"', false)
        ->assertSee('id="catalogStatusFilter"', false)
        ->assertSee('Catalog Raw Material 01')
        ->assertSee('Catalog Raw Material 18')
        ->assertSee('Catalog Finished Carton');
});

it('stores raw materials and finished goods in their correct catalog types', function () {
    $user = User::factory()->create();
    $category = Category::create([
        'name' => 'Catalog Type Test',
        'is_active' => true,
    ]);

    $rawResponse = $this
        ->withoutMiddleware(CheckPermissionWithFeedback::class)
        ->actingAs($user)
        ->post(route('admin.products.store'), [
            'type' => 'raw_material',
            'name' => 'Kraft Test Roll',
            'category_id' => $category->id,
            'unit' => 'roll',
            'default_kg_per_roll' => 500,
            'min_stock_alert' => 5,
            'is_active' => '1',
        ]);

    $rawResponse
        ->assertOk()
        ->assertJson(['success' => true]);

    $finishedResponse = $this
        ->withoutMiddleware(CheckPermissionWithFeedback::class)
        ->actingAs($user)
        ->post(route('admin.products.store'), [
            'type' => 'finished_good',
            'name' => 'Finished Carton Test',
            'category_id' => $category->id,
            'unit' => 'piece',
            'is_active' => '1',
        ]);

    $finishedResponse
        ->assertOk()
        ->assertJson(['success' => true]);

    expect(Product::query()->where('name', 'Kraft Test Roll')->value('type'))
        ->toBe(Product::TYPE_RAW_MATERIAL)
        ->and(Product::query()->where('name', 'Finished Carton Test')->value('type'))
        ->toBe(Product::TYPE_FINISHED_GOOD);
});

it('keeps inactive categories available when editing existing catalog records', function () {
    $user = User::factory()->create();
    $category = Category::create([
        'name' => 'Legacy Inactive Category',
        'is_active' => false,
    ]);

    Product::create([
        'name' => 'Legacy Material',
        'category_id' => $category->id,
        'type' => Product::TYPE_RAW_MATERIAL,
        'unit' => 'kg',
        'is_active' => true,
    ]);

    $this
        ->withoutMiddleware(CheckPermissionWithFeedback::class)
        ->actingAs($user)
        ->get(route('admin.products.index'))
        ->assertOk()
        ->assertSee('Legacy Inactive Category (Inactive)');
});
