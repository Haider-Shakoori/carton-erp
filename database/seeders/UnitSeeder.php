<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            // --- Weights ---
            ['name_en' => 'Ton', 'name_ps' => 'ټن', 'name_dr' => 'تُن', 'short_en' => 'Ton'],
            ['name_en' => 'Kilogram', 'name_ps' => 'کیلوګرام', 'name_dr' => 'کیلوگرم', 'short_en' => 'KG'],
            ['name_en' => 'Gram', 'name_ps' => 'ګرام', 'name_dr' => 'گرام', 'short_en' => 'g'],

            // --- Quantity & Packaging ---
            ['name_en' => 'Piece', 'name_ps' => 'عدد', 'name_dr' => 'دانه', 'short_en' => 'Pcs'],
            ['name_en' => 'Pair', 'name_ps' => 'جوړه', 'name_dr' => 'جوره', 'short_en' => 'Pair'],
            ['name_en' => 'Carton', 'name_ps' => 'کارتن', 'name_dr' => 'کارتن', 'short_en' => 'Ctn'],
            ['name_en' => 'Box', 'name_ps' => 'بکس', 'name_dr' => 'باکس', 'short_en' => 'Box'],
            ['name_en' => 'Pack', 'name_ps' => 'بسته', 'name_dr' => 'بسته', 'short_en' => 'Pack'],
            ['name_en' => 'Set', 'name_ps' => 'سیټ', 'name_dr' => 'ست', 'short_en' => 'Set'],

            // --- Textile / Length ---
            ['name_en' => 'Meter', 'name_ps' => 'متر', 'name_dr' => 'متر', 'short_en' => 'm'],
            ['name_en' => 'Yard', 'name_ps' => 'وار', 'name_dr' => 'وار', 'short_en' => 'Yd'],
            ['name_en' => 'Roll', 'name_ps' => 'طاقه', 'name_dr' => 'طاقه', 'short_en' => 'Roll'],
        ];

        foreach ($units as $u) {
            DB::table('units')->insert(array_merge($u, [
                'short_ps' => $u['name_ps'],
                'short_dr' => $u['name_dr'],
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }
    }
}
