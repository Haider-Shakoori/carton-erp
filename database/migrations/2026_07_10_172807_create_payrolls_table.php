<?php
// database/migrations/2026_07_10_000007_create_payrolls_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('payroll_number')->unique();
            $table->date('period_start');
            $table->date('period_end');
            $table->date('payment_date');

            // Earnings
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('housing_allowance', 15, 2)->default(0);
            $table->decimal('transport_allowance', 15, 2)->default(0);
            $table->decimal('medical_allowance', 15, 2)->default(0);
            $table->decimal('other_allowances', 15, 2)->default(0);
            $table->decimal('overtime_pay', 15, 2)->default(0);
            $table->decimal('bonus', 15, 2)->default(0);
            $table->decimal('commission', 15, 2)->default(0);
            $table->decimal('other_earnings', 15, 2)->default(0);

            // Deductions
            $table->decimal('tax_deduction', 15, 2)->default(0);
            $table->decimal('social_security', 15, 2)->default(0);
            $table->decimal('advance_deduction', 15, 2)->default(0);
            $table->decimal('loan_deduction', 15, 2)->default(0);
            $table->decimal('penalty_deduction', 15, 2)->default(0);
            $table->decimal('other_deductions', 15, 2)->default(0);

            // Totals
            $table->decimal('total_earnings', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);

            $table->enum('status', ['draft', 'processed', 'paid', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->string('attachment')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->dateTime('processed_at')->nullable();

            $table->timestamps();

            $table->index('payroll_number');
            $table->index('period_start');
            $table->index('period_end');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
