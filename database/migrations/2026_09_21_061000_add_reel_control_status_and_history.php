<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_item_reels', function (Blueprint $table) {
            $table->text('status_reason')->nullable()->after('status');
            $table->timestamp('status_changed_at')->nullable()->after('status_reason');
            $table->unsignedBigInteger('status_changed_by')->nullable()->after('status_changed_at');

            $table->foreign('status_changed_by', 'pir_status_changed_by_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->index(
                ['status', 'status_changed_at'],
                'pir_control_status_idx'
            );
        });

        Schema::create('purchase_item_reel_status_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_item_reel_id');
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('reason');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('purchase_item_reel_id', 'pirse_reel_fk')
                ->references('id')->on('purchase_item_reels')
                ->cascadeOnDelete();
            $table->foreign('changed_by', 'pirse_changed_by_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->index(
                ['purchase_item_reel_id', 'changed_at'],
                'pirse_reel_changed_idx'
            );
            $table->index(
                ['to_status', 'changed_at'],
                'pirse_status_changed_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_item_reel_status_events');

        Schema::table('purchase_item_reels', function (Blueprint $table) {
            $table->dropForeign('pir_status_changed_by_fk');
            $table->dropIndex('pir_control_status_idx');
            $table->dropColumn([
                'status_reason',
                'status_changed_at',
                'status_changed_by',
            ]);
        });
    }
};
