<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sale_items', 'quotation_description')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->text('quotation_description')->nullable()->after('remarks');
            });
        }

        if (! Schema::hasColumn('production_orders', 'quantity_planned')) {
            Schema::table('production_orders', function (Blueprint $table) {
                $table->decimal('quantity_planned', 12, 2)
                    ->nullable()
                    ->after('quantity_ordered')
                    ->comment('Manual production quantity entered when production starts.');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('production_orders', 'quantity_planned')) {
            Schema::table('production_orders', function (Blueprint $table) {
                $table->dropColumn('quantity_planned');
            });
        }

        if (Schema::hasColumn('sale_items', 'quotation_description')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->dropColumn('quotation_description');
            });
        }
    }
};
