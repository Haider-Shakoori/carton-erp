<?php

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('passes enterprise readiness on a clean installed database', function () {
    Storage::fake('local');

    Setting::firstOrCreate([], [
        'company_name' => 'Enterprise ERP Test',
        'default_language' => 'en',
        'currency' => 'AFN',
    ]);

    $this->artisan('erp:readiness')
        ->expectsOutputToContain('Enterprise readiness passed')
        ->assertExitCode(0);
});
