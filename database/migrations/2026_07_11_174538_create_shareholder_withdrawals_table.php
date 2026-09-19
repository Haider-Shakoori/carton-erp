<?php
// database/migrations/2026_07_11_174451_create_shareholder_withdrawals_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shareholder_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('withdrawal_number')->unique();
            $table->foreignId('shareholder_id')->constrained('shareholders')->onDelete('cascade');
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('withdrawal_date');
            $table->enum('status', ['pending', 'approved', 'paid', 'rejected'])->default('pending');
            $table->text('reason')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('withdrawal_number');
            $table->index('status');
            $table->index('withdrawal_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shareholder_withdrawals');
    }
};
