<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Persist production costs at full (4-decimal) precision.
     *
     * The authoritative per-material costs produced by
     * SaleProfitService::productionMaterialRequirements() carry 4 decimals
     * (e.g. 0.3938). The previous decimal(15,2) columns truncated these
     * (0.39), so a sale linked to a production order showed a snapshotted
     * estimated cost that differed from the live physical calculation
     * (0.47 USD / 31.02 AFN vs 0.4726 USD / 31.19 AFN).
     *
     * Widening the columns restores exact snapshot values; existing rows are
     * left intact and corrected by the backfill step.
     */
    public function up(): void
    {
        Schema::table('production_order_materials', function (Blueprint $table) {
            $table->decimal('total_cost', 15, 4)->default(0)->change();
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->decimal('total_material_cost', 15, 4)->default(0)->change();
            $table->decimal('total_labor_cost', 15, 4)->default(0)->change();
            $table->decimal('total_overhead_cost', 15, 4)->default(0)->change();
            $table->decimal('total_cost', 15, 4)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('production_order_materials', function (Blueprint $table) {
            $table->decimal('total_cost', 15, 2)->default(0)->change();
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->decimal('total_material_cost', 15, 2)->default(0)->change();
            $table->decimal('total_labor_cost', 15, 2)->default(0)->change();
            $table->decimal('total_overhead_cost', 15, 2)->default(0)->change();
            $table->decimal('total_cost', 15, 2)->default(0)->change();
        });
    }
};