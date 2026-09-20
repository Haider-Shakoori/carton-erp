<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('categories') || ! Schema::hasTable('products')) {
            return;
        }

        $now = now();

        $paperCategoryId = DB::table('categories')
            ->where('name', 'Paper Materials')
            ->value('id');

        if (! $paperCategoryId) {
            $paperCategoryId = DB::table('categories')->insertGetId([
                'name' => 'Paper Materials',
                'slug' => 'paper-materials',
                'description' => 'Paper materials used in carton manufacturing.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $mixingCategoryId = DB::table('categories')
            ->where('name', 'Mixing Materials')
            ->value('id');

        if (! $mixingCategoryId) {
            $mixingCategoryId = DB::table('categories')->insertGetId([
                'name' => 'Mixing Materials',
                'slug' => 'mixing-materials',
                'description' => 'Glue-mixing ingredients used in corrugated 3D carton production.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $materials = [
            ['name' => 'Test Liner', 'unit' => 'roll', 'category_id' => $paperCategoryId, 'min_stock' => 10],
            ['name' => 'Fluting', 'unit' => 'roll', 'category_id' => $paperCategoryId, 'min_stock' => 10],
            ['name' => 'Kraft Liner', 'unit' => 'roll', 'category_id' => $paperCategoryId, 'min_stock' => 10],
            ['name' => 'Semi Kraft', 'unit' => 'roll', 'category_id' => $paperCategoryId, 'min_stock' => 10],
            ['name' => 'White Liner', 'unit' => 'roll', 'category_id' => $paperCategoryId, 'min_stock' => 10],
            ['name' => 'Box Board', 'unit' => 'roll', 'category_id' => $paperCategoryId, 'min_stock' => 10],
            ['name' => 'Seligate (Glue)', 'unit' => 'kg', 'category_id' => $mixingCategoryId, 'min_stock' => 25],
            ['name' => 'Corn Flour', 'unit' => 'kg', 'category_id' => $mixingCategoryId, 'min_stock' => 25],
            ['name' => 'Borax', 'unit' => 'kg', 'category_id' => $mixingCategoryId, 'min_stock' => 10],
            ['name' => 'Caustic Soda', 'unit' => 'kg', 'category_id' => $mixingCategoryId, 'min_stock' => 10],
        ];

        foreach ($materials as $material) {
            if (DB::table('products')->where('name', $material['name'])->exists()) {
                continue;
            }

            $row = [
                'name' => $material['name'],
                'slug' => Str::slug($material['name']) . '-' . Str::lower(Str::random(6)),
                'unit' => $material['unit'],
                'category_id' => $material['category_id'],
                'description' => 'Client-approved 3D carton raw material - ' . $material['name'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (Schema::hasColumn('products', 'type')) {
                $row['type'] = 'raw_material';
            }

            if (Schema::hasColumn('products', 'min_stock_alert')) {
                $row['min_stock_alert'] = $material['min_stock'];
            }

            DB::table('products')->insert($row);
        }
    }

    public function down(): void
    {
        // Client master-data is intentionally retained on rollback. These
        // materials may already have purchase, inventory or BOM history.
    }
};
