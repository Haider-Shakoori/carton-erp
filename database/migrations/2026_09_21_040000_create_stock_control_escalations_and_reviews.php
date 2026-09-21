<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_control_escalations', function (Blueprint $table) {
            $table->id();
            $table->string('source_key', 191)->unique();
            $table->string('source_type', 80);
            $table->string('severity', 30);
            $table->unsignedTinyInteger('level')->default(1);
            $table->string('status', 30)->default('open');

            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('root_cause_code', 80)->nullable();
            $table->unsignedBigInteger('investigation_id')->nullable();

            $table->string('title', 255);
            $table->text('message');
            $table->unsignedInteger('occurrences')->default(1);
            $table->decimal('absolute_value_usd', 18, 4)->default(0);

            $table->unsignedBigInteger('escalated_to')->nullable();
            $table->date('review_due_date')->nullable();

            $table->timestamp('opened_at');
            $table->timestamp('last_detected_at');
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('product_id', 'sce_product_fk')
                ->references('id')->on('products')->nullOnDelete();
            $table->foreign('investigation_id', 'sce_investigation_fk')
                ->references('id')->on('stock_variance_investigations')
                ->nullOnDelete();
            $table->foreign('escalated_to', 'sce_escalated_to_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('acknowledged_by', 'sce_ack_by_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('closed_by', 'sce_closed_by_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->index(
                ['status', 'level', 'review_due_date'],
                'sce_status_level_due_idx'
            );
            $table->index(
                ['product_id', 'root_cause_code'],
                'sce_product_root_idx'
            );
        });

        Schema::create('stock_control_escalation_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_control_escalation_id');
            $table->string('event_type', 50);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign(
                'stock_control_escalation_id',
                'scee_escalation_fk'
            )->references('id')->on('stock_control_escalations')
                ->cascadeOnDelete();
            $table->foreign('user_id', 'scee_user_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->index(
                ['stock_control_escalation_id', 'created_at'],
                'scee_escalation_created_idx'
            );
        });

        Schema::create('stock_control_reviews', function (Blueprint $table) {
            $table->id();
            $table->date('week_start')->unique();
            $table->date('week_end');
            $table->string('status', 30)->default('open');
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->date('due_date');
            $table->timestamp('generated_at');
            $table->json('summary_snapshot')->nullable();
            $table->text('review_notes')->nullable();
            $table->text('decisions')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('owner_id', 'scr_owner_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('completed_by', 'scr_completed_by_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->index(['status', 'due_date'], 'scr_status_due_idx');
        });

        Schema::create('stock_control_review_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_control_review_id');
            $table->unsignedBigInteger('stock_control_escalation_id');
            $table->string('severity_snapshot', 30);
            $table->unsignedTinyInteger('level_snapshot');
            $table->string('status_snapshot', 30);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('stock_control_review_id', 'scri_review_fk')
                ->references('id')->on('stock_control_reviews')
                ->cascadeOnDelete();
            $table->foreign('stock_control_escalation_id', 'scri_escalation_fk')
                ->references('id')->on('stock_control_escalations')
                ->cascadeOnDelete();

            $table->unique(
                ['stock_control_review_id', 'stock_control_escalation_id'],
                'scri_review_escalation_uq'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_control_review_items');
        Schema::dropIfExists('stock_control_reviews');
        Schema::dropIfExists('stock_control_escalation_events');
        Schema::dropIfExists('stock_control_escalations');
    }
};
