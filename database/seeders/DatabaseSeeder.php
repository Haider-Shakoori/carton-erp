<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();

        DB::table('account_categories')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'Client',
                'code_prefix' => '#',
                'bi_icon' => 'bi-person',
                'bi_icon_color' => '#007bff',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('account_categories')->updateOrInsert(
            ['id' => 2],
            [
                'name' => 'Cash',
                'code_prefix' => 'CA',
                'bi_icon' => 'bi-cash-coin',
                'bi_icon_color' => '#28a745',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('account_categories')->updateOrInsert(
            ['id' => 3],
            [
                'name' => 'Employee',
                'code_prefix' => 'EM',
                'bi_icon' => 'bi-person-badge',
                'bi_icon_color' => '#ffc107',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );



        DB::table('account_sub_categories')->upsert([
            [
                'id' => 1,
                'account_category_id' => 1,
                'name' => 'Customer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'account_category_id' => 1,
                'name' => 'Supplier',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'account_category_id' => 1,
                'name' => 'Agent',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'account_category_id' => 2,
                'name' => 'Safe',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'account_category_id' => 3,
                'name' => 'Office Employee',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['id']);


        DB::table('currencies')->upsert([
            [
                'id' => 1,
                'name' => 'US Dollar',
                'code' => 'USD',
                'symbol' => '$',
                'country' => 'United States',
                'flag' => 'assets/flags/us.svg',
                'exchange_rate' => 1.00,
                'is_default' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Afghan Afghani',
                'code' => 'AFN',
                'symbol' => '؋',
                'country' => 'Afghanistan',
                'flag' => 'assets/flags/af.svg',
                'exchange_rate' => 70.00,
                'is_default' => 0,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['id']);

        $this->call(BusinessUnitSeeder::class);
        $this->call(PermissionsSeeder::class);
        $this->call(HRPermissionSeeder::class);
        $this->call(ShareholderPermissionSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(ClientCartonRawMaterialSeeder::class);
        $this->call(BoardProfileSeeder::class);
        $this->call(CustomerCartonSizeSeeder::class);
        $this->call(CustomerCartonBomSeeder::class);
        $this->call(ClientCartonOpeningStockSeeder::class);
        // $this->call(ProductSeeder::class);
    }
}
