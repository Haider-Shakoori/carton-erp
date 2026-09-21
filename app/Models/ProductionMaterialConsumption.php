<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionMaterialConsumption extends Model
{
    protected $fillable = [
        'production_order_id',
        'sale_id',
        'sale_item_id',
        'material_id',
        'purchase_item_id',
        'planned_quantity',
        'actual_quantity',
        'wastage_quantity',
        'unit',
        'cost_per_unit_usd',
        'cost_per_unit_afn',
        'total_cost_usd',
        'total_cost_afn',
        'wastage_cost_usd',
        'wastage_cost_afn',
        'consumed_at',
        'created_by',
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:4',
        'actual_quantity' => 'decimal:4',
        'wastage_quantity' => 'decimal:4',
        'cost_per_unit_usd' => 'decimal:6',
        'cost_per_unit_afn' => 'decimal:6',
        'total_cost_usd' => 'decimal:4',
        'total_cost_afn' => 'decimal:4',
        'wastage_cost_usd' => 'decimal:4',
        'wastage_cost_afn' => 'decimal:4',
        'consumed_at' => 'datetime',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'material_id');
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function reelConsumptions()
    {
        return $this->hasMany(
            ProductionReelConsumption::class,
            'production_material_consumption_id'
        );
    }
}
