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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('logo')->nullable();
            $table->string('contact')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('note_en')->nullable();
            $table->string('note_fa')->nullable();
            $table->string('note_ps')->nullable();
            $table->string('default_language')->default('en');
            $table->string('currency')->default('USD');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
