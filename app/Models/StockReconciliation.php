<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockReconciliation extends Model
{
    public const STATUS_COUNTING = 'counting';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_POSTED = 'posted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'reconciliation_no',
        'count_date',
        'snapshot_at',
        'status',
        'notes',
        'created_by',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'posted_by',
        'posted_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'count_date' => 'date',
        'snapshot_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(StockReconciliationItem::class);
    }

    public function adjustment()
    {
        return $this->hasOne(StockAdjustment::class, 'stock_reconciliation_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_COUNTING;
    }

    public function getTotalVarianceValueUsdAttribute(): float
    {
        if ($this->relationLoaded('items')) {
            return (float) $this->items->sum('variance_value_usd');
        }

        return (float) $this->items()->sum('variance_value_usd');
    }
}
