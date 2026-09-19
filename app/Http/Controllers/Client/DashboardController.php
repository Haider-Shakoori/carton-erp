<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\{
    Account,
    AccountBalance,
    Transaction,
    Exchange,
    Remittance,
    Currency,
    ExchangeRate,
    Setting
};
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $accountId = Auth::user()->account_id ?? Account::where('user_id', Auth::id())->value('id');
        $account   = Account::findOrFail($accountId);

        // ---------------- BALANCES ----------------
        $balances = AccountBalance::with('currency:id,code,symbol')
            ->where('account_id', $accountId)
            ->orderBy('currency_id')
            ->get(['currency_id', 'credit', 'debit', 'balance']);

        // ---------------- TOTALS ----------------
        $totalExchange = (float) Exchange::where('customer_account_id', $accountId)->sum('base_amount');
        $totalExchangeCount = (int) Exchange::where('customer_account_id', $accountId)->count();

        $totalRemittance = (float) Remittance::where('account_id', $accountId)->sum('amount');
        $totalRemittanceCount = (int) Remittance::where('account_id', $accountId)->count();

        // ---------------- RECENT TRANSACTIONS ----------------
        $recentTransactions = Transaction::with('currency:id,code,symbol')
            ->where('account_id', $accountId)
            ->orderByDesc('created_at')
            ->take(5)
            ->get(['id', 'created_at', 'note', 'currency_id', 'amount', 'transaction_type']);

        // ---------------- FILTER OPTIONS ----------------
        $currencies = Currency::where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'symbol']);

        // ---------------- MONTHLY NET CHART ----------------
        $monthlyNet = Transaction::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym")
            ->selectRaw("SUM(CASE WHEN transaction_type='credit' THEN amount ELSE -amount END) as net")
            ->where('account_id', $accountId)
            ->groupBy('ym')
            ->orderBy('ym')
            ->limit(6)
            ->pluck('net', 'ym');

        // ---------------- EXCHANGE SUMMARY ----------------
        $exBase = Exchange::select('base_currency_id', DB::raw('SUM(base_amount) as total'))
            ->where('customer_account_id', $accountId)
            ->groupBy('base_currency_id')
            ->pluck('total', 'base_currency_id');

        $exTarget = Exchange::select('target_currency_id', DB::raw('SUM(target_amount) as total'))
            ->where('customer_account_id', $accountId)
            ->groupBy('target_currency_id')
            ->pluck('total', 'target_currency_id');

        // ---------------- REMITTANCE SUMMARY ----------------
        $remitByCurrency = Remittance::select('currency_id', DB::raw('SUM(amount) as total'))
            ->where('account_id', $accountId)
            ->groupBy('currency_id')
            ->pluck('total', 'currency_id');

        $today = Carbon::today();

        $latestRates = ExchangeRate::with(['baseCurrency:id,code', 'targetCurrency:id,code'])
        ->whereDate('created_at', $today)
        ->select('id', 'base_currency_id', 'target_currency_id', 'min_amount', 'max_amount', 'rate')
        ->latest('id')
        ->get();

        return view('client.dashboard', compact(
            'account',
            'balances',
            'totalExchange',
            'totalExchangeCount',
            'totalRemittance',
            'totalRemittanceCount',
            'recentTransactions',
            'currencies',
            'monthlyNet',
            'exBase',
            'exTarget',
            'remitByCurrency',
            'latestRates',
        ));
    }

    // ============================================================
    // JOURNAL
    // ============================================================
    public function journal(Request $r, $accountId)
    {
        $q = Transaction::with('currency:id,code,symbol')
            ->where('account_id', $accountId)
            ->orderByDesc('created_at');

        if ($r->filled('currency_id')) $q->where('currency_id', $r->currency_id);
        if ($r->filled('type') && in_array($r->type, ['credit', 'debit'])) $q->where('transaction_type', $r->type);
        if ($r->filled('start_date') && $r->filled('end_date')) {
            $q->whereBetween(DB::raw('DATE(created_at)'), [$r->start_date, $r->end_date]);
        }

        $total = (clone $q)->count();
        $data = $q->skip($r->start ?? 0)->take($r->length ?? 20)->get();

        return response()->json([
            'data' => $data,
            'recordsTotal' => $total,
        ]);
    }

    public function journalSummary(Request $r, $accountId)
    {
        $q = Transaction::where('account_id', $accountId);

        if ($r->filled('currency_id')) $q->where('currency_id', $r->currency_id);
        if ($r->filled('type') && in_array($r->type, ['credit', 'debit'])) $q->where('transaction_type', $r->type);
        if ($r->filled('start_date') && $r->filled('end_date')) {
            $q->whereBetween(DB::raw('DATE(created_at)'), [$r->start_date, $r->end_date]);
        }

        $credit = (float) (clone $q)->where('transaction_type', 'credit')->sum('amount');
        $debit  = (float) (clone $q)->where('transaction_type', 'debit')->sum('amount');

        $byCurrency = (clone $q)->select('currency_id')
            ->selectRaw("SUM(CASE WHEN transaction_type='credit' THEN amount ELSE 0 END) AS credit")
            ->selectRaw("SUM(CASE WHEN transaction_type='debit' THEN amount ELSE 0 END) AS debit")
            ->groupBy('currency_id')
            ->get();

        return response()->json([
            'balance' => $credit - $debit,
            'credit'  => $credit,
            'debit'   => $debit,
            'by_currency' => $byCurrency,
        ]);
    }

    // ============================================================
    // EXCHANGES
    // ============================================================
    public function exchanges(Request $r, $accountId)
    {
        $q = Exchange::with(['baseCurrency:id,code,symbol', 'targetCurrency:id,code,symbol'])
            ->where('customer_account_id', $accountId)
            ->orderByDesc('created_at');

        if ($r->filled('currency_id')) {
            $cid = $r->currency_id;
            $q->where(function ($qq) use ($cid) {
                $qq->where('base_currency_id', $cid)
                    ->orWhere('target_currency_id', $cid);
            });
        }

        if ($r->filled('start_date') && $r->filled('end_date')) {
            $q->whereBetween(DB::raw('DATE(created_at)'), [$r->start_date, $r->end_date]);
        }

        $total = (clone $q)->count();
        $data  = $q->skip($r->start ?? 0)->take($r->length ?? 20)->get();

        return response()->json([
            'data' => $data,
            'recordsTotal' => $total,
        ]);
    }

    public function exchangesSummary($accountId)
    {
        $base = Exchange::select('base_currency_id', DB::raw('SUM(base_amount) as total'))
            ->where('customer_account_id', $accountId)
            ->groupBy('base_currency_id')
            ->get();

        $target = Exchange::select('target_currency_id', DB::raw('SUM(target_amount) as total'))
            ->where('customer_account_id', $accountId)
            ->groupBy('target_currency_id')
            ->get();

        return response()->json(compact('base', 'target'));
    }

    // ============================================================
    // REMITTANCES
    // ============================================================
    public function remittances(Request $r, $accountId)
    {
        $q = Remittance::with('currency:id,code,symbol')
            ->where('account_id', $accountId)
            ->orderByDesc('created_at');

        if ($r->filled('currency_id')) $q->where('currency_id', $r->currency_id);
        if ($r->filled('start_date') && $r->filled('end_date')) {
            $q->whereBetween(DB::raw('DATE(created_at)'), [$r->start_date, $r->end_date]);
        }

        $total = (clone $q)->count();
        $data  = $q->skip($r->start ?? 0)->take($r->length ?? 20)->get();

        return response()->json([
            'data' => $data,
            'recordsTotal' => $total,
        ]);
    }

    public function remittancesSummary($accountId)
    {
        $byCurrency = Remittance::select('currency_id', DB::raw('SUM(amount) as total'))
            ->where('account_id', $accountId)
            ->groupBy('currency_id')
            ->get();

        $pending  = (float) Remittance::where('account_id', $accountId)->where('status', 'pending')->sum('amount');
        $approved = (float) Remittance::where('account_id', $accountId)->where('status', 'processed')->sum('amount');

        return response()->json([
            'by_currency'    => $byCurrency,
            'pending_total'  => $pending,
            'approved_total' => $approved,
        ]);
    }

    public function statement(Request $r, $accountId)
{
    $account = Account::findOrFail($accountId);
    $setting = Setting::first();

    $q = Transaction::with('currency:id,code,symbol')
        ->where('account_id', $accountId)
        ->orderBy('created_at');

    if ($r->filled('currency_id')) {
        $q->where('currency_id', $r->currency_id);
    }
    if ($r->filled('type') && in_array($r->type, ['credit','debit'])) {
        $q->where('transaction_type', $r->type);
    }
    if ($r->filled('start_date') && $r->filled('end_date')) {
        $q->whereBetween(DB::raw('DATE(created_at)'), [$r->start_date, $r->end_date]);
    }

    $transactions = $q->get();

    // Summaries grouped by currency
    $summariesByCurrency = [];
    foreach ($transactions->groupBy('currency.code') as $currency => $group) {
        $summariesByCurrency[$currency] = [
            'credit' => $group->where('transaction_type','credit')->sum('amount'),
            'debit'  => $group->where('transaction_type','debit')->sum('amount'),
        ];
    }

    $pdf = \PDF::loadView('admin.accounts.exports.account_statement', compact('account','setting','transactions','summariesByCurrency'))
        ->setPaper('A4','portrait');

    return $pdf->download("Statement-{$account->code}.pdf");
}

}
