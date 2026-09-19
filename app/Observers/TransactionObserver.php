<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Models\AccountBalance;
use Illuminate\Support\Facades\DB;

class TransactionObserver
{
    public function created(Transaction $transaction)
    {
        $this->recalculate($transaction);
    }

    public function updated(Transaction $transaction)
    {
        $this->recalculate($transaction);
    }

    // Use `deleting` instead of `deleted`
    public function deleting(Transaction $transaction)
    {
        $this->recalculate($transaction, true);
    }

    protected function recalculate(Transaction $transaction, $isDeleting = false)
    {
        $accountId = $transaction->account_id;
        $currencyId = $transaction->currency_id;

        // Shareholder sub-ledger transactions intentionally have no account_id.
        // They must not update the account_balances table.
        if (!$accountId || !$currencyId) {
            return;
        }

        $query = DB::table('transactions')
            ->where('account_id', $accountId)
            ->where('currency_id', $currencyId);

        // Exclude the deleting transaction (if needed)
        if ($isDeleting) {
            $query->where('id', '!=', $transaction->id);
        }

        $summary = $query->selectRaw("
                SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as credit,
                SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as debit
            ")
            ->first();

        $credit = $summary->credit ?? 0;
        $debit = $summary->debit ?? 0;
        $balance = $credit - $debit;

        AccountBalance::updateOrCreate(
            ['account_id' => $accountId, 'currency_id' => $currencyId],
            ['credit' => $credit, 'debit' => $debit, 'balance' => $balance]
        );
    }
}
