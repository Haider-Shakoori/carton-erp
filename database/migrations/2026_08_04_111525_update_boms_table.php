<?php
// database/migrations/2026_08_04_000000_update_boms_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ─── DROP INDEX BEFORE COLUMN (SQLite compatibility) ───
        // SQLite refuses to drop a column that still has an index.
        // Drop the formula_type index first so the migration also works
        // on SQLite (test DB); the end schema is identical to MySQL.
        if (Schema::hasColumn('boms', 'formula_type') && Schema::hasIndex('boms', 'boms_formula_type_index')) {
            Schema::table('boms', function (Blueprint $table) {
                $table->dropIndex('boms_formula_type_index');
            });
        }

        Schema::table('boms', function (Blueprint $table) {
            // ─── REMOVE COLUMNS (moved to BOMItem) ───
            // These formula fields belong on BOMItem, not BOM
            if (Schema::hasColumn('boms', 'formula_type')) {
                $table->dropColumn('formula_type');
            }
            if (Schema::hasColumn('boms', 'length_inch')) {
                $table->dropColumn('length_inch');
            }
            if (Schema::hasColumn('boms', 'width_inch')) {
                $table->dropColumn('width_inch');
            }
            if (Schema::hasColumn('boms', 'height_inch')) {
                $table->dropColumn('height_inch');
            }
            if (Schema::hasColumn('boms', 'reel_length_inch')) {
                $table->dropColumn('reel_length_inch');
            }
            if (Schema::hasColumn('boms', 'reel_height_inch')) {
                $table->dropColumn('reel_height_inch');
            }
            if (Schema::hasColumn('boms', 'paper_gsm')) {
                $table->dropColumn('paper_gsm');
            }
            if (Schema::hasColumn('boms', 'layers')) {
                $table->dropColumn('layers');
            }
            if (Schema::hasColumn('boms', 'division_factor')) {
                $table->dropColumn('division_factor');
            }
            if (Schema::hasColumn('boms', 'cut_length_inch')) {
                $table->dropColumn('cut_length_inch');
            }
            if (Schema::hasColumn('boms', 'cut_width_inch')) {
                $table->dropColumn('cut_width_inch');
            }
            if (Schema::hasColumn('boms', 'grh')) {
                $table->dropColumn('grh');
            }
            if (Schema::hasColumn('boms', 'ply')) {
                $table->dropColumn('ply');
            }
            if (Schema::hasColumn('boms', 'print')) {
                $table->dropColumn('print');
            }
            if (Schema::hasColumn('boms', 'per_gram_rate')) {
                $table->dropColumn('per_gram_rate');
            }
            if (Schema::hasColumn('boms', 'multiplication_layer')) {
                $table->dropColumn('multiplication_layer');
            }
            if (Schema::hasColumn('boms', 'formula_constant')) {
                $table->dropColumn('formula_constant');
            }
            if (Schema::hasColumn('boms', 'work_percentage')) {
                $table->dropColumn('work_percentage');
            }
            if (Schema::hasColumn('boms', 'multiplication_method')) {
                $table->dropColumn('multiplication_method');
            }

            // ─── REMOVE CALCULATED FIELDS THAT ARE NOW ON BOMItem ───
            if (Schema::hasColumn('boms', 'calculated_paper_rate')) {
                $table->dropColumn('calculated_paper_rate');
            }
            if (Schema::hasColumn('boms', 'calculated_paper_rate_by_layers')) {
                $table->dropColumn('calculated_paper_rate_by_layers');
            }
            if (Schema::hasColumn('boms', 'calculated_work_amount')) {
                $table->dropColumn('calculated_work_amount');
            }
            if (Schema::hasColumn('boms', 'calculated_net_rate')) {
                $table->dropColumn('calculated_net_rate');
            }
            if (Schema::hasColumn('boms', 'calculated_total_area')) {
                $table->dropColumn('calculated_total_area');
            }
            if (Schema::hasColumn('boms', 'calculated_paper_weight')) {
                $table->dropColumn('calculated_paper_weight');
            }
            if (Schema::hasColumn('boms', 'cartons_per_roll')) {
                $table->dropColumn('cartons_per_roll');
            }
            if (Schema::hasColumn('boms', 'carton_blank_length')) {
                $table->dropColumn('carton_blank_length');
            }
            if (Schema::hasColumn('boms', 'carton_blank_width')) {
                $table->dropColumn('carton_blank_width');
            }
            if (Schema::hasColumn('boms', 'cost_per_roll')) {
                $table->dropColumn('cost_per_roll');
            }
            if (Schema::hasColumn('boms', 'total_paper_weight_per_roll')) {
                $table->dropColumn('total_paper_weight_per_roll');
            }

            // ─── REMOVE COST FIELDS THAT ARE NOW ON BOMItem ───
            if (Schema::hasColumn('boms', 'labor_cost_per_unit')) {
                $table->dropColumn('labor_cost_per_unit');
            }
            if (Schema::hasColumn('boms', 'overhead_cost_per_unit')) {
                $table->dropColumn('overhead_cost_per_unit');
            }

            // ─── ADD NEW COLUMNS ───

            // Work percentage (default 40%)
            if (!Schema::hasColumn('boms', 'work_percentage')) {
                $table->decimal('work_percentage', 5, 2)->default(40)->after('description')
                    ->comment('Work, Overhead & Profit percentage (added once)');
            }

            // Profit margin percentage
            if (!Schema::hasColumn('boms', 'profit_margin_percentage')) {
                $table->decimal('profit_margin_percentage', 5, 2)->default(0)->after('work_percentage')
                    ->comment('Additional markup on top of work percentage');
            }

            // Exchange rate
            if (!Schema::hasColumn('boms', 'exchange_rate')) {
                $table->decimal('exchange_rate', 10, 4)->nullable()->after('profit_margin_percentage')
                    ->comment('USD to AFN conversion rate');
            }

            // Exchange rate updated at
            if (!Schema::hasColumn('boms', 'exchange_rate_updated_at')) {
                $table->timestamp('exchange_rate_updated_at')->nullable()->after('exchange_rate');
            }

            // ─── UPDATE/ADD TOTAL COST FIELDS ───

            // Total material cost USD (including wastage)
            if (Schema::hasColumn('boms', 'total_material_cost_usd')) {
                // Modify existing column to have proper precision
                $table->decimal('total_material_cost_usd', 15, 4)->default(0)->change();
            } else {
                $table->decimal('total_material_cost_usd', 15, 4)->default(0)->after('exchange_rate_updated_at')
                    ->comment('Total material cost in USD including wastage');
            }

            // Total material cost AFN
            if (Schema::hasColumn('boms', 'total_material_cost_afn')) {
                $table->decimal('total_material_cost_afn', 15, 4)->default(0)->change();
            } else {
                $table->decimal('total_material_cost_afn', 15, 4)->default(0)->after('total_material_cost_usd')
                    ->comment('Total material cost in AFN including wastage');
            }

            // Total cost AFN (material + work)
            if (Schema::hasColumn('boms', 'total_cost_afn')) {
                $table->decimal('total_cost_afn', 15, 4)->default(0)->change();
            } else {
                $table->decimal('total_cost_afn', 15, 4)->default(0)->after('total_material_cost_afn')
                    ->comment('Total cost in AFN (material + work)');
            }

            // Selling price AFN
            if (Schema::hasColumn('boms', 'selling_price_afn')) {
                $table->decimal('selling_price_afn', 15, 4)->default(0)->change();
            } else {
                $table->decimal('selling_price_afn', 15, 4)->default(0)->after('total_cost_afn')
                    ->comment('Selling price in AFN (including profit margin)');
            }

            // Profit AFN
            if (Schema::hasColumn('boms', 'profit_afn')) {
                $table->decimal('profit_afn', 15, 4)->default(0)->change();
            } else {
                $table->decimal('profit_afn', 15, 4)->default(0)->after('selling_price_afn')
                    ->comment('Profit in AFN');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boms', function (Blueprint $table) {
            // ─── RESTORE REMOVED COLUMNS ───

            // Formula fields
            if (!Schema::hasColumn('boms', 'formula_type')) {
                $table->string('formula_type')->nullable()->after('code');
            }
            if (Schema::hasColumn('boms', 'formula_type') && !Schema::hasIndex('boms', 'boms_formula_type_index')) {
                $table->index('formula_type');
            }
            if (!Schema::hasColumn('boms', 'length_inch')) {
                $table->decimal('length_inch', 10, 2)->nullable()->after('formula_type');
            }
            if (!Schema::hasColumn('boms', 'width_inch')) {
                $table->decimal('width_inch', 10, 2)->nullable()->after('length_inch');
            }
            if (!Schema::hasColumn('boms', 'height_inch')) {
                $table->decimal('height_inch', 10, 2)->nullable()->after('width_inch');
            }
            if (!Schema::hasColumn('boms', 'reel_length_inch')) {
                $table->decimal('reel_length_inch', 10, 2)->nullable()->after('height_inch');
            }
            if (!Schema::hasColumn('boms', 'reel_height_inch')) {
                $table->decimal('reel_height_inch', 10, 2)->nullable()->after('reel_length_inch');
            }
            if (!Schema::hasColumn('boms', 'paper_gsm')) {
                $table->integer('paper_gsm')->nullable()->after('reel_height_inch');
            }
            if (!Schema::hasColumn('boms', 'layers')) {
                $table->integer('layers')->nullable()->after('paper_gsm');
            }
            if (!Schema::hasColumn('boms', 'division_factor')) {
                $table->decimal('division_factor', 10, 2)->nullable()->after('layers');
            }
            if (!Schema::hasColumn('boms', 'cut_length_inch')) {
                $table->decimal('cut_length_inch', 10, 2)->nullable()->after('division_factor');
            }
            if (!Schema::hasColumn('boms', 'cut_width_inch')) {
                $table->decimal('cut_width_inch', 10, 2)->nullable()->after('cut_length_inch');
            }
            if (!Schema::hasColumn('boms', 'grh')) {
                $table->integer('grh')->nullable()->after('cut_width_inch');
            }
            if (!Schema::hasColumn('boms', 'ply')) {
                $table->integer('ply')->nullable()->after('grh');
            }
            if (!Schema::hasColumn('boms', 'print')) {
                $table->integer('print')->nullable()->after('ply');
            }
            if (!Schema::hasColumn('boms', 'per_gram_rate')) {
                $table->decimal('per_gram_rate', 10, 2)->nullable()->after('print');
            }
            if (!Schema::hasColumn('boms', 'multiplication_layer')) {
                $table->integer('multiplication_layer')->nullable()->after('per_gram_rate');
            }
            if (!Schema::hasColumn('boms', 'formula_constant')) {
                $table->integer('formula_constant')->nullable()->after('multiplication_layer');
            }
            if (!Schema::hasColumn('boms', 'work_percentage')) {
                $table->decimal('work_percentage', 5, 2)->nullable()->after('formula_constant');
            }
            if (!Schema::hasColumn('boms', 'multiplication_method')) {
                $table->string('multiplication_method')->nullable()->after('work_percentage');
            }

            // Calculated fields
            if (!Schema::hasColumn('boms', 'calculated_paper_rate')) {
                $table->decimal('calculated_paper_rate', 15, 8)->nullable()->after('multiplication_method');
            }
            if (!Schema::hasColumn('boms', 'calculated_paper_rate_by_layers')) {
                $table->decimal('calculated_paper_rate_by_layers', 15, 8)->nullable()->after('calculated_paper_rate');
            }
            if (!Schema::hasColumn('boms', 'calculated_work_amount')) {
                $table->decimal('calculated_work_amount', 15, 8)->nullable()->after('calculated_paper_rate_by_layers');
            }
            if (!Schema::hasColumn('boms', 'calculated_net_rate')) {
                $table->decimal('calculated_net_rate', 15, 8)->nullable()->after('calculated_work_amount');
            }
            if (!Schema::hasColumn('boms', 'calculated_total_area')) {
                $table->decimal('calculated_total_area', 15, 2)->nullable()->after('calculated_net_rate');
            }
            if (!Schema::hasColumn('boms', 'calculated_paper_weight')) {
                $table->decimal('calculated_paper_weight', 15, 4)->nullable()->after('calculated_total_area');
            }
            if (!Schema::hasColumn('boms', 'cartons_per_roll')) {
                $table->decimal('cartons_per_roll', 15, 2)->nullable()->after('calculated_paper_weight');
            }
            if (!Schema::hasColumn('boms', 'carton_blank_length')) {
                $table->decimal('carton_blank_length', 15, 2)->nullable()->after('cartons_per_roll');
            }
            if (!Schema::hasColumn('boms', 'carton_blank_width')) {
                $table->decimal('carton_blank_width', 15, 2)->nullable()->after('carton_blank_length');
            }
            if (!Schema::hasColumn('boms', 'cost_per_roll')) {
                $table->decimal('cost_per_roll', 15, 4)->nullable()->after('carton_blank_width');
            }
            if (!Schema::hasColumn('boms', 'total_paper_weight_per_roll')) {
                $table->decimal('total_paper_weight_per_roll', 15, 4)->nullable()->after('cost_per_roll');
            }

            // Cost fields
            if (!Schema::hasColumn('boms', 'labor_cost_per_unit')) {
                $table->decimal('labor_cost_per_unit', 15, 4)->nullable()->after('total_paper_weight_per_roll');
            }
            if (!Schema::hasColumn('boms', 'overhead_cost_per_unit')) {
                $table->decimal('overhead_cost_per_unit', 15, 4)->nullable()->after('labor_cost_per_unit');
            }

            // ─── DROP NEW COLUMNS ───
            if (Schema::hasColumn('boms', 'profit_margin_percentage')) {
                $table->dropColumn('profit_margin_percentage');
            }
            if (Schema::hasColumn('boms', 'exchange_rate_updated_at')) {
                $table->dropColumn('exchange_rate_updated_at');
            }

            // Note: work_percentage and exchange_rate are restored above
            // Note: total cost columns are kept as they existed before
        });
    }
};
