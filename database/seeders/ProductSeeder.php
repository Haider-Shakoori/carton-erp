<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Legacy compatibility seeder.
 *
 * The previous implementation populated a large demo catalogue of papers,
 * inks, glues, packaging supplies, machine parts and chemicals. The client
 * provided exactly ten carton raw materials, so manual execution of this
 * legacy entry point is now intentionally restricted to that source of truth.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ClientCartonRawMaterialSeeder::class);
    }
}
