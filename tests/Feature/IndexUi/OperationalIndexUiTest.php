<?php

use App\Http\Middleware\CheckPermissionWithFeedback;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('applies the shared operational index UI contract to the redesigned list pages', function () {
    $paths = [
        'resources/views/admin/stock/index.blade.php',
        'resources/views/admin/purchase-orders/index.blade.php',
        'resources/views/admin/sales/index.blade.php',
        'resources/views/admin/production-orders/index.blade.php',
        'resources/views/admin/work-orders/index.blade.php',
        'resources/views/admin/customers/index.blade.php',
        'resources/views/admin/suppliers/index.blade.php',
        'resources/views/admin/expenses/index.blade.php',
        'resources/views/admin/sale-returns/index.blade.php',
    ];

    foreach ($paths as $path) {
        $view = file_get_contents(base_path($path));

        expect($view)
            ->toContain("asset('css/admin-index.css')")
            ->toContain('erp-index-ui');
    }

    $css = file_get_contents(public_path('css/admin-index.css'));

    expect($css)
        ->toContain('.erp-index-ui .page-header')
        ->toContain('.erp-index-ui .stats-grid')
        ->toContain('.erp-index-ui .filter-bar')
        ->toContain('.erp-index-ui .table-card')
        ->toContain('.erp-index-ui .action-btn');
});

it('renders the stock inventory index with its KPI data contract', function () {
    $user = User::factory()->create();

    $response = $this
        ->withoutMiddleware(CheckPermissionWithFeedback::class)
        ->actingAs($user)
        ->get(route('admin.stock.index'));

    $response
        ->assertOk()
        ->assertSee('Inventory')
        ->assertSee('Product Inventory Profiles')
        ->assertSee('css/admin-index.css');
});

it('returns a browser redirect after deleting a purchase order', function () {
    $user = User::factory()->create();

    $purchase = Purchase::create([
        'purchase_no' => 'PO-UI-DELETE-001',
        'status' => 'draft',
        'purchase_date' => now(),
    ]);

    $response = $this
        ->withoutMiddleware(CheckPermissionWithFeedback::class)
        ->actingAs($user)
        ->delete(route('admin.purchase-orders.destroy', $purchase));

    $response
        ->assertRedirect(route('admin.purchase-orders.index'))
        ->assertSessionHas('success');

    expect(Purchase::query()->withoutGlobalScope('business_unit')->find($purchase->id))->toBeNull();
});

it('keeps list filters attached to paginated operational queries', function () {
    $controllers = [
        'app/Http/Controllers/Admin/StockController.php',
        'app/Http/Controllers/Admin/PurchaseOrderController.php',
        'app/Http/Controllers/Admin/SaleController.php',
        'app/Http/Controllers/Admin/SaleReturnController.php',
    ];

    foreach ($controllers as $path) {
        $source = file_get_contents(base_path($path));
        expect($source)->toContain('withQueryString()');
    }
});

it('keeps the stock empty state aligned with the eight-column inventory table', function () {
    $view = file_get_contents(resource_path('views/admin/stock/index.blade.php'));

    expect($view)
        ->toContain('<td colspan="8">')
        ->toContain('inventory profiles');
});
