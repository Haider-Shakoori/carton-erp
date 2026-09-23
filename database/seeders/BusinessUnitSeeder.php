<?php

namespace Database\Seeders;

use App\Services\BusinessUnitProvisioningService;
use Illuminate\Database\Seeder;

class BusinessUnitSeeder extends Seeder
{
    public function run(): void
    {
        app(BusinessUnitProvisioningService::class)->ensureRequiredUnits();

        $this->command?->info(
            'Business units ready: 3D Carton and Syrup Pack.'
        );
    }
}
