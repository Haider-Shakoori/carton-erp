<?php

namespace App\Services;

use App\Models\AccountingSetting;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\ProductionOrder;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SupplierInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class AccountingService
{
    private const EPSILON = 0.00001;

    public function enabled(): bool
    {
        return Schema::hasTable('journal_entries')
            && Schema::hasTable('journal_lines')
            && Schema::hasTable('accounting_settings');
    }

    public function postPurchaseReceipt(Purchase $purchase): ?JournalEntry
    {
        if (! $this->enabled()) {
            return null;
        }

        $amount = max((float) $purchase->usd_grand_total, 0);
        if ($amount <= self::EPSILON) {
            return null;
        }

        $map = $this->map();

        return $this->postOrReplace(
            rootKey: 'purchase_receipt:'.$purchase->id,
            date: $purchase->arrival_date ?: now(),
            sourceType: 'purchase_receipt',
            sourceId: $purchase->id,
            description: 'Goods receipt / landed raw-material inventory for '.$purchase->purchase_no,
            businessUnitId: $purchase->business_unit_id,
            lines: [
                ['account_id' => $map->raw_material_inventory_account_id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $map->grni_account_id, 'debit' => 0, 'credit' => $amount],
            ]
        );
    }

    public function postSupplierInvoice(SupplierInvoice $invoice): ?JournalEntry
    {
        if (! $this->enabled()) {
            return null;
        }

        $invoice->loadMissing('purchase');
        $purchase = $invoice->purchase;
        if (! $purchase) {
            throw new RuntimeException('Supplier invoice requires its purchase order for accounting.');
        }

        $poAmount = max((float) $purchase->usd_grand_total, 0);
        $invoiceAmount = max((float) $invoice->usd_total, 0);
        if ($invoiceAmount <= self::EPSILON) {
            return null;
        }

        $map = $this->map();
        $lines = [
            ['account_id' => $map->grni_account_id, 'debit' => $poAmount, 'credit' => 0],
        ];

        $difference = $invoiceAmount - $poAmount;
        if ($difference > self::EPSILON) {
            $lines[] = ['account_id' => $map->purchase_variance_account_id, 'debit' => $difference, 'credit' => 0];
        } elseif ($difference < -self::EPSILON) {
            $lines[] = ['account_id' => $map->purchase_variance_account_id, 'debit' => 0, 'credit' => abs($difference)];
        }

        $lines[] = ['account_id' => $map->payables_account_id, 'debit' => 0, 'credit' => $invoiceAmount];

        return $this->postOrReplace(
            rootKey: 'supplier_invoice:'.$invoice->id,
            date: $invoice->invoice_date,
            sourceType: 'supplier_invoice',
            sourceId: $invoice->id,
            description: 'Supplier invoice '.$invoice->invoice_no.' / '.$purchase->purchase_no,
            businessUnitId: $invoice->business_unit_id ?: $purchase->business_unit_id,
            lines: $lines
        );
    }

    public function postSupplierPayment(SupplierInvoice $invoice): ?JournalEntry
    {
        if (! $this->enabled()) {
            return null;
        }

        $amount = max((float) $invoice->usd_total, 0);
        if ($amount <= self::EPSILON) {
            return null;
        }

        $map = $this->map();

        return $this->postOrReplace(
            rootKey: 'supplier_payment:'.$invoice->id,
            date: $invoice->paid_at ?: now(),
            sourceType: 'supplier_payment',
            sourceId: $invoice->id,
            description: 'Supplier invoice payment '.$invoice->invoice_no,
            businessUnitId: $invoice->business_unit_id,
            lines: [
                ['account_id' => $map->payables_account_id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $map->cash_account_id, 'debit' => 0, 'credit' => $amount],
            ]
        );
    }

    public function postSaleInvoice(Sale $sale): ?JournalEntry
    {
        if (! $this->enabled()) {
            return null;
        }

        $amount = max((float) $sale->usd_grand_total, 0);
        if ($amount <= self::EPSILON) {
            return null;
        }

        $map = $this->map();

        return $this->postOrReplace(
            rootKey: 'sale_invoice:'.$sale->id,
            date: $sale->sale_date ?: now(),
            sourceType: 'sale_invoice',
            sourceId: $sale->id,
            description: 'Customer invoice '.$sale->sale_no,
            businessUnitId: $sale->business_unit_id,
            lines: [
                ['account_id' => $map->receivables_account_id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $map->sales_revenue_account_id, 'debit' => 0, 'credit' => $amount],
            ]
        );
    }

    public function postCustomerAdvance(Sale $sale): ?JournalEntry
    {
        if (! $this->enabled()) {
            return null;
        }

        $amount = max((float) $sale->usd_advance_payment, 0);
        if ($amount <= self::EPSILON) {
            return null;
        }

        $map = $this->map();

        return $this->postOrReplace(
            rootKey: 'sale_advance:'.$sale->id,
            date: $sale->confirmed_at ?: now(),
            sourceType: 'customer_payment',
            sourceId: $sale->id,
            description: 'Customer advance/payment for '.$sale->sale_no,
            businessUnitId: $sale->business_unit_id,
            lines: [
                ['account_id' => $map->cash_account_id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $map->receivables_account_id, 'debit' => 0, 'credit' => $amount],
            ]
        );
    }

    public function postProductionMaterialIssue(ProductionOrder $order): ?JournalEntry
    {
        if (! $this->enabled()) {
            return null;
        }

        $amount = max((float) $order->total_material_cost, 0);
        if ($amount <= self::EPSILON) {
            return null;
        }

        $map = $this->map();

        return $this->postOrReplace(
            rootKey: 'production_material:'.$order->id,
            date: $order->start_date ?: now(),
            sourceType: 'production_material',
            sourceId: $order->id,
            description: 'Raw materials issued to WIP for '.$order->order_number,
            businessUnitId: $order->business_unit_id,
            lines: [
                ['account_id' => $map->wip_account_id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $map->raw_material_inventory_account_id, 'debit' => 0, 'credit' => $amount],
            ]
        );
    }

    public function postProductionCompletion(ProductionOrder $order): array
    {
        if (! $this->enabled()) {
            return [];
        }

        // Actual material reconciliation may change material cost after start.
        $material = $this->postProductionMaterialIssue($order);

        $map = $this->map();
        $conversion = max(
            (float) $order->total_labor_cost + (float) $order->total_overhead_cost,
            0
        );
        $total = max((float) $order->total_cost, 0);
        $entries = array_filter([$material]);

        if ($conversion > self::EPSILON) {
            $entries[] = $this->postOrReplace(
                rootKey: 'production_conversion:'.$order->id,
                date: $order->completion_date ?: now(),
                sourceType: 'production_conversion',
                sourceId: $order->id,
                description: 'Labour/overhead absorbed into WIP for '.$order->order_number,
                businessUnitId: $order->business_unit_id,
                lines: [
                    ['account_id' => $map->wip_account_id, 'debit' => $conversion, 'credit' => 0],
                    ['account_id' => $map->production_conversion_clearing_account_id, 'debit' => 0, 'credit' => $conversion],
                ]
            );
        }

        if ($total > self::EPSILON) {
            $entries[] = $this->postOrReplace(
                rootKey: 'production_completion:'.$order->id,
                date: $order->completion_date ?: now(),
                sourceType: 'production_completion',
                sourceId: $order->id,
                description: 'Completed production transferred from WIP to finished goods: '.$order->order_number,
                businessUnitId: $order->business_unit_id,
                lines: [
                    ['account_id' => $map->finished_goods_inventory_account_id, 'debit' => $total, 'credit' => 0],
                    ['account_id' => $map->wip_account_id, 'debit' => 0, 'credit' => $total],
                ]
            );
        }

        return array_values($entries);
    }

    public function postDeliveryCogs(Sale $sale): ?JournalEntry
    {
        if (! $this->enabled()) {
            return null;
        }

        $sale->loadMissing('productionOrder');
        $order = $sale->productionOrder;
        if (! $order) {
            return null;
        }

        $amount = max((float) $order->total_cost, 0);
        if ($amount <= self::EPSILON) {
            return null;
        }

        $map = $this->map();

        return $this->postOrReplace(
            rootKey: 'sale_cogs:'.$sale->id,
            date: $sale->delivery_date ?: now(),
            sourceType: 'sale_cogs',
            sourceId: $sale->id,
            description: 'COGS recognized on delivery of '.$sale->sale_no,
            businessUnitId: $sale->business_unit_id,
            lines: [
                ['account_id' => $map->cogs_account_id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $map->finished_goods_inventory_account_id, 'debit' => 0, 'credit' => $amount],
            ]
        );
    }

    public function reverseProductionEntries(ProductionOrder $order, string $reason): void
    {
        foreach ([
            'production_completion:'.$order->id,
            'production_conversion:'.$order->id,
            'production_material:'.$order->id,
        ] as $root) {
            $entry = JournalEntry::query()
                ->withoutGlobalScope('business_unit')
                ->where('source_root_key', $root)
                ->where('status', 'posted')
                ->latest('source_version')
                ->first();

            if ($entry) {
                $this->reverseEntry($entry, $reason);
            }
        }
    }

    public function reverseEntry(JournalEntry $entry, string $reason): JournalEntry
    {
        if ($entry->status !== 'posted') {
            throw new RuntimeException('Only posted journal entries can be reversed.');
        }

        $entry->loadMissing('lines');

        return DB::transaction(function () use ($entry, $reason) {
            $locked = JournalEntry::query()
                ->withoutGlobalScope('business_unit')
                ->lockForUpdate()
                ->findOrFail($entry->id);

            if ($locked->status !== 'posted') {
                throw new RuntimeException('Journal entry was already reversed.');
            }

            $lines = $locked->lines()->get()->map(fn ($line) => [
                'account_id' => $line->gl_account_id,
                'debit' => (float) $line->credit_usd,
                'credit' => (float) $line->debit_usd,
                'currency_id' => $line->currency_id,
                'original_amount' => $line->original_amount,
                'exchange_rate' => $line->exchange_rate,
                'description' => 'Reversal: '.($line->description ?: $locked->description),
            ])->all();

            $period = $this->periodFor(now());
            $reversal = $this->createEntry(
                period: $period,
                rootKey: 'reversal:'.$locked->id,
                version: 1,
                date: now(),
                sourceType: 'journal_reversal',
                sourceId: $locked->id,
                description: 'Reversal of '.$locked->entry_no.': '.$reason,
                businessUnitId: $locked->business_unit_id,
                lines: $lines,
                reversalOfId: $locked->id
            );

            $locked->status = 'reversed';
            $locked->reversal_reason = $reason;
            $locked->save();

            return $reversal;
        });
    }

    public function postOrReplace(
        string $rootKey,
        Carbon|string|null $date,
        string $sourceType,
        int $sourceId,
        string $description,
        ?int $businessUnitId,
        array $lines
    ): JournalEntry {
        if (! $this->enabled()) {
            throw new RuntimeException('Accounting subsystem is not installed.');
        }

        return DB::transaction(function () use (
            $rootKey, $date, $sourceType, $sourceId, $description, $businessUnitId, $lines
        ) {
            $date = $date instanceof Carbon ? $date : Carbon::parse($date ?: now());
            $period = $this->periodFor($date);

            $current = JournalEntry::query()
                ->withoutGlobalScope('business_unit')
                ->where('source_root_key', $rootKey)
                ->where('status', 'posted')
                ->with('lines')
                ->latest('source_version')
                ->lockForUpdate()
                ->first();

            $normalized = $this->normalizeLines($lines);

            if ($current && $this->sameLines($current, $normalized)) {
                return $current;
            }

            $version = (int) JournalEntry::query()
                ->withoutGlobalScope('business_unit')
                ->where('source_root_key', $rootKey)
                ->max('source_version') + 1;
            $version = max($version, 1);

            if ($current) {
                $this->reverseEntry($current, 'Automatic replacement after source document changed.');
            }

            return $this->createEntry(
                period: $period,
                rootKey: $rootKey,
                version: $version,
                date: $date,
                sourceType: $sourceType,
                sourceId: $sourceId,
                description: $description,
                businessUnitId: $businessUnitId,
                lines: $normalized
            );
        });
    }

    public function trialBalance(
        ?Carbon $asOf = null,
        bool $consolidated = false,
        array $allowedBusinessUnitIds = []
    ): array {
        $asOf ??= now();

        $query = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('gl_accounts', 'gl_accounts.id', '=', 'journal_lines.gl_account_id')
            ->whereDate('journal_entries.entry_date', '<=', $asOf->toDateString());

        $this->applyJournalBusinessScope($query, $consolidated, $allowedBusinessUnitIds);

        $rows = $query
            ->select(
                'gl_accounts.id',
                'gl_accounts.code',
                'gl_accounts.name',
                'gl_accounts.type',
                DB::raw('SUM(journal_lines.debit_usd) as debit_usd'),
                DB::raw('SUM(journal_lines.credit_usd) as credit_usd')
            )
            ->groupBy('gl_accounts.id','gl_accounts.code','gl_accounts.name','gl_accounts.type')
            ->orderBy('gl_accounts.code')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'type' => $row->type,
                'debit_usd' => (float) $row->debit_usd,
                'credit_usd' => (float) $row->credit_usd,
                'balance_usd' => (float) $row->debit_usd - (float) $row->credit_usd,
            ])
            ->all();

        return [
            'as_of' => $asOf->toDateString(),
            'rows' => $rows,
            'debit_usd' => array_sum(array_column($rows, 'debit_usd')),
            'credit_usd' => array_sum(array_column($rows, 'credit_usd')),
        ];
    }

    public function profitAndLoss(
        Carbon $from,
        Carbon $to,
        bool $consolidated = false,
        array $allowedBusinessUnitIds = []
    ): array {
        $query = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('gl_accounts', 'gl_accounts.id', '=', 'journal_lines.gl_account_id')
            ->whereBetween('journal_entries.entry_date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('gl_accounts.type', ['revenue', 'expense']);

        $this->applyJournalBusinessScope($query, $consolidated, $allowedBusinessUnitIds);

        $rows = $query
            ->select(
                'gl_accounts.code',
                'gl_accounts.name',
                'gl_accounts.type',
                DB::raw('SUM(journal_lines.debit_usd) AS debit_usd'),
                DB::raw('SUM(journal_lines.credit_usd) AS credit_usd')
            )
            ->groupBy('gl_accounts.id', 'gl_accounts.code', 'gl_accounts.name', 'gl_accounts.type')
            ->orderBy('gl_accounts.code')
            ->get()
            ->map(function ($row) {
                $amount = $row->type === 'revenue'
                    ? (float) $row->credit_usd - (float) $row->debit_usd
                    : (float) $row->debit_usd - (float) $row->credit_usd;

                return [
                    'code' => $row->code,
                    'name' => $row->name,
                    'type' => $row->type,
                    'amount_usd' => round($amount, 6),
                ];
            });

        $revenue = (float) $rows->where('type', 'revenue')->sum('amount_usd');
        $expenses = (float) $rows->where('type', 'expense')->sum('amount_usd');

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'rows' => $rows->all(),
            'revenue_usd' => round($revenue, 6),
            'expenses_usd' => round($expenses, 6),
            'net_profit_usd' => round($revenue - $expenses, 6),
        ];
    }

    public function balanceSheet(
        ?Carbon $asOf = null,
        bool $consolidated = false,
        array $allowedBusinessUnitIds = []
    ): array {
        $asOf ??= now();

        $query = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('gl_accounts', 'gl_accounts.id', '=', 'journal_lines.gl_account_id')
            ->whereDate('journal_entries.entry_date', '<=', $asOf->toDateString())
            ->whereIn('gl_accounts.type', ['asset', 'liability', 'equity']);

        $this->applyJournalBusinessScope($query, $consolidated, $allowedBusinessUnitIds);

        $rows = $query
            ->select(
                'gl_accounts.code',
                'gl_accounts.name',
                'gl_accounts.type',
                DB::raw('SUM(journal_lines.debit_usd) AS debit_usd'),
                DB::raw('SUM(journal_lines.credit_usd) AS credit_usd')
            )
            ->groupBy('gl_accounts.id', 'gl_accounts.code', 'gl_accounts.name', 'gl_accounts.type')
            ->orderBy('gl_accounts.code')
            ->get()
            ->map(function ($row) {
                $amount = $row->type === 'asset'
                    ? (float) $row->debit_usd - (float) $row->credit_usd
                    : (float) $row->credit_usd - (float) $row->debit_usd;

                return [
                    'code' => $row->code,
                    'name' => $row->name,
                    'type' => $row->type,
                    'amount_usd' => round($amount, 6),
                ];
            });

        return [
            'as_of' => $asOf->toDateString(),
            'rows' => $rows->all(),
            'assets_usd' => round((float) $rows->where('type', 'asset')->sum('amount_usd'), 6),
            'liabilities_usd' => round((float) $rows->where('type', 'liability')->sum('amount_usd'), 6),
            'equity_usd' => round((float) $rows->where('type', 'equity')->sum('amount_usd'), 6),
        ];
    }

    public function cashFlow(
        Carbon $from,
        Carbon $to,
        bool $consolidated = false,
        array $allowedBusinessUnitIds = []
    ): array {
        $cashAccountId = $this->map()->cash_account_id;

        $query = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.gl_account_id', $cashAccountId)
            ->whereBetween('journal_entries.entry_date', [$from->toDateString(), $to->toDateString()]);

        $this->applyJournalBusinessScope($query, $consolidated, $allowedBusinessUnitIds);

        $rows = $query
            ->select(
                'journal_entries.source_type',
                DB::raw('SUM(journal_lines.debit_usd) AS cash_in_usd'),
                DB::raw('SUM(journal_lines.credit_usd) AS cash_out_usd')
            )
            ->groupBy('journal_entries.source_type')
            ->orderBy('journal_entries.source_type')
            ->get()
            ->map(fn ($row) => [
                'source_type' => $row->source_type ?: 'manual',
                'cash_in_usd' => round((float) $row->cash_in_usd, 6),
                'cash_out_usd' => round((float) $row->cash_out_usd, 6),
                'net_usd' => round((float) $row->cash_in_usd - (float) $row->cash_out_usd, 6),
            ]);

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'rows' => $rows->all(),
            'cash_in_usd' => round((float) $rows->sum('cash_in_usd'), 6),
            'cash_out_usd' => round((float) $rows->sum('cash_out_usd'), 6),
            'net_cash_flow_usd' => round((float) $rows->sum('net_usd'), 6),
        ];
    }

    private function applyJournalBusinessScope(
        $query,
        bool $consolidated,
        array $allowedBusinessUnitIds
    ): void {
        $context = app(\App\Support\Business\BusinessUnitContext::class);

        if (! $context->enabled()) {
            return;
        }

        if ($consolidated) {
            if ($allowedBusinessUnitIds !== []) {
                $query->where(function ($businessQuery) use ($allowedBusinessUnitIds) {
                    $businessQuery->whereIn('journal_entries.business_unit_id', $allowedBusinessUnitIds)
                        ->orWhereNull('journal_entries.business_unit_id');
                });
            }

            return;
        }

        $current = $context->current();
        if ($current) {
            $query->where(function ($businessQuery) use ($current) {
                $businessQuery->where('journal_entries.business_unit_id', $current->id)
                    ->orWhereNull('journal_entries.business_unit_id');
            });
        }
    }

    private function createEntry(
        FiscalPeriod $period,
        string $rootKey,
        int $version,
        Carbon $date,
        string $sourceType,
        int $sourceId,
        string $description,
        ?int $businessUnitId,
        array $lines,
        ?int $reversalOfId = null
    ): JournalEntry {
        $normalized = $this->normalizeLines($lines);
        $debit = array_sum(array_column($normalized, 'debit'));
        $credit = array_sum(array_column($normalized, 'credit'));

        if (abs($debit - $credit) > self::EPSILON) {
            throw new RuntimeException(sprintf(
                'Journal is not balanced. Debit %.6f, credit %.6f.',
                $debit,
                $credit
            ));
        }

        if ($debit <= self::EPSILON) {
            throw new RuntimeException('Journal entry amount must be greater than zero.');
        }

        $entry = JournalEntry::query()->withoutGlobalScope('business_unit')->create([
            'business_unit_id' => $businessUnitId,
            'fiscal_period_id' => $period->id,
            'entry_no' => $this->nextEntryNumber(),
            'entry_date' => $date->toDateString(),
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_root_key' => $rootKey,
            'source_version' => $version,
            'description' => $description,
            'status' => 'posted',
            'reversal_of_id' => $reversalOfId,
            'posted_by' => auth()->id(),
            'posted_at' => now(),
        ]);

        foreach ($normalized as $line) {
            $entry->lines()->create([
                'gl_account_id' => $line['account_id'],
                'currency_id' => $line['currency_id'] ?? null,
                'debit_usd' => $line['debit'],
                'credit_usd' => $line['credit'],
                'original_amount' => $line['original_amount'] ?? null,
                'exchange_rate' => $line['exchange_rate'] ?? null,
                'description' => $line['description'] ?? $description,
            ]);
        }

        return $entry->fresh('lines');
    }

    private function periodFor(Carbon|string $date): FiscalPeriod
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        $period = FiscalPeriod::query()
            ->whereDate('starts_on', '<=', $date->toDateString())
            ->whereDate('ends_on', '>=', $date->toDateString())
            ->first();

        if (! $period) {
            $period = FiscalPeriod::query()->firstOrCreate(
                [
                    'starts_on' => $date->copy()->startOfYear()->toDateString(),
                    'ends_on' => $date->copy()->endOfYear()->toDateString(),
                ],
                [
                    'name' => $date->format('Y').' Fiscal Year',
                    'status' => 'open',
                ]
            );
        }

        if ($period->status !== 'open') {
            throw new RuntimeException(
                "Accounting period {$period->name} is {$period->status}; posting is blocked."
            );
        }

        return $period;
    }

    private function map(): AccountingSetting
    {
        return AccountingSetting::query()->firstOrFail();
    }

    private function normalizeLines(array $lines): array
    {
        return collect($lines)
            ->map(function (array $line): array {
                return [
                    'account_id' => (int) ($line['account_id'] ?? 0),
                    'debit' => round(max((float) ($line['debit'] ?? 0), 0), 6),
                    'credit' => round(max((float) ($line['credit'] ?? 0), 0), 6),
                    'currency_id' => isset($line['currency_id']) ? (int) $line['currency_id'] : null,
                    'original_amount' => isset($line['original_amount']) ? (float) $line['original_amount'] : null,
                    'exchange_rate' => isset($line['exchange_rate']) ? (float) $line['exchange_rate'] : null,
                    'description' => $line['description'] ?? null,
                ];
            })
            ->filter(fn (array $line) => $line['debit'] > self::EPSILON || $line['credit'] > self::EPSILON)
            ->values()
            ->all();
    }

    private function sameLines(JournalEntry $entry, array $lines): bool
    {
        $existing = $entry->lines
            ->groupBy('gl_account_id')
            ->map(fn ($rows, $accountId) => [
                'account_id' => (int) $accountId,
                'debit' => round((float) $rows->sum('debit_usd'), 6),
                'credit' => round((float) $rows->sum('credit_usd'), 6),
            ])
            ->sortBy('account_id')
            ->values()
            ->all();

        $wanted = collect($lines)
            ->groupBy('account_id')
            ->map(fn ($rows, $accountId) => [
                'account_id' => (int) $accountId,
                'debit' => round((float) $rows->sum('debit'), 6),
                'credit' => round((float) $rows->sum('credit'), 6),
            ])
            ->sortBy('account_id')
            ->values()
            ->all();

        return $existing === $wanted;
    }

    private function nextEntryNumber(): string
    {
        $prefix = 'JE-'.now()->format('Ym').'-';
        $last = JournalEntry::query()
            ->withoutGlobalScope('business_unit')
            ->where('entry_no', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('entry_no');

        $next = $last ? ((int) substr($last, -5)) + 1 : 1;

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
