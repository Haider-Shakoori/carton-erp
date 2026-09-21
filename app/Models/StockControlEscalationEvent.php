<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockControlEscalationEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'stock_control_escalation_id',
        'event_type',
        'user_id',
        'from_status',
        'to_status',
        'notes',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function escalation(): BelongsTo
    {
        return $this->belongsTo(
            StockControlEscalation::class,
            'stock_control_escalation_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
