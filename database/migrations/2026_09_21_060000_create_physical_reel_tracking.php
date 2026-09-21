<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_item_reels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_item_id');
            $table->unsignedInteger('sequence_no');
            $table->string('reel_code', 120)->unique();

            $table->decimal('registered_weight_kg', 18, 4);
            $table->decimal('system_remaining_weight_kg', 18, 4);
            $table->decimal('last_measured_weight_kg', 18, 4)->nullable();
            $table->decimal('measurement_variance_kg', 18, 4)->nullable();

            $table->string('status', 30)->default('sealed');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('depleted_at')->nullable();

            $table->timestamp('last_measured_at')->nullable();
            $table->unsignedBigInteger('last_measured_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('purchase_item_id', 'pir_purchase_item_fk')
                ->references('id')->on('purchase_items')->cascadeOnDelete();
            $table->foreign('last_measured_by', 'pir_measured_by_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->unique(
                ['purchase_item_id', 'sequence_no'],
                'pir_batch_sequence_uq'
            );
            $table->index(
                ['purchase_item_id', 'status'],
                'pir_batch_status_idx'
            );
            $table->index(
                ['status', 'last_measured_at'],
                'pir_status_measured_idx'
            );
        });

        Schema::create('purchase_item_reel_measurements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_item_reel_id');
            $table->decimal('system_weight_snapshot_kg', 18, 4);
            $table->decimal('measured_weight_kg', 18, 4);
            $table->decimal('variance_kg', 18, 4);
            $table->unsignedBigInteger('measured_by')->nullable();
            $table->timestamp('measured_at');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('purchase_item_reel_id', 'pirm_reel_fk')
                ->references('id')->on('purchase_item_reels')
                ->cascadeOnDelete();
            $table->foreign('measured_by', 'pirm_measured_by_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->index(
                ['purchase_item_reel_id', 'measured_at'],
                'pirm_reel_measured_idx'
            );
        });

        Schema::create('production_reel_consumptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_material_consumption_id');
            $table->unsignedBigInteger('purchase_item_reel_id');
            $table->decimal('quantity_kg', 18, 4);
            $table->decimal('before_weight_kg', 18, 4);
            $table->decimal('after_weight_kg', 18, 4);
            $table->timestamp('consumed_at');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign(
                'production_material_consumption_id',
                'prc_material_consumption_fk'
            )->references('id')->on('production_material_consumptions')
                ->cascadeOnDelete();
            $table->foreign('purchase_item_reel_id', 'prc_reel_fk')
                ->references('id')->on('purchase_item_reels')
                ->restrictOnDelete();

            $table->index(
                ['purchase_item_reel_id', 'consumed_at'],
                'prc_reel_consumed_idx'
            );
            $table->index(
                'production_material_consumption_id',
                'prc_material_consumption_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_reel_consumptions');
        Schema::dropIfExists('purchase_item_reel_measurements');
        Schema::dropIfExists('purchase_item_reels');
    }
};
