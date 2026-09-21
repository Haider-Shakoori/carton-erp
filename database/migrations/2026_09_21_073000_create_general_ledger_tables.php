<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gl_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('type', 20)->index();
            $table->string('normal_balance', 10);
            $table->string('system_key', 80)->nullable()->unique();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->foreign('parent_id')->references('id')->on('gl_accounts')->nullOnDelete();
        });

        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('starts_on')->index();
            $table->date('ends_on')->index();
            $table->string('status', 20)->default('open')->index();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('close_reason')->nullable();
            $table->unsignedBigInteger('reopened_by')->nullable();
            $table->timestamp('reopened_at')->nullable();
            $table->text('reopen_reason')->nullable();
            $table->timestamps();
            $table->foreign('closed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reopened_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['starts_on','ends_on']);
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_no', 80)->unique();
            $table->date('entry_date')->index();
            $table->foreignId('accounting_period_id')->constrained('accounting_periods');
            $table->string('source_type', 100)->nullable()->index();
            $table->unsignedBigInteger('source_id')->nullable()->index();
            $table->string('idempotency_key', 160)->nullable()->unique();
            $table->text('description');
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedBigInteger('reversal_of_id')->nullable()->index();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('posted_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reversed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reversal_of_id')->references('id')->on('journal_entries')->nullOnDelete();
            $table->index(['source_type','source_id'], 'journal_source_idx');
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('gl_account_id')->constrained('gl_accounts');
            $table->unsignedBigInteger('currency_id')->nullable()->index();
            $table->decimal('exchange_rate', 18, 6)->default(1);
            $table->decimal('debit', 20, 4)->default(0);
            $table->decimal('credit', 20, 4)->default(0);
            $table->decimal('debit_usd', 20, 4)->default(0);
            $table->decimal('credit_usd', 20, 4)->default(0);
            $table->string('memo', 500)->nullable();
            $table->timestamps();
            $table->foreign('currency_id')->references('id')->on('currencies')->nullOnDelete();
            $table->index(['gl_account_id','journal_entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounting_periods');
        Schema::dropIfExists('gl_accounts');
    }
};
