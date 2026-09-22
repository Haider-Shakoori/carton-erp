<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stock_notification_preferences', 'assignment_alerts_enabled')) {
            Schema::table('stock_notification_preferences', function (Blueprint $table) {
                $table->boolean('assignment_alerts_enabled')
                    ->default(true)
                    ->after('minimum_escalation_level');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stock_notification_preferences', 'assignment_alerts_enabled')) {
            Schema::table('stock_notification_preferences', function (Blueprint $table) {
                $table->dropColumn('assignment_alerts_enabled');
            });
        }
    }
};
