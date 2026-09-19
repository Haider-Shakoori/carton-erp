<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Account transactions and shareholder sub-ledger transactions are
            // different concepts. A shareholder ID must never be stored as an
            // accounts.id value.
            $table->unsignedBigInteger('account_id')->nullable()->change();
            $table->foreignId('shareholder_id')
                ->nullable()
                ->after('account_id')
                ->constrained('shareholders')
                ->nullOnDelete();
            $table->index(['shareholder_id', 'status'], 'tx_shareholder_status_idx');
        });

        // Backfill legacy profit-distribution transactions. The old code put
        // shareholder_id into account_id, so resolve the shareholder primarily
        // from the distribution item and only then use the legacy account_id.
        DB::table('transactions')
            ->where('table_name', 'profit_distributions')
            ->orderBy('id')
            ->get()
            ->each(function ($transaction) {
                $shareholderId = DB::table('profit_distribution_items')
                    ->where('transaction_id', $transaction->id)
                    ->value('shareholder_id');

                if (!$shareholderId && $transaction->account_id) {
                    $shareholderId = DB::table('shareholders')
                        ->where('id', $transaction->account_id)
                        ->value('id');
                }

                if ($shareholderId) {
                    DB::table('transactions')
                        ->where('id', $transaction->id)
                        ->update([
                            'shareholder_id' => $shareholderId,
                            'account_id' => null,
                            // adjustment is valid in the canonical transaction enum;
                            // table_name identifies the business event.
                            'type' => 'adjustment',
                        ]);
                }
            });

        DB::table('transactions')
            ->where('table_name', 'shareholder_withdrawals')
            ->orderBy('id')
            ->get()
            ->each(function ($transaction) {
                $shareholderId = DB::table('shareholder_withdrawals')
                    ->where(function ($query) use ($transaction) {
                        $query->where('transaction_id', $transaction->id);
                        if ($transaction->table_row_id) {
                            $query->orWhere('id', $transaction->table_row_id);
                        }
                    })
                    ->value('shareholder_id');

                if (!$shareholderId && $transaction->account_id) {
                    $shareholderId = DB::table('shareholders')
                        ->where('id', $transaction->account_id)
                        ->value('id');
                }

                if ($shareholderId) {
                    DB::table('transactions')
                        ->where('id', $transaction->id)
                        ->update([
                            'shareholder_id' => $shareholderId,
                            'account_id' => null,
                            'type' => 'adjustment',
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Restore the old layout only for rollback compatibility.
        DB::table('transactions')
            ->whereNotNull('shareholder_id')
            ->whereNull('account_id')
            ->update(['account_id' => DB::raw('shareholder_id')]);

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('tx_shareholder_status_idx');
            $table->dropConstrainedForeignId('shareholder_id');
            $table->unsignedBigInteger('account_id')->nullable(false)->change();
        });
    }
};
