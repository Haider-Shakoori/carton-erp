<?php
// database/migrations/2026_07_18_000005_add_bom_id_to_sale_items.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'bom_id')) {
                $table->foreignId('bom_id')->nullable()->after('purchase_item_id')
                    ->constrained('boms')->nullOnDelete();
            }
        });
    }

    public function down()
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['bom_id']);
            $table->dropColumn('bom_id');
        });
    }
};
