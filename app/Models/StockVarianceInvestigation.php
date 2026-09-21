<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockVarianceInvestigation extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_INVESTIGATING = 'investigating';
    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'stock_adjustment_item_id',
        'status',
        'assigned_to',
        'due_date',
        'root_cause_code',
        'root_cause_details',
        'investigation_notes',
        'corrective_action',
        'resolution_notes',
        'opened_by',
        'opened_at',
        'started_at',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'opened_at' => 'datetime',
        'started_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    protected $appends = [
        'age_days',
        'is_overdue',
        'aging_bucket',
    ];

    public function adjustmentItem(): BelongsTo
    {
        return $this->belongsTo(StockAdjustmentItem::class, 'stock_adjustment_item_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(StockVarianceInvestigationEvent::class)
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function getAgeDaysAttribute(): int
    {
        $start = $this->opened_at ?: $this->created_at;
        if (! $start) {
            return 0;
        }

        $end = $this->resolved_at ?: now();

        return max((int) $start->startOfDay()->diffInDays($end->startOfDay()), 0);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== self::STATUS_RESOLVED
            && $this->due_date !== null
            && $this->due_date->endOfDay()->isPast();
    }

    public function getAgingBucketAttribute(): string
    {
        $days = $this->age_days;

        return match (true) {
            $days <= 7 => '0-7 days',
            $days <= 14 => '8-14 days',
            $days <= 30 => '15-30 days',
            default => '31+ days',
        };
    }
}
