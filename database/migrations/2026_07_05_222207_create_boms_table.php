<?php
// database/migrations/2026_01_01_000001_create_boms_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('version')->default('1.0');
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->text('description')->nullable();
            $table->decimal('wastage_percentage', 5, 2)->default(0);
            $table->decimal('labor_cost_per_unit', 15, 2)->default(0);
            $table->decimal('overhead_cost_per_unit', 15, 2)->default(0);
            $table->decimal('profit_margin_percentage', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'status']);
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boms');
    }
};
