<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add indexes for the two most frequent per-row lookup patterns that were
     * previously unindexed:
     *
     *   - purchase_expenses.purchase_id: hot query "SELECT SUM(usd_amount) FROM
     *     purchase_expenses WHERE purchase_id = ?" (stock, sale, batch costing).
     *   - sale_items.product_id: hot query "SELECT * FROM sale_items WHERE
     *     product_id = ?" (stock-out / product movement reports).
     *
     * Both are non-unique FKs that match real frequent WHERE/JOIN patterns,
     * are not redundant with existing indexes, and do not change data semantics.
     */
    public function up(): void
    {
        Schema::table('purchase_expenses', function (Blueprint $table) {
            $table->index('purchase_id', 'purchase_expenses_purchase_id_index');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->index('product_id', 'sale_items_product_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('sale_items_product_id_index');
        });

        Schema::table('purchase_expenses', function (Blueprint $table) {
            $table->dropIndex('purchase_expenses_purchase_id_index');
        });
    }
};
