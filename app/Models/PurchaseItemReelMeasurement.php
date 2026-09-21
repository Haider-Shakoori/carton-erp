<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItemReelMeasurement extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'purchase_item_reel_id',
        'system_weight_snapshot_kg',
        'measured_weight_kg',
        'variance_kg',
        'measured_by',
        'measured_at',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'system_weight_snapshot_kg' => 'decimal:4',
        'measured_weight_kg' => 'decimal:4',
        'variance_kg' => 'decimal:4',
        'measured_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function reel(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseItemReel::class,
            'purchase_item_reel_id'
        );
    }

    public function measurer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'measured_by');
    }
}
