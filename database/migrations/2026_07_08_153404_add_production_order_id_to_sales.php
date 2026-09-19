<?php
// database/migrations/2026_07_08_000000_add_production_order_id_to_sales.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Check if column doesn't exist before adding
            if (!Schema::hasColumn('sales', 'production_order_id')) {
                $table->foreignId('production_order_id')->nullable()->after('customer_id')
                    ->constrained('production_orders')->onDelete('set null');
            }

            if (!Schema::hasColumn('sales', 'is_produced')) {
                $table->boolean('is_produced')->default(false)->after('status')
                    ->comment('Whether production has been completed for this sale');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['production_order_id']);
            $table->dropColumn(['production_order_id', 'is_produced']);
        });
    }
};
