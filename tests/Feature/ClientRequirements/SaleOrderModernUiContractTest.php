<?php

it('keeps the approved modern sale order UI contract wired to existing sale behavior', function () {
    $show = file_get_contents(resource_path('views/admin/sales/show.blade.php'));
    $modern = file_get_contents(resource_path('views/admin/sales/partials/modern-show.blade.php'));
    $controller = file_get_contents(app_path('Http/Controllers/Admin/SaleController.php'));

    expect($show)
        ->toContain("@include('admin.sales.partials.modern-show')")
        ->toContain('id="legacySaleOrderUi"');

    expect($modern)
        ->toContain('Sale Items')
        ->toContain('Manual Override Price')
        ->toContain('Effective Selling Price')
        ->toContain('data-sox-tab="bom"')
        ->toContain('BOM Breakdown')
        ->toContain('sox-item-select')
        ->toContain('Print Quotation')
        ->toContain('Print Invoice')
        ->toContain('soxAddCartonDrawer')
        ->toContain('Existing BOM')
        ->toContain('Quick Quotation')
        ->toContain('The override changes selling price only');

    expect($controller)
        ->toContain("'items.bom.items.material'")
        ->toContain("'unit_price' => 'nullable|numeric|min:0.0001'")
        ->toContain('Manual override cleared. BOM/system price restored.');
});
