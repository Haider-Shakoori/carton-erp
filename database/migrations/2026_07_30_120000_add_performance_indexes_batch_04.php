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
};
