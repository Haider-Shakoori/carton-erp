<?php

namespace Database\Seeders;

use App\Models\AccountCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccountCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AccountCategory::create([
            'name' => 'Client',
            'code_prefix' => '#', // Customer category
        ]);

        AccountCategory::create([
            'name' => 'Cash',
            'code_prefix' => 'CA', // Expense category
        ]);

        AccountCategory::create([
            'name' => 'Employee',
            'code_prefix' => 'EM', // Employee category
        ]);
    }
}
