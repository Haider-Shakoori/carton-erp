<?php
// database/migrations/2026_07_08_000000_fix_qty_available_column.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First, drop the existing column
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('qty_available');
        });

        // Then add it as a normal column (not generated)
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('qty_available', 15, 4)->default(0)->after('qty')
                ->comment('Available quantity for sale/use');
        });

        // Update existing records
        DB::statement("
            UPDATE purchase_items
            SET qty_available = qty - COALESCE(qty_sold, 0) - COALESCE(qty_used, 0) - COALESCE(qty_wasted, 0)
        ");
    }

    public function down(): void
    {
        // Revert back to generated column if needed
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('qty_available');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('qty_available', 15, 4)->virtual()->after('qty')
                ->comment('Available quantity for sale/use');
        });
    }
};
