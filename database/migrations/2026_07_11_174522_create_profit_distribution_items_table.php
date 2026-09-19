<?php
// database/migrations/2026_07_11_174450_create_profit_distribution_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profit_distribution_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')->constrained('profit_distributions')->onDelete('cascade');
            $table->foreignId('shareholder_id')->constrained('shareholders')->onDelete('cascade');
            $table->decimal('share_percentage', 5, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['distribution_id', 'shareholder_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_distribution_items');
    }
};
