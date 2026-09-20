<?php
// app/Models/ProductionOrder.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductionOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'production_orders';

    protected $fillable = [
        'order_number',
        'product_id',
        'bom_id',
        'quantity_ordered',
        'quantity_planned',
        'quantity_manufactured',
        'quantity_produced',
        'quantity_rejected',
        'status',
        'start_date',
        'completion_date',
        'total_material_cost',
        'total_labor_cost',
        'total_overhead_cost',
        'total_cost',
        'created_by',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:2',
        'quantity_planned' => 'decimal:2',
        'quantity_manufactured' => 'decimal:2',
        'quantity_produced' => 'decimal:2',
        'quantity_rejected' => 'decimal:2',
        'total_material_cost' => 'decimal:4',
        'total_labor_cost' => 'decimal:4',
        'total_overhead_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'start_date' => 'date',
        'completion_date' => 'date',
        'approved_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_IN_PROGRESS => 'In Progress',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'PROD-' . date('Y') . '-' . strtoupper(Str::random(8));
            }
        });
    }

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function bom()
    {
        return $this->belongsTo(BOM::class, 'bom_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function materials()
    {
        return $this->hasMany(ProductionOrderMaterial::class);
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    // Accessors
    public function getStatusLabelAttribute()
    {
        return self::STATUS_LABELS[$this->status] ?? 'Unknown';
    }

    public function getProgressPercentageAttribute()
    {
        if ($this->quantity_ordered <= 0) {
            return 0;
        }
        return min(100, ($this->quantity_produced / $this->quantity_ordered) * 100);
    }

    public function getGoodQuantityAttribute(): float
    {
        return (float) ($this->quantity_produced ?? 0);
    }

    public function getProductionVarianceQuantityAttribute(): float
    {
        $planned = (float) ($this->quantity_planned ?? $this->quantity_ordered ?? 0);
        $manufactured = (float) ($this->quantity_manufactured ?? $this->quantity_produced ?? 0);

        return $manufactured - $planned;
    }

    public function getYieldPercentageAttribute(): float
    {
        $manufactured = (float) ($this->quantity_manufactured ?? 0);

        if ($manufactured <= 0) {
            return 0.0;
        }

        return min(100, max(0, ((float) $this->quantity_produced / $manufactured) * 100));
    }

    public function getIsCompletedAttribute()
    {
        // A production order may validly finish below or above the customer
        // order quantity. Completion is a workflow state, not a quantity test.
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getCostPerUnitAttribute()
    {
        if ($this->quantity_produced <= 0) {
            return 0;
        }
        return $this->total_cost / $this->quantity_produced;
    }

    public function getSellingPricePerUnitAttribute()
    {
        $costPerUnit = $this->cost_per_unit;
        $profitMargin = $this->bom->profit_margin_percentage ?? 0;
        return $costPerUnit * (1 + ($profitMargin / 100));
    }

    public function sale()
    {
        return $this->hasOne(Sale::class, 'production_order_id');
    }

    /**
     * Check if this production order is linked to a sale
     */
    public function hasSale()
    {
        return $this->sale()->exists();
    }
}
