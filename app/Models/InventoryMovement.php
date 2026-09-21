<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $fillable = [
        'purchase_item_id',
        'product_id',
        'warehouse_id',
        'warehouse_location_id',
        'movement_type',
        'direction',
        'quantity',
        'quantity_kg',
        'unit',
        'unit_cost_usd',
        'reference_type',
        'reference_id',
        'actor_id',
        'occurred_at',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'quantity_kg' => 'decimal:6',
        'unit_cost_usd' => 'decimal:6',
        'occurred_at' => 'datetime',
        'metadata' => 'array',
    ];
}
