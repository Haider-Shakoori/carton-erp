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
        Schema::create('remittances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('currency_id')->constrained('currencies');
            $table->decimal('amount', 20, 2);
            $table->string('bank_name');
            $table->string('account_holder');
            $table->string('bank_account_number')->index();
            $table->string('bank_address')->nullable();
            $table->string('phone_number')->nullable();
            $table->text('note')->nullable();
            $table->enum('status', ['pending', 'processed'])->default('pending')->index();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remittances');
    }
};
