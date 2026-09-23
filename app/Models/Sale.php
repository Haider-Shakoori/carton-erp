<?php
// app/Models/Sale.php

namespace App\Models;

use App\Models\Concerns\BelongsToBusinessUnit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    use SoftDeletes, BelongsToBusinessUnit;

    protected $fillable = [
        'sale_no',
        'customer_id',
        'production_order_id', // Add this
        'is_produced',         // Add this
        'currency_id',
        'total_cost_usd',
        'total_cost_afn',
        'total_profit_usd',
        'total_profit_afn',
        'sale_date',
        'delivery_date',
        'confirmed_at',
        'status',
        'exchange_rate',
        'subtotal',
        'discount_total',
        'tax_total',
        'shipping_cost',
        'grand_total',
        'usd_subtotal',
        'usd_discount_total',
        'usd_tax_total',
        'usd_shipping_cost',
        'usd_grand_total',
        'advance_payment',
        'usd_advance_payment',
        'due_amount',
        'usd_due_amount',
        'notes',
        'shipping_address',
        'billing_address',
        'deleted_at_confirmed',
        'deleted_by',
        'deletion_reason',
        'deletion_data',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'delivery_date' => 'date',
        'confirmed_at' => 'datetime',
        'deleted_at' => 'datetime',
        'deleted_at_confirmed' => 'datetime',
        'deletion_data' => 'array',
        'exchange_rate' => 'decimal:6',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'usd_subtotal' => 'decimal:2',
        'usd_discount_total' => 'decimal:2',
        'usd_tax_total' => 'decimal:2',
        'usd_shipping_cost' => 'decimal:2',
        'usd_grand_total' => 'decimal:2',
        'advance_payment' => 'decimal:2',
        'usd_advance_payment' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'usd_due_amount' => 'decimal:2',
    ];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($sale) {
            if ($sale->isForceDeleting()) {
                // Force delete - remove all related data permanently
                $sale->items()->forceDelete();
                $sale->returns()->forceDelete();
            }
        });
    }

    /**
     * Get the customer for this sale
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'customer_id');
    }

    /**
     * Get the currency for this sale
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the items for this sale
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * All production orders created for this sale.
     *
     * production_order_id on sales is retained as the legacy/primary pointer;
     * this plural relationship is authoritative for multi-line sales.
     */
    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class, 'sale_id');
    }

    public function gatePass()
    {
        return $this->hasOne(GatePass::class);
    }

    /**
     * Get the returns for this sale
     */
    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    /**
     * Get the return items for this sale (through returns)
     * Using HasManyThrough relationship
     */
    public function returnItems(): HasManyThrough
    {
        return $this->hasManyThrough(
            SaleReturnItem::class,    // The final model
            SaleReturn::class,        // The intermediate model
            'sale_id',               // Foreign key on SaleReturn
            'sale_return_id',        // Foreign key on SaleReturnItem
            'id',                    // Local key on Sale
            'id'                     // Local key on SaleReturn
        );
    }

    /**
     * Get the user who deleted this sale
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Generate a unique sale number
     */
    public static function generateNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "SO-{$year}{$month}-";

        // Include soft-deleted rows because the DB unique constraint on
        // sale_no does not honor SoftDeletes. Skipping them here would let us
        // re-issue a number that still physically exists in the sales table.
        $existing = self::withTrashed()
            ->where('sale_no', 'like', $prefix . '%')
            ->pluck('sale_no');

        $maxNumber = 0;
        foreach ($existing as $saleNo) {
            $suffix = (int) substr($saleNo, strlen($prefix));
            if ($suffix > $maxNumber) {
                $maxNumber = $suffix;
            }
        }

        return $prefix . str_pad($maxNumber + 1, 4, '0', STR_PAD_LEFT);
    }


    public function recalculateTotals()
    {
        // Always read the persisted rows. Several controller flows create/update
        // sale items after the Sale model has already eager-loaded its items
        // relation; using the cached relation here can silently calculate a
        // zero/stale sale total.
        $items = $this->items()->get();
        $this->setRelation('items', $items);

        $this->subtotal = $items->sum('total');
        $this->discount_total = $items->sum('discount');
        $this->tax_total = $items->sum('tax');
        $this->grand_total = $this->subtotal - $this->discount_total + $this->tax_total + $this->shipping_cost;

        $this->usd_subtotal = $items->sum('usd_total');
        $this->usd_discount_total = $items->sum('usd_discount');
        $this->usd_tax_total = $items->sum('usd_tax');
        $this->usd_grand_total = $this->usd_subtotal - $this->usd_discount_total + $this->usd_tax_total + $this->usd_shipping_cost;

        $this->due_amount = $this->grand_total - $this->advance_payment;
        $this->usd_due_amount = $this->usd_grand_total - $this->usd_advance_payment;

        // ─── RECALCULATE COGS AND PROFIT FROM ITEMS ───
        $totalCostUsd = 0;
        $totalProfitUsd = 0;
        $totalCostAfn = 0;
        $totalProfitAfn = 0;

        foreach ($items as $item) {
            // If cost is not set or is 0, calculate from BOM
            if ($item->total_cost_usd <= 0 && $item->bom_id) {
                $item->calculateCostFromBOM();
                $item->saveQuietly();
            }

            $totalCostUsd += $item->total_cost_usd ?? 0;
            $totalProfitUsd += $item->profit_usd ?? 0;
            $totalCostAfn += $item->total_cost_afn ?? 0;
            $totalProfitAfn += $item->profit_afn ?? 0;
        }

        // Store the totals on the sale
        $this->total_cost_usd = $totalCostUsd;
        $this->total_cost_afn = $totalCostAfn;
        $this->total_profit_usd = $totalProfitUsd;
        $this->total_profit_afn = $totalProfitAfn;

        $this->save();

        return [
            'total_cogs_usd' => $totalCostUsd,
            'total_cogs_afn' => $totalCostAfn,
            'total_profit_usd' => $totalProfitUsd,
            'total_profit_afn' => $totalProfitAfn,
            'profit_margin' => $totalCostUsd > 0 ? ($totalProfitUsd / $totalCostUsd) * 100 : 0,
        ];
    }
    /**
     * Check if sale can be deleted - ALL statuses can be deleted
     */
    public function canBeDeleted(): bool
    {
        // Allow deletion for ALL statuses
        return in_array($this->status, ['draft', 'confirmed', 'shipped', 'delivered']);
    }

    /**
     * Get deletion warning message
     */
    public function getDeletionWarning(): string
    {
        if ($this->status === 'draft') {
            return 'This is a draft sale. It will be permanently removed.';
        }

        $warnings = [
            'confirmed' => '⚠️ This sale is CONFIRMED. Deleting will:
- Restore all stock quantities
- Reverse all transactions
- Remove all sale records including returns
- This action is permanent!',
            'shipped' => '⚠️ This sale is SHIPPED. Deleting will:
- Restore all stock quantities
- Reverse all transactions
- Remove all shipping records
- Remove all returns
- This action is permanent!',
            'delivered' => '⚠️ This sale is DELIVERED. Deleting will:
- Restore all stock quantities
- Reverse all transactions
- Remove all delivery records
- Remove all returns
- This action is permanent!',
        ];

        return $warnings[$this->status] ?? 'Are you sure you want to delete this sale?';
    }

    /**
     * Perform HARD DELETE with complete cleanup - EVERYTHING is removed
     */
    public function performHardDelete(string $reason = null): bool
    {
        if (!$this->canBeDeleted()) {
            throw new \Exception('This sale cannot be deleted.');
        }

        DB::beginTransaction();

        try {
            // 1. RESTORE STOCK QUANTITIES from sale items
            foreach ($this->items as $item) {
                if ($item->purchaseItem) {
                    $purchaseItem = PurchaseItem::find($item->purchase_item_id);
                    if ($purchaseItem) {
                        $purchaseItem->qty_sold -= $item->qty;
                        $purchaseItem->save();
                    }
                }
            }

            // 2. DELETE ALL SALE RETURNS AND THEIR ITEMS
            foreach ($this->returns as $return) {
                // Delete return items
                $return->items()->delete();
                // Delete the return
                $return->delete();
            }

            // 3. DELETE ALL TRANSACTIONS related to this sale
            $transactions = Transaction::where('table_name', 'sales')
                ->where('table_row_id', $this->id)
                ->get();

            foreach ($transactions as $transaction) {
                // Create reversal transaction if not already reversed
                if ($transaction->status !== 'reversed') {
                    $reversalType = $transaction->transaction_type === 'debit' ? 'credit' : 'debit';

                    Transaction::create([
                        'type' => 'sale_deletion',
                        'table_name' => 'sales',
                        'table_row_id' => $this->id,
                        'account_id' => $transaction->account_id,
                        'currency_id' => $transaction->currency_id,
                        'amount' => $transaction->amount,
                        'transaction_type' => $reversalType,
                        'is_cash' => $transaction->is_cash,
                        'description' => "REVERSAL: Deleted Sale #{$this->sale_no}",
                        'is_visible' => false,
                        'status' => 'reversed',
                        'created_by' => auth()->id(),
                    ]);
                }

                // Delete the original transaction
                $transaction->delete();
            }

            // 4. DELETE ALL SALE ITEMS
            $this->items()->delete();

            // 5. DELETE THE SALE ITSELF (HARD DELETE - bypasses soft delete)
            $this->forceDelete();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Perform SOFT DELETE with cleanup (keeps returns but marks as deleted)
     */
    public function performSoftDelete(string $reason = null): bool
    {
        if (!$this->canBeDeleted()) {
            throw new \Exception('This sale cannot be deleted.');
        }

        DB::beginTransaction();

        try {
            // 1. Store deletion data snapshot
            $this->deletion_data = [
                'sale_no' => $this->sale_no,
                'customer' => $this->customer?->name,
                'status' => $this->status,
                'total' => $this->grand_total,
                'items' => $this->items->map(function ($item) {
                    return [
                        'product' => $item->product?->name,
                        'qty' => $item->qty,
                        'total' => $item->total,
                    ];
                })->toArray(),
                'returns' => $this->returns->map(function ($return) {
                    return [
                        'return_no' => $return->return_no,
                        'total' => $return->grand_total,
                        'items' => $return->items->count(),
                    ];
                })->toArray(),
                'transactions' => Transaction::where('table_name', 'sales')
                    ->where('table_row_id', $this->id)
                    ->get()
                    ->map(function ($txn) {
                        return [
                            'id' => $txn->id,
                            'type' => $txn->transaction_type,
                            'amount' => $txn->amount,
                            'account' => $txn->account?->name,
                        ];
                    })->toArray(),
            ];

            // 2. Restore stock quantities
            foreach ($this->items as $item) {
                if ($item->purchaseItem) {
                    $purchaseItem = PurchaseItem::find($item->purchase_item_id);
                    if ($purchaseItem) {
                        $purchaseItem->qty_sold -= $item->qty;
                        $purchaseItem->save();
                    }
                }
            }

            // 3. Delete all return items and returns (SOFT DELETE)
            foreach ($this->returns as $return) {
                // Soft delete return items
                foreach ($return->items as $returnItem) {
                    $returnItem->delete();
                }
                // Soft delete the return
                $return->delete();
            }

            // 4. Reverse transactions
            $transactions = Transaction::where('table_name', 'sales')
                ->where('table_row_id', $this->id)
                ->get();

            foreach ($transactions as $transaction) {
                // Create reversal transaction
                $reversalType = $transaction->transaction_type === 'debit' ? 'credit' : 'debit';

                Transaction::create([
                    'type' => 'sale_deletion',
                    'table_name' => 'sales',
                    'table_row_id' => $this->id,
                    'account_id' => $transaction->account_id,
                    'currency_id' => $transaction->currency_id,
                    'amount' => $transaction->amount,
                    'transaction_type' => $reversalType,
                    'is_cash' => $transaction->is_cash,
                    'description' => "REVERSAL: Deleted Sale #{$this->sale_no}",
                    'is_visible' => false,
                    'status' => 'reversed',
                    'created_by' => auth()->id(),
                ]);

                // Mark original as reversed
                $transaction->status = 'reversed';
                $transaction->save();
            }

            // 5. Set deletion metadata
            $this->deleted_by = auth()->id();
            $this->deletion_reason = $reason;
            $this->deleted_at_confirmed = now();

            // 6. Soft delete the sale
            $this->delete();

            // 7. Soft delete sale items
            foreach ($this->items as $item) {
                $item->delete();
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Restore a soft-deleted sale and all its related data
     */
    public function performRestore(): bool
    {
        if (!$this->trashed()) {
            throw new \Exception('This sale is not deleted.');
        }

        DB::beginTransaction();

        try {
            // 1. Restore the sale
            $this->restore();

            // 2. Restore items
            foreach ($this->items()->withTrashed()->get() as $item) {
                $item->restore();
            }

            // 3. Restore returns and return items
            foreach ($this->returns()->withTrashed()->get() as $return) {
                // Restore return items
                foreach ($return->items()->withTrashed()->get() as $returnItem) {
                    $returnItem->restore();
                }
                // Restore the return
                $return->restore();
            }

            // 4. Restore stock
            foreach ($this->items as $item) {
                if ($item->purchaseItem) {
                    $purchaseItem = PurchaseItem::find($item->purchase_item_id);
                    if ($purchaseItem) {
                        $purchaseItem->qty_sold += $item->qty;
                        $purchaseItem->save();
                    }
                }
            }

            // 5. Restore transactions (reverse the reversals)
            $reversals = Transaction::where('table_name', 'sales')
                ->where('table_row_id', $this->id)
                ->where('type', 'sale_deletion')
                ->where('status', 'reversed')
                ->get();

            foreach ($reversals as $reversal) {
                // Reverse the reversal - restore original transaction
                $originalType = $reversal->transaction_type === 'debit' ? 'credit' : 'debit';

                Transaction::create([
                    'type' => 'sale_restoration',
                    'table_name' => 'sales',
                    'table_row_id' => $this->id,
                    'account_id' => $reversal->account_id,
                    'currency_id' => $reversal->currency_id,
                    'amount' => $reversal->amount,
                    'transaction_type' => $originalType,
                    'is_cash' => $reversal->is_cash,
                    'description' => "RESTORE: Restored Sale #{$this->sale_no}",
                    'is_visible' => true,
                    'status' => 'active',
                    'created_by' => auth()->id(),
                ]);

                // Delete the reversal transaction
                $reversal->delete();
            }

            // 6. Clear deletion metadata
            $this->deleted_by = null;
            $this->deletion_reason = null;
            $this->deleted_at_confirmed = null;
            $this->deletion_data = null;
            $this->save();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }
    public static function findByProductionOrder($productionOrderId)
    {
        return self::where('production_order_id', $productionOrderId)->first();
    }

    /**
     * Get total discount amount for the sale.
     */
    public function getTotalDiscountAttribute()
    {
        return $this->items->sum('discount_amount');
    }

    /**
     * Get total discount percentage (average).
     */
    public function getAverageDiscountPercentageAttribute()
    {
        $totalBase = $this->items->sum(function($item) {
            return ($item->base_price ?: $item->original_unit_price ?: $item->unit_price) * $item->qty;
        });

        if ($totalBase <= 0) {
            return 0;
        }

        $totalDiscount = $this->total_discount;
        return ($totalDiscount / $totalBase) * 100;
    }

    /**
     * Check if any item has price adjustment.
     */
    public function getHasPriceAdjustmentAttribute()
    {
        return $this->items->contains(function($item) {
            return $item->price_adjustment_type !== 'none';
        });
    }

    /**
     * Get original total without discounts.
     */
    public function getOriginalTotalAttribute()
    {
        return $this->items->sum(function($item) {
            return ($item->original_unit_price ?: $item->unit_price) * $item->qty;
        });
    }
}
