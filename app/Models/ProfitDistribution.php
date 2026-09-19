<?php
// app/Models/ProfitDistribution.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProfitDistribution extends Model
{
    use HasFactory;

    protected $table = 'profit_distributions';

    protected $fillable = [
        'distribution_number',
        'period_start',
        'period_end',
        'total_profit',
        'distributed_amount',
        'remaining_amount',
        'distribution_date',
        'status',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'distribution_date' => 'date',
        'total_profit' => 'decimal:2',
        'distributed_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_DISTRIBUTED = 'distributed';
    const STATUS_CANCELLED = 'cancelled';

    const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PENDING => 'Pending Approval',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_DISTRIBUTED => 'Distributed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    // ─── Relationships ───

    /**
     * Get the items for this distribution.
     * Explicitly define foreign key as 'distribution_id'
     */
    public function items()
    {
        return $this->hasMany(ProfitDistributionItem::class, 'distribution_id', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ─── Boot Method ───

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($distribution) {
            if (empty($distribution->distribution_number)) {
                $distribution->distribution_number = 'PD-' . date('Y') . '-' . strtoupper(Str::random(8));
            }
        });
    }

    // ─── Accessors ───

    public function getStatusLabelAttribute()
    {
        return self::STATUS_LABELS[$this->status] ?? 'Unknown';
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'info',
            self::STATUS_DISTRIBUTED => 'success',
            self::STATUS_CANCELLED => 'danger',
        ];
        return $badges[$this->status] ?? 'secondary';
    }

    public function getPeriodLabelAttribute()
    {
        return $this->period_start->format('M d, Y') . ' - ' . $this->period_end->format('M d, Y');
    }

    // ─── Scopes ───

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeDistributed($query)
    {
        return $query->where('status', self::STATUS_DISTRIBUTED);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    // ─── Helper Methods ───

    public function calculateTotalDistributed()
    {
        return $this->items()->sum('amount');
    }

    public function getIsFullyDistributedAttribute()
    {
        return $this->remaining_amount <= 0;
    }

    public function getProgressPercentageAttribute()
    {
        if ($this->total_profit <= 0) {
            return 0;
        }
        return min(100, ($this->distributed_amount / $this->total_profit) * 100);
    }
}
