<?php
// app/Http/Controllers/Admin/CustomerController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\Sale;
use App\Models\SaleReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
    {
        $query = Account::where('account_type', Account::TYPE_CUSTOMER);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('contact', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('currency_id')) {
            $currencyId = $request->currency_id;
            $query->whereHas('transactions', function ($q) use ($currencyId) {
                $q->where('currency_id', $currencyId);
            });
        }

        $customers = $query->orderBy('name')->paginate(15);
        $currencies = Currency::where('is_active', true)->get();
        $customerBalances = $this->getCustomerBalances($customers->pluck('id')->toArray());

        $stats = [
            'total_customers' => Account::where('account_type', Account::TYPE_CUSTOMER)->count(),
            'active_customers' => Account::where('account_type', Account::TYPE_CUSTOMER)->where('is_active', true)->count(),
            'currencies' => $this->getCurrencyStats(),
        ];

        return view('admin.customers.index', compact('customers', 'currencies', 'customerBalances', 'stats'));
    }

    /**
     * Display the specified customer with full details.
     */
    public function show(Request $request, $id)
    {
        $customer = Account::with([
            'transactions' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'transactions.currency',
            'sales' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'sales.currency',
            'sales.items',
            'sales.items.product',
            'saleReturns' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'saleReturns.currency',
            'saleReturns.items',
            'saleReturns.items.product',
        ])->findOrFail($id);

        // Get balances by currency (only USD)
        $balances = $customer->getBalancesByCurrency();

        // Get transaction summary
        $transactionSummary = $this->getTransactionSummary($id);

        // Get sales summary
        $salesSummary = $this->getSalesSummary($id);

        // Get currencies for filters
        $currencies = Currency::where('is_active', true)->get();

        // Get filter data from request
        $filters = $request->only(['currency_id', 'transaction_type', 'date_range', 'status']);

        // Get filtered transactions
        $transactions = $this->getFilteredTransactions($id, $filters);

        // Get filtered sales
        $sales = $this->getFilteredSales($id, $filters);

        return view('admin.customers.show', compact(
            'customer',
            'balances',
            'transactionSummary',
            'salesSummary',
            'currencies',
            'filters',
            'transactions',
            'sales'
        ));
    }

    /**
     * Get customer balances for multiple customers.
     */
    private function getCustomerBalances($customerIds)
    {
        if (empty($customerIds)) {
            return [];
        }

        $results = DB::table('transactions')
            ->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->whereIn('transactions.account_id', $customerIds)
            ->where('transactions.status', 'active')
            ->select(
                'transactions.account_id',
                'currencies.id as currency_id',
                'currencies.code as currency_code',
                'currencies.symbol as currency_symbol',
                DB::raw("
                    COALESCE(
                        SUM(CASE 
                            WHEN transactions.transaction_type = 'credit' THEN transactions.amount 
                            ELSE -transactions.amount 
                        END),
                        0
                    ) as balance
                ")
            )
            ->groupBy('transactions.account_id', 'currencies.id', 'currencies.code', 'currencies.symbol')
            ->get();

        $balances = [];
        foreach ($results as $result) {
            $balances[$result->account_id][] = [
                'currency_id' => $result->currency_id,
                'currency_code' => $result->currency_code,
                'currency_symbol' => $result->currency_symbol,
                'balance' => (float) $result->balance,
            ];
        }

        return $balances;
    }

    /**
     * Get currency stats for dashboard.
     */
    private function getCurrencyStats()
    {
        return DB::table('transactions')
            ->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->where('accounts.account_type', Account::TYPE_CUSTOMER)
            ->where('transactions.status', 'active')
            ->select(
                'currencies.id',
                'currencies.code as currency_code',
                'currencies.symbol as currency_symbol',
                DB::raw("
                    COALESCE(
                        SUM(CASE 
                            WHEN transactions.transaction_type = 'credit' THEN transactions.amount 
                            ELSE -transactions.amount 
                        END),
                        0
                    ) as balance
                ")
            )
            ->groupBy('currencies.id', 'currencies.code', 'currencies.symbol')
            ->having('balance', '!=', 0)
            ->orderBy('currencies.code')
            ->get();
    }

    /**
     * Get transaction summary for a customer.
     */
    private function getTransactionSummary($customerId)
    {
        $summary = DB::table('transactions')
            ->where('account_id', $customerId)
            ->where('status', 'active')
            ->select(
                DB::raw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as total_credit"),
                DB::raw("SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as total_debit"),
                DB::raw("COUNT(*) as total_transactions"),
                DB::raw("COUNT(DISTINCT currency_id) as currencies_used")
            )
            ->first();

        return [
            'total_credit' => (float) ($summary->total_credit ?? 0),
            'total_debit' => (float) ($summary->total_debit ?? 0),
            'total_transactions' => (int) ($summary->total_transactions ?? 0),
            'currencies_used' => (int) ($summary->currencies_used ?? 0),
        ];
    }

    /**
     * Get sales summary for a customer.
     */
    private function getSalesSummary($customerId)
    {
        return [
            'total_sales' => Sale::where('customer_id', $customerId)->count(),
            'total_amount' => Sale::where('customer_id', $customerId)->sum('grand_total') ?? 0,
            'confirmed_sales' => Sale::where('customer_id', $customerId)->where('status', 'confirmed')->count(),
            'delivered_sales' => Sale::where('customer_id', $customerId)->where('status', 'delivered')->count(),
            'draft_sales' => Sale::where('customer_id', $customerId)->where('status', 'draft')->count(),
        ];
    }

    /**
     * Get filtered transactions with running balance.
     */
    private function getFilteredTransactions($customerId, $filters)
    {
        $query = Transaction::where('account_id', $customerId)
            ->where('status', 'active')
            ->with(['currency']);

        if (!empty($filters['currency_id'])) {
            $query->where('currency_id', $filters['currency_id']);
        }

        if (!empty($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        if (!empty($filters['date_range'])) {
            [$start, $end] = explode(' - ', $filters['date_range']);
            $query->whereBetween('created_at', [
                Carbon::parse($start)->startOfDay(),
                Carbon::parse($end)->endOfDay()
            ]);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

        $this->applyRunningBalances($query, $transactions);

        return $transactions;
    }

    /**
     * Get filtered sales.
     */
    private function getFilteredSales($customerId, $filters)
    {
        $query = Sale::where('customer_id', $customerId)
            ->with(['currency', 'items']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_range'])) {
            [$start, $end] = explode(' - ', $filters['date_range']);
            $query->whereBetween('sale_date', [
                Carbon::parse($start)->startOfDay(),
                Carbon::parse($end)->endOfDay()
            ]);
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:accounts,code',
            'contact' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'whatsapp' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $customer = Account::create([
                'name' => $request->name,
                'code' => $request->code,
                'account_type' => Account::TYPE_CUSTOMER,
                'contact' => $request->contact,
                'email' => $request->email,
                'whatsapp' => $request->whatsapp,
                'company' => $request->company,
                'address' => $request->address,
                'notes' => $request->notes,
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully.',
                'customer' => $customer
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:accounts,code,' . $id,
            'contact' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'whatsapp' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $customer = Account::findOrFail($id);
            $customer->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully.',
                'customer' => $customer
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified customer.
     */
    public function destroy($id)
    {
        try {
            $customer = Account::findOrFail($id);

            if ($customer->hasTransactions()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete customer with existing transactions.'
                ], 400);
            }

            $customer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show edit form.
     */
    public function edit($id)
    {
        $customer = Account::findOrFail($id);
        return response()->json($customer);
    }

    /**
     * Check code availability.
     */
    public function checkCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $exists = Account::where('code', $request->code)->exists();

        return response()->json([
            'available' => !$exists,
            'code' => $request->code
        ]);
    }

    /**
     * Generate customer code.
     */
    public function generateCode()
    {
        $code = Account::generateCode(Account::TYPE_CUSTOMER);
        return response()->json(['code' => $code]);
    }

    /**
     * Print customer statement with currency selection.
     */
    public function print(Request $request, $id)
    {
        $customer = Account::findOrFail($id);
        $currencyId = $request->currency_id;
        $currency = Currency::findOrFail($currencyId);

        $transactions = Transaction::where('account_id', $id)
            ->where('currency_id', $currencyId)
            ->where('status', 'active')
            ->with('currency')
            ->orderBy('created_at', 'asc')
            ->get();

        $this->applyRunningBalances($query, $transactions);

        $balance = $runningBalance;

        return view('admin.customers.print', compact('customer', 'transactions', 'currency', 'balance'));
    }

    /**
     * Export customer statement to PDF with currency selection.
     */
    public function exportPdf(Request $request, $id)
    {
        $customer = Account::findOrFail($id);
        $currencyId = $request->currency_id;
        $currency = Currency::findOrFail($currencyId);

        $transactions = Transaction::where('account_id', $id)
            ->where('currency_id', $currencyId)
            ->where('status', 'active')
            ->with('currency')
            ->orderBy('created_at', 'asc')
            ->get();

        $runningBalance = 0;
        foreach ($transactions as $transaction) {
            if ($transaction->transaction_type === 'credit') {
                $runningBalance += $transaction->amount;
            } else {
                $runningBalance -= $transaction->amount;
            }
            $transaction->running_balance = $runningBalance;
        }

        $balance = $runningBalance;

        $pdf = Pdf::loadView('admin.customers.export-pdf', compact('customer', 'transactions', 'currency', 'balance'));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('customer-statement-' . $customer->code . '-' . $currency->code . '.pdf');
    }

    /**
     * Export customer transactions to CSV with currency selection.
     */
    public function exportCsv(Request $request, $id)
    {
        $customer = Account::findOrFail($id);
        $currencyId = $request->currency_id;
        $currency = Currency::findOrFail($currencyId);

        $transactions = Transaction::where('account_id', $id)
            ->where('currency_id', $currencyId)
            ->where('status', 'active')
            ->with('currency')
            ->orderBy('created_at', 'asc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="customer-transactions-' . $customer->code . '-' . $currency->code . '.csv"',
        ];

        $callback = function () use ($transactions, $currency) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Type', 'Description', 'Amount (' . $currency->code . ')', 'Balance (' . $currency->code . ')']);

            $runningBalance = 0;
            foreach ($transactions as $transaction) {
                $amount = $transaction->transaction_type === 'credit' ? $transaction->amount : -$transaction->amount;
                $runningBalance += $amount;

                fputcsv($handle, [
                    $transaction->created_at->format('Y-m-d H:i'),
                    ucfirst($transaction->transaction_type),
                    $transaction->description ?? '',
                    number_format($amount, 2),
                    number_format($runningBalance, 2),
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Send WhatsApp message to customer.
     */
    public function sendWhatsApp($id)
    {
        $customer = Account::findOrFail($id);

        if (!$customer->whatsapp) {
            return response()->json([
                'success' => false,
                'message' => 'Customer does not have a WhatsApp number.'
            ], 400);
        }

        try {
            return response()->json([
                'success' => true,
                'message' => 'WhatsApp message sent successfully to ' . $customer->whatsapp
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error sending WhatsApp: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction data for AJAX.
     */
    public function transactionData(Request $request, $id)
    {
        $query = Transaction::where('account_id', $id)
            ->where('status', 'active')
            ->with(['currency']);

        if ($request->filled('currency_id')) {
            $query->where('currency_id', $request->currency_id);
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('date_range')) {
            [$start, $end] = explode(' - ', $request->date_range);
            $query->whereBetween('created_at', [
                Carbon::parse($start)->startOfDay(),
                Carbon::parse($end)->endOfDay()
            ]);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

        // Calculate running balance
        $runningBalance = 0;
        foreach ($transactions as $transaction) {
            if ($transaction->transaction_type === 'credit') {
                $runningBalance += $transaction->amount;
            } else {
                $runningBalance -= $transaction->amount;
            }
            $transaction->running_balance = $runningBalance;
        }

        return response()->json([
            'html' => view('admin.customers.partials.transactions-table', compact('transactions'))->render(),
            'pagination' => $transactions->links('pagination::bootstrap-5')->render(),
            'total' => $transactions->total(),
            'current_page' => $transactions->currentPage(),
            'last_page' => $transactions->lastPage(),
        ]);
    }

    /**
     * Get sales data for AJAX.
     */
    public function salesData(Request $request, $id)
    {
        $query = Sale::where('customer_id', $id)
            ->with(['currency']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_range')) {
            [$start, $end] = explode(' - ', $request->date_range);
            $query->whereBetween('sale_date', [
                Carbon::parse($start)->startOfDay(),
                Carbon::parse($end)->endOfDay()
            ]);
        }

        $sales = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'html' => view('admin.customers.partials.sales-table', compact('sales'))->render(),
            'pagination' => $sales->links('pagination::bootstrap-5')->render(),
            'total' => $sales->total(),
            'current_page' => $sales->currentPage(),
            'last_page' => $sales->lastPage(),
        ]);
    }

    private function applyRunningBalances($query, $transactions): void
    {
        $balances = [];
        $runningByTransaction = [];

        foreach ((clone $query)->reorder()->orderBy('created_at')->orderBy('id')->get() as $transaction) {
            $currencyId = $transaction->currency_id;
            $balances[$currencyId] = $balances[$currencyId] ?? 0;
            $balances[$currencyId] += $transaction->transaction_type === 'credit'
                ? (float) $transaction->amount
                : -(float) $transaction->amount;
            $runningByTransaction[$transaction->id] = $balances[$currencyId];
        }

        foreach ($transactions as $transaction) {
            $transaction->running_balance = $runningByTransaction[$transaction->id] ?? 0;
        }
    }

    /**
     * Get balance summary for AJAX.
     */
    public function balanceSummary($id)
    {
        $customer = Account::findOrFail($id);
        $balances = $customer->getBalancesByCurrency();
        $summary = $this->getTransactionSummary($id);

        return response()->json([
            'balances' => $balances,
            'summary' => $summary
        ]);
    }
}
