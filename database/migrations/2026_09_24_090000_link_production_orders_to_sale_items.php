<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_id')->nullable()->after('bom_id');
            $table->unsignedBigInteger('sale_item_id')->nullable()->after('sale_id');
            $table->index('sale_id', 'production_orders_sale_id_idx');
            $table->index('sale_item_id', 'production_orders_sale_item_id_idx');
        });

        DB::table('production_orders')
            ->select(['id', 'product_id', 'bom_id', 'notes'])
            ->orderBy('id')
            ->chunkById(200, function ($orders): void {
                foreach ($orders as $order) {
                    $saleId = null;
                    $saleItemId = null;

                    if (preg_match('/SaleItem ID:\s*(\d+)/i', (string) $order->notes, $matches)) {
                        $candidate = DB::table('sale_items')
                            ->where('id', (int) $matches[1])
                            ->first(['id', 'sale_id']);

                        if ($candidate) {
                            $saleItemId = (int) $candidate->id;
                            $saleId = (int) $candidate->sale_id;
                        }
                    }

                    if (! $saleItemId) {
                        $consumption = DB::table('production_material_consumptions')
                            ->where('production_order_id', $order->id)
                            ->whereNotNull('sale_item_id')
                            ->orderBy('id')
                            ->first(['sale_id', 'sale_item_id']);

                        if ($consumption) {
                            $saleItemId = (int) $consumption->sale_item_id;
                            $saleId = $consumption->sale_id ? (int) $consumption->sale_id : null;
                        }
                    }

                    if (! $saleId) {
                        $consumptionSaleId = DB::table('production_material_consumptions')
                            ->where('production_order_id', $order->id)
                            ->whereNotNull('sale_id')
                            ->orderBy('id')
                            ->value('sale_id');

                        if ($consumptionSaleId) {
                            $saleId = (int) $consumptionSaleId;
                        }
                    }

                    if (! $saleId) {
                        $legacySaleId = DB::table('sales')
                            ->where('production_order_id', $order->id)
                            ->value('id');

                        if ($legacySaleId) {
                            $saleId = (int) $legacySaleId;
                        }
                    }

                    if ($saleId && ! $saleItemId) {
                        $query = DB::table('sale_items')
                            ->where('sale_id', $saleId)
                            ->where('product_id', $order->product_id);

                        if ($order->bom_id) {
                            $query->where('bom_id', $order->bom_id);
                        }

                        $matches = $query->orderBy('id')->pluck('id');

                        if ($matches->count() === 1) {
                            $saleItemId = (int) $matches->first();
                        }
                    }

                    if ($saleId || $saleItemId) {
                        DB::table('production_orders')
                            ->where('id', $order->id)
                            ->update([
                                'sale_id' => $saleId,
                                'sale_item_id' => $saleItemId,
                            ]);
                    }
                }
            });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->foreign('sale_id', 'production_orders_sale_fk')
                ->references('id')->on('sales')->nullOnDelete();
            $table->foreign('sale_item_id', 'production_orders_sale_item_fk')
                ->references('id')->on('sale_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropForeign('production_orders_sale_item_fk');
            $table->dropForeign('production_orders_sale_fk');
            $table->dropIndex('production_orders_sale_item_id_idx');
            $table->dropIndex('production_orders_sale_id_idx');
            $table->dropColumn(['sale_item_id', 'sale_id']);
        });
    }
};
