<?php
// database/seeders/CategorySeeder.php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ============================================================
        // RAW MATERIALS CATEGORIES (Purchased items)
        // ============================================================
        $rawMaterialCategories = [
            [
                'name' => 'Paper Materials',
                'description' => 'Kraft paper, corrugated paper, cardboard sheets, liner board, duplex board, art paper, and other paper-based materials used in carton manufacturing.',
            ],
            [
                'name' => 'Printing Consumables',
                'description' => 'CMYK printing inks, UV inks, spot colors, varnish, coatings, and other printing consumables.',
            ],
            [
                'name' => 'Adhesives & Glues',
                'description' => 'Starch adhesive, hot melt glue, PVA glue, dextrin adhesive, and other bonding materials.',
            ],
            [
                'name' => 'Packaging Supplies',
                'description' => 'Strapping, corner protectors, tapes, labels, stretch film, and other packaging accessories.',
            ],
            [
                'name' => 'Machine Parts & Consumables',
                'description' => 'Cutting blades, printing plates, rollers, nozzles, and other machine consumables.',
            ],
            [
                'name' => 'Chemicals & Lubricants',
                'description' => 'Machine oil, grease, degreaser, cleaning solvents, and other industrial chemicals.',
            ],
            [
                'name' => 'Mixing Materials',
                'description' => 'Glue-mixing ingredients used in corrugated 3D carton production, including Seligate, corn flour, Borax and Caustic Soda.',
            ],
        ];

        // ============================================================
        // FINISHED GOODS CATEGORIES (BOM Products - Sold)
        // ============================================================
        $finishedGoodCategories = [
            [
                'name' => 'Syrup Boxes',
                'description' => 'Finished carton boxes for syrup packaging including 120ml, 200ml, 250ml, 500ml, and 1000ml sizes.',
            ],
            [
                'name' => 'Pharmaceutical Boxes',
                'description' => 'Carton boxes for pharmaceutical products including tablets, capsules, and liquid medicines.',
            ],
            [
                'name' => 'Custom Cartons',
                'description' => 'Custom-designed carton boxes for various products and specifications.',
            ],
        ];

        // ============================================================
        // SEED ALL CATEGORIES
        // ============================================================
        $allCategories = array_merge($rawMaterialCategories, $finishedGoodCategories);

        foreach ($allCategories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                [
                    'slug' => Str::slug($category['name']),
                    'description' => $category['description'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('✅ ' . Category::count() . ' categories seeded successfully!');

        $rawCount = Category::whereIn('name', [
            'Paper Materials', 'Printing Consumables', 'Adhesives & Glues',
            'Packaging Supplies', 'Machine Parts & Consumables', 'Chemicals & Lubricants',
            'Mixing Materials'
        ])->count();

        $finishedCount = Category::whereIn('name', [
            'Syrup Boxes', 'Pharmaceutical Boxes', 'Custom Cartons'
        ])->count();

        $this->command->info('📦 Raw Material Categories: ' . $rawCount);
        $this->command->info('📦 Finished Goods Categories: ' . $finishedCount);
    }
}
