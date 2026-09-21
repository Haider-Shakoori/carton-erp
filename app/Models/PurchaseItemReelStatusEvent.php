<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItemReelStatusEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'purchase_item_reel_id',
        'from_status',
        'to_status',
        'reason',
        'changed_by',
        'changed_at',
        'created_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function reel(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseItemReel::class,
            'purchase_item_reel_id'
        );
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
