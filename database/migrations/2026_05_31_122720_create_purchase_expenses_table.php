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
        Schema::create('purchase_expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('currency_id');
            $table->text('description')->nullable();
            $table->double('amount', 35, 2)->nullable();     // Original currency amount
            $table->double('usd_amount', 35, 2);             // USD equivalent
            $table->double('rate', 35, 2);                   // Exchange rate
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_expenses');
    }
};
