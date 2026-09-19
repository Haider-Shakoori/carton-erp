<?php

it('keeps the BOM creation UI explicit about physical and commercial costing', function () {
    $source = file_get_contents(resource_path('views/admin/bom/create.blade.php'));

    expect($source)
        ->toContain('Landed Inventory Cost')
        ->toContain('USD / ${basisUnit}')
        ->toContain('Consumption / Finished Unit')
        ->toContain('Wastage Cost')
        ->toContain('Physical Material Cost')
        ->toContain('Standard Work / Profit')
        ->toContain('Additional Markup')
        ->toContain('Commercial Base Rate')
        ->toContain('Final Selling Price / Unit')
        ->toContain('wastage is added only to physical production cost')
        ->not->toContain('netWithWastageAfn')
        ->not->toContain('workCost = paperRateByLayers * 0.40')
        ->not->toContain('$itemsAdded->map');
});
