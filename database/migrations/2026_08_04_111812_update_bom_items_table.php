<?php
// database/migrations/2026_08_04_000002_update_bom_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            // ─── ADD PURCHASE CURRENCY ID (if not exists) ───
            if (!Schema::hasColumn('bom_items', 'purchase_currency_id')) {
                $table->unsignedBigInteger('purchase_currency_id')->nullable()
                    ->after('purchase_currency')
                    ->comment('Reference to currency table');
            }

            // ─── ADD ROLL WEIGHT FIELD ───
            if (!Schema::hasColumn('bom_items', 'roll_weight')) {
                $table->decimal('roll_weight', 15, 4)->default(0)
                    ->after('purchase_currency_id')
                    ->comment('Calculated roll/weight from formula');
            }

            // ─── ADD MISSING 3D CARTON FIELDS ───
            // reel_length_inch (if missing)
            if (!Schema::hasColumn('bom_items', 'reel_length_inch')) {
                $table->decimal('reel_length_inch', 10, 2)->nullable()
                    ->comment('Calculated reel length = ((L+W)*2)+4');
            }

            // reel_height_inch (if missing)
            if (!Schema::hasColumn('bom_items', 'reel_height_inch')) {
                $table->decimal('reel_height_inch', 10, 2)->nullable()
                    ->after('reel_length_inch')
                    ->comment('Calculated reel height = W+H+1');
            }

            // ─── ADD MISSING COMMON FIELDS ───
            // per_gram_rate (if missing)
            if (!Schema::hasColumn('bom_items', 'per_gram_rate')) {
                $table->decimal('per_gram_rate', 10, 2)->nullable()
                    ->comment('Per gram rate');
            }

            // multiplication_layer (if missing)
            if (!Schema::hasColumn('bom_items', 'multiplication_layer')) {
                $table->integer('multiplication_layer')->nullable()
                    ->after('per_gram_rate')
                    ->comment('Multiplication layer value');
            }

            // formula_constant (if missing)
            if (!Schema::hasColumn('bom_items', 'formula_constant')) {
                $table->integer('formula_constant')->nullable()
                    ->after('multiplication_layer')
                    ->comment('Formula constant (default 1550000)');
            }

            // work_percentage (if missing)
            if (!Schema::hasColumn('bom_items', 'work_percentage')) {
                $table->decimal('work_percentage', 5, 2)->nullable()
                    ->after('formula_constant')
                    ->comment('Work percentage for this item');
            }

            // multiplication_method (if missing)
            if (!Schema::hasColumn('bom_items', 'multiplication_method')) {
                $table->string('multiplication_method', 20)->nullable()
                    ->after('work_percentage')
                    ->comment('multiply or divide');
            }

            // ─── ADD FIXED PERCENTAGE FIELDS (if missing) ───
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

            // ─── ADD FIXED RATE FIELDS (if missing) ───
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
            if (!Schema::hasIndex('bom_items', 'idx_bom_items_material_id')) {
                $table->index('material_id', 'idx_bom_items_material_id');
            }
            if (!Schema::hasIndex('bom_items', 'idx_bom_items_bom_id')) {
                $table->index('bom_id', 'idx_bom_items_bom_id');
            }
        });

        // ─── UPDATE EXISTING DATA ───
        // Set default values for existing records
        DB::table('bom_items')->whereNull('work_percentage')->update(['work_percentage' => 40]);
        DB::table('bom_items')->whereNull('formula_constant')->update(['formula_constant' => 1550000]);
        DB::table('bom_items')->whereNull('multiplication_method')->update(['multiplication_method' => 'multiply']);
        DB::table('bom_items')->whereNull('multiplication_layer')->update(['multiplication_layer' => 1]);
        DB::table('bom_items')->whereNull('rate_base_units')->update(['rate_base_units' => 100]);

        // ─── POPULATE REEL DIMENSIONS FOR EXISTING 3D CARTON ITEMS ───
        // Calculate reel_length_inch and reel_height_inch for existing carton_3d items
        if (Schema::hasColumn('bom_items', 'length_inch')
            && Schema::hasColumn('bom_items', 'width_inch')
            && Schema::hasColumn('bom_items', 'height_inch')) {
            $items = DB::table('bom_items')
                ->where('formula_type', 'carton_3d')
                ->whereNotNull('length_inch')
                ->whereNotNull('width_inch')
                ->whereNotNull('height_inch')
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
        $items = DB::table('bom_items')->get();

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
            // Drop added columns
            $columnsToDrop = [
                'purchase_currency_id',
                'roll_weight',
                // Only drop if they were added by this migration
                // Check if columns exist before dropping
            ];

            // Check and drop columns conditionally
            if (Schema::hasColumn('bom_items', 'purchase_currency_id')) {
                $table->dropColumn('purchase_currency_id');
            }
            if (Schema::hasColumn('bom_items', 'roll_weight')) {
                $table->dropColumn('roll_weight');
            }
            if (Schema::hasColumn('bom_items', 'reel_length_inch')) {
                $table->dropColumn('reel_length_inch');
            }
            if (Schema::hasColumn('bom_items', 'reel_height_inch')) {
                $table->dropColumn('reel_height_inch');
            }
            if (Schema::hasColumn('bom_items', 'per_gram_rate')) {
                $table->dropColumn('per_gram_rate');
            }
            if (Schema::hasColumn('bom_items', 'multiplication_layer')) {
                $table->dropColumn('multiplication_layer');
            }
            if (Schema::hasColumn('bom_items', 'formula_constant')) {
                $table->dropColumn('formula_constant');
            }
            if (Schema::hasColumn('bom_items', 'work_percentage')) {
                $table->dropColumn('work_percentage');
            }
            if (Schema::hasColumn('bom_items', 'multiplication_method')) {
                $table->dropColumn('multiplication_method');
            }
            if (Schema::hasColumn('bom_items', 'base_material_id')) {
                $table->dropColumn('base_material_id');
            }
            if (Schema::hasColumn('bom_items', 'percentage_of_base')) {
                $table->dropColumn('percentage_of_base');
            }
            if (Schema::hasColumn('bom_items', 'rate_per_unit')) {
                $table->dropColumn('rate_per_unit');
            }
            if (Schema::hasColumn('bom_items', 'rate_base_units')) {
                $table->dropColumn('rate_base_units');
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
            if (Schema::hasIndex('bom_items', 'idx_bom_items_material_id')) {
                $table->dropIndex('idx_bom_items_material_id');
            }
            if (Schema::hasIndex('bom_items', 'idx_bom_items_bom_id')) {
                $table->dropIndex('idx_bom_items_bom_id');
            }
        });
    }
};
