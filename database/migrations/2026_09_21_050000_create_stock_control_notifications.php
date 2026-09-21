<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::create('stock_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->boolean('in_app_enabled')->default(true);
            $table->boolean('email_enabled')->default(false);
            $table->boolean('whatsapp_enabled')->default(false);
            $table->unsignedTinyInteger('minimum_escalation_level')->default(2);
            $table->boolean('overdue_reminders_enabled')->default(true);
            $table->boolean('recurrence_alerts_enabled')->default(true);
            $table->boolean('weekly_review_alerts_enabled')->default(true);
            $table->timestamps();

            $table->foreign('user_id', 'snp_user_fk')
                ->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('stock_notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('event_key', 191);
            $table->string('event_type', 80);
            $table->string('channel', 30);
            $table->string('status', 30)->default('pending');
            $table->string('recipient')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id', 'snd_user_fk')
                ->references('id')->on('users')->cascadeOnDelete();

            $table->unique(
                ['user_id', 'event_key', 'channel'],
                'snd_user_event_channel_uq'
            );
            $table->index(['status', 'channel'], 'snd_status_channel_idx');
            $table->index('notification_id', 'snd_notification_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_notification_deliveries');
        Schema::dropIfExists('stock_notification_preferences');
        Schema::dropIfExists('notifications');
    }
};
