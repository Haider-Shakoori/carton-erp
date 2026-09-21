<?php
// app/Models/Purchase.php

namespace App\Models;

use App\Models\Concerns\BelongsToBusinessUnit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Purchase extends Model
{
    use BelongsToBusinessUnit;
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
        $itemSubtotal = (float) ($this->items()->sum('total') ?? 0);
        $itemUsdSubtotal = (float) ($this->items()->sum('usd_total') ?? 0);
        $expenseUsdTotal = (float) ($this->expenses()->sum('usd_amount') ?? 0);

        // "subtotal / expense_total / grand_total" are always stored in the
        // purchase-order currency. Individual expenses may be USD, AFN, etc.,
        // so raw expense.amount values must never be added together.
        $purchaseCurrencyRate = $this->purchaseCurrencyRateToUsd();
        $expenseTotal = $expenseUsdTotal * $purchaseCurrencyRate;

        $grandTotal = $itemSubtotal + $expenseTotal;
        $usdGrandTotal = $itemUsdSubtotal + $expenseUsdTotal;

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
     * Number of purchase-currency units represented by one USD.
     */
    public function purchaseCurrencyRateToUsd(): float
    {
        $this->loadMissing('currency');

        $rate = (float) ($this->currency?->exchange_rate ?? 0);
        if ($rate > 0) {
            return $rate;
        }

        $stored = (float) ($this->exchange_rate ?? 0);
        return $stored > 0 ? $stored : 1.0;
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

        // Normalize mixed-currency expenses before allocating them.
        $totalExpenseUsd = (float) ($this->expenses()->sum('usd_amount') ?? 0);
        $totalExpenseAmount = $totalExpenseUsd * $this->purchaseCurrencyRateToUsd();

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
