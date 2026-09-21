<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseItemReel extends Model
{
    public const STATUS_SEALED = 'sealed';
    public const STATUS_OPEN = 'open';
    public const STATUS_CONSUMED = 'consumed';
    public const STATUS_DAMAGED = 'damaged';
    public const STATUS_QUARANTINED = 'quarantined';

    protected $fillable = [
        'purchase_item_id',
        'sequence_no',
        'reel_code',
        'registered_weight_kg',
        'system_remaining_weight_kg',
        'last_measured_weight_kg',
        'measurement_variance_kg',
        'status',
        'status_reason',
        'status_changed_at',
        'status_changed_by',
        'opened_at',
        'depleted_at',
        'last_measured_at',
        'last_measured_by',
        'notes',
    ];

    protected $casts = [
        'registered_weight_kg' => 'decimal:4',
        'system_remaining_weight_kg' => 'decimal:4',
        'last_measured_weight_kg' => 'decimal:4',
        'measurement_variance_kg' => 'decimal:4',
        'status_changed_at' => 'datetime',
        'opened_at' => 'datetime',
        'depleted_at' => 'datetime',
        'last_measured_at' => 'datetime',
    ];

    protected $appends = [
        'is_measured',
        'measurement_age_days',
    ];

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(PurchaseItemReelMeasurement::class)
            ->orderByDesc('measured_at')
            ->orderByDesc('id');
    }

    public function productionConsumptions(): HasMany
    {
        return $this->hasMany(ProductionReelConsumption::class);
    }

    public function statusEvents(): HasMany
    {
        return $this->hasMany(PurchaseItemReelStatusEvent::class)
            ->orderByDesc('changed_at')
            ->orderByDesc('id');
    }

    public function statusChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }

    public function measuredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_measured_by');
    }

    public function isProductionEligible(): bool
    {
        return in_array(
            $this->status,
            [self::STATUS_SEALED, self::STATUS_OPEN],
            true
        ) && (float) $this->system_remaining_weight_kg > 0;
    }

    public function isBlockedFromProduction(): bool
    {
        return in_array(
            $this->status,
            [self::STATUS_DAMAGED, self::STATUS_QUARANTINED],
            true
        );
    }

    public function getIsMeasuredAttribute(): bool
    {
        return $this->last_measured_at !== null;
    }

    public function getMeasurementAgeDaysAttribute(): ?int
    {
        if (! $this->last_measured_at) {
            return null;
        }

        return max(
            (int) $this->last_measured_at->copy()->startOfDay()
                ->diffInDays(now()->startOfDay()),
            0
        );
    }
}
