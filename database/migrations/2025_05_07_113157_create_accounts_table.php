<?php
// database/migrations/2025_07_01_000000_create_accounts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('code')->unique()->index();
            $table->enum('account_type', [
                'customer',
                'supplier',
                'agent',
                'expense',
                'saraf',
            ])->default('customer')->index();
            $table->string('contact')->nullable()->index();
            $table->text('address')->nullable();
            $table->string('company')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('whatsapp')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('currency_code')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->string('bi_icon')->nullable();
            $table->string('bi_icon_color')->nullable();
            $table->string('profile_bg')->nullable();
            $table->boolean('is_safe')->default(false)->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
