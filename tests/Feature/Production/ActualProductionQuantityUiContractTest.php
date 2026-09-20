<?php

it('requires real output and actual material consumption at production completion', function () {
    $source = file_get_contents(resource_path('views/admin/production-orders/show.blade.php'));

    expect($source)
        ->toContain('Manufactured Qty')
        ->toContain('name="quantity_manufactured"')
        ->toContain('Good / Actual Finished Qty')
        ->toContain('name="quantity_produced"')
        ->toContain('Rejected / Scrap Qty')
        ->toContain('name="quantity_rejected"')
        ->toContain('Actual Raw Material Consumption')
        ->toContain('name="materials[{{ $index }}][actual_quantity]"')
        ->toContain('name="materials[{{ $index }}][wastage_quantity]"')
        ->toContain('BOM remains the planned baseline')
        ->toContain('Save Actuals & Complete Production')
        ->toContain('Planned vs Actual Production Variance')
        ->not->toContain('reconcile raw-material consumption to this actual quantity');
});
