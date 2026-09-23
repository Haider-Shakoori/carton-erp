<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('production_orders', 'sale_id')) {
                $table->foreignId('sale_id')
                    ->nullable()
                    ->after('bom_id')
                    ->constrained('sales')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('production_orders', 'sale_item_id')) {
                $table->foreignId('sale_item_id')
                    ->nullable()
                    ->after('sale_id')
                    ->constrained('sale_items')
                    ->nullOnDelete();
            }
        });

        // 1. Exact sale-item linkage embedded in legacy production notes.
        DB::table('production_orders')
            ->select(['id', 'notes'])
            ->whereNotNull('notes')
            ->orderBy('id')
            ->chunkById(200, function ($orders): void {
                foreach ($orders as $order) {
                    if (! preg_match('/SaleItem ID:\s*(\d+)/i', (string) $order->notes, $matches)) {
                        continue;
                    }

                    $saleItem = DB::table('sale_items')
                        ->where('id', (int) $matches[1])
                        ->first(['id', 'sale_id']);

                    if (! $saleItem) {
                        continue;
                    }

                    DB::table('production_orders')
                        ->where('id', $order->id)
                        ->update([
                            'sale_id' => (int) $saleItem->sale_id,
                            'sale_item_id' => (int) $saleItem->id,
                        ]);
                }
            });

        // 2. Production material consumptions already carry sale/sale-item lineage.
        if (Schema::hasTable('production_material_consumptions')) {
            $lineage = DB::table('production_material_consumptions')
                ->whereNotNull('production_order_id')
                ->selectRaw(
                    'production_order_id, MAX(sale_id) AS sale_id, MAX(sale_item_id) AS sale_item_id'
                )
                ->groupBy('production_order_id')
                ->get();

            foreach ($lineage as $row) {
                $updates = [];

                if ($row->sale_id) {
                    $updates['sale_id'] = (int) $row->sale_id;
                }

                if ($row->sale_item_id) {
                    $updates['sale_item_id'] = (int) $row->sale_item_id;
                }

                if ($updates !== []) {
                    DB::table('production_orders')
                        ->where('id', $row->production_order_id)
                        ->update($updates);
                }
            }
        }

        // 3. Preserve the legacy sales.production_order_id relationship as a
        // sale-level fallback for old records that predate item-level linkage.
        DB::table('sales')
            ->whereNotNull('production_order_id')
            ->select(['id', 'production_order_id'])
            ->orderBy('id')
            ->chunkById(200, function ($sales): void {
                foreach ($sales as $sale) {
                    DB::table('production_orders')
                        ->where('id', $sale->production_order_id)
                        ->whereNull('sale_id')
                        ->update(['sale_id' => (int) $sale->id]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table): void {
            if (Schema::hasColumn('production_orders', 'sale_item_id')) {
                $table->dropConstrainedForeignId('sale_item_id');
            }

            if (Schema::hasColumn('production_orders', 'sale_id')) {
                $table->dropConstrainedForeignId('sale_id');
            }
        });
    }
};
