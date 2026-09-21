<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_no', 80)->unique();
            $table->foreignId('from_warehouse_id')->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->constrained('warehouses');
            $table->string('status', 30)->default('draft')->index();
            $table->text('reason');
            $table->unsignedBigInteger('requested_by')->nullable()->index();
            $table->unsignedBigInteger('posted_by')->nullable()->index();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->foreign('requested_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('posted_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->unsignedBigInteger('purchase_item_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('from_location_id')->nullable();
            $table->unsignedBigInteger('to_location_id')->nullable();
            $table->decimal('quantity', 18, 6)->default(0);
            $table->decimal('quantity_kg', 18, 6)->default(0);
            $table->string('unit', 50)->nullable();
            $table->decimal('unit_cost_usd', 18, 6)->default(0);
            $table->timestamps();
            $table->foreign('purchase_item_id')->references('id')->on('purchase_items');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('from_location_id')->references('id')->on('warehouse_locations')->nullOnDelete();
            $table->foreign('to_location_id')->references('id')->on('warehouse_locations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};
