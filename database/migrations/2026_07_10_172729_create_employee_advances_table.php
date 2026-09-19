<?php
// database/migrations/2026_07_10_000005_create_employee_advances_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('advance_number')->unique();
            $table->decimal('amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            $table->integer('installments')->default(1);
            $table->integer('installments_paid')->default(0);
            $table->decimal('installment_amount', 15, 2)->default(0);
            $table->enum('type', ['salary_advance', 'loan', 'emergency'])->default('salary_advance');
            $table->text('reason')->nullable();
            $table->date('request_date');
            $table->date('approval_date')->nullable();
            $table->date('disbursement_date')->nullable();
            $table->date('first_installment_date')->nullable();
            $table->enum('status', ['pending', 'approved', 'disbursed', 'partially_paid', 'fully_paid', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('rejection_reason')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();

            $table->index('advance_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_advances');
    }
};
