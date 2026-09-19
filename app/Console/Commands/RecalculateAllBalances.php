<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\AccountBalance;
use Illuminate\Support\Facades\DB;

class RecalculateAllBalances extends Command
{
    protected $signature = 'balances:recalculate';
    protected $description = 'Recalculate account balances for all accounts and currencies';

    public function handle(): void
    {
        $this->info("Recalculating balances...");

        $rows = DB::table('transactions')
            ->whereNotNull('account_id')
            ->select('account_id', 'currency_id')
            ->selectRaw("
                SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as credit,
                SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as debit
            ")
            ->groupBy('account_id', 'currency_id')
            ->get();

        foreach ($rows as $row) {
            AccountBalance::updateOrCreate(
                ['account_id' => $row->account_id, 'currency_id' => $row->currency_id],
                [
                    'credit' => $row->credit,
                    'debit' => $row->debit,
                    'balance' => $row->credit - $row->debit
                ]
            );
        }

        $this->info("Done.");
    }
}
