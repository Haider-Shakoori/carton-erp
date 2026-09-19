<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\Currency;
use Barryvdh\DomPDF\Facade\Pdf;

class JournalController extends Controller
{
    public function index()
    {
        $accounts = DB::table('accounts')->select('id', 'name', 'code')->get();
        $currencies = DB::table('currencies')->select('id', 'code')->get();
        return view('admin.journal.index', compact('accounts', 'currencies'));
    }

    public function show(Request $request)
    {
        //
    }

    public function data(Request $request)
    {
        $query = Transaction::with(['account', 'currency'])
            ->whereHas('account', function ($q) {
                $q->where('is_active', true);
            })
            ->when($request->account_id, fn($q) => $q->where('account_id', $request->account_id))
            ->when($request->currency_id, fn($q) => $q->where('currency_id', $request->currency_id))
            ->when($request->transaction_type, fn($q) => $q->where('transaction_type', $request->transaction_type))
            ->when($request->date_range, function ($q) use ($request) {
                [$start, $end] = explode(' - ', $request->date_range);
                $q->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
            })
            // ->where('transactions.is_visible', true)
            ->orderBy('transactions.created_at', 'desc');

        return datatables()->of($query)
            ->addIndexColumn()
            ->addColumn('account_name', fn($row) => $row->account->name ?? '-')
            ->addColumn('currency_code', fn($row) => $row->currency->code ?? '-')
            ->addColumn('currency_symbol', fn($row) => $row->currency->symbol ?? '-')
            ->editColumn('created_at', fn($row) => $row->created_at->toDateTimeString())
            ->make(true);
    }

    public function summary(Request $request)
    {
        $query = DB::table('transactions')
            ->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->select(
                'currencies.code as currency',
                'currencies.symbol as symbol',
                'transactions.transaction_type',
                DB::raw('SUM(transactions.amount) as total')
            )
            ->where('accounts.is_active', true)
            ->when($request->account_id, fn($q) => $q->where('transactions.account_id', $request->account_id))
            ->when($request->currency_id, fn($q) => $q->where('transactions.currency_id', $request->currency_id))
            ->when($request->date_range, function ($q) use ($request) {
                [$start, $end] = explode(' - ', $request->date_range);
                $q->whereBetween(DB::raw('DATE(transactions.created_at)'), [$start, $end]);
            })
            ->groupBy('currencies.code', 'currencies.symbol', 'transactions.transaction_type')
            ->get();

        $summary = [];

        foreach ($query as $row) {
            $currency = $row->currency;
            $type = $row->transaction_type;

            if (!isset($summary[$currency])) {
                $summary[$currency] = [
                    'symbol' => $row->symbol,
                    'credit' => 0,
                    'debit' => 0,
                    'balance' => 0,
                ];
            }

            $summary[$currency][$type] = (float) $row->total;
        }

        foreach ($summary as $currency => &$values) {
            $credit = $values['credit'] ?? 0;
            $debit = $values['debit'] ?? 0;
            $values['balance'] = round($credit - $debit, 2);
        }

        return response()->json(['summary' => $summary]);
    }



    private function normalizeZero(float $value): float
    {
        // Force -0 or near zero to 0
        return abs($value) < 0.000001 ? 0.0 : $value;
    }

    public function export(Request $request)
    {
        $query = Transaction::with(['account', 'currency'])
            ->when($request->account_id, fn($q) => $q->where('account_id', $request->account_id))
            ->when($request->currency_id, fn($q) => $q->where('currency_id', $request->currency_id))
            ->when($request->transaction_type, fn($q) => $q->where('transaction_type', $request->transaction_type));

        if ($request->filled('date_range')) {
            [$start, $end] = explode(' - ', $request->date_range);
            $query->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
        }

        $transactions = $query->latest()->get();

        if ($transactions->isEmpty()) {
            return "No data found for export.";
        }

        $pdf = Pdf::loadView('admin.journal.export', compact('transactions'))->setPaper('A4', 'portrait');
        return $pdf->stream('journal.pdf');
    }
}
