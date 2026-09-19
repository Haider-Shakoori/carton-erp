<?php
// database/migrations/2026_07_21_000000_add_price_adjustment_fields_to_sale_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // Price Adjustment Fields
            $table->decimal('base_price', 15, 2)->default(0)->after('unit_price');
            $table->decimal('discount_percentage', 5, 2)->default(0)->after('base_price');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('discount_percentage');
            $table->decimal('final_price', 15, 2)->default(0)->after('discount_amount');
            $table->enum('price_adjustment_type', ['none', 'manual', 'discount_percent', 'discount_fixed'])->default('none')->after('final_price');
            $table->text('price_adjustment_note')->nullable()->after('price_adjustment_type');

            // Original values for tracking
            $table->decimal('original_unit_price', 15, 2)->nullable()->after('price_adjustment_note');
            $table->decimal('original_total', 15, 2)->nullable()->after('original_unit_price');
            $table->decimal('original_profit_usd', 15, 2)->nullable()->after('original_total');

            // Indexes
            $table->index('price_adjustment_type');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn([
                'base_price',
                'discount_percentage',
                'discount_amount',
                'final_price',
                'price_adjustment_type',
                'price_adjustment_note',
                'original_unit_price',
                'original_total',
                'original_profit_usd',
            ]);
        });
    }
};
