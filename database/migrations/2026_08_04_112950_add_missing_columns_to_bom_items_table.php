<?php
// database/migrations/2026_08_04_000001_add_missing_columns_to_bom_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            // ─── ADD PURCHASE CURRENCY ID ───
            if (!Schema::hasColumn('bom_items', 'purchase_currency_id')) {
                $table->unsignedBigInteger('purchase_currency_id')->nullable()
                    ->after('purchase_currency')
                    ->comment('Reference to currency table');
            }

            // ─── ADD ROLL WEIGHT ───
            if (!Schema::hasColumn('bom_items', 'roll_weight')) {
                $table->decimal('roll_weight', 15, 4)->default(0)
                    ->after('purchase_currency_id')
                    ->comment('Calculated roll/weight from formula');
            }

            // ─── ADD REEL DIMENSIONS ───
            if (!Schema::hasColumn('bom_items', 'reel_length_inch')) {
                $table->decimal('reel_length_inch', 10, 2)->nullable()
                    ->comment('Calculated reel length = ((L+W)*2)+4');
            }

            if (!Schema::hasColumn('bom_items', 'reel_height_inch')) {
                $table->decimal('reel_height_inch', 10, 2)->nullable()
                    ->after('reel_length_inch')
                    ->comment('Calculated reel height = W+H+1');
            }

            // ─── ADD COMMON FORMULA FIELDS ───
            if (!Schema::hasColumn('bom_items', 'per_gram_rate')) {
                $table->decimal('per_gram_rate', 10, 2)->nullable()
                    ->comment('Per gram rate');
            }

            if (!Schema::hasColumn('bom_items', 'multiplication_layer')) {
                $table->integer('multiplication_layer')->nullable()
                    ->after('per_gram_rate')
                    ->comment('Multiplication layer value');
            }

            if (!Schema::hasColumn('bom_items', 'formula_constant')) {
                $table->integer('formula_constant')->nullable()
                    ->after('multiplication_layer')
                    ->comment('Formula constant (default 1550000)');
            }

            if (!Schema::hasColumn('bom_items', 'work_percentage')) {
                $table->decimal('work_percentage', 5, 2)->nullable()
                    ->after('formula_constant')
                    ->comment('Work percentage for this item');
            }

            if (!Schema::hasColumn('bom_items', 'multiplication_method')) {
                $table->string('multiplication_method', 20)->nullable()
                    ->after('work_percentage')
                    ->comment('multiply or divide');
            }

            // ─── ADD FIXED PERCENTAGE FIELDS ───
            if (!Schema::hasColumn('bom_items', 'base_material_id')) {
                $table->unsignedBigInteger('base_material_id')->nullable()
                    ->after('multiplication_method')
                    ->comment('Reference to base material for percentage calculation');
            }

            if (!Schema::hasColumn('bom_items', 'percentage_of_base')) {
                $table->decimal('percentage_of_base', 5, 2)->nullable()
                    ->after('base_material_id')
                    ->comment('Percentage of base material');
            }

            // ─── ADD FIXED RATE FIELDS ───
            if (!Schema::hasColumn('bom_items', 'rate_per_unit')) {
                $table->decimal('rate_per_unit', 15, 4)->nullable()
                    ->after('percentage_of_base')
                    ->comment('Rate per unit for fixed rate formula');
            }

            if (!Schema::hasColumn('bom_items', 'rate_base_units')) {
                $table->integer('rate_base_units')->nullable()
                    ->after('rate_per_unit')
                    ->comment('Base units for rate calculation');
            }

            // ─── ADD INDEXES ───
            if (!Schema::hasIndex('bom_items', 'idx_bom_items_purchase_currency')) {
                $table->index('purchase_currency', 'idx_bom_items_purchase_currency');
            }
            if (!Schema::hasIndex('bom_items', 'idx_bom_items_base_material')) {
                $table->index('base_material_id', 'idx_bom_items_base_material');
            }
            if (!Schema::hasIndex('bom_items', 'idx_bom_items_formula_type')) {
                $table->index('formula_type', 'idx_bom_items_formula_type');
            }
        });

        // ─── UPDATE EXISTING RECORDS WITH DEFAULT VALUES ───
        DB::table('bom_items')->whereNull('work_percentage')->update(['work_percentage' => 40]);
        DB::table('bom_items')->whereNull('formula_constant')->update(['formula_constant' => 1550000]);
        DB::table('bom_items')->whereNull('multiplication_method')->update(['multiplication_method' => 'multiply']);
        DB::table('bom_items')->whereNull('multiplication_layer')->update(['multiplication_layer' => 1]);
        DB::table('bom_items')->whereNull('rate_base_units')->update(['rate_base_units' => 100]);

        // ─── POPULATE REEL DIMENSIONS FOR EXISTING 3D CARTON ITEMS ───
        if (Schema::hasColumn('bom_items', 'length_inch')
            && Schema::hasColumn('bom_items', 'width_inch')
            && Schema::hasColumn('bom_items', 'height_inch')) {
            $items = DB::table('bom_items')
                ->where('formula_type', 'carton_3d')
                ->whereNotNull('length_inch')
                ->whereNotNull('width_inch')
                ->whereNotNull('height_inch')
                ->whereNull('reel_length_inch')
                ->get();

            foreach ($items as $item) {
                $reelLength = ((floatval($item->length_inch) + floatval($item->width_inch)) * 2) + 4;
                $reelHeight = floatval($item->width_inch) + floatval($item->height_inch) + 1;

                DB::table('bom_items')
                    ->where('id', $item->id)
                    ->update([
                        'reel_length_inch' => $reelLength,
                        'reel_height_inch' => $reelHeight
                    ]);
            }
        }

        // ─── POPULATE ROLL WEIGHT FOR EXISTING ITEMS ───
        $items = DB::table('bom_items')->where('roll_weight', 0)->get();

        foreach ($items as $item) {
            $rollWeight = 0;

            if ($item->formula_type === 'carton_3d') {
                $length = floatval($item->length_inch ?? 0);
                $width = floatval($item->width_inch ?? 0);
                $height = floatval($item->height_inch ?? 0);
                $gsm = floatval($item->paper_gsm ?? 0);

                if ($length > 0 && $width > 0 && $height > 0 && $gsm > 0) {
                    $reelLength = (($length + $width) * 2) + 4;
                    $reelHeight = $width + $height + 1;
                    $quantity = floatval($item->quantity ?? 1);
                    $rollWeight = $quantity * ($reelLength * $reelHeight * $gsm) / 1000;
                }
            } elseif ($item->formula_type === 'cut_roll') {
                $cutLength = floatval($item->cut_length_inch ?? 0);
                $cutWidth = floatval($item->cut_width_inch ?? 0);
                $grh = floatval($item->grh ?? 0);

                if ($cutLength > 0 && $cutWidth > 0 && $grh > 0) {
                    $quantity = floatval($item->quantity ?? 1);
                    $rollWeight = $quantity * ($cutLength * $cutWidth * $grh) / 1000;
                }
            } else {
                $rollWeight = floatval($item->quantity ?? 0);
            }

            if ($rollWeight > 0) {
                DB::table('bom_items')
                    ->where('id', $item->id)
                    ->update(['roll_weight' => $rollWeight]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            $columns = [
                'purchase_currency_id',
                'roll_weight',
                'reel_length_inch',
                'reel_height_inch',
                'per_gram_rate',
                'multiplication_layer',
                'formula_constant',
                'work_percentage',
                'multiplication_method',
                'base_material_id',
                'percentage_of_base',
                'rate_per_unit',
                'rate_base_units',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('bom_items', $column)) {
                    $table->dropColumn($column);
                }
            }

            // Drop indexes
            if (Schema::hasIndex('bom_items', 'idx_bom_items_purchase_currency')) {
                $table->dropIndex('idx_bom_items_purchase_currency');
            }
            if (Schema::hasIndex('bom_items', 'idx_bom_items_base_material')) {
                $table->dropIndex('idx_bom_items_base_material');
            }
            if (Schema::hasIndex('bom_items', 'idx_bom_items_formula_type')) {
                $table->dropIndex('idx_bom_items_formula_type');
            }
        });
    }
};
