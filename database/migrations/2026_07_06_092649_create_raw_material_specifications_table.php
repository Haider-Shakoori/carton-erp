<?php
// database/migrations/2026_01_01_000001_create_raw_material_specifications_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_material_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            // Paper specific
            $table->decimal('gsm', 10, 2)->nullable()->comment('Grams per square meter');
            $table->decimal('thickness', 10, 2)->nullable()->comment('In mm');
            $table->string('flute_type')->nullable()->comment('B, C, E, etc.');
            $table->string('color')->nullable();
            $table->string('grade')->nullable()->comment('A, B, C, etc.');

            // Ink specific
            $table->decimal('viscosity', 10, 2)->nullable()->comment('In cP');
            $table->decimal('density', 10, 2)->nullable()->comment('In g/cm³');
            $table->string('ink_type')->nullable()->comment('UV, water-based, solvent-based');

            // Chemical specific
            $table->string('chemical_composition')->nullable();
            $table->decimal('ph_level', 5, 2)->nullable();
            $table->string('shelf_life')->nullable();

            // Common fields
            $table->string('supplier_part_number')->nullable();
            $table->decimal('minimum_order_quantity', 15, 2)->default(1);
            $table->string('storage_conditions')->nullable();
            $table->text('safety_instructions')->nullable();

            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_material_specifications');
    }
};
