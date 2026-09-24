<?php

use Database\Seeders\ClientCartonRawMaterialSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Deploy the optional lamination finishing material to existing databases.
     * The idempotent seeder remains the single source of truth.
     */
    public function up(): void
    {
        (new ClientCartonRawMaterialSeeder())->run();
    }

    public function down(): void
    {
        // Keep the material because it may already have purchase/BOM/production history.
    }
};
