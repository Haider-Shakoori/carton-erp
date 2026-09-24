<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Client-approved baseline 3D carton raw materials.
 *
 * Idempotent: existing products are left untouched, so re-running the seeder
 * never creates duplicates. The broad ProductSeeder stays disabled.
 */
class ClientCartonRawMaterialSeeder extends Seeder
{
    /**
     * Paper materials use rolls; mixing materials are consumed in kg.
     */
    public const MATERIALS = [
        ['name' => 'Test Liner', 'unit' => 'roll', 'category' => 'paper', 'min_stock' => 10],
        ['name' => 'Fluting', 'unit' => 'roll', 'category' => 'paper', 'min_stock' => 10],
        ['name' => 'Kraft Liner', 'unit' => 'roll', 'category' => 'paper', 'min_stock' => 10],
        ['name' => 'Semi Kraft', 'unit' => 'roll', 'category' => 'paper', 'min_stock' => 10],
        ['name' => 'White Liner', 'unit' => 'roll', 'category' => 'paper', 'min_stock' => 10],
        ['name' => 'Box Board', 'unit' => 'roll', 'category' => 'paper', 'min_stock' => 10],
        ['name' => 'Seligate (Glue)', 'unit' => 'kg', 'category' => 'mixing', 'min_stock' => 25],
        ['name' => 'Corn Flour', 'unit' => 'kg', 'category' => 'mixing', 'min_stock' => 25],
        ['name' => 'Borax', 'unit' => 'kg', 'category' => 'mixing', 'min_stock' => 10],
        ['name' => 'Caustic Soda', 'unit' => 'kg', 'category' => 'mixing', 'min_stock' => 10],
        ['name' => 'Lamination Plastic', 'unit' => 'kg', 'category' => 'finishing', 'min_stock' => 10],
    ];

    public function run(): void
    {
        if (! Schema::hasTable('categories') || ! Schema::hasTable('products')) {
            return;
        }

        $now = now();

        $categoryIds = [
            'paper' => $this->ensureCategory(
                'Paper Materials',
                'Paper materials used in carton manufacturing.',
                $now
            ),
            'mixing' => $this->ensureCategory(
                'Mixing Materials',
                'Glue-mixing ingredients used in corrugated 3D carton production, including Seligate, corn flour, Borax and Caustic Soda.',
                $now
            ),
            'finishing' => $this->ensureCategory(
                'Finishing Materials',
                'Optional finishing consumables used per customer order, including lamination plastic.',
                $now
            ),
        ];

        foreach (self::MATERIALS as $material) {
            $existing = DB::table('products')->where('name', $material['name'])->first();

            if ($existing) {
                // A same-name legacy sample (e.g. an unused finished good) may
                // exist. Align it to the client baseline only when it is not
                // referenced by any purchase, BOM, sale or production record.
                if ($this->isReferenced((int) $existing->id)) {
                    continue;
                }

                DB::table('products')->where('id', $existing->id)->update(
                    $this->baselineAttributes($material, $categoryIds[$material['category']], $now)
                );

                continue;
            }

            DB::table('products')->insert(array_merge(
                ['name' => $material['name']],
                $this->baselineAttributes($material, $categoryIds[$material['category']], $now),
                [
                    'slug' => Str::slug($material['name']) . '-' . Str::lower(Str::random(6)),
                    'description' => 'Client-approved 3D carton raw material - ' . $material['name'],
                    'created_at' => $now,
                ]
            ));
        }
    }

    /**
     * Baseline product attributes shared by create and align paths.
     */
    private function baselineAttributes(array $material, int $categoryId, $now): array
    {
        $attributes = [
            'unit' => $material['unit'],
            'category_id' => $categoryId,
            'is_active' => true,
            'updated_at' => $now,
        ];

        if (Schema::hasColumn('products', 'type')) {
            $attributes['type'] = 'raw_material';
        }

        if (Schema::hasColumn('products', 'min_stock_alert')) {
            $attributes['min_stock_alert'] = $material['min_stock'];
        }

        return $attributes;
    }

    /**
     * True when the product is already used by any transactional record.
     */
    private function isReferenced(int $productId): bool
    {
        $references = [
            ['purchase_items', 'product_id'],
            ['bom_items', 'material_id'],
            ['boms', 'product_id'],
            ['sale_items', 'product_id'],
            ['production_orders', 'product_id'],
            ['production_order_materials', 'product_id'],
            ['production_material_consumptions', 'material_id'],
        ];

        foreach ($references as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            if (DB::table($table)->where($column, $productId)->exists()) {
                return true;
            }
        }

        return false;
    }

    private function ensureCategory(string $name, string $description, $now): int
    {
        $categoryId = DB::table('categories')->where('name', $name)->value('id');

        if ($categoryId) {
            return (int) $categoryId;
        }

        return (int) DB::table('categories')->insertGetId([
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $description,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
