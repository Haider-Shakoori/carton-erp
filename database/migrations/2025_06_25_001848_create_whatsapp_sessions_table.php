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
        Schema::create('whatsapp_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Main Office", "Branch A"
            $table->string('session_id')->unique(); // Given by WasenderAPI
            $table->string('api_key'); // Token assigned per session
            $table->enum('status', ['connected', 'disconnected'])->default('disconnected');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_sessions');
    }
};
