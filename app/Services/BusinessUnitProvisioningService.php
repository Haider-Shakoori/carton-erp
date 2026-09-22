<?php

namespace App\Services;

use App\Models\BusinessUnit;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class BusinessUnitProvisioningService
{
    public const REQUIRED_UNITS = [
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

    public function ensureRequiredUnits(): Collection
    {
        if (! Schema::hasTable('business_units')) {
            return collect();
        }

        foreach (self::REQUIRED_UNITS as $definition) {
            BusinessUnit::query()->updateOrCreate(
                ['code' => $definition['code']],
                $definition
            );
        }

        $units = BusinessUnit::query()
            ->whereIn('code', collect(self::REQUIRED_UNITS)->pluck('code')->all())
            ->active()
            ->get();

        if (
            Schema::hasTable('settings')
            && Schema::hasColumn('settings', 'default_business_unit_id')
        ) {
            $setting = Setting::query()->firstOrCreate([]);
            $defaultExists = $setting->default_business_unit_id
                && $units->contains('id', (int) $setting->default_business_unit_id);

            if (! $defaultExists) {
                $carton = $units->firstWhere('code', '3d_carton');

                if ($carton) {
                    $setting->update([
                        'default_business_unit_id' => $carton->id,
                    ]);
                }
            }
        }

        return $units;
    }
}
