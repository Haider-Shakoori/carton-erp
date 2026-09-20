<?php

it('locks the client-required production, quotation and invoice UI contracts', function () {
    $saleSource = file_get_contents(resource_path('views/admin/sales/show.blade.php'));
    $invoiceSource = file_get_contents(resource_path('views/admin/sales/print.blade.php'));
    $quotationSource = file_get_contents(resource_path('views/admin/sales/quotation.blade.php'));
    $gatePassSource = file_get_contents(resource_path('views/admin/sales/gate-pass.blade.php'));
    $productionSource = file_get_contents(resource_path('views/admin/production-orders/show.blade.php'));

    expect($saleSource)
        ->toContain('Manual Unit Price')
        ->toContain('id="manualUnitPrice"')
        ->toContain('Quotation Description')
        ->toContain('quotation-description-input')
        ->toContain('manual-line-price')
        ->toContain("route('admin.sales.quotation'")
        ->toContain("route('admin.sales.gate-pass'")
        ->toContain('data-formula-type="carton_3d"')
        ->toContain('.not($sourceRow)')
        ->toContain(".find(fieldClass)")
        ->toContain('.val($input.val())')
        ->toContain('PO Rate (AFN/kg)')
        ->toContain('Auto-populated from latest arrived purchase order.')
        ->not->toContain('<span class="badge bg-primary">{{ $item->bom->code');

    expect($invoiceSource)
        ->not->toContain('$item->remarks')
        ->not->toContain('$item->bom')
        ->not->toContain('BOM');

    expect($quotationSource)
        ->toContain('QUOTATION')
        ->toContain('$item->quotation_description')
        ->not->toContain('$item->bom')
        ->not->toContain('BOM');

    expect($gatePassSource)
        ->toContain('GATE PASS')
        ->toContain('$item->item_name')
        ->toContain('$item->description')
        ->toContain('$item->quantity');

    expect($productionSource)
        ->toContain('Planned Production Quantity')
        ->toContain('name="quantity_planned"')
        ->toContain('Current Raw Material Supports')
        ->toContain('Calculate & Start Production')
        ->toContain('Manufactured Qty')
        ->toContain('Good / Actual Finished Qty')
        ->toContain('Rejected / Scrap Qty')
        ->toContain('Actual Raw Material Consumption')
        ->toContain('Planned for This Run');
});
