<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ClientCartonRawMaterialSeeder extends Seeder
{
    /**
     * Seed the client's baseline carton-manufacturing raw materials.
     *
     * This list is intentionally small and idempotent. It represents the
     * client's current source-of-truth material catalogue; additional raw
     * materials can be added later without changing or duplicating these rows.
     */
    public function run(): void
    {
        $paperCategory = Category::firstOrCreate(
            ['name' => 'Paper Materials'],
            [
                'slug' => 'paper-materials',
                'description' => 'Paper-based raw materials used in corrugated carton manufacturing.',
                'is_active' => true,
            ]
        );

        $adhesiveCategory = Category::firstOrCreate(
            ['name' => 'Adhesives & Glues'],
            [
                'slug' => 'adhesives-glues',
                'description' => 'Raw materials and ingredients used to prepare corrugation adhesive.',
                'is_active' => true,
            ]
        );

        $materials = [
            // Corrugation adhesive / mixing ingredients.
            [
                'name' => 'Seligate (Sodium Silicate)',
                'unit' => 'kg',
                'category_id' => $adhesiveCategory->id,
                'description' => 'Client-specified seligate/sodium silicate used in the corrugation adhesive mixture.',
            ],
            [
                'name' => 'Corn Flour',
                'unit' => 'kg',
                'category_id' => $adhesiveCategory->id,
                'description' => 'Starch/corn flour used as the primary corrugation adhesive ingredient.',
            ],
            [
                'name' => 'Borax',
                'unit' => 'kg',
                'category_id' => $adhesiveCategory->id,
                'description' => 'Borax used in the corrugation adhesive mixture.',
            ],
            [
                'name' => 'Caustic Soda',
                'unit' => 'kg',
                'category_id' => $adhesiveCategory->id,
                'description' => 'Caustic soda used in the corrugation adhesive mixture.',
            ],

            // Client-specified paper materials. GSM and layer count belong to
            // the BOM row, so the base material catalogue remains reusable.
            [
                'name' => 'Test Liner',
                'unit' => 'roll',
                'category_id' => $paperCategory->id,
                'description' => 'Client-specified test liner paper for corrugated carton BOMs.',
            ],
            [
                'name' => 'Fluting',
                'unit' => 'roll',
                'category_id' => $paperCategory->id,
                'description' => 'Client-specified fluting medium for corrugated carton BOMs.',
            ],
            [
                'name' => 'Kraft Liner',
                'unit' => 'roll',
                'category_id' => $paperCategory->id,
                'description' => 'Client-specified kraft liner paper for corrugated carton BOMs.',
            ],
            [
                'name' => 'Semi Kraft',
                'unit' => 'roll',
                'category_id' => $paperCategory->id,
                'description' => 'Client-specified semi-kraft paper for corrugated carton BOMs.',
            ],
            [
                'name' => 'White Liner',
                'unit' => 'roll',
                'category_id' => $paperCategory->id,
                'description' => 'Client-specified white liner paper for corrugated carton BOMs.',
            ],
            [
                'name' => 'Box Board',
                'unit' => 'roll',
                'category_id' => $paperCategory->id,
                'description' => 'Client-specified box board used when required by a carton specification.',
            ],
        ];

        foreach ($materials as $material) {
            Product::firstOrCreate(
                ['name' => $material['name']],
                [
                    'unit' => $material['unit'],
                    'category_id' => $material['category_id'],
                    'type' => Product::TYPE_RAW_MATERIAL,
                    // Do not invent client stock thresholds. They can be set
                    // later from the product screen when operating levels are known.
                    'min_stock_alert' => 0,
                    'is_active' => true,
                    'description' => $material['description'],
                ]
            );
        }

        $this->command?->info('Client carton raw materials seeded successfully.');
    }
}
