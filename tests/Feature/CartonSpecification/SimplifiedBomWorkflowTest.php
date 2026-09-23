<?php

it('keeps simplified BOM creation focused on factory inputs while preserving the advanced builder', function () {
    $simple = file_get_contents(resource_path('views/admin/bom/create-simple.blade.php'));
    $controller = file_get_contents(app_path('Http/Controllers/Admin/BOMController.php'));
    $routes = file_get_contents(base_path('routes/admin.php'));

    expect($simple)
        ->toContain('Create Carton BOM')
        ->toContain('Finished Product')
        ->toContain('name="length"')
        ->toContain('name="width"')
        ->toContain('name="height"')
        ->toContain('name="board_profile_id"')
        ->toContain('name="printing_option"')
        ->toContain('Advanced overrides')
        ->toContain('Standard Work / Profit')
        ->toContain('Standard Customer Rate')
        ->toContain('Adhesive / Carton')
        ->toContain('Technical details')
        ->toContain('Save & Add Another Size')
        ->toContain("route('bom.create-advanced')")
        ->not->toContain('name="items[0][formula_constant]"');

    expect($controller)
        ->toContain('public function advancedCreate()')
        ->toContain('public function previewSimple(')
        ->toContain('public function storeSimple(')
        ->toContain('persistTechnicalBom(')
        ->toContain("'simple_bom_defaults'")
        ->toContain("config('carton.standard_work_percentage', 40)");

    expect($routes)
        ->toContain("name('bom.create-advanced')")
        ->toContain("name('bom.preview-simple')")
        ->toContain("name('bom.store-simple')");
});

it('keeps the client commercial work percentage centralized and separate from physical adhesive cost', function () {
    $config = require config_path('carton.php');
    $service = file_get_contents(app_path('Services/CartonSpecificationService.php'));

    expect((float) $config['standard_work_percentage'])->toBe(40.0)
        ->and($service)->toContain("config('carton.standard_work_percentage', 40)")
        ->and($service)->toContain("'apply_work_percentage' => false")
        ->and($service)->toContain("'component_type' => self::COMPONENT_ADHESIVE");
});
