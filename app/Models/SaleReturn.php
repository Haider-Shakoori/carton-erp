<?php
// app/Models/SaleReturn.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class SaleReturn extends Model
{
    protected $fillable = [
        'sale_id',
        'return_no',
        'customer_id',
        'currency_id',
        'return_date',
        'processed_at',
        'status',
        'exchange_rate',
        'subtotal',
        'discount_total',
        'grand_total',
        'usd_subtotal',
        'usd_discount_total',
        'usd_grand_total',
        'reason',
        'notes',
        'restocking_fee',
        'usd_restocking_fee',
        'refund_amount',
        'usd_refund_amount',
    ];

    protected $casts = [
        'return_date' => 'date',
        'processed_at' => 'datetime',
        'exchange_rate' => 'decimal:6',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'usd_subtotal' => 'decimal:2',
        'usd_discount_total' => 'decimal:2',
        'usd_grand_total' => 'decimal:2',
        'restocking_fee' => 'decimal:2',
        'usd_restocking_fee' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'usd_refund_amount' => 'decimal:2',
    ];

    /**
     * Generate a unique return number
     */
    public static function generateNumber(): string
    {
        $year = date('Y');
        $month = date('m');

        $lastReturn = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastReturn) {
            $lastNumber = intval(substr($lastReturn->return_no, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "SR-{$year}{$month}-{$newNumber}";
    }

    /**
     * Recalculate totals
     */
    public function recalculateTotals(): void
    {
        $items = $this->items;

        $this->subtotal = $items->sum('total');
        $this->discount_total = $items->sum('discount');
        $this->grand_total = $this->subtotal - $this->discount_total;

        $this->usd_subtotal = $items->sum('usd_total');
        $this->usd_discount_total = $items->sum('usd_discount');
        $this->usd_grand_total = $this->usd_subtotal - $this->usd_discount_total;

        // Calculate refund amount (grand total minus restocking fee)
        $this->refund_amount = $this->grand_total - $this->restocking_fee;
        $this->usd_refund_amount = $this->usd_grand_total - $this->usd_restocking_fee;

        $this->save();
    }

    /**
     * Get the sale that this return belongs to
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the customer for this return
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'customer_id');
    }

    /**
     * Get the currency for this return
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the items for this return
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    /**
     * Process the return (restock items and create reversal transactions)
     */
    public function process(): bool
    {
        if ($this->status !== 'approved') {
            throw new \Exception('Only approved returns can be processed.');
        }

        DB::beginTransaction();

        try {
            // 1. Restock each item
            foreach ($this->items as $returnItem) {
                if ($returnItem->restocked) {
                    continue;
                }

                $purchaseItem = PurchaseItem::find($returnItem->purchase_item_id);
                if ($purchaseItem) {
                    // Restore stock to purchase item
                    $purchaseItem->qty_sold -= $returnItem->qty_returned;
                    $purchaseItem->save();
                }

                $returnItem->restocked = true;
                $returnItem->restocked_at = now();
                $returnItem->save();
            }

            // 2. Create reversal transactions
            $this->createReversalTransactions();

            // 3. Update status
            $this->status = 'processed';
            $this->processed_at = now();
            $this->save();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create reversal transactions for the return
     */
    private function createReversalTransactions(): void
    {
        $sale = $this->sale;
        $currencySymbol = $this->currency->symbol ?? '$';
        $returnNo = $this->return_no;
        $refundAmount = $this->refund_amount;

        // 1. CREDIT: Customer account (reduce the receivable)
        Transaction::create([
            'type' => 'sale_return',
            'table_name' => 'sale_returns',
            'table_row_id' => $this->id,
            'account_id' => $this->customer_id,
            'currency_id' => $this->currency_id,
            'amount' => $refundAmount,
            'transaction_type' => 'credit',
            'is_cash' => false,
            'description' => "Sale Return #{$returnNo} - Refund to customer " . $currencySymbol . ' ' . number_format($refundAmount, 2),
            'is_visible' => true,
            'status' => 'active',
            'created_by' => auth()->id(),
        ]);

        // 2. DEBIT: Cash/Bank account (money goes out)
        Transaction::create([
            'type' => 'sale_return',
            'table_name' => 'sale_returns',
            'table_row_id' => $this->id,
            'account_id' => $this->getCashAccountId(),
            'currency_id' => $this->currency_id,
            'amount' => $refundAmount,
            'transaction_type' => 'debit',
            'is_cash' => true,
            'description' => "Sale Return #{$returnNo} - Cash refund " . $currencySymbol . ' ' . number_format($refundAmount, 2),
            'is_visible' => true,
            'status' => 'active',
            'created_by' => auth()->id(),
        ]);

        // If there was a restocking fee, create a separate transaction
        if ($this->restocking_fee > 0) {
            // CREDIT: Restocking fee income account
            Transaction::create([
                'type' => 'sale_return',
                'table_name' => 'sale_returns',
                'table_row_id' => $this->id,
                'account_id' => $this->getRestockingFeeAccountId(),
                'currency_id' => $this->currency_id,
                'amount' => $this->restocking_fee,
                'transaction_type' => 'credit',
                'is_cash' => false,
                'description' => "Sale Return #{$returnNo} - Restocking fee " . $currencySymbol . ' ' . number_format($this->restocking_fee, 2),
                'is_visible' => true,
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Get the cash account ID for refunds
     */
    private function getCashAccountId(): int
    {
        // You should implement this based on your accounting structure
        // For example, get the default cash account from settings
        $cashAccount = Account::where('account_type', 'cash')
            ->where('is_active', true)
            ->first();

        return $cashAccount?->id ?? 1; // Fallback to default
    }

    /**
     * Get the restocking fee income account ID
     */
    private function getRestockingFeeAccountId(): int
    {
        // You should implement this based on your accounting structure
        $account = Account::where('account_type', 'income')
            ->where('name', 'like', '%restocking%')
            ->first();

        return $account?->id ?? 1;
    }
}
