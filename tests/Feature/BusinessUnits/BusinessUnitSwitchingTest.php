<?php

use App\Http\Middleware\CheckPermissionWithFeedback;
use App\Models\BusinessUnit;
use App\Models\BOM;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Purchase;
use App\Models\User;
use App\Support\Business\BusinessUnitContext;
use App\Services\BusinessUnitProvisioningService;
use Database\Seeders\BusinessUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

it('self-heals missing business units for settings and the topbar context', function () {
    $setting = Setting::firstOrCreate([]);
    $setting->update([
        'separate_business_units_enabled' => true,
        'default_business_unit_id' => null,
    ]);

    BusinessUnit::query()->delete();

    expect(BusinessUnit::query()->count())->toBe(0);

    $units = app(BusinessUnitProvisioningService::class)
        ->ensureRequiredUnits();

    expect($units->pluck('code')->all())->toBe(['3d_carton', 'syrup_pack'])
        ->and((int) $setting->fresh()->default_business_unit_id)
        ->toBe((int) $units->firstWhere('code', '3d_carton')->id);

    $user = User::factory()->create();
    $this->actingAs($user);

    $context = app(BusinessUnitContext::class);
    $context->reset();

    BusinessUnit::query()->delete();
    $setting->fresh()->update(['default_business_unit_id' => null]);

    expect($context->available()->pluck('code')->all())
        ->toBe(['3d_carton', 'syrup_pack'])
        ->and($context->current()?->code)->toBe('3d_carton');
});

it('repairs a database where the business-unit schema is missing despite migration history', function () {
    Schema::disableForeignKeyConstraints();
    Schema::dropIfExists('business_unit_user');
    Schema::dropIfExists('business_units');
    Schema::enableForeignKeyConstraints();

    expect(Schema::hasTable('business_units'))->toBeFalse();

    $migration = require database_path('migrations/2026_09_23_093000_repair_business_unit_schema.php');
    $migration->up();

    expect(Schema::hasTable('business_units'))->toBeTrue()
        ->and(Schema::hasTable('business_unit_user'))->toBeTrue()
        ->and(BusinessUnit::query()->active()->pluck('code')->all())
        ->toBe(['3d_carton', 'syrup_pack']);

    $setting = Setting::firstOrCreate([]);

    expect((int) $setting->fresh()->default_business_unit_id)
        ->toBe((int) BusinessUnit::query()->where('code', '3d_carton')->value('id'));
});

