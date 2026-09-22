<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'production_approval_required')) {
                $table->boolean('production_approval_required')
                    ->default(false)
                    ->after('default_business_unit_id');
            }
        });

        Schema::table('production_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('production_orders', 'closed_by')) {
                $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('closed_at')->nullable()->index();
            }

            if (! Schema::hasColumn('production_orders', 'reversed_by')) {
                $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reversed_at')->nullable()->index();
            }

            if (! Schema::hasColumn('production_orders', 'reopen_count')) {
                $table->unsignedInteger('reopen_count')->default(0);
            }
        });

        Schema::create('production_order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 50)->index();
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50)->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['production_order_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_events');

        Schema::table('production_orders', function (Blueprint $table) {
            if (Schema::hasColumn('production_orders', 'closed_by')) {
                $table->dropConstrainedForeignId('closed_by');
                $table->dropColumn('closed_at');
            }

            if (Schema::hasColumn('production_orders', 'reversed_by')) {
                $table->dropConstrainedForeignId('reversed_by');
                $table->dropColumn('reversed_at');
            }

            if (Schema::hasColumn('production_orders', 'reopen_count')) {
                $table->dropColumn('reopen_count');
            }
        });

        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'production_approval_required')) {
                $table->dropColumn('production_approval_required');
            }
        });
    }
};
