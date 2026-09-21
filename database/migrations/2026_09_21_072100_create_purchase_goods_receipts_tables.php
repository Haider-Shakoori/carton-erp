<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->string('receipt_no', 80)->unique();
            $table->string('status', 20)->default('posted')->index();
            $table->timestamp('received_at')->index();
            $table->unsignedBigInteger('received_by')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('received_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('purchase_goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_goods_receipt_id')->constrained('purchase_goods_receipts')->cascadeOnDelete();
            $table->unsignedBigInteger('purchase_item_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->decimal('ordered_quantity', 18, 6)->default(0);
            $table->decimal('received_quantity', 18, 6)->default(0);
            $table->decimal('received_quantity_kg', 18, 6)->default(0);
            $table->string('unit', 50)->nullable();
            $table->timestamps();
            $table->foreign('purchase_item_id')->references('id')->on('purchase_items')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_goods_receipt_items');
        Schema::dropIfExists('purchase_goods_receipts');
    }
};
