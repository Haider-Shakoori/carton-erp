<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('business_units')) {
            return;
        }

        $cartonId = (int) DB::table('business_units')
            ->where('code', '3d_carton')
            ->value('id');

        if ($cartonId <= 0) {
            return;
        }

        // Business separation was introduced after all of the client's current
        // operational data already existed. That historical dataset belongs to
        // 3D Carton. Syrup Pack intentionally starts with no historical
        // operational records; future records are assigned by the active
        // workspace at creation time.
        foreach ([
            'boms',
            'sales',
            'sale_returns',
            'purchases',
            'production_orders',
            'work_orders',
            'transactions',
            'inventory_transfers',
            'purchase_requests',
            'request_for_quotations',
            'goods_receipts',
            'supplier_invoices',
            'journal_entries',
        ] as $table) {
            if (
                Schema::hasTable($table)
                && Schema::hasColumn($table, 'business_unit_id')
            ) {
                DB::table($table)->update([
                    'business_unit_id' => $cartonId,
                ]);
            }
        }

        if (
            Schema::hasTable('settings')
            && Schema::hasColumn('settings', 'default_business_unit_id')
        ) {
            DB::table('settings')->update([
                'default_business_unit_id' => $cartonId,
            ]);
        }
    }

    public function down(): void
    {
        // Historical ownership cannot be inferred safely once corrected.
    }
};
