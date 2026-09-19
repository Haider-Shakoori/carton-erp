<?php
// database/migrations/2026_07_21_000001_add_formula_fields_to_bom_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            // ─── Formula Reference ───
            if (!Schema::hasColumn('bom_items', 'formula_type')) {
                $table->string('formula_type')->nullable()->after('notes');
            }

            // ─── Parameter Data ───
            if (!Schema::hasColumn('bom_items', 'parameter_data')) {
                $table->json('parameter_data')->nullable()->after('formula_type');
            }

            // ─── AFN Fields (if not exists) ───
            if (!Schema::hasColumn('bom_items', 'cost_per_unit_afn')) {
                $table->decimal('cost_per_unit_afn', 15, 4)->default(0)->after('total_cost_usd');
            }

            if (!Schema::hasColumn('bom_items', 'total_cost_afn')) {
                $table->decimal('total_cost_afn', 15, 4)->default(0)->after('cost_per_unit_afn');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            $table->dropColumn([
                'formula_type',
                'parameter_data',
                'cost_per_unit_afn',
                'total_cost_afn',
            ]);
        });
    }
};
