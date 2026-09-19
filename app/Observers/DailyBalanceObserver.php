<?php

namespace App\Observers;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailyBalanceObserver
{
    public function created(Transaction $transaction)
    {
        $this->sync($transaction);
    }
    public function updated(Transaction $transaction)
    {
        $this->sync($transaction);
    }
    public function deleted(Transaction $transaction)
    {
        $this->sync($transaction);
    }

    protected function sync(Transaction $transaction)
    {
        // Start from the date of the changed transaction
        $date = Carbon::parse($transaction->created_at)->startOfDay();
        $today = Carbon::today();
        $currencyId = $transaction->currency_id;

        // Loop from the affected date until today to fix every day's balance
        while ($date->lte($today)) {
            $currentDate = $date->toDateString();

            // 1. Get Opening: Sum of all history before this date
            $opening = DB::table('transactions')
                ->where('currency_id', $currencyId)
                ->where('status', 'active')
                ->where('is_cash', 1)
                ->whereDate('created_at', '<', $currentDate)
                ->selectRaw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END) as total")
                ->value('total') ?? 0;

            // 2. Get Today's specific Credit/Debit
            $activity = DB::table('transactions')
                ->where('currency_id', $currencyId)
                ->whereDate('created_at', $currentDate)
                ->where('status', 'active')
                ->where('is_cash', 1)
                ->selectRaw("
                    SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as credits,
                    SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as debits
                ")
                ->first();

            $credits = $activity->credits ?? 0;
            $debits = $activity->debits ?? 0;
            $closing = $opening + $credits - $debits;

            // 3. Update the balances table for this day
            DB::table('balances')->updateOrInsert(
                ['currency_id' => $currencyId, 'date' => $currentDate],
                [
                    'starting_balance' => $opening,
                    'closing_balance' => $closing,
                    'updated_at' => now()
                ]
            );

            $date->addDay(); // Move to the next day in the chain
        }
    }
}
