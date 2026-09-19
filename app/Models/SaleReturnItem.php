<?php
// app/Models/SaleReturnItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnItem extends Model
{
    protected $fillable = [
        'sale_return_id',
        'sale_id',
        'sale_item_id',
        'product_id',
        'purchase_item_id',
        'qty_returned',
        'original_qty',
        'original_unit_price',
        'original_usd_unit_price',
        'unit_price',
        'total',
        'discount',
        'usd_unit_price',
        'usd_total',
        'usd_discount',
        'rate',
        'cost_per_unit_usd',
        'total_cost_usd',
        'reason',
        'reason_notes',
        'condition',
        'restocked',
        'restocked_at',
    ];

    protected $casts = [
        'qty_returned' => 'decimal:2',
        'original_qty' => 'decimal:2',
        'original_unit_price' => 'decimal:4',
        'original_usd_unit_price' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'total' => 'decimal:2',
        'discount' => 'decimal:2',
        'usd_unit_price' => 'decimal:4',
        'usd_total' => 'decimal:2',
        'usd_discount' => 'decimal:2',
        'rate' => 'decimal:6',
        'cost_per_unit_usd' => 'decimal:4',
        'total_cost_usd' => 'decimal:2',
        'restocked' => 'boolean',
        'restocked_at' => 'datetime',
    ];

    /**
     * Get the return that this item belongs to
     */
    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    /**
     * Get the original sale
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the original sale item
     */
    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the purchase item (for stock tracking)
     */
    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    /**
     * Get the reason label
     */
    public function getReasonLabelAttribute(): string
    {
        return [
            'damaged' => 'Damaged',
            'defective' => 'Defective',
            'wrong_item' => 'Wrong Item',
            'wrong_quantity' => 'Wrong Quantity',
            'customer_cancelled' => 'Customer Cancelled',
            'quality_issue' => 'Quality Issue',
            'other' => 'Other',
        ][$this->reason] ?? 'Other';
    }

    /**
     * Get the condition label
     */
    public function getConditionLabelAttribute(): string
    {
        return [
            'new' => 'New/Unused',
            'used' => 'Used',
            'damaged' => 'Damaged',
        ][$this->condition] ?? 'Used';
    }

    /**
     * Get the restock status badge class
     */
    public function getRestockBadgeClassAttribute(): string
    {
        return $this->restocked ? 'success' : 'warning';
    }

    /**
     * Get the restock status label
     */
    public function getRestockStatusLabelAttribute(): string
    {
        return $this->restocked ? 'Restocked' : 'Pending Restock';
    }
}
