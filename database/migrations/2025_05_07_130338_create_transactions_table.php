<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            // ─── Polymorphic Reference (keep for flexibility) ───
            $table->string('table_name')->nullable();
            $table->bigInteger('table_row_id')->nullable();

            // ─── Specific Foreign Keys (PROFESSIONAL APPROACH) ───
            $table->unsignedBigInteger('purchase_id')->nullable()->index();
            $table->unsignedBigInteger('sale_id')->nullable()->index();
            $table->unsignedBigInteger('sale_return_id')->nullable()->index();
            $table->unsignedBigInteger('purchase_return_id')->nullable()->index();
            $table->unsignedBigInteger('exchange_id')->nullable()->index();

            // ─── Transaction Type ───
            $table->enum('type', [
                'purchase',
                'sale',
                'sale_return',
                'purchase_return',
                'exchange',
                'remittance',
                'transfer',
                'adjustment'
            ])->nullable()->index();

            // ─── Account References ───
            $table->unsignedBigInteger('account_id')->index();
            $table->unsignedBigInteger('currency_id')->index();

            // ─── Amounts ───
            $table->decimal('amount', 20, 2);
            $table->decimal('usd_amount', 20, 2)->nullable();
            $table->decimal('exchange_rate', 18, 6)->nullable()->default(1);

            // ─── Transaction Direction ───
            $table->enum('transaction_type', ['credit', 'debit'])->index();

            // ─── Cash Flag ───
            $table->boolean('is_cash')->default(false);

            // ─── Description & Notes ───
            $table->text('description')->nullable();
            $table->text('notes')->nullable();

            // ─── Status Flags ───
            $table->boolean('is_visible')->default(true)->index();
            $table->enum('status', ['active', 'cancelled', 'reversed', 'pending'])->default('active');

            // ─── Audit Trail ───
            $table->unsignedBigInteger('created_by')->index();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            // ─── Soft Delete ───
            $table->softDeletes();

            // ─── Timestamps ───
            $table->timestamps();

            // ─── Composite Indexes for Performance ───
            $table->index(['purchase_id', 'type']);
            $table->index(['sale_id', 'type']);
            $table->index(['account_id', 'transaction_type']);
            $table->index(['account_id', 'currency_id']);
            $table->index(['created_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
