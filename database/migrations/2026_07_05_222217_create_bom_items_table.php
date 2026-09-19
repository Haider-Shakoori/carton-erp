<?php
// database/migrations/2026_01_01_000002_create_bom_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('boms')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('products')->onDelete('cascade');
            $table->decimal('quantity', 15, 4);
            $table->string('unit')->nullable();
            $table->decimal('wastage_percentage', 5, 2)->default(0);
            $table->decimal('cost_per_unit', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['bom_id', 'material_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_items');
    }
};
