<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\WhatsappSession;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransactionsSeeder extends Seeder
{
    public function run(): void
    {
        // $accounts = Account::pluck('id')->toArray();

        // if (empty($accounts)) {
        //     $this->command->warn('No accounts found. Skipping transactions seeding.');
        //     return;
        // }

        // $chunkSize = 5000;
        // $totalRecords = 1000000;

        // for ($i = 0; $i < $totalRecords; $i += $chunkSize) {
        //     $transactions = [];

        //     for ($j = 0; $j < $chunkSize; $j++) {
        //         $transactions[] = [
        //             'table_name' => null,
        //             'table_row_id' => null,
        //             'account_id' => $accounts[array_rand($accounts)],
        //             'currency_id' => rand(1, 2),
        //             'amount' => rand(1000, 10000),
        //             'transaction_type' => rand(0, 1) ? 'credit' : 'debit',
        //             'status' => 'active',
        //             'type' => 'journal',
        //             'is_cash' => rand(0, 1),
        //             'note' => 'this is just a test entry.',
        //             'created_by' => 1,
        //             'created_at' => now()->subDays(rand(0, 6)), // Random date within the last 7 days
        //             'updated_at' => now()->subDays(rand(0, 6)), // Random date within the last 7 days
        //         ];
        //     }

        //     // DB::table('transactions')->insert($transactions);
        // }

        // $this->command->info("Seeded {$totalRecords} transactions.");

        WhatsappSession::create([
            'api_key' => 'fdda5e4871ea45310440c553ebb46dfa92c3c8b71396004e0fe2d4be164f3fb7'
        ]);
    }
}
