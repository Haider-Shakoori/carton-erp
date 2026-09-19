<?php
// database/migrations/2024_01_01_000000_add_carton_metrics_to_boms.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('boms', function (Blueprint $table) {
            $table->decimal('cartons_per_roll', 10, 2)->nullable()->after('calculated_paper_weight');
            $table->decimal('carton_blank_length', 10, 2)->nullable()->after('cartons_per_roll');
            $table->decimal('carton_blank_width', 10, 2)->nullable()->after('carton_blank_length');
            $table->decimal('cost_per_roll', 20, 4)->nullable()->after('carton_blank_width');
            $table->decimal('total_paper_weight_per_roll', 20, 4)->nullable()->after('cost_per_roll');
        });

        Schema::table('bom_items', function (Blueprint $table) {
            $table->string('purchase_currency', 3)->default('AFN')->after('total_cost_afn');
        });
    }

    public function down()
    {
        Schema::table('boms', function (Blueprint $table) {
            $table->dropColumn([
                'cartons_per_roll',
                'carton_blank_length',
                'carton_blank_width',
                'cost_per_roll',
                'total_paper_weight_per_roll'
            ]);
        });

        Schema::table('bom_items', function (Blueprint $table) {
            $table->dropColumn('purchase_currency');
        });
    }
};
