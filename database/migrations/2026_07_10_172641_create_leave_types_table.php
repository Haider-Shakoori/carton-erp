<?php
// database/migrations/2026_07_10_000003_create_leave_types_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->integer('days_per_year')->default(0);
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->integer('max_continuous_days')->nullable();
            $table->string('color')->default('#4f46e5');
            $table->integer('carry_forward_days')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
