<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLocationBalance extends Model
{
    protected $fillable = [
        'purchase_item_id',
        'product_id',
        'warehouse_id',
        'warehouse_location_id',
        'quantity',
        'quantity_kg',
        'unit',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'quantity_kg' => 'decimal:6',
    ];

    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location()
    {
        return $this->belongsTo(WarehouseLocation::class, 'warehouse_location_id');
    }
}
