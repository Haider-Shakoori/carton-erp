<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->enum('status', ['open', 'closed', 'locked'])->default('open')->index();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['starts_on', 'ends_on']);
        });

        Schema::create('gl_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense'])->index();
            $table->foreignId('parent_id')->nullable()->constrained('gl_accounts')->nullOnDelete();
            $table->boolean('is_control')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('accounting_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cash_account_id');
            $table->unsignedBigInteger('receivables_account_id');
            $table->unsignedBigInteger('raw_material_inventory_account_id');
            $table->unsignedBigInteger('wip_account_id');
            $table->unsignedBigInteger('finished_goods_inventory_account_id');
            $table->unsignedBigInteger('grni_account_id');
            $table->unsignedBigInteger('payables_account_id');
            $table->unsignedBigInteger('sales_revenue_account_id');
            $table->unsignedBigInteger('cogs_account_id');
            $table->unsignedBigInteger('production_conversion_clearing_account_id');
            $table->unsignedBigInteger('purchase_variance_account_id');
            $table->unsignedBigInteger('production_variance_account_id');
            $table->unsignedBigInteger('wastage_expense_account_id');
            $table->timestamps();

            $table->foreign('cash_account_id', 'acct_cfg_cash_fk')->references('id')->on('gl_accounts')->restrictOnDelete();
            $table->foreign('receivables_account_id', 'acct_cfg_ar_fk')->references('id')->on('gl_accounts')->restrictOnDelete();
            $table->foreign('raw_material_inventory_account_id', 'acct_cfg_raw_inv_fk')->references('id')->on('gl_accounts')->restrictOnDelete();
            $table->foreign('wip_account_id', 'acct_cfg_wip_fk')->references('id')->on('gl_accounts')->restrictOnDelete();
            $table->foreign('finished_goods_inventory_account_id', 'acct_cfg_fg_inv_fk')->references('id')->on('gl_accounts')->restrictOnDelete();
            $table->foreign('grni_account_id', 'acct_cfg_grni_fk')->references('id')->on('gl_accounts')->restrictOnDelete();
            $table->foreign('payables_account_id', 'acct_cfg_ap_fk')->references('id')->on('gl_accounts')->restrictOnDelete();
            $table->foreign('sales_revenue_account_id', 'acct_cfg_sales_fk')->references('id')->on('gl_accounts')->restrictOnDelete();
            $table->foreign('cogs_account_id', 'acct_cfg_cogs_fk')->references('id')->on('gl_accounts')->restrictOnDelete();
            $table->foreign('production_conversion_clearing_account_id', 'acct_cfg_prod_clear_fk')->references('id')->on('gl_accounts')->restrictOnDelete();
            $table->foreign('purchase_variance_account_id', 'acct_cfg_purch_var_fk')->references('id')->on('gl_accounts')->restrictOnDelete();
            $table->foreign('production_variance_account_id', 'acct_cfg_prod_var_fk')->references('id')->on('gl_accounts')->restrictOnDelete();
            $table->foreign('wastage_expense_account_id', 'acct_cfg_waste_fk')->references('id')->on('gl_accounts')->restrictOnDelete();
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
            $table->foreignId('fiscal_period_id')->constrained('fiscal_periods')->restrictOnDelete();
            $table->string('entry_no')->unique();
            $table->date('entry_date')->index();
            $table->string('source_type', 80)->nullable()->index();
            $table->unsignedBigInteger('source_id')->nullable()->index();
            $table->string('source_root_key', 190)->nullable()->index();
            $table->unsignedInteger('source_version')->default(1);
            $table->text('description');
            $table->enum('status', ['posted', 'reversed'])->default('posted')->index();
            $table->foreignId('reversal_of_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();

            $table->unique(['source_root_key', 'source_version'], 'journal_source_version_unique');
            $table->index(['source_type', 'source_id', 'status']);
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gl_account_id')->constrained('gl_accounts')->restrictOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('debit_usd', 20, 6)->default(0);
            $table->decimal('credit_usd', 20, 6)->default(0);
            $table->decimal('original_amount', 20, 6)->nullable();
            $table->decimal('exchange_rate', 20, 8)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['gl_account_id', 'journal_entry_id']);
        });

        $now = now();

        DB::table('fiscal_periods')->insert([
            'name' => now()->format('Y').' Fiscal Year',
            'starts_on' => now()->startOfYear()->toDateString(),
            'ends_on' => now()->endOfYear()->toDateString(),
            'status' => 'open',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $accounts = [
            ['1000', 'Cash / Bank', 'asset', true],
            ['1100', 'Accounts Receivable', 'asset', true],
            ['1200', 'Raw Material Inventory', 'asset', true],
            ['1300', 'Work in Process Inventory', 'asset', true],
            ['1400', 'Finished Goods Inventory', 'asset', true],
            ['2000', 'Goods Received Not Invoiced', 'liability', true],
            ['2100', 'Accounts Payable', 'liability', true],
            ['2200', 'Manufacturing Conversion Clearing', 'liability', true],
            ['3000', 'Owner Equity', 'equity', true],
            ['4000', 'Sales Revenue', 'revenue', true],
            ['5000', 'Cost of Goods Sold', 'expense', true],
            ['5100', 'Purchase Price / Invoice Variance', 'expense', false],
            ['5200', 'Production Cost Variance', 'expense', false],
            ['5300', 'Production Wastage Expense', 'expense', false],
        ];

        foreach ($accounts as [$code, $name, $type, $control]) {
            DB::table('gl_accounts')->insert([
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'is_control' => $control,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $ids = DB::table('gl_accounts')->pluck('id', 'code');

        DB::table('accounting_settings')->insert([
            'cash_account_id' => $ids['1000'],
            'receivables_account_id' => $ids['1100'],
            'raw_material_inventory_account_id' => $ids['1200'],
            'wip_account_id' => $ids['1300'],
            'finished_goods_inventory_account_id' => $ids['1400'],
            'grni_account_id' => $ids['2000'],
            'payables_account_id' => $ids['2100'],
            'production_conversion_clearing_account_id' => $ids['2200'],
            'sales_revenue_account_id' => $ids['4000'],
            'cogs_account_id' => $ids['5000'],
            'purchase_variance_account_id' => $ids['5100'],
            'production_variance_account_id' => $ids['5200'],
            'wastage_expense_account_id' => $ids['5300'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounting_settings');
        Schema::dropIfExists('gl_accounts');
        Schema::dropIfExists('fiscal_periods');
    }
};
