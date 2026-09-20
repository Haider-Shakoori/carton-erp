<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReconciliationItem extends Model
{
    protected $fillable = [
        'stock_reconciliation_id',
        'product_id',
        'purchase_item_id',
        'batch_no',
        'purchase_no',
        'inventory_unit',
        'system_quantity',
        'physical_quantity',
        'variance_quantity',
        'cost_per_unit_usd',
        'variance_value_usd',
        'reason_code',
        'notes',
        'batch_updated_at_snapshot',
        'batch_native_quantity_snapshot',
        'batch_kg_quantity_snapshot',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:6',
        'physical_quantity' => 'decimal:6',
        'variance_quantity' => 'decimal:6',
        'cost_per_unit_usd' => 'decimal:6',
        'variance_value_usd' => 'decimal:4',
        'batch_updated_at_snapshot' => 'datetime',
        'batch_native_quantity_snapshot' => 'decimal:6',
        'batch_kg_quantity_snapshot' => 'decimal:6',
    ];

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(StockReconciliation::class, 'stock_reconciliation_id');
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
