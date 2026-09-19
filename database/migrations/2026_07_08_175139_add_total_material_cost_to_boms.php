<?php
// database/migrations/2026_07_08_000000_add_total_material_cost_to_boms.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boms', function (Blueprint $table) {
            if (!Schema::hasColumn('boms', 'total_material_cost')) {
                $table->decimal('total_material_cost', 15, 2)->default(0)->after('profit_margin_percentage')
                    ->comment('Total material cost including wastage');
            }
        });
    }

    public function down(): void
    {
        Schema::table('boms', function (Blueprint $table) {
            if (Schema::hasColumn('boms', 'total_material_cost')) {
                $table->dropColumn('total_material_cost');
            }
        });
    }
};
