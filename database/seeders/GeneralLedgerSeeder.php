<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GeneralLedgerSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $accounts = [
            ['code' => '1000', 'name' => 'Cash and Cash Equivalents', 'type' => 'asset', 'normal_balance' => 'debit', 'system_key' => 'cash'],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'type' => 'asset', 'normal_balance' => 'debit', 'system_key' => 'accounts_receivable'],
            ['code' => '1200', 'name' => 'Raw Material Inventory', 'type' => 'asset', 'normal_balance' => 'debit', 'system_key' => 'raw_material_inventory'],
            ['code' => '1300', 'name' => 'Work in Progress', 'type' => 'asset', 'normal_balance' => 'debit', 'system_key' => 'work_in_progress'],
            ['code' => '1400', 'name' => 'Finished Goods Inventory', 'type' => 'asset', 'normal_balance' => 'debit', 'system_key' => 'finished_goods_inventory'],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'normal_balance' => 'credit', 'system_key' => 'accounts_payable'],
            ['code' => '2100', 'name' => 'Goods Received Not Invoiced', 'type' => 'liability', 'normal_balance' => 'credit', 'system_key' => 'grni'],
            ['code' => '2200', 'name' => 'Landed Cost Accrual', 'type' => 'liability', 'normal_balance' => 'credit', 'system_key' => 'landed_cost_accrual'],
            ['code' => '3000', 'name' => 'Owner Equity', 'type' => 'equity', 'normal_balance' => 'credit', 'system_key' => 'equity'],
            ['code' => '4000', 'name' => 'Sales Revenue', 'type' => 'revenue', 'normal_balance' => 'credit', 'system_key' => 'sales_revenue'],
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'normal_balance' => 'debit', 'system_key' => 'cogs'],
            ['code' => '5100', 'name' => 'Production Variance', 'type' => 'expense', 'normal_balance' => 'debit', 'system_key' => 'production_variance'],
            ['code' => '5200', 'name' => 'Purchase Price Variance', 'type' => 'expense', 'normal_balance' => 'debit', 'system_key' => 'purchase_price_variance'],
            ['code' => '6000', 'name' => 'Operating Expenses', 'type' => 'expense', 'normal_balance' => 'debit', 'system_key' => 'operating_expense'],
        ];

        foreach ($accounts as $account) {
            DB::table('gl_accounts')->updateOrInsert(
                ['code' => $account['code']],
                $account + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $year = (int) $now->year;
        for ($month = 1; $month <= 12; $month++) {
            $start = Carbon::create($year, $month, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            DB::table('accounting_periods')->updateOrInsert(
                ['starts_on' => $start->toDateString(), 'ends_on' => $end->toDateString()],
                [
                    'name' => $start->format('F Y'),
                    'status' => 'open',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
