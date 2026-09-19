<?php
// database/migrations/2026_07_11_000000_create_leaves_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('days');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            // Indexes for better performance
            $table->index('start_date');
            $table->index('end_date');
            $table->index('status');
            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
