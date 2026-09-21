<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_location_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_item_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->unsignedBigInteger('warehouse_location_id')->nullable()->index();
            $table->decimal('quantity', 18, 6)->default(0);
            $table->decimal('quantity_kg', 18, 6)->default(0);
            $table->string('unit', 50)->nullable();
            $table->timestamps();
            $table->foreign('purchase_item_id')->references('id')->on('purchase_items')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('warehouse_location_id')->references('id')->on('warehouse_locations')->nullOnDelete();
            $table->index(['warehouse_id', 'product_id'], 'inventory_location_product_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_location_balances');
    }
};
