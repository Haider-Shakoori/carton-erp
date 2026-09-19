<?php
// app/Models/ProductionOrderMaterial.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionOrderMaterial extends Model
{
    use HasFactory;

    protected $table = 'production_order_materials';

    protected $fillable = [
        'production_order_id',
        'product_id',
        'required_quantity',
        'available_quantity',
        'shortage_quantity',
        'unit',
        'cost_per_unit',
        'total_cost',
        'batch_id',
        'consumed_quantity',
    ];

    protected $casts = [
        'required_quantity' => 'decimal:4',
        'available_quantity' => 'decimal:4',
        'shortage_quantity' => 'decimal:4',
        'cost_per_unit' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'consumed_quantity' => 'decimal:4',
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function batch()
    {
        return $this->belongsTo(PurchaseItem::class, 'batch_id');
    }
}
