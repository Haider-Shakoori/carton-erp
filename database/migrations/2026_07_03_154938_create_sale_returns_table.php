<?php
// database/migrations/2026_07_03_000001_create_sale_returns_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();

            // Reference to original sale
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->string('return_no', 255)->unique()->index();

            // Customer reference (denormalized for quick access)
            $table->foreignId('customer_id')->nullable()->constrained('accounts')->onDelete('set null');

            // Currency reference
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('set null');

            // Dates
            $table->date('return_date')->nullable();
            $table->timestamp('processed_at')->nullable();

            // Status: draft, approved, rejected, processed
            $table->enum('status', ['draft', 'approved', 'rejected', 'processed'])->default('draft');

            // Exchange rate
            $table->decimal('exchange_rate', 18, 6)->default(1.000000);

            // Local currency totals
            $table->decimal('subtotal', 18, 2)->default(0.00);
            $table->decimal('discount_total', 18, 2)->default(0.00);
            $table->decimal('grand_total', 18, 2)->default(0.00);

            // USD totals
            $table->decimal('usd_subtotal', 18, 2)->default(0.00);
            $table->decimal('usd_discount_total', 18, 2)->default(0.00);
            $table->decimal('usd_grand_total', 18, 2)->default(0.00);

            // Return reason and notes
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            // Restocking fee
            $table->decimal('restocking_fee', 18, 2)->default(0.00);
            $table->decimal('usd_restocking_fee', 18, 2)->default(0.00);

            // Refund amount
            $table->decimal('refund_amount', 18, 2)->default(0.00);
            $table->decimal('usd_refund_amount', 18, 2)->default(0.00);

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index(['sale_id', 'status']);
            $table->index('return_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_returns');
    }
};
