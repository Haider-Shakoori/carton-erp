<?php
// database/migrations/2026_07_18_000000_add_hybrid_currency_fields_to_boms.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Update BOMs table
        Schema::table('boms', function (Blueprint $table) {
            // Material costs in USD (rename existing)
            if (Schema::hasColumn('boms', 'total_material_cost') && !Schema::hasColumn('boms', 'total_material_cost_usd')) {
                $table->renameColumn('total_material_cost', 'total_material_cost_usd');
            }

            // Add AFN calculated fields
            if (!Schema::hasColumn('boms', 'total_material_cost_afn')) {
                $table->decimal('total_material_cost_afn', 15, 4)->default(0)->after('total_material_cost_usd');
            }

            if (!Schema::hasColumn('boms', 'total_cost_afn')) {
                $table->decimal('total_cost_afn', 15, 4)->default(0)->after('total_material_cost_afn');
            }

            if (!Schema::hasColumn('boms', 'selling_price_afn')) {
                $table->decimal('selling_price_afn', 15, 4)->default(0)->after('total_cost_afn');
            }

            if (!Schema::hasColumn('boms', 'profit_afn')) {
                $table->decimal('profit_afn', 15, 4)->default(0)->after('selling_price_afn');
            }

            // Labor and overhead already exist
            if (Schema::hasColumn('boms', 'labor_cost_per_unit')) {
                $table->decimal('labor_cost_per_unit', 15, 4)->default(0)->change();
            }

            if (Schema::hasColumn('boms', 'overhead_cost_per_unit')) {
                $table->decimal('overhead_cost_per_unit', 15, 4)->default(0)->change();
            }
        });

        // Update BOM items table
        Schema::table('bom_items', function (Blueprint $table) {
            // Rename existing cost columns
            if (Schema::hasColumn('bom_items', 'cost_per_unit') && !Schema::hasColumn('bom_items', 'cost_per_unit_usd')) {
                $table->renameColumn('cost_per_unit', 'cost_per_unit_usd');
            }

            if (Schema::hasColumn('bom_items', 'total_cost') && !Schema::hasColumn('bom_items', 'total_cost_usd')) {
                $table->renameColumn('total_cost', 'total_cost_usd');
            }

            // Add AFN fields
            if (!Schema::hasColumn('bom_items', 'cost_per_unit_afn')) {
                $table->decimal('cost_per_unit_afn', 15, 4)->default(0)->after('cost_per_unit_usd');
            }

            if (!Schema::hasColumn('bom_items', 'total_cost_afn')) {
                $table->decimal('total_cost_afn', 15, 4)->default(0)->after('total_cost_usd');
            }
        });

        // Update sale items table
        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'bom_id')) {
                $table->foreignId('bom_id')->nullable()->after('purchase_item_id')
                    ->constrained('boms')->nullOnDelete();
            }

            if (!Schema::hasColumn('sale_items', 'profit_usd')) {
                $table->decimal('profit_usd', 15, 4)->default(0)->after('usd_tax');
            }

            if (!Schema::hasColumn('sale_items', 'profit_percentage')) {
                $table->decimal('profit_percentage', 8, 2)->default(0)->after('profit_usd');
            }

            // Add manual exchange rate column
            if (!Schema::hasColumn('sale_items', 'manual_exchange_rate')) {
                $table->decimal('manual_exchange_rate', 15, 4)->default(1)->after('rate');
            }
        });

        // Add exchange rate to sales table
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'manual_exchange_rate')) {
                $table->decimal('manual_exchange_rate', 15, 4)->default(1)->after('exchange_rate');
            }
        });
    }

    public function down()
    {
        Schema::table('boms', function (Blueprint $table) {
            $table->dropColumn([
                'total_material_cost_afn',
                'total_cost_afn',
                'selling_price_afn',
                'profit_afn'
            ]);
        });

        Schema::table('bom_items', function (Blueprint $table) {
            $table->dropColumn([
                'cost_per_unit_afn',
                'total_cost_afn'
            ]);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['bom_id']);
            $table->dropColumn([
                'bom_id',
                'profit_usd',
                'profit_percentage',
                'manual_exchange_rate'
            ]);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('manual_exchange_rate');
        });
    }
};
