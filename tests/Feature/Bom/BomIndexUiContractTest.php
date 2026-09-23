<?php

it('keeps the BOM library on the simplified server-driven index UI', function () {
    $view = file_get_contents(resource_path('views/admin/bom/index.blade.php'));
    $controller = file_get_contents(app_path('Http/Controllers/Admin/BOMController.php'));

    expect($view)
        ->toContain('BOM Library')
        ->toContain('Physical Cost')
        ->toContain('Standard Rate')
        ->toContain('work/profit')
        ->toContain('Search code, BOM, or product')
        ->toContain('name="sort"')
        ->toContain('name="per_page"')
        ->toContain('Locked')
        ->toContain('Effective')
        ->toContain("route('bom.create')")
        ->toContain("route('bom.calculator')")
        ->not->toContain('jquery.dataTables')
        ->not->toContain('.DataTable(');

    expect($controller)
        ->toContain('public function index(Request $request)')
        ->toContain("'total' => (clone \$statsQuery)->count()")
        ->toContain("'active' => (clone \$statsQuery)->where('status', 'active')->count()")
        ->toContain("->orWhereHas('product'")
        ->toContain("request->input('sort', 'latest')")
        ->toContain('->withQueryString()');
});
