<?php

it('exposes enterprise control centers without reintroducing excluded production scheduling or QC', function () {
    $routes = file_get_contents(base_path('routes/admin.php'));
    $layout = file_get_contents(resource_path('views/layouts/admin/base.blade.php'));
    $management = file_get_contents(resource_path('views/admin/reports/management.blade.php'));
    $accounting = file_get_contents(resource_path('views/admin/accounting/index.blade.php'));
    $settings = file_get_contents(resource_path('views/admin/settings/index.blade.php'));

    $routeNames = collect(app('router')->getRoutes()->getRoutes())
        ->map(fn ($route) => $route->getName())
        ->filter()
        ->values();

    expect($routeNames)
        ->toContain('admin.management-reporting.index')
        ->toContain('admin.accounting.index')
        ->toContain('admin.procurement.index')
        ->toContain('admin.warehouses.index')
        ->toContain('bom.revise')
        ->toContain('bom.approve-revision');

    expect($layout)
        ->toContain('Enterprise Control')
        ->toContain('Management Reporting')
        ->toContain('Financial Accounting')
        ->toContain('Procure to Pay')
        ->toContain('Warehouses');

    expect($management)
        ->toContain('Customer Profitability')
        ->toContain('Supplier Performance')
        ->toContain('Material Usage')
        ->toContain('Material Rate')
        ->toContain('Business Unit Breakdown');

    expect($accounting)
        ->toContain('Trial Balance')
        ->toContain('Profit & Loss')
        ->toContain('Balance Sheet')
        ->toContain('Cash Flow')
        ->toContain('Fiscal Periods');

    expect($settings)
        ->toContain('Enable separate business units')
        ->toContain('production_approval_required')
        ->toContain('purchase_approval_required');

    // Explicitly preserve scope: the user excluded new production scheduling /
    // machine-planning and quality-control modules from this enterprise batch.
    expect($routes)
        ->not->toContain('production-scheduling')
        ->not->toContain('quality-control');
});
