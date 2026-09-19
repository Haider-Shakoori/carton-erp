<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            $table->decimal('stock_consumption_override', 18, 8)->nullable();
            $table->string('stock_consumption_unit', 20)->default('kg')->after('stock_consumption_override');
        });
    }

    public function down(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            $table->dropColumn(['stock_consumption_override', 'stock_consumption_unit']);
        });
    }
};
