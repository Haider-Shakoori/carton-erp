<?php
// database/migrations/2026_08_04_000000_add_missing_columns_to_boms_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('boms', function (Blueprint $table) {
            // ─── ADD WORK PERCENTAGE ───
            if (!Schema::hasColumn('boms', 'work_percentage')) {
                $table->decimal('work_percentage', 5, 2)->default(40)
                    ->after('description')
                    ->comment('Work, Overhead & Profit percentage (added once)');
            }

            // ─── ADD PROFIT MARGIN PERCENTAGE ───
            if (!Schema::hasColumn('boms', 'profit_margin_percentage')) {
                $table->decimal('profit_margin_percentage', 5, 2)->default(0)
                    ->after('work_percentage')
                    ->comment('Additional markup on top of work percentage');
            }

            // ─── ADD EXCHANGE RATE ───
            if (!Schema::hasColumn('boms', 'exchange_rate')) {
                $table->decimal('exchange_rate', 10, 4)->nullable()
                    ->after('profit_margin_percentage')
                    ->comment('USD to AFN conversion rate');
            }

            // ─── ADD EXCHANGE RATE UPDATED AT ───
            if (!Schema::hasColumn('boms', 'exchange_rate_updated_at')) {
                $table->timestamp('exchange_rate_updated_at')->nullable()
                    ->after('exchange_rate');
            }

            // ─── ADD TOTAL COST FIELDS ───
            if (!Schema::hasColumn('boms', 'total_material_cost_usd')) {
                $table->decimal('total_material_cost_usd', 15, 4)->default(0)
                    ->after('exchange_rate_updated_at')
                    ->comment('Total material cost in USD including wastage');
            }

            if (!Schema::hasColumn('boms', 'total_material_cost_afn')) {
                $table->decimal('total_material_cost_afn', 15, 4)->default(0)
                    ->after('total_material_cost_usd')
                    ->comment('Total material cost in AFN including wastage');
            }

            if (!Schema::hasColumn('boms', 'total_cost_afn')) {
                $table->decimal('total_cost_afn', 15, 4)->default(0)
                    ->after('total_material_cost_afn')
                    ->comment('Total cost in AFN (material + work)');
            }

            if (!Schema::hasColumn('boms', 'selling_price_afn')) {
                $table->decimal('selling_price_afn', 15, 4)->default(0)
                    ->after('total_cost_afn')
                    ->comment('Selling price in AFN (including profit margin)');
            }

            if (!Schema::hasColumn('boms', 'profit_afn')) {
                $table->decimal('profit_afn', 15, 4)->default(0)
                    ->after('selling_price_afn')
                    ->comment('Profit in AFN');
            }

            // ─── ADD CREATED BY AND UPDATED BY (if not exists) ───
            if (!Schema::hasColumn('boms', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('is_active');
            }

            if (!Schema::hasColumn('boms', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
        });

        // ─── UPDATE EXISTING RECORDS WITH DEFAULT VALUES ───
        DB::table('boms')->whereNull('work_percentage')->update(['work_percentage' => 40]);
        DB::table('boms')->whereNull('profit_margin_percentage')->update(['profit_margin_percentage' => 0]);

        // Set exchange rate if null
        $exchangeRate = 85; // Default
        DB::table('boms')->whereNull('exchange_rate')->update([
            'exchange_rate' => $exchangeRate,
            'exchange_rate_updated_at' => now()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boms', function (Blueprint $table) {
            $columns = [
                'work_percentage',
                'profit_margin_percentage',
                'exchange_rate',
                'exchange_rate_updated_at',
                'total_material_cost_usd',
                'total_material_cost_afn',
                'total_cost_afn',
                'selling_price_afn',
                'profit_afn',
                'created_by',
                'updated_by'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('boms', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
