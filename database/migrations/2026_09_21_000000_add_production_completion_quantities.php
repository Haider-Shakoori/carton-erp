<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('production_orders', 'quantity_manufactured')) {
            Schema::table('production_orders', function (Blueprint $table) {
                $table->decimal('quantity_manufactured', 15, 2)
                    ->nullable()
                    ->after('quantity_planned')
                    ->comment('Total units physically manufactured, including rejected/scrap units.');
            });
        }

        if (! Schema::hasColumn('production_orders', 'quantity_rejected')) {
            Schema::table('production_orders', function (Blueprint $table) {
                $table->decimal('quantity_rejected', 15, 2)
                    ->default(0)
                    ->after('quantity_produced')
                    ->comment('Manufactured units rejected as defective/scrap at completion.');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('production_orders', 'quantity_rejected')) {
            Schema::table('production_orders', function (Blueprint $table) {
                $table->dropColumn('quantity_rejected');
            });
        }

        if (Schema::hasColumn('production_orders', 'quantity_manufactured')) {
            Schema::table('production_orders', function (Blueprint $table) {
                $table->dropColumn('quantity_manufactured');
            });
        }
    }
};
