<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    protected $fillable = [
        'stock_transfer_id',
        'purchase_item_id',
        'product_id',
        'from_location_id',
        'to_location_id',
        'quantity',
        'quantity_kg',
        'unit',
        'unit_cost_usd',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'quantity_kg' => 'decimal:6',
        'unit_cost_usd' => 'decimal:6',
    ];

    public function transfer()
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }
}
