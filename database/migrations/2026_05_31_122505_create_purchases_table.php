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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_no', 255)->index();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->date('purchase_date')->nullable();
            $table->enum('status', ['draft', 'shipping', 'arrived'])->default('draft');
            $table->date('arrival_date')->nullable();
            $table->decimal('exchange_rate', 18, 6)->default(1.000000);
            $table->decimal('subtotal', 18, 2)->default(0.00);
            $table->decimal('expense_total', 18, 2)->default(0.00);
            $table->decimal('shipping_cost', 18, 2)->default(0.00);
            $table->decimal('grand_total', 18, 2)->default(0.00);
            $table->decimal('usd_subtotal', 18, 2)->default(0.00);
            $table->decimal('usd_expense_total', 18, 2)->default(0.00);
            $table->decimal('usd_shipping_cost', 18, 2)->default(0.00);
            $table->decimal('usd_grand_total', 18, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
