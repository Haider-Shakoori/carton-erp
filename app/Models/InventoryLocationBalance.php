<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLocationBalance extends Model
{
    protected $fillable = [
        'purchase_item_id',
        'warehouse_location_id',
        'condition_status',
        'quantity',
        'unit',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
    ];

    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function location()
    {
        return $this->belongsTo(WarehouseLocation::class, 'warehouse_location_id');
    }
}
