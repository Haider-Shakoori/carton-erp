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
        ->toContain('BOM Planned')
        ->toContain('Actual Consumed')
        ->toContain('Waste within Actual')
        ->toContain('Difference = Actual Consumed − BOM Planned.')
        ->toContain('data-variance-target="materialVariance{{ $index }}"')
        ->toContain('Reel details')
        ->toContain('Use physical reel declaration')
        ->toContain('No barcode or scanner workflow is used.')
        ->toContain('name="materials[{{ $index }}][use_reel_selection]"')
        ->toContain('[consumed_kg]')
        ->toContain('[final_remaining_kg]')
        ->toContain('Scale remainders are observational')
        ->toContain('Save Actuals & Complete Production')
        ->toContain('Planned vs Actual Production Variance')
        ->not->toContain('Scan / enter reel code')
        ->not->toContain('reel-scan-input')
        ->not->toContain('bi-upc-scan')
        ->not->toContain('reconcile raw-material consumption to this actual quantity');
});
