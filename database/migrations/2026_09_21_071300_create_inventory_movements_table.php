<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_item_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->unsignedBigInteger('warehouse_location_id')->nullable()->index();
            $table->string('movement_type', 50)->index();
            $table->string('direction', 10);
            $table->decimal('quantity', 18, 6)->default(0);
            $table->decimal('quantity_kg', 18, 6)->default(0);
            $table->string('unit', 50)->nullable();
            $table->decimal('unit_cost_usd', 18, 6)->nullable();
            $table->string('reference_type', 100)->nullable()->index();
            $table->unsignedBigInteger('reference_id')->nullable()->index();
            $table->unsignedBigInteger('reversal_of_id')->nullable()->index();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->timestamp('occurred_at')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('purchase_item_id')->references('id')->on('purchase_items')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('warehouse_location_id')->references('id')->on('warehouse_locations')->nullOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['reference_type', 'reference_id'], 'inventory_movement_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
