<?php
// database/migrations/2026_01_01_000002_create_finished_good_specifications_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finished_good_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            // Carton specific
            $table->decimal('length', 10, 2)->nullable()->comment('In mm');
            $table->decimal('width', 10, 2)->nullable()->comment('In mm');
            $table->decimal('height', 10, 2)->nullable()->comment('In mm');
            $table->decimal('weight', 10, 2)->nullable()->comment('In grams');
            $table->integer('fold_count')->default(0);
            $table->string('flute_type')->nullable()->comment('B, C, E, etc.');

            // Printing specific
            $table->integer('color_count')->default(1);
            $table->string('printing_type')->nullable()->comment('Flexo, Offset, Digital');
            $table->string('finish_type')->nullable()->comment('Matte, Glossy, UV');

            // Packaging specific
            $table->integer('pieces_per_carton')->default(1);
            $table->decimal('carton_size', 10, 2)->nullable()->comment('In mm³');

            // Pricing
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('minimum_order_quantity', 15, 2)->default(1);

            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finished_good_specifications');
    }
};
