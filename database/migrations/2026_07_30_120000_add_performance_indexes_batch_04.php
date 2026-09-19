<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index(
                ['customer_id', 'status', 'created_at'],
                'sales_cust_stat_date_idx'
            );
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index(
                ['account_id', 'currency_id', 'status', 'created_at'],
                'txn_account_curr_stat_date_idx'
            );
            $table->index(
                ['table_name', 'table_row_id'],
                'txn_reference_idx'
            );
        });

        Schema::table('exchanges', function (Blueprint $table) {
            $table->index(
                [
                    'customer_account_id',
                    'base_currency_id',
                    'target_currency_id',
                    'created_at',
                ],
                'exchange_report_idx'
            );
        });

        Schema::table('remittances', function (Blueprint $table) {
            $table->index(
                ['account_id', 'currency_id', 'status', 'created_at'],
                'remittance_report_idx'
            );
        });
    }

    public function down(): void
    {
        // MySQL may discard a narrower FK-supporting index once a wider
        // composite index with the same leading FK column exists. Recreate a
        // conventional support index before dropping the performance index so
        // rollback never requires disabling or rebuilding foreign keys.
        $this->ensureForeignKeySupportIndex(
            'remittances',
            'account_id',
            'remittances_account_id_index',
            'remittance_report_idx'
        );
        $this->ensureForeignKeySupportIndex(
            'exchanges',
            'customer_account_id',
            'exchanges_customer_account_id_index',
            'exchange_report_idx'
        );
        $this->ensureForeignKeySupportIndex(
            'transactions',
            'account_id',
            'transactions_account_id_index',
            'txn_account_curr_stat_date_idx'
        );
        $this->ensureForeignKeySupportIndex(
            'sales',
            'customer_id',
            'sales_customer_id_index',
            'sales_cust_stat_date_idx'
        );

        Schema::table('remittances', function (Blueprint $table) {
            $table->dropIndex('remittance_report_idx');
        });

        Schema::table('exchanges', function (Blueprint $table) {
            $table->dropIndex('exchange_report_idx');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('txn_reference_idx');
            $table->dropIndex('txn_account_curr_stat_date_idx');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_cust_stat_date_idx');
        });
    }

    private function ensureForeignKeySupportIndex(
        string $table,
        string $column,
        string $indexName,
        string $performanceIndex
    ): void {
        $hasAlternative = collect(Schema::getIndexes($table))
            ->filter(fn (array $index) => ($index['name'] ?? null) !== $performanceIndex)
            ->contains(function (array $index) use ($column) {
                $columns = array_values($index['columns'] ?? []);
                return ($columns[0] ?? null) === $column;
            });

        if (! $hasAlternative) {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $indexName) {
                $blueprint->index([$column], $indexName);
            });
        }
    }
};
