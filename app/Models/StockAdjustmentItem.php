<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    protected $fillable = [
        'stock_adjustment_id',
        'product_id',
        'purchase_item_id',
        'inventory_unit',
        'before_quantity',
        'adjustment_quantity',
        'after_quantity',
        'cost_per_unit_usd',
        'adjustment_value_usd',
        'reason_code',
        'notes',
    ];

    protected $casts = [
        'before_quantity' => 'decimal:6',
        'adjustment_quantity' => 'decimal:6',
        'after_quantity' => 'decimal:6',
        'cost_per_unit_usd' => 'decimal:6',
        'adjustment_value_usd' => 'decimal:4',
    ];

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }
}
