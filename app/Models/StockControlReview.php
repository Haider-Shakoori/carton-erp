<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockControlReview extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'week_start',
        'week_end',
        'status',
        'owner_id',
        'due_date',
        'generated_at',
        'summary_snapshot',
        'review_notes',
        'decisions',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'week_start' => 'date',
        'week_end' => 'date',
        'due_date' => 'date',
        'generated_at' => 'datetime',
        'summary_snapshot' => 'array',
        'completed_at' => 'datetime',
    ];

    protected $appends = ['is_overdue'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockControlReviewItem::class);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== self::STATUS_COMPLETED
            && $this->due_date !== null
            && $this->due_date->endOfDay()->isPast();
    }
}
