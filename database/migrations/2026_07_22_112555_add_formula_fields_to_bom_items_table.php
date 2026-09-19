<?php
// database/migrations/2026_07_22_000000_add_formula_fields_to_bom_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            // ─── Formula Fields ───
            if (!Schema::hasColumn('bom_items', 'formula_type')) {
                $table->string('formula_type')->nullable()->after('notes');
            }

            if (!Schema::hasColumn('bom_items', 'formula_data')) {
                $table->json('formula_data')->nullable()->after('formula_type');
            }

            if (!Schema::hasColumn('bom_items', 'is_formula_based')) {
                $table->boolean('is_formula_based')->default(false)->after('formula_data');
            }

            // ─── AFN Fields ───
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
                'formula_data',
                'is_formula_based',
                'cost_per_unit_afn',
                'total_cost_afn',
            ]);
        });
    }
};
