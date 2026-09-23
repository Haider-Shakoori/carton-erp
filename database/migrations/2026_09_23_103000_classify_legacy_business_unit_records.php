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

        $cartonId = (int) DB::table('business_units')->where('code', '3d_carton')->value('id');
        $syrupId = (int) DB::table('business_units')->where('code', 'syrup_pack')->value('id');

        if ($cartonId <= 0 || $syrupId <= 0) {
            return;
        }

        $this->classifyLegacyBoms($cartonId, $syrupId);
        $this->inheritFromParent('production_orders', 'bom_id', 'boms');
        $this->inheritFromParent('work_orders', 'production_order_id', 'production_orders');
        $this->inheritSalesFromProduction();
        $this->inheritSalesFromBoms();
        $this->inheritFromParent('sale_returns', 'sale_id', 'sales');

        // Historical procurement records did not contain enough information to
        // distinguish businesses reliably. Put remaining legacy rows in the
        // configured default workspace rather than leaking them into both.
        $defaultId = (int) (
            Schema::hasTable('settings') && Schema::hasColumn('settings', 'default_business_unit_id')
                ? (DB::table('settings')->value('default_business_unit_id') ?: $cartonId)
                : $cartonId
        );

        $this->assignRemaining('purchases', $defaultId);
        $this->assignRemaining('purchase_requests', $defaultId);
        $this->inheritFromParent('request_for_quotations', 'purchase_request_id', 'purchase_requests');
        $this->inheritFromParent('goods_receipts', 'purchase_id', 'purchases');
        $this->inheritFromParent('supplier_invoices', 'purchase_id', 'purchases');
        $this->inheritInventoryTransfers();
        $this->assignRemaining('inventory_transfers', $defaultId);
        $this->assignRemaining('journal_entries', $defaultId);

        $this->inheritTransactions();
        $this->assignRemaining('transactions', $defaultId);

        foreach ([
            'boms',
            'production_orders',
            'work_orders',
            'sales',
            'sale_returns',
            'request_for_quotations',
            'goods_receipts',
            'supplier_invoices',
        ] as $table) {
            $this->assignRemaining($table, $defaultId);
        }

        // WH-SHARED intentionally remains NULL as an internal fallback warehouse.
        // It is hidden by strict workspace scope but can still be used explicitly
        // by inventory services through withoutGlobalScope().
    }

    private function classifyLegacyBoms(int $cartonId, int $syrupId): void
    {
        if (! $this->hasBusinessColumn('boms')) {
            return;
        }

        $syrupBomIds = collect();

        if (Schema::hasTable('products') && Schema::hasTable('categories')) {
            $syrupBomIds = DB::table('boms')
                ->join('products', 'products.id', '=', 'boms.product_id')
                ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
                ->whereNull('boms.business_unit_id')
                ->where(function ($query) {
                    $query->where('categories.name', 'Syrup Boxes')
                        ->orWhere('products.name', 'like', '%syrup%');
                })
                ->pluck('boms.id');
        }

        if (Schema::hasTable('bom_items') && Schema::hasColumn('bom_items', 'formula_type')) {
            $cutRollIds = DB::table('bom_items')
                ->join('boms', 'boms.id', '=', 'bom_items.bom_id')
                ->whereNull('boms.business_unit_id')
                ->where('bom_items.formula_type', 'cut_roll')
                ->pluck('boms.id');

            $syrupBomIds = $syrupBomIds->merge($cutRollIds)->unique()->values();
        }

        if ($syrupBomIds->isNotEmpty()) {
            DB::table('boms')
                ->whereIn('id', $syrupBomIds->all())
                ->whereNull('business_unit_id')
                ->update(['business_unit_id' => $syrupId]);
        }

        DB::table('boms')
            ->whereNull('business_unit_id')
            ->update(['business_unit_id' => $cartonId]);
    }

    private function inheritSalesFromProduction(): void
    {
        if (
            ! $this->hasBusinessColumn('sales')
            || ! Schema::hasColumn('sales', 'production_order_id')
            || ! $this->hasBusinessColumn('production_orders')
        ) {
            return;
        }

        $rows = DB::table('sales')
            ->join('production_orders', 'production_orders.id', '=', 'sales.production_order_id')
            ->whereNull('sales.business_unit_id')
            ->whereNotNull('production_orders.business_unit_id')
            ->get(['sales.id', 'production_orders.business_unit_id']);

        foreach ($rows as $row) {
            DB::table('sales')
                ->where('id', $row->id)
                ->whereNull('business_unit_id')
                ->update(['business_unit_id' => $row->business_unit_id]);
        }
    }

    private function inheritSalesFromBoms(): void
    {
        if (
            ! $this->hasBusinessColumn('sales')
            || ! Schema::hasTable('sale_items')
            || ! Schema::hasColumn('sale_items', 'bom_id')
            || ! $this->hasBusinessColumn('boms')
        ) {
            return;
        }

        $rows = DB::table('sale_items')
            ->join('boms', 'boms.id', '=', 'sale_items.bom_id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereNull('sales.business_unit_id')
            ->whereNotNull('boms.business_unit_id')
            ->orderBy('sale_items.id')
            ->get(['sale_items.sale_id', 'boms.business_unit_id'])
            ->unique('sale_id');

        foreach ($rows as $row) {
            DB::table('sales')
                ->where('id', $row->sale_id)
                ->whereNull('business_unit_id')
                ->update(['business_unit_id' => $row->business_unit_id]);
        }
    }

    private function inheritInventoryTransfers(): void
    {
        if (
            ! $this->hasBusinessColumn('inventory_transfers')
            || ! Schema::hasTable('warehouse_locations')
            || ! $this->hasBusinessColumn('warehouses')
            || ! Schema::hasColumn('inventory_transfers', 'from_location_id')
        ) {
            return;
        }

        $rows = DB::table('inventory_transfers')
            ->join('warehouse_locations', 'warehouse_locations.id', '=', 'inventory_transfers.from_location_id')
            ->join('warehouses', 'warehouses.id', '=', 'warehouse_locations.warehouse_id')
            ->whereNull('inventory_transfers.business_unit_id')
            ->whereNotNull('warehouses.business_unit_id')
            ->get(['inventory_transfers.id', 'warehouses.business_unit_id']);

        foreach ($rows as $row) {
            DB::table('inventory_transfers')
                ->where('id', $row->id)
                ->whereNull('business_unit_id')
                ->update(['business_unit_id' => $row->business_unit_id]);
        }
    }

    private function inheritTransactions(): void
    {
        if (! $this->hasBusinessColumn('transactions')) {
            return;
        }

        foreach ([
            'sales',
            'purchases',
            'production_orders',
            'work_orders',
            'sale_returns',
            'boms',
        ] as $sourceTable) {
            if (! $this->hasBusinessColumn($sourceTable)) {
                continue;
            }

            $rows = DB::table('transactions')
                ->join($sourceTable, $sourceTable.'.id', '=', 'transactions.table_row_id')
                ->whereNull('transactions.business_unit_id')
                ->where('transactions.table_name', $sourceTable)
                ->whereNotNull($sourceTable.'.business_unit_id')
                ->get(['transactions.id', $sourceTable.'.business_unit_id']);

            foreach ($rows as $row) {
                DB::table('transactions')
                    ->where('id', $row->id)
                    ->whereNull('business_unit_id')
                    ->update(['business_unit_id' => $row->business_unit_id]);
            }
        }
    }

    private function inheritFromParent(string $table, string $foreignKey, string $parentTable): void
    {
        if (
            ! $this->hasBusinessColumn($table)
            || ! Schema::hasColumn($table, $foreignKey)
            || ! $this->hasBusinessColumn($parentTable)
        ) {
            return;
        }

        $rows = DB::table($table)
            ->join($parentTable, $parentTable.'.id', '=', $table.'.'.$foreignKey)
            ->whereNull($table.'.business_unit_id')
            ->whereNotNull($parentTable.'.business_unit_id')
            ->get([$table.'.id', $parentTable.'.business_unit_id']);

        foreach ($rows as $row) {
            DB::table($table)
                ->where('id', $row->id)
                ->whereNull('business_unit_id')
                ->update(['business_unit_id' => $row->business_unit_id]);
        }
    }

    private function assignRemaining(string $table, int $businessUnitId): void
    {
        if (! $this->hasBusinessColumn($table) || $businessUnitId <= 0) {
            return;
        }

        DB::table($table)
            ->whereNull('business_unit_id')
            ->update(['business_unit_id' => $businessUnitId]);
    }

    private function hasBusinessColumn(string $table): bool
    {
        return Schema::hasTable($table)
            && Schema::hasColumn($table, 'business_unit_id');
    }

    public function down(): void
    {
        // Data classification is intentionally non-destructive.
    }
};
