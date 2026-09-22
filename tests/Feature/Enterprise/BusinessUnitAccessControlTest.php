<?php

use App\Models\BusinessUnit;
use App\Models\Setting;
use App\Models\User;
use App\Support\Business\BusinessUnitContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('restricts the business switcher to explicitly assigned businesses', function () {
    $user = User::factory()->create();
    $carton = BusinessUnit::query()->where('code', '3d_carton')->firstOrFail();
    $syrup = BusinessUnit::query()->where('code', 'syrup_pack')->firstOrFail();

    Setting::firstOrCreate([])->update([
        'separate_business_units_enabled' => true,
        'default_business_unit_id' => $syrup->id,
    ]);

    $user->businessUnits()->sync([
        $carton->id => ['is_default' => true],
    ]);

    $this->actingAs($user);

    $context = app(BusinessUnitContext::class);
    $context->reset();

    expect($context->available()->pluck('code')->all())->toBe(['3d_carton'])
        ->and($context->current()?->id)->toBe($carton->id);

    expect(fn () => $context->switchTo($syrup))
        ->toThrow(RuntimeException::class, 'You do not have access');

    expect($context->switchTo($carton)->id)->toBe($carton->id);
});

it('allows a manager assigned to both businesses to switch between them', function () {
    $user = User::factory()->create();
    $carton = BusinessUnit::query()->where('code', '3d_carton')->firstOrFail();
    $syrup = BusinessUnit::query()->where('code', 'syrup_pack')->firstOrFail();

    Setting::firstOrCreate([])->update([
        'separate_business_units_enabled' => true,
        'default_business_unit_id' => $carton->id,
    ]);

    $user->businessUnits()->sync([
        $carton->id => ['is_default' => false],
        $syrup->id => ['is_default' => true],
    ]);

    $this->actingAs($user);

    $context = app(BusinessUnitContext::class);
    $context->reset();

    expect($context->available()->pluck('code')->sort()->values()->all())
        ->toBe(['3d_carton', 'syrup_pack'])
        ->and($context->current()?->id)->toBe($syrup->id);

    expect($context->switchTo($carton)->code)->toBe('3d_carton');
});

it('keeps unassigned legacy users backward compatible with all active businesses', function () {
    $user = User::factory()->create();

    Setting::firstOrCreate([])->update([
        'separate_business_units_enabled' => true,
        'default_business_unit_id' => BusinessUnit::query()->where('code', '3d_carton')->value('id'),
    ]);

    $this->actingAs($user);

    $context = app(BusinessUnitContext::class);
    $context->reset();

    expect($user->businessUnits()->count())->toBe(0)
        ->and($context->available()->pluck('code')->sort()->values()->all())
        ->toBe(['3d_carton', 'syrup_pack']);
});
