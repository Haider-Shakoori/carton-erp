<?php
// database/migrations/2026_07_18_000007_add_profit_afn_to_sale_items.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'profit_afn')) {
                $table->decimal('profit_afn', 15, 4)->default(0)->after('profit_usd');
            }
            if (!Schema::hasColumn('sale_items', 'profit_percentage')) {
                $table->decimal('profit_percentage', 8, 2)->default(0)->after('profit_afn');
            }
        });
    }

    public function down()
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['profit_afn', 'profit_percentage']);
        });
    }
};
