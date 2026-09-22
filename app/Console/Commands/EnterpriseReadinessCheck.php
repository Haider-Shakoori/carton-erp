<?php

namespace App\Console\Commands;

use App\Models\BusinessUnit;
use App\Models\FiscalPeriod;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class EnterpriseReadinessCheck extends Command
{
    protected $signature = 'erp:readiness {--strict : Treat operational warnings as failures}';

    protected $description = 'Verify enterprise ERP database, accounting, business-unit and runtime readiness.';

    public function handle(): int
    {
        $checks = [];
        $criticalFailure = false;
        $warningFailure = false;

        $add = function (
            string $name,
            bool $ok,
            string $detail,
            bool $critical = true
        ) use (&$checks, &$criticalFailure, &$warningFailure): void {
            $checks[] = [$name, $ok ? 'PASS' : ($critical ? 'FAIL' : 'WARN'), $detail];

            if (! $ok) {
                $critical ? $criticalFailure = true : $warningFailure = true;
            }
        };

        try {
            DB::select('SELECT 1');
            $add('Database connection', true, 'Database responded successfully.');
        } catch (Throwable $e) {
            $add('Database connection', false, $e->getMessage());
        }

        foreach ([
            'business_units',
            'production_order_events',
            'warehouses',
            'warehouse_locations',
            'inventory_location_balances',
            'inventory_transfers',
            'purchase_requests',
            'goods_receipts',
            'supplier_invoices',
            'fiscal_periods',
            'gl_accounts',
            'journal_entries',
            'journal_lines',
            'business_unit_user',
        ] as $table) {
            $add(
                "Schema: {$table}",
                Schema::hasTable($table),
                Schema::hasTable($table) ? 'Installed.' : 'Missing required enterprise table.'
            );
        }

        if (Schema::hasTable('settings')) {
            $settings = Setting::query()->first();
            $add('System settings', (bool) $settings, $settings ? 'Settings row available.' : 'No settings row exists.');

            if ($settings && (bool) $settings->separate_business_units_enabled) {
                $codes = BusinessUnit::query()->where('is_active', true)->pluck('code')->all();
                $add(
                    'Business units',
                    in_array('3d_carton', $codes, true) && in_array('syrup_pack', $codes, true),
                    'Separate mode requires active 3D Carton and Syrup Pack workspaces.'
                );
            }
        }

        if (Schema::hasTable('accounting_settings') && Schema::hasTable('gl_accounts')) {
            $mapping = DB::table('accounting_settings')->first();
            $add(
                'Accounting map',
                (bool) $mapping,
                $mapping ? 'Control-account mapping available.' : 'Accounting control mapping is missing.'
            );

            $openPeriod = FiscalPeriod::query()
                ->where('status', 'open')
                ->whereDate('starts_on', '<=', now()->toDateString())
                ->whereDate('ends_on', '>=', now()->toDateString())
                ->exists();

            $add(
                'Open fiscal period',
                $openPeriod,
                $openPeriod ? 'Current date accepts accounting postings.' : 'No open fiscal period covers today.'
            );
        }

        if (Schema::hasTable('journal_entries') && Schema::hasTable('journal_lines')) {
            $unbalanced = DB::table('journal_lines')
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
                ->where('journal_entries.status', 'posted')
                ->groupBy('journal_entries.id')
                ->havingRaw('ABS(SUM(journal_lines.debit_usd) - SUM(journal_lines.credit_usd)) > 0.00001')
                ->select('journal_entries.id as journal_entry_id')
                ->get()
                ->count();

            $add(
                'Journal balance integrity',
                $unbalanced === 0,
                $unbalanced === 0 ? 'All posted journals balance.' : "{$unbalanced} posted journal(s) are unbalanced."
            );
        }

        if (Schema::hasTable('purchase_items')) {
            $negative = DB::table('purchase_items')
                ->where(function ($query) {
                    $query->where('qty_available', '<', 0);

                    if (Schema::hasColumn('purchase_items', 'qty_kg_available')) {
                        $query->orWhere('qty_kg_available', '<', 0);
                    }
                })
                ->count();

            $add(
                'Negative inventory',
                $negative === 0,
                $negative === 0 ? 'No negative purchase-batch inventory found.' : "{$negative} batch(es) contain negative inventory."
            );
        }

        try {
            $path = 'readiness/.enterprise-write-test';
            Storage::disk('local')->put($path, now()->toIso8601String());
            $written = Storage::disk('local')->exists($path);
            Storage::disk('local')->delete($path);
            $add('Storage write', $written, $written ? 'Local storage is writable.' : 'Local storage write failed.');
        } catch (Throwable $e) {
            $add('Storage write', false, $e->getMessage());
        }

        $appKey = (string) config('app.key');
        $add('Application key', $appKey !== '', $appKey !== '' ? 'APP_KEY configured.' : 'APP_KEY is missing.');

        if (app()->environment('production')) {
            $add(
                'Production debug',
                ! (bool) config('app.debug'),
                config('app.debug') ? 'APP_DEBUG must be false in production.' : 'Debug is disabled.'
            );

            $queue = (string) config('queue.default');
            $add(
                'Production queue',
                $queue !== 'sync',
                "Queue connection: {$queue}.",
                false
            );

            $url = (string) config('app.url');
            $add(
                'HTTPS application URL',
                str_starts_with($url, 'https://'),
                "APP_URL: {$url}.",
                false
            );
        }

        $this->table(['Check', 'Status', 'Detail'], $checks);

        if ($criticalFailure || ($this->option('strict') && $warningFailure)) {
            $this->error('Enterprise readiness checks failed.');

            return self::FAILURE;
        }

        if ($warningFailure) {
            $this->warn('Enterprise readiness passed with warnings.');
        } else {
            $this->info('Enterprise readiness passed.');
        }

        return self::SUCCESS;
    }
}
