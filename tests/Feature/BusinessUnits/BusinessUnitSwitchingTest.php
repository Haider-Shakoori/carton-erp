<?php

use App\Models\BusinessUnit;
use App\Models\Setting;
use App\Models\Purchase;
use App\Models\User;
use App\Support\Business\BusinessUnitContext;
use Database\Seeders\BusinessUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('installs the two client business units with unified mode disabled by default', function () {
    $carton = BusinessUnit::query()->where('code', '3d_carton')->first();
    $syrup = BusinessUnit::query()->where('code', 'syrup_pack')->first();

    expect($carton)->not->toBeNull()
        ->and($carton->name)->toBe('3D Carton')
        ->and($syrup)->not->toBeNull()
        ->and($syrup->name)->toBe('Syrup Pack');

    $setting = Setting::firstOrCreate([]);

    expect((bool) $setting->separate_business_units_enabled)->toBeFalse();
});

it('restores the required business units and default safely on existing databases', function () {
    $setting = Setting::firstOrCreate([]);
    $setting->update(['default_business_unit_id' => null]);

    BusinessUnit::query()->delete();

    expect(BusinessUnit::query()->count())->toBe(0);

    $this->seed(BusinessUnitSeeder::class);
    $this->seed(BusinessUnitSeeder::class);

    $units = BusinessUnit::query()
        ->active()
        ->get();

    expect($units)->toHaveCount(2)
        ->and($units->pluck('code')->all())->toBe(['3d_carton', 'syrup_pack'])
        ->and($units->pluck('name')->all())->toBe(['3D Carton', 'Syrup Pack'])
        ->and((int) $setting->fresh()->default_business_unit_id)
        ->toBe((int) $units->firstWhere('code', '3d_carton')->id);
});

it('switches the active business only when separate business mode is enabled', function () {
    $user = User::factory()->create();
    $carton = BusinessUnit::query()->where('code', '3d_carton')->firstOrFail();
    $syrup = BusinessUnit::query()->where('code', 'syrup_pack')->firstOrFail();

    $setting = Setting::firstOrCreate([]);
    $setting->update([
        'separate_business_units_enabled' => true,
        'default_business_unit_id' => $carton->id,
    ]);

    $this->actingAs($user)
        ->post(route('admin.business-units.switch', $syrup))
        ->assertRedirect();

    expect(session(BusinessUnitContext::SESSION_KEY))->toBe($syrup->id)
        ->and(app(BusinessUnitContext::class)->current()?->code)->toBe('syrup_pack');

    $setting->update(['separate_business_units_enabled' => false]);
    app(BusinessUnitContext::class)->reset();

    expect(app(BusinessUnitContext::class)->current())->toBeNull()
        ->and(session()->has(BusinessUnitContext::SESSION_KEY))->toBeFalse();
});

it('automatically tags and scopes new operational records to the active business', function () {
    $carton = BusinessUnit::query()->where('code', '3d_carton')->firstOrFail();
    $syrup = BusinessUnit::query()->where('code', 'syrup_pack')->firstOrFail();

    Setting::firstOrCreate([])->update([
        'separate_business_units_enabled' => true,
        'default_business_unit_id' => $carton->id,
    ]);

    $context = app(BusinessUnitContext::class);
    $context->switchTo($carton);

    $cartonPurchase = Purchase::create([
        'purchase_no' => 'BU-CARTON-001',
        'status' => 'draft',
    ]);

    expect((int) $cartonPurchase->business_unit_id)->toBe($carton->id);

    $context->switchTo($syrup);

    $syrupPurchase = Purchase::create([
        'purchase_no' => 'BU-SYRUP-001',
        'status' => 'draft',
    ]);

    expect((int) $syrupPurchase->business_unit_id)->toBe($syrup->id)
        ->and(Purchase::query()->pluck('purchase_no')->all())->toBe(['BU-SYRUP-001'])
        ->and(Purchase::query()->withoutGlobalScope('business_unit')->count())->toBe(2);

    $context->switchTo($carton);

    expect(Purchase::query()->pluck('purchase_no')->all())->toBe(['BU-CARTON-001']);
});

it('exposes the setting toggle and top navigation business switcher contract', function () {
    $settings = file_get_contents(resource_path('views/admin/settings/index.blade.php'));
    $layout = file_get_contents(resource_path('views/layouts/admin/base.blade.php'));

    expect($settings)
        ->toContain('name="separate_business_units_enabled"')
        ->toContain('Enable separate business units')
        ->toContain('3D Carton')
        ->toContain('Syrup Pack')
        ->toContain('name="default_business_unit_id"');

    expect($layout)
        ->toContain('id="businessUnitSwitcher"')
        ->toContain("route('admin.business-units.switch', \$businessUnit)")
        ->toContain('Switch business workspace')
        ->toContain('Business unit settings');
});
