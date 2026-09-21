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

        Schema::create('management_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();

            $table->boolean('in_app_enabled')->default(true);
            $table->boolean('email_enabled')->default(false);
            $table->boolean('whatsapp_enabled')->default(false);

            $table->boolean('level_2_enabled')->default(true);
            $table->boolean('level_3_enabled')->default(true);
            $table->boolean('assignment_enabled')->default(true);
            $table->boolean('overdue_enabled')->default(true);
            $table->boolean('recurrence_enabled')->default(true);
            $table->boolean('weekly_review_enabled')->default(true);

            $table->timestamps();

            $table->foreign('user_id', 'mnp_user_fk')
                ->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('management_notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 191)->unique();
            $table->string('event_type', 80);
            $table->unsignedBigInteger('recipient_user_id');
            $table->string('channel', 30);
            $table->string('related_type', 100)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('status', 30)->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->foreign('recipient_user_id', 'mnd_user_fk')
                ->references('id')->on('users')->cascadeOnDelete();

            $table->index(
                ['recipient_user_id', 'status', 'created_at'],
                'mnd_user_status_created_idx'
            );
            $table->index(
                ['related_type', 'related_id'],
                'mnd_related_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('management_notification_deliveries');
        Schema::dropIfExists('management_notification_preferences');

        if (Schema::hasTable('notifications')) {
            Schema::drop('notifications');
        }
    }
};
