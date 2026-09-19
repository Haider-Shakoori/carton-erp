<?php
// database/migrations/2026_06_28_000001_create_sales_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            // Primary and identifiers
            $table->id();
            $table->string('sale_no', 255)->index();
            $table->timestamp('deleted_at_confirmed')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->text('deletion_reason')->nullable();
            $table->json('deletion_data')->nullable(); // Store snapshot of deleted data

            // Foreign keys
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();

            // Dates
            $table->date('sale_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->timestamp('confirmed_at')->nullable();

            // Status
            $table->enum('status', ['draft', 'confirmed', 'shipped', 'delivered'])->default('draft');

            // Exchange rate
            $table->decimal('exchange_rate', 18, 6)->default(1.000000);

            // Local currency totals
            $table->decimal('subtotal', 18, 2)->default(0.00);
            $table->decimal('discount_total', 18, 2)->default(0.00);
            $table->decimal('tax_total', 18, 2)->default(0.00);
            $table->decimal('shipping_cost', 18, 2)->default(0.00);
            $table->decimal('grand_total', 18, 2)->default(0.00);

            // USD totals
            $table->decimal('usd_subtotal', 18, 2)->default(0.00);
            $table->decimal('usd_discount_total', 18, 2)->default(0.00);
            $table->decimal('usd_tax_total', 18, 2)->default(0.00);
            $table->decimal('usd_shipping_cost', 18, 2)->default(0.00);
            $table->decimal('usd_grand_total', 18, 2)->default(0.00);

            // Payment fields
            $table->decimal('advance_payment', 18, 2)->default(0);
            $table->decimal('usd_advance_payment', 18, 2)->default(0);
            $table->decimal('due_amount', 18, 2)->default(0);
            $table->decimal('usd_due_amount', 18, 2)->default(0);

            // Addresses and notes
            $table->text('notes')->nullable();
            $table->text('shipping_address')->nullable();
            $table->text('billing_address')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
