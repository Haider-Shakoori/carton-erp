<?php
// database/migrations/2026_07_07_000001_create_production_order_materials_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_order_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');

            $table->decimal('required_quantity', 15, 4);
            $table->decimal('available_quantity', 15, 4)->default(0);
            $table->decimal('shortage_quantity', 15, 4)->default(0);
            $table->string('unit', 50)->nullable();

            $table->decimal('cost_per_unit', 15, 4)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);

            $table->foreignId('batch_id')->nullable()->constrained('purchase_items');
            $table->decimal('consumed_quantity', 15, 4)->default(0);

            $table->timestamps();

            $table->index('production_order_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_materials');
    }
};
