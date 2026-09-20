<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gate_passes', function (Blueprint $table) {
            $table->id();
            $table->string('gate_pass_no')->unique();
            $table->foreignId('sale_id')->unique()->constrained('sales')->cascadeOnDelete();
            $table->dateTime('issued_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('gate_pass_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gate_pass_id')->constrained('gate_passes')->cascadeOnDelete();
            $table->foreignId('sale_item_id')->nullable()->constrained('sale_items')->nullOnDelete();
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 12, 2);
            $table->string('unit', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gate_pass_items');
        Schema::dropIfExists('gate_passes');
    }
};
