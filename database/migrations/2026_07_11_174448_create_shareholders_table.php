<?php
// database/migrations/2026_07_11_174448_create_shareholders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shareholders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->decimal('share_percentage', 5, 2)->default(0);
            $table->decimal('capital_contribution', 15, 2)->default(0);
            $table->date('joining_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->string('profile_image')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shareholders');
    }
};
