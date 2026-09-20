<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_reconciliation_id')
                ->constrained('stock_reconciliations')
                ->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('purchase_item_id')->constrained('purchase_items');

            $table->string('batch_no')->nullable();
            $table->string('purchase_no')->nullable();
            $table->string('inventory_unit', 30);
            $table->decimal('system_quantity', 18, 6);
            $table->decimal('physical_quantity', 18, 6)->nullable();
            $table->decimal('variance_quantity', 18, 6)->nullable();
            $table->decimal('cost_per_unit_usd', 18, 6)->default(0);
            $table->decimal('variance_value_usd', 18, 4)->nullable();

            $table->string('reason_code', 60)->nullable();
            $table->text('notes')->nullable();

            // Used later to detect stock movements that happened after the count snapshot.
            $table->timestamp('batch_updated_at_snapshot')->nullable();
            $table->decimal('batch_native_quantity_snapshot', 18, 6)->nullable();
            $table->decimal('batch_kg_quantity_snapshot', 18, 6)->nullable();

            $table->timestamps();

            $table->unique(
                ['stock_reconciliation_id', 'purchase_item_id'],
                'stock_reconciliation_batch_unique'
            );
            $table->index(['product_id', 'purchase_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reconciliation_items');
    }
};
