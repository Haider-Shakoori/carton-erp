<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('currencies')->insert([
            [
                'name' => 'US Dollar',
                'code' => 'USD',
                'symbol' => '$',
                'country' => 'United States',
                'flag' => 'assets/flags/us.svg',
                'exchange_rate' => 1,
                'is_default' => true,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Chinese Yuan',
                'code' => 'CNY',
                'symbol' => '¥',
                'country' => 'China',
                'flag' => 'assets/flags/cn.svg',
                'exchange_rate' => 7.3,
                'is_default' => false,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);
    }
}
