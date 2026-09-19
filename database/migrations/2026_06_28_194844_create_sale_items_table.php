<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('purchase_item_id')->nullable();
            $table->unsignedBigInteger('sale_currency_id')->nullable();

            // Quantity
            $table->decimal('qty', 12, 2)->default(0);

            // Cost from purchase (for tracking profit)
            $table->decimal('cost_per_unit_usd', 18, 4)->default(0);
            $table->decimal('total_cost_usd', 18, 2)->default(0);

            // Sale price in local currency
            $table->decimal('unit_price', 18, 4)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->decimal('discount', 18, 2)->default(0);
            $table->decimal('tax', 18, 2)->default(0);

            // Sale price in USD
            $table->decimal('usd_unit_price', 18, 4)->default(0);
            $table->decimal('usd_total', 18, 2)->default(0);
            $table->decimal('usd_discount', 18, 2)->default(0);
            $table->decimal('usd_tax', 18, 2)->default(0);

            // Exchange rate at time of sale
            $table->decimal('rate', 18, 6)->default(1);

            // Profit/Loss tracking
            $table->decimal('profit_usd', 18, 2)->default(0);
            $table->decimal('profit_percentage', 8, 2)->default(0);

            $table->text('remarks')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
