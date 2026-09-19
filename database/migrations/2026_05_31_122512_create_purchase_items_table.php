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
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained();
            $table->foreignId('purchase_currency_id')->nullable()->constrained('currencies');

            // --- FIXED: Changed to decimal to match tracking quantities for the storedAs formula ---
            $table->decimal('qty', 12, 2)->default(0); 
            $table->text('remarks')->nullable();
            $table->string('batch_no', 100)->nullable();

            // --- OPERATIONAL TRACKING QUANTITIES ---
            $table->decimal('qty_sold', 12, 2)->default(0);
            $table->decimal('qty_returned', 12, 2)->default(0);
            $table->decimal('qty_wasted', 12, 2)->default(0);

            // Automated virtual column for remaining stock in this batch
            $table->decimal('qty_available', 12, 2)
                ->storedAs('qty - qty_sold + qty_returned - qty_wasted');

            // --- LOCAL CURRENCY COLUMNS ---
            $table->decimal('unit_price', 18, 4)->default(0);      // e.g., ¥1,500
            $table->decimal('total', 18, 2)->default(0);           // e.g., ¥600,000 (qty * unit_price)
            $table->decimal('rate', 18, 6)->default(1);            // e.g., 7.2 (Exchange rate to USD)

            // New Column: Total allocated expense for this specific item line in local currency
            $table->decimal('expense', 18, 2)->default(0);         // e.g., ¥7,882.13 (Derived from USD Expense * Rate)
            $table->decimal('expense_per_item', 18, 4)->default(0); // e.g., ¥19.71

            // --- USD COLUMNS ---
            $table->decimal('usd_unit_price', 18, 4)->default(0);  // e.g., $208.33 (unit_price / rate)
            $table->decimal('usd_total', 18, 2)->default(0);       // e.g., $83,333.33 (total / rate)

            // From your UI data: Expense ($1,094.74) and Expense/Item ($2.74)
            $table->decimal('usd_expense', 18, 2)->default(0);     // e.g., $1,094.74
            $table->decimal('usd_expense_per_item', 18, 4)->default(0); // e.g., $2.74

            // New Column: Final total cost including expenses in USD
            $table->decimal('usd_total_cost', 18, 2)->default(0);  // e.g., $84,428.07 (usd_total + usd_expense)
            $table->decimal('usd_cost_per_item', 18, 4)->default(0); // e.g., $211.07 (usd_total_cost / qty)

            // --- REALIZED REVENUE SNAPSHOTS FOR THIS BATCH ---
            $table->decimal('sale_price_local', 18, 2)->default(0);
            $table->decimal('sale_discount_local', 18, 2)->default(0);
            $table->decimal('sale_price_usd', 18, 2)->default(0);
            $table->decimal('sale_discount_usd', 18, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};