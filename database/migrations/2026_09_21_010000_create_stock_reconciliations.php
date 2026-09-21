<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->string('reconciliation_no', 50)->unique();
            $table->date('count_date');
            $table->timestamp('snapshot_at');
            $table->string('status', 30)->default('counting');
            $table->string('scope', 50)->default('all_active_batches');
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->foreign('created_by', 'stock_rec_created_by_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('submitted_by', 'stock_rec_submitted_by_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by', 'stock_rec_approved_by_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('posted_by', 'stock_rec_posted_by_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->index(['status', 'count_date'], 'stock_rec_status_date_idx');
            $table->index('snapshot_at', 'stock_rec_snapshot_idx');
        });

        Schema::create('stock_reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_reconciliation_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('purchase_item_id');

            $table->string('material_name', 255);
            $table->string('batch_no', 100)->nullable();
            $table->string('purchase_no', 100)->nullable();
            $table->string('unit', 50);

            $table->decimal('system_quantity', 18, 6);
            $table->decimal('physical_quantity', 18, 6)->nullable();
            $table->decimal('variance_quantity', 18, 6)->nullable();

            $table->decimal('cost_per_unit_usd', 18, 6)->default(0);
            $table->decimal('variance_value_usd', 18, 4)->nullable();

            $table->timestamp('batch_updated_at_snapshot')->nullable();
            $table->string('reason_code', 80)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('stock_reconciliation_id', 'stock_rec_item_header_fk')
                ->references('id')->on('stock_reconciliations')->cascadeOnDelete();
            $table->foreign('product_id', 'stock_rec_item_product_fk')
                ->references('id')->on('products')->restrictOnDelete();
            $table->foreign('purchase_item_id', 'stock_rec_item_purchase_fk')
                ->references('id')->on('purchase_items')->restrictOnDelete();

            $table->unique(
                ['stock_reconciliation_id', 'purchase_item_id'],
                'stock_rec_item_batch_unique'
            );
            $table->index(['product_id', 'purchase_item_id'], 'stock_rec_item_product_batch_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reconciliation_items');
        Schema::dropIfExists('stock_reconciliations');
    }
};
