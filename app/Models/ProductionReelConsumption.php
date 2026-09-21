<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionReelConsumption extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'production_material_consumption_id',
        'purchase_item_reel_id',
        'quantity_kg',
        'before_weight_kg',
        'after_weight_kg',
        'consumed_at',
        'created_at',
    ];

    protected $casts = [
        'quantity_kg' => 'decimal:4',
        'before_weight_kg' => 'decimal:4',
        'after_weight_kg' => 'decimal:4',
        'consumed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function materialConsumption(): BelongsTo
    {
        return $this->belongsTo(
            ProductionMaterialConsumption::class,
            'production_material_consumption_id'
        );
    }

    public function reel(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseItemReel::class,
            'purchase_item_reel_id'
        );
    }
}
