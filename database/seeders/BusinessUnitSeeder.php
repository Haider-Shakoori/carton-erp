<?php

namespace Database\Seeders;

use App\Models\BusinessUnit;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class BusinessUnitSeeder extends Seeder
{
    public const UNITS = [
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

    public function run(): void
    {
        if (! Schema::hasTable('business_units')) {
            return;
        }

        foreach (self::UNITS as $definition) {
            BusinessUnit::query()->updateOrCreate(
                ['code' => $definition['code']],
                $definition
            );
        }

        if (
            Schema::hasTable('settings')
            && Schema::hasColumn('settings', 'default_business_unit_id')
        ) {
            $defaultId = BusinessUnit::query()
                ->where('code', '3d_carton')
                ->value('id');

            $setting = Setting::query()->firstOrCreate([]);

            if (! $setting->default_business_unit_id && $defaultId) {
                $setting->update([
                    'default_business_unit_id' => $defaultId,
                ]);
            }
        }

        $this->command?->info(
            'Business units ready: 3D Carton and Syrup Pack.'
        );
    }
}
