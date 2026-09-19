<?php
// app/Models/Purchase.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Purchase extends Model
{
    protected $table = 'purchases';

    protected $fillable = [
        'purchase_no',
        'supplier_id',
        'currency_id',
        'exchange_rate',
        'purchase_date',
        'arrival_date',
        'status',
        'notes',
        'created_by',
        'subtotal',
        'expense_total',
        'grand_total',
        'usd_subtotal',
        'usd_expense_total',
        'usd_grand_total',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'subtotal' => 'decimal:2',
        'expense_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'usd_subtotal' => 'decimal:2',
        'usd_expense_total' => 'decimal:2',
        'usd_grand_total' => 'decimal:2',
        'purchase_date' => 'date',
        'arrival_date' => 'date',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    /**
     * Get the supplier for this purchase.
     */
    public function supplier()
    {
        return $this->belongsTo(Account::class, 'supplier_id');
    }

    /**
     * Get the currency for this purchase.
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Get the items for this purchase.
     */
    public function items()
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }

    /**
     * Get the expenses for this purchase.
     */
    public function expenses()
    {
        return $this->hasMany(PurchaseExpense::class, 'purchase_id');
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    /**
     * Generate a unique purchase number.
     */
    public static function generateNumber()
    {
        $lastPurchase = self::latest('id')->first();

        $nextNumber = $lastPurchase
            ? ((int) str_replace('PO-', '', $lastPurchase->purchase_no)) + 1
            : 1;

        return 'PO-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Recalculate all totals for this purchase.
     */
    public function recalculateTotals()
    {
        // Calculate from items
        $itemSubtotal = $this->items()->sum('total') ?? 0;
        $itemUsdSubtotal = $this->items()->sum('usd_total') ?? 0;

        // Calculate from expenses
        $expenseTotal = $this->expenses()->sum('amount') ?? 0;
        $expenseUsdTotal = $this->expenses()->sum('usd_amount') ?? 0;

        // Calculate grand totals
        $grandTotal = $itemSubtotal + $expenseTotal;
        $usdGrandTotal = $itemUsdSubtotal + $expenseUsdTotal;

        // Update the purchase record
        $this->update([
            'subtotal' => $itemSubtotal,
            'usd_subtotal' => $itemUsdSubtotal,
            'expense_total' => $expenseTotal,
            'usd_expense_total' => $expenseUsdTotal,
            'grand_total' => $grandTotal,
            'usd_grand_total' => $usdGrandTotal,
        ]);

        return $this;
    }

    /**
     * Distribute expenses proportionally across items.
     */
    public function distributeExpenses()
    {
        $items = $this->items()->get();

        if ($items->isEmpty()) {
            return;
        }

        $grandUsdTotal = $items->sum('usd_total');

        if ($grandUsdTotal == 0) {
            foreach ($items as $item) {
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

        // Get total expenses
        $totalExpenseAmount = $this->expenses()->sum('amount') ?? 0;
        $totalExpenseUsd = $this->expenses()->sum('usd_amount') ?? 0;

        foreach ($items as $item) {
            $qty = $item->qty > 0 ? $item->qty : 1;
            $rate = $item->rate > 0 ? $item->rate : 1;

            // Calculate weight based on USD total
            $weight = $item->usd_total / $grandUsdTotal;

            // Allocate expenses
            $allocatedLocalExpense = $totalExpenseAmount * $weight;
            $allocatedUsdExpense = $totalExpenseUsd * $weight;

            // Calculate per-item values
            $expense_per_item = $allocatedUsdExpense;
            $usd_expense_per_item = $allocatedUsdExpense / $qty;
            $expense = $allocatedLocalExpense;

            // Final totals
            $usd_total_cost = $item->usd_total + $expense_per_item;
            $usd_cost_per_item = $usd_total_cost / $qty;

            // Update item
            $item->update([
                'expense' => $expense,
                'expense_per_item' => $expense_per_item,
                'usd_expense' => $allocatedUsdExpense,
                'usd_expense_per_item' => $usd_expense_per_item,
                'usd_total_cost' => $usd_total_cost,
                'usd_cost_per_item' => $usd_cost_per_item,
            ]);
        }

        // Recalculate purchase totals after expense distribution
        $this->recalculateTotals();
    }
}
