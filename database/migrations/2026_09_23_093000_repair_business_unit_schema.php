<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $operationalTables = [
        'sales',
        'sale_returns',
        'purchases',
        'production_orders',
        'work_orders',
        'boms',
        'transactions',
        'warehouses',
        'inventory_transfers',
        'purchase_requests',
        'request_for_quotations',
        'goods_receipts',
        'supplier_invoices',
        'journal_entries',
    ];

    public function up(): void
    {
        $this->ensureBusinessUnitsTable();
        $this->ensureSettingsColumns();
        $this->ensureRequiredUnits();
        $this->ensureUserAccessTable();
        $this->ensureOperationalColumns();
        $this->repairDefaultBusinessUnit();
    }

    private function ensureBusinessUnitsTable(): void
    {
        if (Schema::hasTable('business_units')) {
            return;
        }

        Schema::create('business_units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('icon', 100)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    private function ensureSettingsColumns(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        if (! Schema::hasColumn('settings', 'separate_business_units_enabled')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->boolean('separate_business_units_enabled')->default(false);
            });
        }

        if (! Schema::hasColumn('settings', 'default_business_unit_id')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->foreignId('default_business_unit_id')
                    ->nullable()
                    ->constrained('business_units')
                    ->nullOnDelete();
            });
        }
    }

    private function ensureRequiredUnits(): void
    {
        $now = now();

        $units = [
            [
                'code' => '3d_carton',
                'name' => '3D Carton',
                'icon' => 'bi-box-seam',
                'description' => '3D corrugated carton business operations.',
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'code' => 'syrup_pack',
                'name' => 'Syrup Pack',
                'icon' => 'bi-box2-heart',
                'description' => 'Syrup packaging business operations.',
                'is_active' => true,
                'sort_order' => 20,
            ],
        ];

        foreach ($units as $unit) {
            $existingId = DB::table('business_units')
                ->where('code', $unit['code'])
                ->value('id');

            if ($existingId) {
                DB::table('business_units')
                    ->where('id', $existingId)
                    ->update([
                        'name' => $unit['name'],
                        'icon' => $unit['icon'],
                        'description' => $unit['description'],
                        'is_active' => $unit['is_active'],
                        'sort_order' => $unit['sort_order'],
                        'updated_at' => $now,
                    ]);

                continue;
            }

            DB::table('business_units')->insert([
                ...$unit,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function ensureUserAccessTable(): void
    {
        if (Schema::hasTable('business_unit_user') || ! Schema::hasTable('users')) {
            return;
        }

        Schema::create('business_unit_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['business_unit_id', 'user_id']);
            $table->index(['user_id', 'is_default']);
        });
    }

    private function ensureOperationalColumns(): void
    {
        foreach ($this->operationalTables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'business_unit_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('business_unit_id')
                    ->nullable()
                    ->constrained('business_units')
                    ->nullOnDelete();
            });
        }
    }

    private function repairDefaultBusinessUnit(): void
    {
        if (
            ! Schema::hasTable('settings')
            || ! Schema::hasColumn('settings', 'default_business_unit_id')
        ) {
            return;
        }

        $cartonId = DB::table('business_units')
            ->where('code', '3d_carton')
            ->value('id');

        if (! $cartonId) {
            return;
        }

        DB::table('settings')
            ->where(function ($query) {
                $query->whereNull('default_business_unit_id')
                    ->orWhereNotIn(
                        'default_business_unit_id',
                        DB::table('business_units')->select('id')
                    );
            })
            ->update(['default_business_unit_id' => $cartonId]);
    }

    public function down(): void
    {
        // Recovery migration: intentionally non-destructive on rollback.
    }
};
