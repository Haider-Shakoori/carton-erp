<?php

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
        Schema::create('production_material_consumptions', function (Blueprint $table) {
            $table->id();

            // Foreign-key columns - use unsignedBigInteger to prevent auto-indexing
            $table->unsignedBigInteger('production_order_id');
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('sale_item_id')->nullable();
            $table->unsignedBigInteger('material_id');
            $table->unsignedBigInteger('purchase_item_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            // Consumption quantities
            $table->decimal('planned_quantity', 18, 4)->default(0);
            $table->decimal('actual_quantity', 18, 4)->default(0);
            $table->decimal('wastage_quantity', 18, 4)->default(0);
            $table->string('unit', 50)->nullable();

            // Exact inventory-batch costs
            $table->decimal('cost_per_unit_usd', 18, 6)->default(0);
            $table->decimal('cost_per_unit_afn', 18, 6)->default(0);
            $table->decimal('total_cost_usd', 18, 4)->default(0);
            $table->decimal('total_cost_afn', 18, 4)->default(0);
            $table->decimal('wastage_cost_usd', 18, 4)->default(0);
            $table->decimal('wastage_cost_afn', 18, 4)->default(0);

            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            // Define foreign keys with short names
            $table->foreign('production_order_id', 'pmc_prod_order_fk')
                ->references('id')
                ->on('production_orders')
                ->cascadeOnDelete();

            $table->foreign('sale_id', 'pmc_sale_fk')
                ->references('id')
                ->on('sales')
                ->nullOnDelete();

            $table->foreign('sale_item_id', 'pmc_sale_item_fk')
                ->references('id')
                ->on('sale_items')
                ->nullOnDelete();

            $table->foreign('material_id', 'pmc_material_fk')
                ->references('id')
                ->on('products')
                ->restrictOnDelete();

            $table->foreign('purchase_item_id', 'pmc_purchase_item_fk')
                ->references('id')
                ->on('purchase_items')
                ->nullOnDelete();

            $table->foreign('created_by', 'pmc_created_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Define indexes with short names
            $table->index('production_order_id', 'pmc_prod_order_idx');
            $table->index('sale_id', 'pmc_sale_idx');
            $table->index('sale_item_id', 'pmc_sale_item_idx');
            $table->index('material_id', 'pmc_material_idx');
            $table->index('purchase_item_id', 'pmc_purchase_item_idx');
            $table->index('consumed_at', 'pmc_consumed_at_idx');

            // Composite indexes
            $table->index(['sale_id', 'production_order_id'], 'pmc_sale_prod_idx');
            $table->index(['material_id', 'purchase_item_id'], 'pmc_material_purchase_idx');
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->decimal('actual_labour_cost_afn', 18, 4)
                ->default(0)
                ->after('id');

            $table->decimal('actual_overhead_cost_afn', 18, 4)
                ->default(0)
                ->after('actual_labour_cost_afn');

            $table->decimal('other_direct_cost_afn', 18, 4)
                ->default(0)
                ->after('actual_overhead_cost_afn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_material_consumptions');

        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropColumn([
                'actual_labour_cost_afn',
                'actual_overhead_cost_afn',
                'other_direct_cost_afn',
            ]);
        });
    }
};
