<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            if (Schema::hasColumn('bom_items', 'quantity')) {
                $table->decimal('quantity', 18, 8)->change();
            }
            if (Schema::hasColumn('bom_items', 'cost_per_unit_usd')) {
                $table->decimal('cost_per_unit_usd', 18, 8)->default(0)->change();
            }
            if (Schema::hasColumn('bom_items', 'cost_per_unit_afn')) {
                $table->decimal('cost_per_unit_afn', 18, 8)->default(0)->change();
            }
            if (Schema::hasColumn('bom_items', 'total_cost_usd')) {
                $table->decimal('total_cost_usd', 18, 8)->default(0)->change();
            }
            if (Schema::hasColumn('bom_items', 'total_cost_afn')) {
                $table->decimal('total_cost_afn', 18, 8)->default(0)->change();
            }
            if (Schema::hasColumn('bom_items', 'roll_weight')) {
                $table->decimal('roll_weight', 18, 8)->default(0)->change();
            }
            if (Schema::hasColumn('bom_items', 'per_gram_rate')) {
                $table->decimal('per_gram_rate', 18, 8)->nullable()->change();
            }
            if (Schema::hasColumn('bom_items', 'rate_per_unit')) {
                $table->decimal('rate_per_unit', 18, 8)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // Precision increases are intentionally not reversed. Narrowing these
        // columns can destroy valid production-costing data.
    }
};
