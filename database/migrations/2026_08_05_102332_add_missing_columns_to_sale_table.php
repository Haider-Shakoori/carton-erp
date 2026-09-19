<?php
// database/migrations/2026_08_05_000000_add_cost_and_profit_columns_to_sales_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // ─── ADD COST AND PROFIT COLUMNS ───
            if (!Schema::hasColumn('sales', 'total_cost_usd')) {
                $table->decimal('total_cost_usd', 15, 4)->default(0)
                    ->after('usd_grand_total')
                    ->comment('Total cost of goods sold in USD');
            }

            if (!Schema::hasColumn('sales', 'total_cost_afn')) {
                $table->decimal('total_cost_afn', 15, 4)->default(0)
                    ->after('total_cost_usd')
                    ->comment('Total cost of goods sold in AFN');
            }

            if (!Schema::hasColumn('sales', 'total_profit_usd')) {
                $table->decimal('total_profit_usd', 15, 4)->default(0)
                    ->after('total_cost_afn')
                    ->comment('Total profit in USD');
            }

            if (!Schema::hasColumn('sales', 'total_profit_afn')) {
                $table->decimal('total_profit_afn', 15, 4)->default(0)
                    ->after('total_profit_usd')
                    ->comment('Total profit in AFN');
            }

            // ─── ADD PRODUCTION COST COLUMNS ───
            if (!Schema::hasColumn('sales', 'actual_production_cost_usd')) {
                $table->decimal('actual_production_cost_usd', 15, 4)->default(0)
                    ->after('total_profit_afn')
                    ->comment('Actual production cost from production order in USD');
            }

            if (!Schema::hasColumn('sales', 'actual_production_cost_afn')) {
                $table->decimal('actual_production_cost_afn', 15, 4)->default(0)
                    ->after('actual_production_cost_usd')
                    ->comment('Actual production cost from production order in AFN');
            }

            // ─── ADD ESTIMATED PROFIT COLUMNS ───
            if (!Schema::hasColumn('sales', 'estimated_profit_usd')) {
                $table->decimal('estimated_profit_usd', 15, 4)->default(0)
                    ->after('actual_production_cost_afn')
                    ->comment('Estimated profit from BOM in USD');
            }

            if (!Schema::hasColumn('sales', 'estimated_profit_afn')) {
                $table->decimal('estimated_profit_afn', 15, 4)->default(0)
                    ->after('estimated_profit_usd')
                    ->comment('Estimated profit from BOM in AFN');
            }

            // ─── ADD PROFIT MARGIN ───
            if (!Schema::hasColumn('sales', 'profit_margin_percentage')) {
                $table->decimal('profit_margin_percentage', 10, 2)->default(0)
                    ->after('estimated_profit_afn')
                    ->comment('Profit margin percentage');
            }

            // ─── ADD INDEXES ───
            if (!Schema::hasIndex('sales', 'idx_sales_total_cost_usd')) {
                $table->index('total_cost_usd', 'idx_sales_total_cost_usd');
            }
            if (!Schema::hasIndex('sales', 'idx_sales_total_profit_usd')) {
                $table->index('total_profit_usd', 'idx_sales_total_profit_usd');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $columns = [
                'total_cost_usd',
                'total_cost_afn',
                'total_profit_usd',
                'total_profit_afn',
                'actual_production_cost_usd',
                'actual_production_cost_afn',
                'estimated_profit_usd',
                'estimated_profit_afn',
                'profit_margin_percentage',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('sales', $column)) {
                    $table->dropColumn($column);
                }
            }

            // Drop indexes
            if (Schema::hasIndex('sales', 'idx_sales_total_cost_usd')) {
                $table->dropIndex('idx_sales_total_cost_usd');
            }
            if (Schema::hasIndex('sales', 'idx_sales_total_profit_usd')) {
                $table->dropIndex('idx_sales_total_profit_usd');
            }
        });
    }
};
