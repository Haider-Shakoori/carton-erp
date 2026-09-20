<?php
// database/seeders/ProductSeeder.php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ============================================================
        // GET CATEGORIES (They should exist from CategorySeeder)
        // ============================================================
        $paperCategory = Category::where('name', 'Paper Materials')->first();
        $inkCategory = Category::where('name', 'Printing Consumables')->first();
        $adhesiveCategory = Category::where('name', 'Adhesives & Glues')->first();
        $packagingCategory = Category::where('name', 'Packaging Supplies')->first();
        $machineCategory = Category::where('name', 'Machine Parts & Consumables')->first();
        $chemicalCategory = Category::where('name', 'Chemicals & Lubricants')->first();
        $mixingCategory = Category::where('name', 'Mixing Materials')->first();
        $syrupBoxCategory = Category::where('name', 'Syrup Boxes')->first();
        $pharmaBoxCategory = Category::where('name', 'Pharmaceutical Boxes')->first();
        $customCartonCategory = Category::where('name', 'Custom Cartons')->first();

        // If categories don't exist, create them or skip
        if (!$paperCategory) {
            $this->command->error('Please run CategorySeeder first.');
            return;
        }

        // ============================================================
        // 1. PAPER MATERIALS
        // ============================================================
        $paperMaterials = [
            ['name' => 'Kraft Paper 120 GSM', 'unit' => 'roll', 'min_stock' => 20],
            ['name' => 'Kraft Paper 150 GSM', 'unit' => 'roll', 'min_stock' => 20],
            ['name' => 'Kraft Paper 200 GSM', 'unit' => 'roll', 'min_stock' => 20],
            ['name' => 'Kraft Paper 250 GSM', 'unit' => 'roll', 'min_stock' => 15],
            ['name' => 'Kraft Paper 300 GSM', 'unit' => 'roll', 'min_stock' => 15],
            ['name' => 'Corrugated Paper B-Flute', 'unit' => 'sheet', 'min_stock' => 500],
            ['name' => 'Corrugated Paper C-Flute', 'unit' => 'sheet', 'min_stock' => 500],
            ['name' => 'Corrugated Paper E-Flute', 'unit' => 'sheet', 'min_stock' => 500],
            ['name' => 'Cardboard Sheet 2mm', 'unit' => 'sheet', 'min_stock' => 300],
            ['name' => 'Cardboard Sheet 3mm', 'unit' => 'sheet', 'min_stock' => 300],
            ['name' => 'Cardboard Sheet 5mm', 'unit' => 'sheet', 'min_stock' => 200],
            ['name' => 'Liner Board 150 GSM', 'unit' => 'roll', 'min_stock' => 15],
            ['name' => 'Liner Board 200 GSM', 'unit' => 'roll', 'min_stock' => 15],
            ['name' => 'Liner Board 250 GSM', 'unit' => 'roll', 'min_stock' => 15],
            ['name' => 'Duplex Board 250 GSM', 'unit' => 'sheet', 'min_stock' => 200],
            ['name' => 'Duplex Board 300 GSM', 'unit' => 'sheet', 'min_stock' => 200],
            ['name' => 'Art Paper 300 GSM', 'unit' => 'sheet', 'min_stock' => 100],
            ['name' => 'Art Paper 350 GSM', 'unit' => 'sheet', 'min_stock' => 100],
            ['name' => 'Coated Paper 150 GSM', 'unit' => 'roll', 'min_stock' => 10],
            ['name' => 'Coated Paper 200 GSM', 'unit' => 'roll', 'min_stock' => 10],
            ['name' => 'Recycled Paper Roll', 'unit' => 'roll', 'min_stock' => 10],
            ['name' => 'Testliner Paper', 'unit' => 'roll', 'min_stock' => 10],
            ['name' => 'Fluting Paper', 'unit' => 'roll', 'min_stock' => 10],
            // Client-approved 3D carton paper materials
            ['name' => 'Test Liner', 'unit' => 'roll', 'min_stock' => 10],
            ['name' => 'Fluting', 'unit' => 'roll', 'min_stock' => 10],
            ['name' => 'Kraft Liner', 'unit' => 'roll', 'min_stock' => 10],
            ['name' => 'Semi Kraft', 'unit' => 'roll', 'min_stock' => 10],
            ['name' => 'White Liner', 'unit' => 'roll', 'min_stock' => 10],
            ['name' => 'Box Board', 'unit' => 'roll', 'min_stock' => 10],
        ];

        foreach ($paperMaterials as $material) {
            Product::firstOrCreate(
                ['name' => $material['name']],
                [
                    'slug' => Str::slug($material['name']) . '-' . Str::random(6),
                    'unit' => $material['unit'],
                    'category_id' => $paperCategory->id,
                    'type' => 'raw_material',
                    'min_stock_alert' => $material['min_stock'],
                    'is_active' => true,
                    'description' => 'Paper material for carton manufacturing - ' . $material['name'],
                ]
            );
        }

        // ============================================================
        // 2. PRINTING CONSUMABLES
        // ============================================================
        $printingMaterials = [
            ['name' => 'Printing Ink - Cyan', 'unit' => 'kg', 'min_stock' => 10],
            ['name' => 'Printing Ink - Magenta', 'unit' => 'kg', 'min_stock' => 10],
            ['name' => 'Printing Ink - Yellow', 'unit' => 'kg', 'min_stock' => 10],
            ['name' => 'Printing Ink - Black', 'unit' => 'kg', 'min_stock' => 10],
            ['name' => 'UV Printing Ink - Cyan', 'unit' => 'kg', 'min_stock' => 5],
            ['name' => 'UV Printing Ink - Magenta', 'unit' => 'kg', 'min_stock' => 5],
            ['name' => 'UV Printing Ink - Yellow', 'unit' => 'kg', 'min_stock' => 5],
            ['name' => 'UV Printing Ink - Black', 'unit' => 'kg', 'min_stock' => 5],
            ['name' => 'UV Coating', 'unit' => 'liter', 'min_stock' => 10],
            ['name' => 'Varnish', 'unit' => 'liter', 'min_stock' => 10],
            ['name' => 'Wax Coating', 'unit' => 'kg', 'min_stock' => 10],
            ['name' => 'Lamination Film 50 micron', 'unit' => 'roll', 'min_stock' => 5],
            ['name' => 'Lamination Film 75 micron', 'unit' => 'roll', 'min_stock' => 5],
            ['name' => 'Lamination Film 100 micron', 'unit' => 'roll', 'min_stock' => 5],
            ['name' => 'Flexo Printing Ink - Black', 'unit' => 'kg', 'min_stock' => 5],
            ['name' => 'Flexo Printing Ink - Blue', 'unit' => 'kg', 'min_stock' => 5],
            ['name' => 'Flexo Printing Ink - Red', 'unit' => 'kg', 'min_stock' => 5],
            ['name' => 'Flexo Printing Ink - Green', 'unit' => 'kg', 'min_stock' => 5],
            ['name' => 'Offset Printing Ink - Black', 'unit' => 'kg', 'min_stock' => 5],
            ['name' => 'Offset Printing Ink - Cyan', 'unit' => 'kg', 'min_stock' => 5],
            ['name' => 'Offset Printing Ink - Magenta', 'unit' => 'kg', 'min_stock' => 5],
            ['name' => 'Offset Printing Ink - Yellow', 'unit' => 'kg', 'min_stock' => 5],
            ['name' => 'Water Resistant Coating', 'unit' => 'kg', 'min_stock' => 5],
            ['name' => 'Anti-Slip Coating', 'unit' => 'kg', 'min_stock' => 5],
        ];

        foreach ($printingMaterials as $material) {
            Product::firstOrCreate(
                ['name' => $material['name']],
                [
                    'slug' => Str::slug($material['name']) . '-' . Str::random(6),
                    'unit' => $material['unit'],
                    'category_id' => $inkCategory->id,
                    'type' => 'raw_material',
                    'min_stock_alert' => $material['min_stock'],
                    'is_active' => true,
                    'description' => 'Printing consumable for carton manufacturing - ' . $material['name'],
                ]
            );
        }

        // ============================================================
        // 3. ADHESIVES & GLUES
        // ============================================================
        $adhesiveMaterials = [
            ['name' => 'Starch Adhesive', 'unit' => 'kg', 'min_stock' => 25],
            ['name' => 'Hot Melt Glue', 'unit' => 'kg', 'min_stock' => 20],
            ['name' => 'PVA Glue', 'unit' => 'liter', 'min_stock' => 10],
            ['name' => 'Dextrin Adhesive', 'unit' => 'kg', 'min_stock' => 15],
            ['name' => 'Pressure Sensitive Adhesive', 'unit' => 'kg', 'min_stock' => 10],
            ['name' => 'Synthetic Resin Glue', 'unit' => 'kg', 'min_stock' => 10],
            ['name' => 'Water Based Adhesive', 'unit' => 'kg', 'min_stock' => 15],
            ['name' => 'Solvent Based Adhesive', 'unit' => 'liter', 'min_stock' => 10],
        ];

        foreach ($adhesiveMaterials as $material) {
            Product::firstOrCreate(
                ['name' => $material['name']],
                [
                    'slug' => Str::slug($material['name']) . '-' . Str::random(6),
                    'unit' => $material['unit'],
                    'category_id' => $adhesiveCategory->id,
                    'type' => 'raw_material',
                    'min_stock_alert' => $material['min_stock'],
                    'is_active' => true,
                    'description' => 'Adhesive material for carton manufacturing - ' . $material['name'],
                ]
            );
        }

        // ============================================================
        // 4. MIXING MATERIALS FOR 3D CARTONS
        // ============================================================
        $mixingMaterials = [
            ['name' => 'Seligate (Glue)', 'unit' => 'kg', 'min_stock' => 25],
            ['name' => 'Corn Flour', 'unit' => 'kg', 'min_stock' => 25],
            ['name' => 'Borax', 'unit' => 'kg', 'min_stock' => 10],
            ['name' => 'Caustic Soda', 'unit' => 'kg', 'min_stock' => 10],
        ];

        foreach ($mixingMaterials as $material) {
            Product::firstOrCreate(
                ['name' => $material['name']],
                [
                    'slug' => Str::slug($material['name']) . '-' . Str::random(6),
                    'unit' => $material['unit'],
                    'category_id' => ($mixingCategory ?: $chemicalCategory)->id,
                    'type' => 'raw_material',
                    'min_stock_alert' => $material['min_stock'],
                    'is_active' => true,
                    'description' => '3D carton mixing material - ' . $material['name'],
                ]
            );
        }

        // ============================================================
        // 5. PACKAGING SUPPLIES
        // ============================================================
        $packagingMaterials = [
            ['name' => 'Plastic Strapping', 'unit' => 'roll', 'min_stock' => 10],
            ['name' => 'Steel Strapping', 'unit' => 'roll', 'min_stock' => 5],
            ['name' => 'Corner Protectors', 'unit' => 'pcs', 'min_stock' => 500],
            ['name' => 'Adhesive Tape 50mm', 'unit' => 'roll', 'min_stock' => 20],
            ['name' => 'Adhesive Tape 75mm', 'unit' => 'roll', 'min_stock' => 20],
            ['name' => 'Double Sided Tape 12mm', 'unit' => 'roll', 'min_stock' => 10],
            ['name' => 'Double Sided Tape 24mm', 'unit' => 'roll', 'min_stock' => 10],
            ['name' => 'Masking Tape', 'unit' => 'roll', 'min_stock' => 10],
            ['name' => 'Labels 50x50mm', 'unit' => 'pcs', 'min_stock' => 1000],
            ['name' => 'Labels 100x100mm', 'unit' => 'pcs', 'min_stock' => 500],
            ['name' => 'Labels 150x100mm', 'unit' => 'pcs', 'min_stock' => 500],
            ['name' => 'Shrink Film', 'unit' => 'roll', 'min_stock' => 5],
            ['name' => 'Stretch Film', 'unit' => 'roll', 'min_stock' => 5],
            ['name' => 'Bubble Wrap', 'unit' => 'roll', 'min_stock' => 5],
            ['name' => 'Foam Sheet', 'unit' => 'roll', 'min_stock' => 5],
            ['name' => 'Kraft Tape', 'unit' => 'roll', 'min_stock' => 15],
        ];

        foreach ($packagingMaterials as $material) {
            Product::firstOrCreate(
                ['name' => $material['name']],
                [
                    'slug' => Str::slug($material['name']) . '-' . Str::random(6),
                    'unit' => $material['unit'],
                    'category_id' => $packagingCategory->id,
                    'type' => 'raw_material',
                    'min_stock_alert' => $material['min_stock'],
                    'is_active' => true,
                    'description' => 'Packaging supply for carton manufacturing - ' . $material['name'],
                ]
            );
        }

        // ============================================================
        // 6. MACHINE PARTS & CONSUMABLES
        // ============================================================
        $machineMaterials = [
            ['name' => 'Cutting Blade', 'unit' => 'pcs', 'min_stock' => 10],
            ['name' => 'Die Cutting Plate', 'unit' => 'pcs', 'min_stock' => 5],
            ['name' => 'Printing Plate', 'unit' => 'pcs', 'min_stock' => 5],
            ['name' => 'Rubber Roller', 'unit' => 'pcs', 'min_stock' => 3],
            ['name' => 'Anilox Roller', 'unit' => 'pcs', 'min_stock' => 3],
            ['name' => 'Doctor Blade', 'unit' => 'pcs', 'min_stock' => 10],
            ['name' => 'Gluing Nozzle', 'unit' => 'pcs', 'min_stock' => 5],
            ['name' => 'Slitting Knife', 'unit' => 'pcs', 'min_stock' => 10],
            ['name' => 'Perforating Blade', 'unit' => 'pcs', 'min_stock' => 10],
            ['name' => 'Creasing Matrix', 'unit' => 'pcs', 'min_stock' => 5],
            ['name' => 'Staples', 'unit' => 'box', 'min_stock' => 20],
            ['name' => 'Nails', 'unit' => 'box', 'min_stock' => 20],
            ['name' => 'Screws', 'unit' => 'box', 'min_stock' => 20],
            ['name' => 'Bolts', 'unit' => 'box', 'min_stock' => 20],
            ['name' => 'Washers', 'unit' => 'box', 'min_stock' => 20],
        ];

        foreach ($machineMaterials as $material) {
            Product::firstOrCreate(
                ['name' => $material['name']],
                [
                    'slug' => Str::slug($material['name']) . '-' . Str::random(6),
                    'unit' => $material['unit'],
                    'category_id' => $machineCategory->id,
                    'type' => 'raw_material',
                    'min_stock_alert' => $material['min_stock'],
                    'is_active' => true,
                    'description' => 'Machine part for carton manufacturing - ' . $material['name'],
                ]
            );
        }

        // ============================================================
        // 7. CHEMICALS & LUBRICANTS
        // ============================================================
        $chemicalMaterials = [
            ['name' => 'Machine Oil', 'unit' => 'liter', 'min_stock' => 10],
            ['name' => 'Grease', 'unit' => 'kg', 'min_stock' => 5],
            ['name' => 'Lubricant Oil', 'unit' => 'liter', 'min_stock' => 10],
            ['name' => 'Degreaser', 'unit' => 'liter', 'min_stock' => 5],
            ['name' => 'Cleaning Solvent', 'unit' => 'liter', 'min_stock' => 5],
            ['name' => 'Rust Remover', 'unit' => 'liter', 'min_stock' => 5],
            ['name' => 'Release Agent', 'unit' => 'liter', 'min_stock' => 5],
            ['name' => 'Cleaner Solution', 'unit' => 'liter', 'min_stock' => 5],
        ];

        foreach ($chemicalMaterials as $material) {
            Product::firstOrCreate(
                ['name' => $material['name']],
                [
                    'slug' => Str::slug($material['name']) . '-' . Str::random(6),
                    'unit' => $material['unit'],
                    'category_id' => $chemicalCategory->id,
                    'type' => 'raw_material',
                    'min_stock_alert' => $material['min_stock'],
                    'is_active' => true,
                    'description' => 'Chemical material for carton manufacturing - ' . $material['name'],
                ]
            );
        }

        // ============================================================
        // 8. SYRUP BOXES (Finished Goods)
        // ============================================================
        $syrupBoxes = [
            ['name' => '120ml Syrup Box', 'unit' => 'box', 'min_stock' => 50],
            ['name' => '200ml Syrup Box', 'unit' => 'box', 'min_stock' => 50],
            ['name' => '250ml Syrup Box', 'unit' => 'box', 'min_stock' => 50],
            ['name' => '500ml Syrup Box', 'unit' => 'box', 'min_stock' => 30],
            ['name' => '1000ml Syrup Box', 'unit' => 'box', 'min_stock' => 20],
            ['name' => 'Paracetamol Syrup Box', 'unit' => 'box', 'min_stock' => 30],
            ['name' => 'Cough Syrup Box 120ml', 'unit' => 'box', 'min_stock' => 40],
            ['name' => 'Cough Syrup Box 200ml', 'unit' => 'box', 'min_stock' => 40],
            ['name' => 'Antibiotic Syrup Box', 'unit' => 'box', 'min_stock' => 30],
            ['name' => 'Vitamins Syrup Box', 'unit' => 'box', 'min_stock' => 30],
            ['name' => 'Antacid Syrup Box', 'unit' => 'box', 'min_stock' => 30],
            ['name' => 'Pain Relief Syrup Box', 'unit' => 'box', 'min_stock' => 30],
        ];

        foreach ($syrupBoxes as $product) {
            Product::firstOrCreate(
                ['name' => $product['name']],
                [
                    'slug' => Str::slug($product['name']) . '-' . Str::random(6),
                    'unit' => $product['unit'],
                    'category_id' => $syrupBoxCategory->id,
                    'type' => 'finished_good',
                    'min_stock_alert' => $product['min_stock'],
                    'is_active' => true,
                    'description' => 'Syrup carton box for pharmaceutical packaging - ' . $product['name'],
                ]
            );
        }

        // ============================================================
        // 9. PHARMACEUTICAL BOXES (Finished Goods)
        // ============================================================
        $pharmaBoxes = [
            ['name' => 'Tablets Carton Box 10x10', 'unit' => 'box', 'min_stock' => 50],
            ['name' => 'Tablets Carton Box 10x20', 'unit' => 'box', 'min_stock' => 40],
            ['name' => 'Capsules Carton Box', 'unit' => 'box', 'min_stock' => 40],
            ['name' => 'Medicine Strip Carton', 'unit' => 'box', 'min_stock' => 50],
            ['name' => 'Injection Carton Box', 'unit' => 'box', 'min_stock' => 30],
            ['name' => 'Eye Drops Carton Box', 'unit' => 'box', 'min_stock' => 30],
            ['name' => 'Nasal Spray Carton Box', 'unit' => 'box', 'min_stock' => 30],
            ['name' => 'Cream Carton Box', 'unit' => 'box', 'min_stock' => 40],
            ['name' => 'Ointment Carton Box', 'unit' => 'box', 'min_stock' => 40],
        ];

        foreach ($pharmaBoxes as $product) {
            Product::firstOrCreate(
                ['name' => $product['name']],
                [
                    'slug' => Str::slug($product['name']) . '-' . Str::random(6),
                    'unit' => $product['unit'],
                    'category_id' => $pharmaBoxCategory->id,
                    'type' => 'finished_good',
                    'min_stock_alert' => $product['min_stock'],
                    'is_active' => true,
                    'description' => 'Pharmaceutical carton box - ' . $product['name'],
                ]
            );
        }

        // ============================================================
        // 10. CUSTOM CARTONS (Finished Goods)
        // ============================================================
        $customCartons = [
            ['name' => 'Food Packaging Carton Box', 'unit' => 'box', 'min_stock' => 20],
            ['name' => 'Cosmetics Carton Box', 'unit' => 'box', 'min_stock' => 20],
            ['name' => 'Electronic Appliance Carton', 'unit' => 'box', 'min_stock' => 15],
            ['name' => 'Shoe Carton Box', 'unit' => 'box', 'min_stock' => 20],
            ['name' => 'Gift Carton Box', 'unit' => 'box', 'min_stock' => 15],
            ['name' => 'Luxury Carton Box', 'unit' => 'box', 'min_stock' => 10],
            ['name' => 'Export Carton Box', 'unit' => 'box', 'min_stock' => 25],
            ['name' => 'Moving Carton Box', 'unit' => 'box', 'min_stock' => 20],
        ];

        foreach ($customCartons as $product) {
            Product::firstOrCreate(
                ['name' => $product['name']],
                [
                    'slug' => Str::slug($product['name']) . '-' . Str::random(6),
                    'unit' => $product['unit'],
                    'category_id' => $customCartonCategory->id,
                    'type' => 'finished_good',
                    'min_stock_alert' => $product['min_stock'],
                    'is_active' => true,
                    'description' => 'Custom carton box - ' . $product['name'],
                ]
            );
        }

        // ============================================================
        // SUMMARY
        // ============================================================
        $totalProducts = Product::count();
        $rawMaterials = Product::where('type', 'raw_material')->count();
        $finishedGoods = Product::where('type', 'finished_good')->count();

        $this->command->info('✅ ' . $totalProducts . ' products seeded successfully!');
        $this->command->info('📦 Raw Materials: ' . $rawMaterials);
        $this->command->info('📦 Finished Goods: ' . $finishedGoods);

        // Category breakdown
        $this->command->info("\n📊 Category Breakdown:");
        foreach (Category::all() as $category) {
            $count = Product::where('category_id', $category->id)->count();
            $this->command->info("   - {$category->name}: {$count} products");
        }
    }
}
