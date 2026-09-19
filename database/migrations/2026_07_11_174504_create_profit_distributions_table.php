<?php
// database/migrations/2026_07_11_174449_create_profit_distributions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profit_distributions', function (Blueprint $table) {
            $table->id();
            $table->string('distribution_number')->unique();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_profit', 15, 2)->default(0);
            $table->decimal('distributed_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            $table->date('distribution_date');
            $table->enum('status', ['draft', 'pending', 'approved', 'distributed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('distribution_number');
            $table->index('status');
            $table->index('period_start');
            $table->index('period_end');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_distributions');
    }
};
