<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->boolean('is_receiving')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['warehouse_id', 'code']);
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->foreignId('warehouse_location_id')
                ->nullable()
                ->constrained('warehouse_locations')
                ->nullOnDelete();
        });

        Schema::create('inventory_location_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_item_id')->constrained('purchase_items')->cascadeOnDelete();
            $table->foreignId('warehouse_location_id')->constrained('warehouse_locations')->restrictOnDelete();
            $table->enum('condition_status', ['available', 'blocked', 'damaged'])->default('available');
            $table->decimal('quantity', 18, 6)->default(0);
            $table->string('unit', 30)->default('unit');
            $table->timestamps();

            $table->unique(
                ['purchase_item_id', 'warehouse_location_id', 'condition_status'],
                'inventory_location_balance_unique'
            );
            $table->index(['warehouse_location_id', 'condition_status']);
        });

        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
            $table->string('transfer_no')->unique();
            $table->foreignId('from_location_id')->constrained('warehouse_locations')->restrictOnDelete();
            $table->foreignId('to_location_id')->constrained('warehouse_locations')->restrictOnDelete();
            $table->enum('status', ['draft', 'approved', 'completed', 'cancelled'])->default('draft')->index();
            $table->text('reason')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 6);
            $table->string('unit', 30);
            $table->timestamps();

            $table->unique(
                ['inventory_transfer_id', 'purchase_item_id'],
                'inventory_transfer_item_unique'
            );
        });

        $now = now();
        $units = DB::table('business_units')->get(['id', 'code']);

        $warehouseRows = [[
            'business_unit_id' => null,
            'code' => 'WH-SHARED',
            'name' => 'Shared Main Warehouse',
            'is_default' => true,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]];

        foreach ($units as $unit) {
            $warehouseRows[] = [
                'business_unit_id' => $unit->id,
                'code' => $unit->code === '3d_carton' ? 'WH-CARTON' : 'WH-SYRUP',
                'name' => $unit->code === '3d_carton' ? '3D Carton Warehouse' : 'Syrup Pack Warehouse',
                'is_default' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('warehouses')->insert($warehouseRows);

        foreach (DB::table('warehouses')->get(['id']) as $warehouse) {
            DB::table('warehouse_locations')->insert([
                'warehouse_id' => $warehouse->id,
                'code' => 'RECEIVING',
                'name' => 'Receiving / Main Stock',
                'is_receiving' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $sharedLocationId = DB::table('warehouse_locations')
            ->join('warehouses', 'warehouses.id', '=', 'warehouse_locations.warehouse_id')
            ->where('warehouses.code', 'WH-SHARED')
            ->where('warehouse_locations.code', 'RECEIVING')
            ->value('warehouse_locations.id');

        if ($sharedLocationId) {
            DB::table('purchase_items')
                ->whereNull('warehouse_location_id')
                ->update(['warehouse_location_id' => $sharedLocationId]);

            DB::table('purchase_items')
                ->orderBy('id')
                ->chunkById(250, function ($items) use ($sharedLocationId, $now) {
                    $rows = [];

                    foreach ($items as $item) {
                        $isRoll = strtolower((string) ($item->unit ?? '')) === 'roll'
                            && (float) ($item->kg_per_roll ?? 0) > 0;
                        $qty = $isRoll
                            ? max((float) ($item->qty_kg_available ?? 0), 0)
                            : max((float) ($item->qty_available ?? 0), 0);

                        if ($qty <= 0) {
                            continue;
                        }

                        $rows[] = [
                            'purchase_item_id' => $item->id,
                            'warehouse_location_id' => $sharedLocationId,
                            'condition_status' => 'available',
                            'quantity' => $qty,
                            'unit' => $isRoll ? 'kg' : (strtolower((string) ($item->unit ?? '')) ?: 'unit'),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if ($rows !== []) {
                        DB::table('inventory_location_balances')->insert($rows);
                    }
                }, 'id');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transfer_items');
        Schema::dropIfExists('inventory_transfers');
        Schema::dropIfExists('inventory_location_balances');

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_location_id');
        });

        Schema::dropIfExists('warehouse_locations');
        Schema::dropIfExists('warehouses');
    }
};
