<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->string('invoice_no', 120);
            $table->date('invoice_date');
            $table->unsignedBigInteger('currency_id')->index();
            $table->decimal('exchange_rate', 18, 6)->default(1);
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('expense_total', 18, 4)->default(0);
            $table->decimal('grand_total', 18, 4)->default(0);
            $table->decimal('usd_grand_total', 18, 4)->default(0);
            $table->string('match_status', 30)->default('pending')->index();
            $table->decimal('match_variance', 18, 4)->default(0);
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('matched_by')->nullable()->index();
            $table->timestamp('matched_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('matched_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['purchase_id', 'invoice_no']);
        });

        Schema::create('purchase_supplier_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_supplier_invoice_id')->constrained('purchase_supplier_invoices')->cascadeOnDelete();
            $table->unsignedBigInteger('purchase_item_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->decimal('quantity', 18, 6);
            $table->decimal('unit_price', 18, 6);
            $table->decimal('line_total', 18, 4);
            $table->timestamps();
            $table->foreign('purchase_item_id')->references('id')->on('purchase_items')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_supplier_invoice_items');
        Schema::dropIfExists('purchase_supplier_invoices');
    }
};
