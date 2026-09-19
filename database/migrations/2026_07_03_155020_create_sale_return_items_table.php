<?php
// database/migrations/2026_07_03_000002_create_sale_return_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('sale_return_id')->constrained()->onDelete('cascade');
            $table->foreignId('sale_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('sale_item_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('purchase_item_id')->nullable()->constrained()->onDelete('set null');

            // Quantity returned
            $table->decimal('qty_returned', 12, 2)->default(0);

            // Original sale details (denormalized for history)
            $table->decimal('original_qty', 12, 2)->default(0);
            $table->decimal('original_unit_price', 18, 4)->default(0);
            $table->decimal('original_usd_unit_price', 18, 4)->default(0);

            // Returned item pricing
            $table->decimal('unit_price', 18, 4)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->decimal('discount', 18, 2)->default(0);

            // USD pricing
            $table->decimal('usd_unit_price', 18, 4)->default(0);
            $table->decimal('usd_total', 18, 2)->default(0);
            $table->decimal('usd_discount', 18, 2)->default(0);

            // Exchange rate at time of return
            $table->decimal('rate', 18, 6)->default(1);

            // Cost tracking (for profit/loss recalculation)
            $table->decimal('cost_per_unit_usd', 18, 4)->default(0);
            $table->decimal('total_cost_usd', 18, 2)->default(0);

            // Return reason
            $table->enum('reason', [
                'damaged',
                'defective',
                'wrong_item',
                'wrong_quantity',
                'customer_cancelled',
                'quality_issue',
                'other'
            ])->default('other');

            $table->text('reason_notes')->nullable();

            // Condition of returned item
            $table->enum('condition', ['new', 'used', 'damaged'])->default('used');

            // Restock status
            $table->boolean('restocked')->default(false);
            $table->timestamp('restocked_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['sale_return_id', 'product_id']);
            $table->index('sale_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
    }
};
