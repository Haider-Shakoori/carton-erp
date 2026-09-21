<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('currencies')->upsert([
            [
                'id' => 1,
                'name' => 'US Dollar',
                'code' => 'USD',
                'symbol' => '$',
                'country' => 'United States',
                'flag' => 'assets/flags/us.svg',
                'exchange_rate' => 1,
                'is_default' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'Afghan Afghani',
                'code' => 'AFN',
                'symbol' => '؋',
                'country' => 'Afghanistan',
                'flag' => 'assets/flags/af.svg',
                'exchange_rate' => 70,
                'is_default' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['id']);
    }
}
