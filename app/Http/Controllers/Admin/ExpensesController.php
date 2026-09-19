<?php
// app/Http/Controllers/Admin/ExpensesController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpensesController extends Controller
{
    /**
     * Display a listing of expenses.
     */
    public function index(Request $request)
    {
        $query = Account::where('account_type', 'expense')->where('is_active', true);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Filter by currency
        $selectedCurrencyId = $request->currency_id;
        if ($request->filled('currency_id')) {
            $currencyId = $request->currency_id;
            $expenseIds = Transaction::where('currency_id', $currencyId)
                ->where('status', 'active')
                ->whereHas('account', function ($q) {
                    $q->where('account_type', 'expense')->where('is_active', true);
                })
                ->distinct()
                ->pluck('account_id');

            if ($expenseIds->isNotEmpty()) {
                $query->whereIn('id', $expenseIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $expenses = $query->latest()->paginate(15)->withQueryString();

        // Get balances for each expense account
        $expenseBalances = $this->getExpenseBalances($expenses->pluck('id')->toArray(), $selectedCurrencyId);

        // Attach balances to each expense
        foreach ($expenses as $expense) {
            $expense->balances = collect($expenseBalances[$expense->id] ?? []);
        }

        // Calculate stats per currency
        $stats = $this->calculateStats($selectedCurrencyId);

        $currencies = Currency::where('is_active', true)->get();

        return view('admin.expenses.index', compact('expenses', 'stats', 'currencies'));
    }

    /**
     * Get balances for each expense account per currency.
     */
    private function getExpenseBalances($expenseIds, $currencyId = null)
    {
        if (empty($expenseIds)) {
            return [];
        }

        $query = DB::table('transactions')
            ->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->select(
                'transactions.account_id',
                'currencies.id as currency_id',
                'currencies.code as currency_code',
                'currencies.symbol as currency_symbol',
                DB::raw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END) as balance")
            )
            ->whereIn('transactions.account_id', $expenseIds)
            ->where('transactions.status', 'active');

        if ($currencyId) {
            $query->where('transactions.currency_id', $currencyId);
        }

        $results = $query->groupBy('transactions.account_id', 'currencies.id', 'currencies.code', 'currencies.symbol')
            ->get();

        // Group by account_id
        $grouped = [];
        foreach ($results as $row) {
            $grouped[$row->account_id][] = [
                'currency_id' => $row->currency_id,
                'currency_code' => $row->currency_code,
                'currency_symbol' => $row->currency_symbol,
                'balance' => $row->balance,
            ];
        }

        return $grouped;
    }

    /**
     * Calculate statistics for expenses per currency.
     */
    private function calculateStats($currencyId = null)
    {
        $expenseIds = Account::where('account_type', 'expense')->where('is_active', true)->pluck('id');

        if ($expenseIds->isEmpty()) {
            return [
                'total_expenses' => 0,
                'currencies' => [],
                'total_credit' => 0,
                'total_debit' => 0,
                'balance' => 0,
            ];
        }

        $query = Transaction::whereIn('account_id', $expenseIds)->where('status', 'active');

        if ($currencyId) {
            $query->where('currency_id', $currencyId);
        }

        // Get stats per currency
        $perCurrencyStats = DB::table('transactions')
            ->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->select(
                'currencies.id as currency_id',
                'currencies.code as currency_code',
                'currencies.symbol as currency_symbol',
                DB::raw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as credit"),
                DB::raw("SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as debit")
            )
            ->whereIn('transactions.account_id', $expenseIds)
            ->where('transactions.status', 'active')
            ->when($currencyId, function ($q) use ($currencyId) {
                return $q->where('transactions.currency_id', $currencyId);
            })
            ->groupBy('currencies.id', 'currencies.code', 'currencies.symbol')
            ->get();

        $totalCredit = 0;
        $totalDebit = 0;
        $currenciesData = [];

        foreach ($perCurrencyStats as $stat) {
            $credit = (float) $stat->credit;
            $debit = (float) $stat->debit;
            $balance = $credit - $debit;

            $currenciesData[] = [
                'currency_id' => $stat->currency_id,
                'currency_code' => $stat->currency_code,
                'currency_symbol' => $stat->currency_symbol,
                'credit' => $credit,
                'debit' => $debit,
                'balance' => $balance,
                'balance_color' => $balance >= 0 ? 'positive' : 'negative',
            ];

            $totalCredit += $credit;
            $totalDebit += $debit;
        }

        return [
            'total_expenses' => $expenseIds->count(),
            'currencies' => $currenciesData,
            'total_credit' => $totalCredit,
            'total_debit' => $totalDebit,
            'balance' => $totalCredit - $totalDebit,
        ];
    }

    /**
     * Generate a unique expense code with EXP- prefix.
     */
    public function generateCode()
    {
        $prefix = 'EXP-';
        $lastExpense = Account::where('account_type', 'expense')
            ->where('code', 'LIKE', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastExpense) {
            $lastNumber = intval(substr($lastExpense->code, strlen($prefix)));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return response()->json([
            'code' => $prefix . $newNumber,
            'suffix' => $newNumber
        ]);
    }

    /**
     * Check if expense code already exists.
     */
    public function checkCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $code = $request->code;

        // Ensure code has EXP- prefix
        if (!str_starts_with($code, 'EXP-')) {
            $code = 'EXP-' . $code;
        }

        $exists = Account::where('code', $code)->exists();

        return response()->json([
            'available' => !$exists,
            'code' => $code
        ]);
    }

    /**
     * Store a newly created expense.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:accounts,code',
        ]);

        try {
            // Ensure code has EXP- prefix
            $code = $request->code;
            if (!str_starts_with($code, 'EXP-')) {
                $code = 'EXP-' . $code;
            }

            $account = Account::create([
                'name' => $request->name,
                'code' => $code,
                'account_type' => 'expense',
                'is_active' => true,
                'bi_icon' => 'bi-receipt',
                'bi_icon_color' => 'danger',
                'is_safe' => false,
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Expense account created successfully!',
                'expense' => $account
            ]);
        } catch (Exception $e) {
            Log::error('Expense creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating expense account: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the specified expense.
     */
    public function show($id)
    {
        $expense = Account::with(['transactions.currency'])->findOrFail($id);
        $currencies = Currency::where('is_active', true)->get();

        $summariesByCurrency = DB::table('transactions')
            ->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->select(
                'currencies.id as currency_id',
                'currencies.code as currency',
                'currencies.symbol',
                DB::raw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as credit"),
                DB::raw("SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as debit"),
                DB::raw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END) as balance")
            )
            ->where('account_id', $expense->id)
            ->where('transactions.status', 'active')
            ->groupBy('currencies.id', 'currencies.code', 'currencies.symbol')
            ->get();

        return view('admin.expenses.show', compact('expense', 'currencies', 'summariesByCurrency'));
    }

    /**
     * Edit expense.
     */
    public function edit($id)
    {
        $expense = Account::findOrFail($id);
        return response()->json($expense);
    }

    /**
     * Update expense.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:accounts,code,' . $id,
        ]);

        $expense = Account::findOrFail($id);

        // Ensure code has EXP- prefix
        $code = $request->code;
        if (!str_starts_with($code, 'EXP-')) {
            $code = 'EXP-' . $code;
        }

        $expense->update([
            'name' => $request->name,
            'code' => $code,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense account updated successfully!'
        ]);
    }

    /**
     * Delete expense.
     */
    public function destroy($id)
    {
        try {
            $expense = Account::findOrFail($id);
            Transaction::where('account_id', $expense->id)->delete();
            $expense->delete();

            return response()->json([
                'success' => true,
                'message' => 'Expense account deleted successfully!'
            ]);
        } catch (Exception $e) {
            Log::error('Expense deletion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete expense account: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Print expense statement.
     */
    public function print($id)
    {
        $expense = Account::findOrFail($id);
        $currencies = Currency::where('is_active', true)->get();

        $summariesByCurrency = DB::table('transactions')
            ->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->select(
                'currencies.id as currency_id',
                'currencies.code as currency',
                'currencies.symbol',
                DB::raw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as credit"),
                DB::raw("SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as debit"),
                DB::raw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END) as balance")
            )
            ->where('account_id', $expense->id)
            ->where('transactions.status', 'active')
            ->groupBy('currencies.id', 'currencies.code', 'currencies.symbol')
            ->get();

        return view('admin.expenses.print', compact('expense', 'currencies', 'summariesByCurrency'));
    }

    /**
     * Show account details for API.
     */
    public function showAccount($id)
    {
        $expense = Account::findOrFail($id);

        $balances = DB::table('transactions')
            ->select(
                'currency_id',
                DB::raw('SUM(CASE WHEN transaction_type = "credit" THEN amount ELSE -amount END) as net_balance')
            )
            ->where('account_id', $expense->id)
            ->where('status', 'active')
            ->groupBy('currency_id')
            ->get();

        $currencyBalances = [];
        foreach ($balances as $item) {
            $currency = Currency::find($item->currency_id);
            $currencyBalances[] = [
                'currency' => $currency->code ?? 'N/A',
                'symbol' => $currency->symbol ?? '$',
                'amount' => number_format($item->net_balance, 2),
                'balance' => $item->net_balance,
            ];
        }

        return response()->json([
            'id' => $expense->id,
            'name' => $expense->name,
            'code' => $expense->code,
            'balances' => $currencyBalances,
        ]);
    }
}
