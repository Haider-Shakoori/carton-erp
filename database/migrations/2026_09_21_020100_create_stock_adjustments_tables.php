<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_no')->unique();
            $table->foreignId('stock_reconciliation_id')->nullable()
                ->unique()
                ->constrained('stock_reconciliations')
                ->nullOnDelete();
            $table->date('adjustment_date');
            $table->string('type', 40)->default('reconciliation');
            $table->string('status', 30)->default('posted');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('posted_by')->constrained('users');
            $table->timestamp('posted_at');
            $table->timestamps();
        });

        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')
                ->constrained('stock_adjustments')
                ->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('purchase_item_id')->constrained('purchase_items');
            $table->string('inventory_unit', 30);
            $table->decimal('before_quantity', 18, 6);
            $table->decimal('adjustment_quantity', 18, 6);
            $table->decimal('after_quantity', 18, 6);
            $table->decimal('cost_per_unit_usd', 18, 6)->default(0);
            $table->decimal('adjustment_value_usd', 18, 4)->default(0);
            $table->string('reason_code', 60)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'purchase_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
    }
};
