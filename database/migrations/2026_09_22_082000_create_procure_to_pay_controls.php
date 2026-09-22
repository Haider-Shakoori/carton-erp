<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'purchase_approval_required')) {
                $table->boolean('purchase_approval_required')
                    ->default(false)
                    ->after('production_approval_required');
            }
        });

        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
            $table->string('request_no')->unique();
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'converted'])->default('draft')->index();
            $table->date('needed_by')->nullable();
            $table->text('justification')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_note')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 6);
            $table->string('unit', 30);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('request_for_quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->string('rfq_no')->unique();
            $table->enum('status', ['draft', 'open', 'closed', 'awarded'])->default('draft')->index();
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('rfq_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_for_quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->string('quote_no')->nullable();
            $table->decimal('exchange_rate', 18, 6)->default(1);
            $table->decimal('total', 18, 2)->default(0);
            $table->enum('status', ['submitted', 'selected', 'rejected'])->default('submitted')->index();
            $table->date('quoted_at')->nullable();
            $table->date('valid_until')->nullable();
            $table->text('terms')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['request_for_quotation_id', 'supplier_id']);
        });

        Schema::create('rfq_quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 6);
            $table->decimal('unit_price', 18, 6);
            $table->decimal('total', 18, 2);
            $table->timestamps();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('purchase_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('rfq_quote_id')->nullable()->constrained('rfq_quotes')->nullOnDelete();
            $table->string('approval_status', 30)->default('pending')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
        });

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->string('receipt_no')->unique();
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft')->index();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity_received', 18, 6)->default(0);
            $table->decimal('quantity_rejected', 18, 6)->default(0);
            $table->string('unit', 30);
            $table->timestamps();

            $table->unique(['goods_receipt_id', 'purchase_item_id']);
        });

        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->string('invoice_no');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('expense_total', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->decimal('usd_total', 18, 2)->default(0);
            $table->enum('status', ['draft', 'matched', 'approved', 'paid', 'cancelled'])->default('draft')->index();
            $table->enum('three_way_match_status', ['pending', 'matched', 'exception'])->default('pending')->index();
            $table->text('match_note')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['purchase_id', 'invoice_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_invoices');
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_request_id');
            $table->dropConstrainedForeignId('rfq_quote_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['approval_status', 'approved_at']);
        });

        Schema::dropIfExists('rfq_quote_items');
        Schema::dropIfExists('rfq_quotes');
        Schema::dropIfExists('request_for_quotations');
        Schema::dropIfExists('purchase_request_items');
        Schema::dropIfExists('purchase_requests');

        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'purchase_approval_required')) {
                $table->dropColumn('purchase_approval_required');
            }
        });
    }
};
