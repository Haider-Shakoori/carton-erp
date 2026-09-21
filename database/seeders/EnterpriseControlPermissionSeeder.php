<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class EnterpriseControlPermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'approve production orders',
            'close production orders',
            'reopen production orders',
            'reverse production orders',
            'view warehouses',
            'manage warehouses',
            'transfer stock',
            'approve purchase orders',
            'receive purchase orders',
            'match purchase invoices',
            'view purchase matching',
            'view accounting periods',
            'manage accounting periods',
            'post journal entries',
            'view journal entries',
            'view cost variances',
            'manage master data governance',
            'view enterprise kpis',
            'view enterprise controls',
        ] as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }
    }
}
