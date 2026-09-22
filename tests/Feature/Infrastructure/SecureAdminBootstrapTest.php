<?php

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Support\Facades\Hash;

it('uses the configured bootstrap password and never resets it on reseed', function () {
    $bootstrapPassword = 'CI-bootstrap-password-2026!';
    config()->set('bootstrap.initial_admin_password', $bootstrapPassword);

    $this->seed(PermissionsSeeder::class);

    $admin = User::query()->where('username', 'superadmin')->firstOrFail();

    expect(Hash::check($bootstrapPassword, $admin->password))->toBeTrue();

    $replacementPassword = 'Operator-changed-password-2026!';
    $admin->update(['password' => Hash::make($replacementPassword)]);
    $replacementHash = $admin->fresh()->password;

    $this->seed(PermissionsSeeder::class);

    expect($admin->fresh()->password)->toBe($replacementHash)
        ->and(Hash::check($replacementPassword, $admin->fresh()->password))->toBeTrue();
});
