<?php
// database/migrations/2026_07_07_000002_create_work_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->onDelete('cascade');
            $table->string('work_order_number')->unique();

            $table->enum('operation_type', ['printing', 'cutting', 'gluing', 'folding', 'lamination', 'quality_check']);
            $table->foreignId('machine_id')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users');

            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');

            $table->decimal('estimated_time', 10, 2)->default(0);
            $table->decimal('actual_time', 10, 2)->default(0);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('operation_type');
            $table->index('work_order_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
