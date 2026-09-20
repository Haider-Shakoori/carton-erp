<?php

it('requires real output at production completion and explains invoice impact in the UI', function () {
    $source = file_get_contents(resource_path('views/admin/production-orders/show.blade.php'));

    expect($source)
        ->toContain('Actual Quantity Produced')
        ->toContain('name="quantity_produced"')
        ->toContain('Save Actual Output & Complete')
        ->toContain('real finished quantity')
        ->toContain('final invoice quantity')
        ->toContain('Production Variance')
        ->not->toContain("quantity_produced = quantity_ordered");
});
