<?php

use Database\Seeders\ClientCartonRawMaterialSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Deploy the client-approved baseline 3D carton raw materials.
     *
     * The canonical, idempotent definition lives in the seeder so production
     * deployments (`db:seed`) and fresh installs share one source of truth.
     * This migration only exists to deploy the data to databases created
     * before the seeder was registered.
     */
    public function up(): void
    {
        (new ClientCartonRawMaterialSeeder())->run();
    }

    public function down(): void
    {
        // Client master-data is intentionally retained on rollback. These
        // materials may already have purchase, inventory or BOM history.
    }
};
