<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_items', 'unit')) {
                $table->string('unit', 50)->nullable()->after('product_id')
                    ->comment('Purchase unit for this batch (roll / kg / piece / box / ...). Null keeps legacy behaviour.');
            }

            if (!Schema::hasColumn('purchase_items', 'kg_per_roll')) {
                $table->decimal('kg_per_roll', 15, 4)->nullable()->after('unit')
                    ->comment('Physical weight (kg) of one roll. Required for roll-based paper batches.');
            }

            if (!Schema::hasColumn('purchase_items', 'total_weight_kg')) {
                $table->decimal('total_weight_kg', 18, 4)->nullable()->after('kg_per_roll')
                    ->comment('Total physical weight (kg) of the batch = roll count x kg_per_roll.');
            }

            if (!Schema::hasColumn('purchase_items', 'qty_kg')) {
                $table->decimal('qty_kg', 18, 4)->default(0)->after('total_weight_kg')
                    ->comment('Physical weight (kg) purchased for this batch.');
            }

            if (!Schema::hasColumn('purchase_items', 'qty_kg_sold')) {
                $table->decimal('qty_kg_sold', 18, 4)->default(0)->after('qty_kg');
            }

            if (!Schema::hasColumn('purchase_items', 'qty_kg_used')) {
                $table->decimal('qty_kg_used', 18, 4)->default(0)->after('qty_kg_sold');
            }

            if (!Schema::hasColumn('purchase_items', 'qty_kg_wasted')) {
                $table->decimal('qty_kg_wasted', 18, 4)->default(0)->after('qty_kg_used');
            }

            if (!Schema::hasColumn('purchase_items', 'qty_kg_available')) {
                $table->decimal('qty_kg_available', 18, 4)->default(0)->after('qty_kg_wasted')
                    ->comment('Remaining physical weight (kg) for this batch.');
            }

            if (!Schema::hasColumn('purchase_items', 'landed_cost_per_kg')) {
                $table->decimal('landed_cost_per_kg', 18, 6)->nullable()->after('qty_kg_available')
                    ->comment('Landed USD cost per kg (base + allocated expenses).');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn([
                'unit',
                'kg_per_roll',
                'total_weight_kg',
                'qty_kg',
                'qty_kg_sold',
                'qty_kg_used',
                'qty_kg_wasted',
                'qty_kg_available',
                'landed_cost_per_kg',
            ]);
        });
    }
};