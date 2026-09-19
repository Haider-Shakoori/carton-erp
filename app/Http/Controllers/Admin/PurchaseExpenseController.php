<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseExpense;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseExpenseController extends Controller
{
    /**
     * Store a newly created purchase expense.
     */
    public function store(Request $request)
    {
        $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'agent_id' => 'required|exists:accounts,id',
            'currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0',
            'rate' => 'required|numeric|min:0.0001',
            'description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $purchase = Purchase::with(['supplier', 'currency'])->findOrFail($request->purchase_id);

            // Calculate USD amount
            $usdAmount = $request->amount / $request->rate;

            // Create expense
            $expense = PurchaseExpense::create([
                'purchase_id' => $request->purchase_id,
                'agent_id' => $request->agent_id,
                'currency_id' => $request->currency_id,
                'amount' => $request->amount,
                'usd_amount' => $usdAmount,
                'rate' => $request->rate,
                'description' => $request->description,
            ]);

            // Create transaction for expense (DEBIT to agent/expense account)
            $this->createExpenseTransaction($expense, $purchase);

            // Update purchase totals and recalculate expense distribution
            $this->updatePurchaseTotals($purchase);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Expense added successfully',
                'expense' => $expense
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error adding expense: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified purchase expense.
     */
    public function show($id)
    {
        try {
            $expense = PurchaseExpense::with(['agent', 'currency'])->findOrFail($id);
            return response()->json($expense);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found'
            ], 404);
        }
    }

    /**
     * Update the specified purchase expense.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'agent_id' => 'required|exists:accounts,id',
            'currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0',
            'rate' => 'required|numeric|min:0.0001',
            'description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $expense = PurchaseExpense::findOrFail($id);
            $purchase = Purchase::with(['supplier', 'currency'])->findOrFail($expense->purchase_id);

            // Calculate USD amount
            $usdAmount = $request->amount / $request->rate;

            // Delete old transaction
            Transaction::where('table_name', 'purchase_expenses')
                ->where('table_row_id', $expense->id)
                ->where('type', 'expense')
                ->delete();

            $expense->update([
                'agent_id' => $request->agent_id,
                'currency_id' => $request->currency_id,
                'amount' => $request->amount,
                'usd_amount' => $usdAmount,
                'rate' => $request->rate,
                'description' => $request->description,
            ]);

            // Create new transaction for expense (DEBIT to agent/expense account)
            $this->createExpenseTransaction($expense, $purchase, 'update');

            // Update purchase totals and recalculate expense distribution
            $this->updatePurchaseTotals($purchase);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Expense updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating expense: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified purchase expense.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $expense = PurchaseExpense::findOrFail($id);
            $purchase = Purchase::with(['supplier', 'currency'])->findOrFail($expense->purchase_id);

            // Delete transaction
            Transaction::where('table_name', 'purchase_expenses')
                ->where('table_row_id', $expense->id)
                ->where('type', 'expense')
                ->delete();

            $expense->delete();

            // Update purchase totals and recalculate expense distribution
            $this->updatePurchaseTotals($purchase);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Expense deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting expense: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create transaction for expense
     */
    private function createExpenseTransaction($expense, $purchase, $action = 'store')
    {
        $currencySymbol = $expense->currency->symbol ?? '$';
        $agentName = $expense->agent->name ?? 'Unknown Agent';
        $amount = number_format($expense->amount, 2);
        $purchaseNo = $purchase->purchase_no ?? 'N/A';
        $expenseDescription = $expense->description ?? '';

        // Build description
        $description = "Expense - {$agentName} for Purchase Order #{$purchaseNo} - {$currencySymbol}{$amount}";

        if ($expenseDescription) {
            $description .= " ({$expenseDescription})";
        }

        if ($action === 'update') {
            $description = "Expense - {$agentName} for Purchase Order #{$purchaseNo} - {$currencySymbol}{$amount} (Updated)" . ($expenseDescription ? " - {$expenseDescription}" : '');
        }

        // Create transaction (DEBIT to agent/expense account)
        Transaction::create([
            'type' => 'expense',
            'table_name' => 'purchase_expenses',
            'table_row_id' => $expense->id,
            'account_id' => $expense->agent_id,
            'currency_id' => $expense->currency_id,
            'amount' => $expense->amount,
            'transaction_type' => 'credit', // ................
            'is_cash' => false,
            'description' => $description,
            'is_visible' => true,
            'status' => 'active',
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Update purchase order totals and recalculate expense per item.
     */
    private function updatePurchaseTotals(Purchase $purchase)
    {
        // Keep one canonical mixed-currency implementation on the model.
        // recalculateTotals() normalizes every expense through its USD amount,
        // then converts the aggregate back to the purchase-order currency.
        $purchase->recalculateTotals();
        $purchase->distributeExpenses();
    }

    /**
     * Recalculate expense per item based on proportional distribution.
     * Matches the UI show.blade.php calculation requirements exactly.
     */
    private function recalculateExpensePerItem(Purchase $purchase)
    {
        $purchase->load(['items', 'expenses']);

        $totalUsdValue = $purchase->items->sum('usd_total');

        // If no items or no value, reset all expenses
        if ($totalUsdValue <= 0 || $purchase->items->isEmpty()) {
            foreach ($purchase->items as $item) {
                $item->update([
                    'expense' => 0,
                    'expense_per_item' => 0,
                    'usd_expense' => 0,
                    'usd_expense_per_item' => 0,
                    'usd_total_cost' => $item->usd_total,
                    'usd_cost_per_item' => $item->usd_unit_price,
                ]);
            }
            return;
        }

        // Get total global expenses
        $totalExpenseAmount = $purchase->expenses->sum('amount');
        $totalExpenseUsd = $purchase->expenses->sum('usd_amount');

        foreach ($purchase->items as $item) {
            $qty = $item->qty > 0 ? $item->qty : 1;
            $rate = $item->rate > 0 ? $item->rate : 1;

            // Calculate proportional ratio weight based on USD total
            $itemValueRatio = $item->usd_total / $totalUsdValue;

            // Allocate expenses proportionally
            $allocatedLocalExpense = $totalExpenseAmount * $itemValueRatio;
            $allocatedUsdExpense = $totalExpenseUsd * $itemValueRatio;

            // Calculate per-item values
            $expense_per_item = $allocatedUsdExpense;        // Total USD expense for this item
            $usd_expense_per_item = $allocatedUsdExpense / $qty; // Per unit expense

            $expense = $allocatedLocalExpense;               // Local currency total expense

            // Final totals matching blade calculations
            $usd_total_cost = $item->usd_total + $expense_per_item;
            $usd_cost_per_item = $usd_total_cost / $qty;

            // Update item with all calculated values
            $item->update([
                'expense' => $expense,
                'expense_per_item' => $expense_per_item,
                'usd_expense' => $allocatedUsdExpense,
                'usd_expense_per_item' => $usd_expense_per_item,
                'usd_total_cost' => $usd_total_cost,
                'usd_cost_per_item' => $usd_cost_per_item,
            ]);
        }

        // Update purchase totals again after expense distribution
        $purchase->refresh();
        $purchase->update([
            'grand_total' => $purchase->items->sum('total') + $purchase->expenses->sum('amount'),
            'usd_grand_total' => $purchase->items->sum('usd_total') + $purchase->expenses->sum('usd_amount'),
        ]);
    }
}
