<?php
// database/migrations/2026_07_07_000003_create_machines_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->nullable();
            $table->string('model')->nullable();
            $table->string('manufacturer')->nullable();
            $table->integer('year')->nullable();
            $table->string('serial_number')->nullable();
            $table->enum('status', ['active', 'maintenance', 'inactive'])->default('active');
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('code');
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreign('machine_id')->references('id')->on('machines');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropForeign(['machine_id']);
        });
        Schema::dropIfExists('machines');
    }
};
