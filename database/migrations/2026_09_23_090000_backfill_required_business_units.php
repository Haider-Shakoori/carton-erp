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

        $now = now();

        $requiredUnits = [
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

        foreach ($requiredUnits as $unit) {
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

        if (
            Schema::hasTable('settings')
            && Schema::hasColumn('settings', 'default_business_unit_id')
        ) {
            $cartonId = DB::table('business_units')
                ->where('code', '3d_carton')
                ->value('id');

            if ($cartonId) {
                DB::table('settings')
                    ->whereNull('default_business_unit_id')
                    ->update(['default_business_unit_id' => $cartonId]);
            }
        }
    }

    public function down(): void
    {
        // Intentionally keep required business-unit master data on rollback.
    }
};
