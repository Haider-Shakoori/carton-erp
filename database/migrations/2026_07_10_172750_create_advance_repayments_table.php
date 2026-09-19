<?php
// database/migrations/2026_07_10_000006_create_advance_repayments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advance_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_advance_id')->constrained('employee_advances')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->string('receipt_number')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advance_repayments');
    }
};
