<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sale_items', 'ordered_qty')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->decimal('ordered_qty', 12, 2)->nullable()->after('qty');
            });
        }

        // Existing rows currently still carry the original order quantity in qty.
        // Freeze that value before any future production completion can resize
        // the invoice quantity.
        DB::table('sale_items')
            ->whereNull('ordered_qty')
            ->update(['ordered_qty' => DB::raw('qty')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('sale_items', 'ordered_qty')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->dropColumn('ordered_qty');
            });
        }
    }
};
