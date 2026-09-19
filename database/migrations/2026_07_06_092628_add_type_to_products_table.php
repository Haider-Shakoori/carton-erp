<?php
// database/migrations/2026_01_01_000000_add_type_to_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('type', ['raw_material', 'finished_good', 'equipment', 'service'])
                ->default('finished_good')
                ->after('category_id');

            // Add indexes for better performance
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropIndex(['type']);
        });
    }
};
