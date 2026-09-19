<?php
// database/migrations/2026_01_01_000007_create_advances_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['advance', 'loan'])->default('advance');
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->date('request_date');
            $table->date('deduction_start_date')->nullable();
            $table->date('deduction_end_date')->nullable();
            $table->decimal('deduction_amount', 15, 2)->nullable();
            $table->decimal('remaining_amount', 15, 2);
            $table->text('reason')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['request_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advances');
    }
};
