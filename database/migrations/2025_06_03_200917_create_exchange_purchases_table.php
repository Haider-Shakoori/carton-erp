<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exchange_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('base_currency_id')->constrained('currencies');
            $table->foreignId('target_currency_id')->constrained('currencies');
            $table->decimal('base_amount', 20, 2);
            $table->decimal('rate', 20, 6);
            $table->decimal('target_amount', 20, 2);
            $table->decimal('cost_rate', 20, 2)->nullable();
            $table->decimal('target_profit', 20, 2)->nullable();
            $table->enum('destination', ['cash', 'account'])->default('account')->index();
            $table->boolean('is_withdrawn')->default(false);
            $table->enum('status', ['pending', 'processed'])->default('pending')->index();
            $table->boolean('is_cash')->default(false);
            $table->foreignId('office_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_purchases');
    }
};
