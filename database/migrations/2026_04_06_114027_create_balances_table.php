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
            Schema::create('balances', function (Blueprint $table) {
                $table->id();
                $table->integer('currency_id');
                $table->decimal('starting_balance', 15, 2);
                $table->decimal('closing_balance', 15, 2);
                $table->date('date');
                $table->timestamps();
            });
        }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balances');
    }
};
