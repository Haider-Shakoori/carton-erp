<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockControlEscalation extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'source_key',
        'source_type',
        'severity',
        'level',
        'status',
        'product_id',
        'root_cause_code',
        'investigation_id',
        'title',
        'message',
        'occurrences',
        'absolute_value_usd',
        'escalated_to',
        'review_due_date',
        'opened_at',
        'last_detected_at',
        'acknowledged_by',
        'acknowledged_at',
        'closed_by',
        'closed_at',
        'resolution_notes',
        'metadata',
    ];

    protected $casts = [
        'review_due_date' => 'date',
        'opened_at' => 'datetime',
        'last_detected_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'closed_at' => 'datetime',
        'absolute_value_usd' => 'decimal:4',
        'metadata' => 'array',
    ];

    protected $appends = ['is_overdue'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function investigation(): BelongsTo
    {
        return $this->belongsTo(
            StockVarianceInvestigation::class,
            'investigation_id'
        );
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }

    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(StockControlEscalationEvent::class)
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function reviewItems(): HasMany
    {
        return $this->hasMany(StockControlReviewItem::class);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== self::STATUS_CLOSED
            && $this->review_due_date !== null
            && $this->review_due_date->endOfDay()->isPast();
    }
}
