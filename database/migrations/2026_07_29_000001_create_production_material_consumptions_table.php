<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('production_material_consumptions')) {
            $this->createConsumptionTable();
        } else {
            $this->addMissingConsumptionColumns();
            $this->alignMysqlKeysAndIndexes();
        }

        $this->addMissingProductionOrderCostColumns();
    }

    public function down(): void
    {
        // Intentionally non-destructive. The table and production-order columns
        // are owned by the earlier 2026_07_22_113205 migration.
    }

    private function createConsumptionTable(): void
    {
        Schema::create('production_material_consumptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_order_id');
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('sale_item_id')->nullable();
            $table->unsignedBigInteger('material_id');
            $table->unsignedBigInteger('purchase_item_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->decimal('planned_quantity', 18, 4)->default(0);
            $table->decimal('actual_quantity', 18, 4)->default(0);
            $table->decimal('wastage_quantity', 18, 4)->default(0);
            $table->string('unit', 50)->nullable();
            $table->decimal('cost_per_unit_usd', 18, 6)->default(0);
            $table->decimal('cost_per_unit_afn', 18, 6)->default(0);
            $table->decimal('total_cost_usd', 18, 4)->default(0);
            $table->decimal('total_cost_afn', 18, 4)->default(0);
            $table->decimal('wastage_cost_usd', 18, 4)->default(0);
            $table->decimal('wastage_cost_afn', 18, 4)->default(0);
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->foreign('production_order_id', 'pmc_prod_order_fk')
                ->references('id')->on('production_orders')->cascadeOnDelete();
            $table->foreign('sale_id', 'pmc_sale_fk')
                ->references('id')->on('sales')->nullOnDelete();
            $table->foreign('sale_item_id', 'pmc_sale_item_fk')
                ->references('id')->on('sale_items')->nullOnDelete();
            $table->foreign('material_id', 'pmc_material_fk')
                ->references('id')->on('products')->restrictOnDelete();
            $table->foreign('purchase_item_id', 'pmc_purchase_item_fk')
                ->references('id')->on('purchase_items')->nullOnDelete();
            $table->foreign('created_by', 'pmc_created_by_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->index('production_order_id', 'pmc_prod_order_idx');
            $table->index('sale_id', 'pmc_sale_idx');
            $table->index('sale_item_id', 'pmc_sale_item_idx');
            $table->index('material_id', 'pmc_material_idx');
            $table->index('purchase_item_id', 'pmc_purchase_item_idx');
            $table->index('consumed_at', 'pmc_consumed_at_idx');
            $table->index(['sale_id', 'production_order_id'], 'pmc_sale_prod_idx');
            $table->index(['material_id', 'purchase_item_id'], 'pmc_material_purchase_idx');
        });
    }

    private function addMissingConsumptionColumns(): void
    {
        $columns = [
            'production_order_id' => fn (Blueprint $table) => $table->unsignedBigInteger('production_order_id'),
            'sale_id' => fn (Blueprint $table) => $table->unsignedBigInteger('sale_id')->nullable(),
            'sale_item_id' => fn (Blueprint $table) => $table->unsignedBigInteger('sale_item_id')->nullable(),
            'material_id' => fn (Blueprint $table) => $table->unsignedBigInteger('material_id'),
            'purchase_item_id' => fn (Blueprint $table) => $table->unsignedBigInteger('purchase_item_id')->nullable(),
            'created_by' => fn (Blueprint $table) => $table->unsignedBigInteger('created_by')->nullable(),
            'planned_quantity' => fn (Blueprint $table) => $table->decimal('planned_quantity', 18, 4)->default(0),
            'actual_quantity' => fn (Blueprint $table) => $table->decimal('actual_quantity', 18, 4)->default(0),
            'wastage_quantity' => fn (Blueprint $table) => $table->decimal('wastage_quantity', 18, 4)->default(0),
            'unit' => fn (Blueprint $table) => $table->string('unit', 50)->nullable(),
            'cost_per_unit_usd' => fn (Blueprint $table) => $table->decimal('cost_per_unit_usd', 18, 6)->default(0),
            'cost_per_unit_afn' => fn (Blueprint $table) => $table->decimal('cost_per_unit_afn', 18, 6)->default(0),
            'total_cost_usd' => fn (Blueprint $table) => $table->decimal('total_cost_usd', 18, 4)->default(0),
            'total_cost_afn' => fn (Blueprint $table) => $table->decimal('total_cost_afn', 18, 4)->default(0),
            'wastage_cost_usd' => fn (Blueprint $table) => $table->decimal('wastage_cost_usd', 18, 4)->default(0),
            'wastage_cost_afn' => fn (Blueprint $table) => $table->decimal('wastage_cost_afn', 18, 4)->default(0),
            'consumed_at' => fn (Blueprint $table) => $table->timestamp('consumed_at')->nullable(),
            'created_at' => fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ];

        foreach ($columns as $name => $definition) {
            if (!Schema::hasColumn('production_material_consumptions', $name)) {
                Schema::table('production_material_consumptions', $definition);
            }
        }
    }

    private function addMissingProductionOrderCostColumns(): void
    {
        if (!Schema::hasTable('production_orders')) {
            return;
        }

        $columns = [
            'actual_labour_cost_afn',
            'actual_overhead_cost_afn',
            'other_direct_cost_afn',
        ];

        foreach ($columns as $column) {
            if (!Schema::hasColumn('production_orders', $column)) {
                Schema::table('production_orders', function (Blueprint $table) use ($column) {
                    $table->decimal($column, 18, 4)->default(0);
                });
            }
        }
    }

    private function alignMysqlKeysAndIndexes(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $indexes = $this->mysqlIndexes();
        $this->addIndexUnlessEquivalent($indexes, ['production_order_id'], 'pmc_prod_order_idx');
        $this->addIndexUnlessEquivalent($indexes, ['sale_id'], 'pmc_sale_idx');
        $this->addIndexUnlessEquivalent($indexes, ['sale_item_id'], 'pmc_sale_item_idx');
        $this->addIndexUnlessEquivalent($indexes, ['material_id'], 'pmc_material_idx');
        $this->addIndexUnlessEquivalent($indexes, ['purchase_item_id'], 'pmc_purchase_item_idx');
        $this->addIndexUnlessEquivalent($indexes, ['consumed_at'], 'pmc_consumed_at_idx');
        $this->addIndexUnlessEquivalent($indexes, ['sale_id', 'production_order_id'], 'pmc_sale_prod_idx');
        $this->addIndexUnlessEquivalent($indexes, ['material_id', 'purchase_item_id'], 'pmc_material_purchase_idx');

        $foreignKeys = $this->mysqlForeignKeys();
        $this->addForeignKeyUnlessEquivalent(
            $foreignKeys,
            'production_order_id',
            'production_orders',
            'pmc_prod_order_fk',
            'cascade'
        );
        $this->addForeignKeyUnlessEquivalent($foreignKeys, 'sale_id', 'sales', 'pmc_sale_fk', 'null');
        $this->addForeignKeyUnlessEquivalent($foreignKeys, 'sale_item_id', 'sale_items', 'pmc_sale_item_fk', 'null');
        $this->addForeignKeyUnlessEquivalent($foreignKeys, 'material_id', 'products', 'pmc_material_fk', 'restrict');
        $this->addForeignKeyUnlessEquivalent(
            $foreignKeys,
            'purchase_item_id',
            'purchase_items',
            'pmc_purchase_item_fk',
            'null'
        );
        $this->addForeignKeyUnlessEquivalent($foreignKeys, 'created_by', 'users', 'pmc_created_by_fk', 'null');
    }

    private function mysqlIndexes(): array
    {
        return collect(DB::select('SHOW INDEX FROM production_material_consumptions'))
            ->reject(fn ($row) => $row->Key_name === 'PRIMARY')
            ->groupBy('Key_name')
            ->map(fn ($rows) => $rows->sortBy('Seq_in_index')->pluck('Column_name')->values()->all())
            ->values()
            ->all();
    }

    private function mysqlForeignKeys(): array
    {
        return collect(DB::select(
            'SELECT COLUMN_NAME, REFERENCED_TABLE_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL',
            ['production_material_consumptions']
        ))->map(fn ($row) => [$row->COLUMN_NAME, $row->REFERENCED_TABLE_NAME])->all();
    }

    private function addIndexUnlessEquivalent(array &$indexes, array $columns, string $name): void
    {
        if (in_array($columns, $indexes, true)) {
            return;
        }

        Schema::table('production_material_consumptions', function (Blueprint $table) use ($columns, $name) {
            $table->index($columns, $name);
        });
        $indexes[] = $columns;
    }

    private function addForeignKeyUnlessEquivalent(
        array &$foreignKeys,
        string $column,
        string $referencedTable,
        string $name,
        string $deleteAction
    ): void {
        if (in_array([$column, $referencedTable], $foreignKeys, true)) {
            return;
        }

        Schema::table('production_material_consumptions', function (Blueprint $table) use (
            $column,
            $referencedTable,
            $name,
            $deleteAction
        ) {
            $foreign = $table->foreign($column, $name)->references('id')->on($referencedTable);

            match ($deleteAction) {
                'cascade' => $foreign->cascadeOnDelete(),
                'null' => $foreign->nullOnDelete(),
                default => $foreign->restrictOnDelete(),
            };
        });
        $foreignKeys[] = [$column, $referencedTable];
    }
};