it('renders and persists business-unit settings through the real settings HTTP flow', function () {
    $user = User::factory()->create();
    $setting = Setting::firstOrCreate([]);
    $setting->update([
        'separate_business_units_enabled' => false,
        'default_business_unit_id' => null,
    ]);

    BusinessUnit::query()->delete();

    $response = $this
        ->withoutMiddleware(CheckPermissionWithFeedback::class)
        ->actingAs($user)
        ->get(route('admin.settings.index'));

    $carton = BusinessUnit::query()->where('code', '3d_carton')->firstOrFail();
    $syrup = BusinessUnit::query()->where('code', 'syrup_pack')->firstOrFail();

    $response
        ->assertOk()
        ->assertSee('id="defaultBusinessUnitId"', false)
        ->assertSee('<option value="'.$carton->id.'"', false)
        ->assertSee('<option value="'.$syrup->id.'"', false)
        ->assertDontSee('No business units available — run database migrations.');

    expect(BusinessUnit::query()->count())->toBe(2)
        ->and((int) $setting->fresh()->default_business_unit_id)->toBe($carton->id);

    $settingsView = file_get_contents(resource_path('views/admin/settings/index.blade.php'));
    $formStart = strpos($settingsView, '<form action="{{ route(\'admin.settings.update\') }}"');
    $defaultSelect = strpos($settingsView, 'name="default_business_unit_id"');
    $formEnd = strpos($settingsView, '</form>', $formStart);

    expect($formStart)->not->toBeFalse()
        ->and($defaultSelect)->toBeGreaterThan($formStart)
        ->and($formEnd)->toBeGreaterThan($defaultSelect);

    $update = $this
        ->withoutMiddleware(CheckPermissionWithFeedback::class)
        ->actingAs($user)
        ->post(route('admin.settings.update'), [
            'default_language' => 'en',
            'currency' => 'USD',
            'separate_business_units_enabled' => '1',
            'default_business_unit_id' => $syrup->id,
            'production_approval_required' => '0',
            'purchase_approval_required' => '0',
        ]);

    $update
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect((bool) $setting->fresh()->separate_business_units_enabled)->toBeTrue()
        ->and((int) $setting->fresh()->default_business_unit_id)->toBe($syrup->id);
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

it('classifies legacy BOMs and strictly isolates BOM/product choices by active business', function () {
    $user = User::factory()->create();
    $carton = BusinessUnit::query()->where('code', '3d_carton')->firstOrFail();
    $syrup = BusinessUnit::query()->where('code', 'syrup_pack')->firstOrFail();

    $syrupCategory = Category::firstOrCreate(
        ['name' => 'Syrup Boxes'],
        ['description' => 'Syrup packaging', 'is_active' => true]
    );
    $cartonCategory = Category::firstOrCreate(
        ['name' => 'Custom Cartons'],
        ['description' => '3D cartons', 'is_active' => true]
    );

    $syrupProduct = Product::create([
        'name' => '250ml Syrup Box',
        'unit' => 'piece',
        'category_id' => $syrupCategory->id,
        'type' => Product::TYPE_FINISHED_GOOD,
        'is_active' => true,
    ]);
    $cartonProduct = Product::create([
        'name' => 'Custom Shipping Carton',
        'unit' => 'piece',
        'category_id' => $cartonCategory->id,
        'type' => Product::TYPE_FINISHED_GOOD,
        'is_active' => true,
    ]);

    $now = now();
    DB::table('boms')->insert([
        [
            'name' => 'Legacy Syrup BOM',
            'code' => 'BOM-LEGACY-SYRUP',
            'product_id' => $syrupProduct->id,
            'status' => 'active',
            'is_active' => true,
            'created_by' => $user->id,
            'business_unit_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'name' => 'Legacy Carton BOM',
            'code' => 'BOM-LEGACY-CARTON',
            'product_id' => $cartonProduct->id,
            'status' => 'active',
            'is_active' => true,
            'created_by' => $user->id,
            'business_unit_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ],
    ]);

    $migration = require database_path('migrations/2026_09_23_103000_classify_legacy_business_unit_records.php');
    $migration->up();

    expect((int) DB::table('boms')->where('code', 'BOM-LEGACY-SYRUP')->value('business_unit_id'))
        ->toBe($syrup->id)
        ->and((int) DB::table('boms')->where('code', 'BOM-LEGACY-CARTON')->value('business_unit_id'))
        ->toBe($carton->id);

    Setting::firstOrCreate([])->update([
        'separate_business_units_enabled' => true,
        'default_business_unit_id' => $carton->id,
    ]);

    $this->actingAs($user);
    $context = app(BusinessUnitContext::class);

    $context->switchTo($syrup);

    expect(BOM::query()->pluck('code')->all())
        ->toContain('BOM-LEGACY-SYRUP')
        ->not->toContain('BOM-LEGACY-CARTON')
        ->and(Product::finishedGoods()->forActiveBusiness()->pluck('id')->all())
        ->toContain($syrupProduct->id)
        ->not->toContain($cartonProduct->id);

    $context->switchTo($carton);

    expect(BOM::query()->pluck('code')->all())
        ->toContain('BOM-LEGACY-CARTON')
        ->not->toContain('BOM-LEGACY-SYRUP')
        ->and(Product::finishedGoods()->forActiveBusiness()->pluck('id')->all())
        ->toContain($cartonProduct->id)
        ->not->toContain($syrupProduct->id);
});

it('does not expose unassigned operational rows inside an active business workspace', function () {
    $user = User::factory()->create();
    $carton = BusinessUnit::query()->where('code', '3d_carton')->firstOrFail();

    Setting::firstOrCreate([])->update([
        'separate_business_units_enabled' => true,
        'default_business_unit_id' => $carton->id,
    ]);

    DB::table('purchases')->insert([
        'purchase_no' => 'BU-NULL-LEGACY',
        'status' => 'draft',
        'business_unit_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user);
    app(BusinessUnitContext::class)->switchTo($carton);

    expect(Purchase::query()->where('purchase_no', 'BU-NULL-LEGACY')->exists())->toBeFalse()
        ->and(Purchase::query()->withoutGlobalScope('business_unit')->where('purchase_no', 'BU-NULL-LEGACY')->exists())->toBeTrue();
});

it('exposes the setting toggle and top navigation business switcher contract', function () {
    $settings = file_get_contents(resource_path('views/admin/settings/index.blade.php'));
    $layout = file_get_contents(resource_path('views/layouts/admin/base.blade.php'));

    expect($settings)
        ->toContain('name="separate_business_units_enabled"')
        ->toContain('Enable separate business units')
        ->toContain('$settingsBusinessUnits')
        ->toContain('name="default_business_unit_id"');

    expect($layout)
        ->toContain('id="businessUnitSwitcher"')
        ->toContain('businessUnitSwitchForm')
        ->toContain('data-switch-url')
        ->toContain("route('admin.business-units.switch', \$businessUnit)")
        ->toContain('Switch business workspace');
});
