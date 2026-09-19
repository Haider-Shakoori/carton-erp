<?php
// database/migrations/2026_07_10_000001_create_employees_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->string('alternative_phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();

            // Personal Details
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->string('marital_status')->nullable();
            $table->string('nationality')->nullable();
            $table->string('national_id')->nullable();

            // Employment Details
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->enum('employment_type', ['permanent', 'contract', 'daily_wage', 'temporary'])->default('permanent');
            $table->string('designation')->nullable();
            $table->string('job_title')->nullable();

            // Supervisor
            $table->foreignId('supervisor_id')->nullable()->constrained('employees')->onDelete('set null');

            // Salary Information
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('housing_allowance', 15, 2)->default(0);
            $table->decimal('transport_allowance', 15, 2)->default(0);
            $table->decimal('medical_allowance', 15, 2)->default(0);
            $table->decimal('other_allowances', 15, 2)->default(0);
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->decimal('overtime_rate', 10, 2)->default(0);

            // Bank Details
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_holder')->nullable();

            // Emergency Contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();

            // Documents
            $table->string('profile_photo')->nullable();

            // System Fields
            $table->enum('status', ['active', 'inactive', 'terminated', 'on_leave'])->default('active');
            $table->boolean('is_active')->default(true);
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('employee_code');
            $table->index('status');
            $table->index('designation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
