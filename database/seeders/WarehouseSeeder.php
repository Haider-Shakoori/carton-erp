<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('warehouses')->updateOrInsert(
            ['code' => 'MAIN'],
            [
                'name' => 'Main Warehouse',
                'is_default' => true,
                'is_active' => true,
                'notes' => 'Default warehouse for legacy and unassigned inventory.',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}
