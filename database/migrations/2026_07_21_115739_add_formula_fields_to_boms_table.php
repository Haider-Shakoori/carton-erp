<?php
// database/migrations/2026_07_21_000000_add_formula_fields_to_boms_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boms', function (Blueprint $table) {
            // ─── Formula Type ───
            $table->enum('formula_type', ['carton_3d', 'cut_roll'])->default('carton_3d')->after('code');

            // ─── Formula Parameters (3D Carton) ───
            $table->decimal('length_inch', 10, 2)->nullable()->after('formula_type');
            $table->decimal('width_inch', 10, 2)->nullable()->after('length_inch');
            $table->decimal('height_inch', 10, 2)->nullable()->after('width_inch');
            $table->decimal('reel_length_inch', 10, 2)->nullable()->after('height_inch');
            $table->decimal('reel_height_inch', 10, 2)->nullable()->after('reel_length_inch');
            $table->integer('paper_gsm')->nullable()->after('reel_height_inch');
            $table->integer('layers')->nullable()->after('paper_gsm');
            $table->decimal('division_factor', 10, 2)->default(1)->after('layers');

            // ─── Formula Parameters (Cut/Roll) ───
            $table->decimal('cut_length_inch', 10, 2)->nullable()->after('division_factor');
            $table->decimal('cut_width_inch', 10, 2)->nullable()->after('cut_length_inch');
            $table->integer('grh')->nullable()->after('cut_width_inch');
            $table->integer('ply')->nullable()->after('grh');
            $table->integer('print')->default(0)->after('ply');

            // ─── Common Formula Parameters ───
            $table->decimal('per_gram_rate', 10, 2)->nullable()->after('print');
            $table->integer('multiplication_layer')->default(1)->after('per_gram_rate');
            $table->integer('formula_constant')->default(1550000)->after('multiplication_layer');
            $table->decimal('work_percentage', 5, 2)->default(40)->after('formula_constant');
            $table->enum('multiplication_method', ['multiply', 'divide'])->default('multiply')->after('work_percentage');

            // ─── Calculated Results ───
            $table->decimal('calculated_paper_rate', 15, 8)->nullable()->after('profit_margin_percentage');
            $table->decimal('calculated_paper_rate_by_layers', 15, 8)->nullable()->after('calculated_paper_rate');
            $table->decimal('calculated_work_amount', 15, 8)->nullable()->after('calculated_paper_rate_by_layers');
            $table->decimal('calculated_net_rate', 15, 8)->nullable()->after('calculated_work_amount');
            $table->decimal('calculated_total_area', 15, 2)->nullable()->after('calculated_net_rate');
            $table->decimal('calculated_paper_weight', 15, 4)->nullable()->after('calculated_total_area');

            // ─── Indexes ───
            $table->index('formula_type');
        });
    }

    public function down(): void
    {
        Schema::table('boms', function (Blueprint $table) {
            $table->dropColumn([
                'formula_type',
                'length_inch',
                'width_inch',
                'height_inch',
                'reel_length_inch',
                'reel_height_inch',
                'paper_gsm',
                'layers',
                'division_factor',
                'cut_length_inch',
                'cut_width_inch',
                'grh',
                'ply',
                'print',
                'per_gram_rate',
                'multiplication_layer',
                'formula_constant',
                'work_percentage',
                'multiplication_method',
                'calculated_paper_rate',
                'calculated_paper_rate_by_layers',
                'calculated_work_amount',
                'calculated_net_rate',
                'calculated_total_area',
                'calculated_paper_weight',
            ]);
        });
    }
};
