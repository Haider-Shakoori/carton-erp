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
        Schema::create('account_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();
            $table->decimal('credit', 20, 2)->default(0);
            $table->decimal('debit', 20, 2)->default(0);
            $table->decimal('balance', 20, 2)->default(0);
            $table->timestamps();

            $table->unique(['account_id', 'currency_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_balances');
    }
};
