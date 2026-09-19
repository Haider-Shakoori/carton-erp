<?php
// database/migrations/2026_07_07_000001_update_purchase_items_for_production.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            // Check if columns exist before adding
            if (!Schema::hasColumn('purchase_items', 'qty_available')) {
                $table->decimal('qty_available', 15, 4)->default(0)->after('qty')
                    ->comment('Available quantity for sale/use');
            }

            if (!Schema::hasColumn('purchase_items', 'qty_sold')) {
                $table->decimal('qty_sold', 15, 4)->default(0)->after('qty_available')
                    ->comment('Quantity sold to customers');
            }

            if (!Schema::hasColumn('purchase_items', 'qty_used')) {
                $table->decimal('qty_used', 15, 4)->default(0)->after('qty_sold')
                    ->comment('Quantity used in production');
            }

            if (!Schema::hasColumn('purchase_items', 'qty_wasted')) {
                $table->decimal('qty_wasted', 15, 4)->default(0)->after('qty_used')
                    ->comment('Quantity wasted or damaged');
            }

            if (!Schema::hasColumn('purchase_items', 'cost_per_unit')) {
                $table->decimal('cost_per_unit', 15, 4)->default(0)->after('usd_expense_per_item')
                    ->comment('USD cost per unit including expenses');
            }

            if (!Schema::hasColumn('purchase_items', 'total_cost')) {
                $table->decimal('total_cost', 15, 2)->default(0)->after('cost_per_unit')
                    ->comment('Total USD cost for this batch including expenses');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn([
                'qty_available',
                'qty_sold',
                'qty_used',
                'qty_wasted',
                'cost_per_unit',
                'total_cost',
            ]);
        });
    }
};
