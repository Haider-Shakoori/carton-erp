<?php
// database/migrations/2026_07_07_000000_create_production_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('bom_id')->constrained('boms')->onDelete('cascade');

            $table->decimal('quantity_ordered', 15, 2);
            $table->decimal('quantity_produced', 15, 2)->default(0);

            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');

            $table->date('start_date')->nullable();
            $table->date('completion_date')->nullable();

            $table->decimal('total_material_cost', 15, 2)->default(0);
            $table->decimal('total_labor_cost', 15, 2)->default(0);
            $table->decimal('total_overhead_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('start_date');
            $table->index('order_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};
