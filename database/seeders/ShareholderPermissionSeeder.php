<?php
// database/seeders/ShareholderPermissionSeeder.php

namespace Database\Seeders;

use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;

class ShareholderPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Shareholder Management
            ['name' => 'view shareholders', 'guard_name' => 'web'],
            ['name' => 'create shareholders', 'guard_name' => 'web'],
            ['name' => 'view shareholder profiles', 'guard_name' => 'web'],
            ['name' => 'edit shareholders', 'guard_name' => 'web'],
            ['name' => 'delete shareholders', 'guard_name' => 'web'],
            ['name' => 'view shareholder balances', 'guard_name' => 'web'],

            // Profit Distribution
            ['name' => 'create profit distributions', 'guard_name' => 'web'],
            ['name' => 'view profit distributions', 'guard_name' => 'web'],
            ['name' => 'edit profit distributions', 'guard_name' => 'web'],
            ['name' => 'approve profit distributions', 'guard_name' => 'web'],
            ['name' => 'distribute profits', 'guard_name' => 'web'],
            ['name' => 'delete profit distributions', 'guard_name' => 'web'],

            // Shareholder Withdrawals
            ['name' => 'view shareholder withdrawals', 'guard_name' => 'web'],
            ['name' => 'approve shareholder withdrawals', 'guard_name' => 'web'],
            ['name' => 'reject shareholder withdrawals', 'guard_name' => 'web'],
            ['name' => 'process shareholder withdrawals', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => $permission['guard_name']]
            );
        }
    }
}