<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Transaction, Account, Currency, Exchange, ExchangePurchase, Remittance, Sale, Setting};
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource with pagination.
     */
    public function index(Request $request)
    {
        $accounts = Account::where('is_active', true)->orderBy('name')->get();
        $currencies = Currency::where('is_active', true)->orderBy('code')->get();
        $openSales = Sale::with(['customer', 'currency'])
            ->whereIn('status', ['confirmed', 'delivered'])
            ->where('due_amount', '>', 0)
            ->latest('sale_date')
            ->get();

        $query = Transaction::with(['account', 'currency'])
            ->whereHas('account', function ($q) {
                $q->where('is_active', true);
            })
            ->where('is_visible', true)
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('currency_id')) {
            $query->where('currency_id', $request->currency_id);
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('date_range')) {
            [$start, $end] = explode(' - ', $request->date_range);
            $query->whereBetween('transactions.created_at', [
                $start . ' 00:00:00',
                $end . ' 23:59:59'
            ]);
        }

        $perPage = $request->input('per_page', 15);
        $transactions = $query->paginate($perPage);

        // If AJAX request, return JSON with HTML
        if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            $html = view('admin.transactions.partials.table-rows', compact('transactions'))->render();
            $pagination = $transactions->hasPages() ? $transactions->appends(request()->query())->links('pagination::bootstrap-5')->render() : '';

            return response()->json([
                'html' => $html,
                'pagination' => $pagination,
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage()
            ]);
        }

        return view('admin.transactions.create', compact('accounts', 'currencies', 'transactions', 'openSales'));
    }

    /**
     * Get accounts by account type (for AJAX).
     */
    public function getAccountsByType(Request $request)
    {
        $type = $request->account_type;

        if (!$type) {
            return response()->json(['accounts' => []]);
        }

        $accounts = Account::where('account_type', $type)
            ->where('is_active', true)
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        return response()->json(['accounts' => $accounts]);
    }

    /**
     * Get balances for cash record.
     */
    public function balances(Request $request)
    {
        $currencyId = $request->currency_id;
        $date = $request->date
            ? Carbon::parse($request->date)->format('Y-m-d')
            : Carbon::today()->format('Y-m-d');

        $currencies = DB::table('currencies')
            ->when($currencyId, fn($q) => $q->where('id', $currencyId))
            ->get();

        $data = $currencies->map(function ($currency) use ($date) {
            $starting = DB::table('transactions')
                ->where('currency_id', $currency->id)
                ->where('status', 'active')
                ->where('is_cash', 1)
                ->whereDate('created_at', '<', $date)
                ->selectRaw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END) as total")
                ->value('total') ?? 0;

            $credit = DB::table('transactions')
                ->where('currency_id', $currency->id)
                ->where('transaction_type', 'credit')
                ->where('status', 'active')
                ->where('is_cash', 1)
                ->whereDate('created_at', $date)
                ->sum('amount') ?? 0;

            $debit = DB::table('transactions')
                ->where('currency_id', $currency->id)
                ->where('transaction_type', 'debit')
                ->where('status', 'active')
                ->where('is_cash', 1)
                ->whereDate('created_at', $date)
                ->sum('amount') ?? 0;

            return [
                'currency_name'   => $currency->name,
                'currency_symbol' => $currency->symbol ?? '$',
                'date'            => $date,
                'starting_balance' => (float)$starting,
                'today_credit'    => (float)$credit,
                'today_debit'     => (float)$debit,
                'closing_balance' => (float)$starting + (float)$credit - (float)$debit
            ];
        });

        return response()->json($data);
    }

    /**
     * Get account balances by currency.
     */
    public function getAccountBalances(Request $request)
    {
        $accountId = $request->account_id;

        if (!$accountId) {
            return response()->json(['balances' => []]);
        }

        $balances = DB::table('transactions')
            ->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->select(
                'currencies.code as currency',
                DB::raw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as credit"),
                DB::raw("SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as debit")
            )
            ->where('account_id', $accountId)
            ->groupBy('currencies.code')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->currency => [
                    'credit' => $item->credit,
                    'debit' => $item->debit,
                    'balance' => $item->credit - $item->debit
                ]];
            });

        return response()->json(['balances' => $balances]);
    }

    /**
     * Fetch transaction for edit.
     */
    public function fetch($id)
    {
        $transaction = Transaction::findOrFail($id);
        $account = Account::select('id', 'account_type', 'account_sub_category_id', 'name', 'code')
            ->where('id', $transaction->account_id)
            ->firstOrFail();

        return response()->json([
            'transaction' => $transaction,
            'account' => $account,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'transaction_id'     => 'nullable|exists:transactions,id',
                'account_id'         => 'required|exists:accounts,id',
                'currency_id'        => 'required|exists:currencies,id',
                'amount'             => 'required|numeric|min:0.01',
                'transaction_type'   => 'required|in:credit,debit',
                'description'        => 'nullable|string',
                'status'             => 'nullable|string',
                'created_by'         => 'nullable|exists:users,id',
                'sale_id'            => 'nullable|exists:sales,id',
            ]);

            $saleId = $validated['sale_id'] ?? null;
            unset($validated['sale_id']);

            $validated['is_cash']     = true;
            $validated['type']        = 'journal';
            $validated['created_by']  = $validated['created_by'] ?? Auth::id();
            $validated['status']      = $validated['status'] ?? 'active';

            // Generate description if empty
            if (empty($validated['description'])) {
                $currency = Currency::findOrFail($validated['currency_id'])->code;
                $amount = number_format($validated['amount'], 2);
                $type = ucfirst($validated['transaction_type']);
                $account = Account::findOrFail($validated['account_id']);
                $validated['description'] = sprintf(
                    '%s transaction of %s %s for %s',
                    $type,
                    $amount,
                    $currency,
                    $account->name
                );
            }

            $isUpdate = !empty($request->transaction_id);

            if ($isUpdate) {
                if ($saleId) {
                    throw new Exception('Sale payments cannot be applied through transaction editing.');
                }
                $transaction = Transaction::findOrFail($request->transaction_id);
                $transaction->update($validated);
                $message = 'Transaction updated successfully.';
            } elseif ($saleId) {
                $transaction = DB::transaction(function () use ($saleId, &$validated) {
                    $sale = Sale::with('currency')->lockForUpdate()->findOrFail($saleId);

                    if ((int) $sale->customer_id !== (int) $validated['account_id']
                        || (int) $sale->currency_id !== (int) $validated['currency_id']) {
                        throw new Exception('The selected account and currency must match the sale.');
                    }

                    if ($validated['transaction_type'] !== 'credit') {
                        throw new Exception('A sale payment must be a credit transaction.');
                    }

                    $amount = (float) $validated['amount'];
                    $dueAmount = (float) $sale->due_amount;
                    if ($amount > $dueAmount) {
                        throw new Exception('Payment cannot exceed the sale due amount.');
                    }

                    $validated['type'] = 'sale';
                    $validated['table_name'] = 'sales';
                    $validated['table_row_id'] = $sale->id;
                    if (empty($validated['description'])) {
                        $validated['description'] = "Sale #{$sale->sale_no} - Payment received";
                    }

                    $transaction = Transaction::create($validated);
                    $rate = max((float) ($sale->exchange_rate ?? 1), 0.000001);
                    $isUsd = ($sale->currency->code ?? 'USD') === 'USD';
                    $sale->advance_payment = (float) $sale->advance_payment + $amount;
                    $sale->usd_advance_payment = (float) $sale->usd_advance_payment
                        + ($isUsd ? $amount : $amount / $rate);
                    $sale->due_amount = max(0, (float) $sale->grand_total - (float) $sale->advance_payment);
                    $sale->usd_due_amount = max(0, (float) $sale->usd_grand_total - (float) $sale->usd_advance_payment);
                    $sale->save();

                    return $transaction;
                });
                $message = 'Sale payment created successfully.';
            } else {
                $transaction = Transaction::create($validated);
                $message = 'Transaction created successfully.';
            }

            $currency = Currency::findOrFail($validated['currency_id']);
            $account = Account::findOrFail($validated['account_id']);

            // Calculate new balance
            $balance = DB::table('transactions')
                ->where('account_id', $validated['account_id'])
                ->where('currency_id', $validated['currency_id'])
                ->where('status', 'active')
                ->select(DB::raw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END) as balance"))
                ->value('balance') ?? 0;

            // Check if this is an AJAX request
            $isAjax = $request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest';

            if ($isAjax) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'transaction' => $transaction,
                    'balance' => $balance,
                    'currency_symbol' => $currency->symbol,
                    'currency_code' => $currency->code,
                    'account_name' => $account->name,
                ]);
            }

            return redirect()->route('admin.transactions.index')->with('success', $message);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $isAjax = $request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest';
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        } catch (Exception $e) {
            Log::error('Transaction store error: ' . $e->getMessage());
            $isAjax = $request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest';
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $transaction = Transaction::findOrFail($id);

        try {
            $relatedTransactions = collect();

            if ($transaction->table_name && $transaction->table_row_id) {
                switch ($transaction->table_name) {
                    case 'exchanges':
                        $exchange = Exchange::find($transaction->table_row_id);
                        if ($exchange) {
                            $relatedTransactions = Transaction::where('table_name', 'exchanges')
                                ->where('table_row_id', $exchange->id)
                                ->get();
                            $exchange->delete();
                        }
                        break;

                    case 'remittances':
                        $remittance = Remittance::find($transaction->table_row_id);
                        if ($remittance) {
                            $relatedTransactions = Transaction::where('table_name', 'remittances')
                                ->where('table_row_id', $remittance->id)
                                ->get();
                            $remittance->delete();
                        }
                        break;

                    case 'exchange-purchase':
                        $purchase = ExchangePurchase::find($transaction->table_row_id);
                        if ($purchase) {
                            $relatedTransactions = Transaction::where('table_name', 'exchange-purchase')
                                ->where('table_row_id', $purchase->id)
                                ->get();
                            $purchase->delete();
                        }
                        break;

                    default:
                        break;
                }
            }

            foreach ($relatedTransactions as $relatedTransaction) {
                $relatedTransaction->delete();
            }

            $transaction->delete();

            return response()->json([
                'success' => true,
                'message' => 'Transaction and related data deleted successfully.'
            ]);
        } catch (Exception $e) {
            Log::error('Delete transaction failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete transaction.'
            ], 500);
        }
    }

    /**
     * Export transactions to PDF.
     */
    public function export(Request $request)
    {
        $query = Transaction::with(['account', 'currency'])
            ->when($request->account_id, fn($q) => $q->where('account_id', $request->account_id))
            ->when($request->currency_id, fn($q) => $q->where('currency_id', $request->currency_id))
            ->when($request->transaction_type, fn($q) => $q->where('transaction_type', $request->transaction_type))
            ->whereBetween('created_at', [now()->format('Y-m-d') . ' 00:00:00', now()->format('Y-m-d') . ' 23:59:59']);

        $transactions = $query->latest()->get();

        if ($transactions->isEmpty()) {
            return "No data found for export.";
        }

        $setting = Setting::first();

        $pdf = Pdf::loadView('admin.transactions.export', compact('transactions', 'setting'))
            ->setPaper('A4', 'portrait');

        return $pdf->stream('transactions.pdf');
    }
}
