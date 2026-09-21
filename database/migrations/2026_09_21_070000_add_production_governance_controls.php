<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable()->after('completion_date');
            $table->unsignedBigInteger('closed_by')->nullable()->after('closed_at');
            $table->timestamp('reopened_at')->nullable()->after('closed_by');
            $table->unsignedBigInteger('reopened_by')->nullable()->after('reopened_at');
            $table->timestamp('reversed_at')->nullable()->after('reopened_by');
            $table->unsignedBigInteger('reversed_by')->nullable()->after('reversed_at');
            $table->text('reversal_reason')->nullable()->after('reversed_by');
            $table->text('approval_notes')->nullable()->after('approved_at');

            $table->index(['status', 'approved_at']);
            $table->index(['status', 'closed_at']);
            $table->index('reversed_at');
        });

        Schema::create('production_order_control_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_order_id')->index();
            $table->string('event', 50)->index();
            $table->string('from_state', 50)->nullable();
            $table->string('to_state', 50)->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->foreign('production_order_id')
                ->references('id')->on('production_orders')
                ->cascadeOnDelete();
            $table->foreign('actor_id')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->index(['production_order_id', 'created_at'], 'po_control_event_order_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_control_events');

        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'approved_at']);
            $table->dropIndex(['status', 'closed_at']);
            $table->dropIndex(['reversed_at']);
            $table->dropColumn([
                'closed_at',
                'closed_by',
                'reopened_at',
                'reopened_by',
                'reversed_at',
                'reversed_by',
                'reversal_reason',
                'approval_notes',
            ]);
        });
    }
};
