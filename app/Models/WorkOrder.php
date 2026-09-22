<?php
// app/Models/WorkOrder.php

namespace App\Models;

use App\Models\Concerns\BelongsToBusinessUnit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WorkOrder extends Model
{
    use HasFactory, BelongsToBusinessUnit;

    protected $table = 'work_orders';

    protected $fillable = [
        'production_order_id',
        'work_order_number',
        'operation_type',
        'machine_id',
        'assigned_to',
        'status',
        'estimated_time',
        'actual_time',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'estimated_time' => 'decimal:2',
        'actual_time' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Operation types
    const OPERATION_PRINTING = 'printing';
    const OPERATION_CUTTING = 'cutting';
    const OPERATION_GLUING = 'gluing';
    const OPERATION_FOLDING = 'folding';
    const OPERATION_LAMINATION = 'lamination';
    const OPERATION_QUALITY_CHECK = 'quality_check';

    const OPERATION_LABELS = [
        self::OPERATION_PRINTING => 'Printing',
        self::OPERATION_CUTTING => 'Cutting',
        self::OPERATION_GLUING => 'Gluing',
        self::OPERATION_FOLDING => 'Folding',
        self::OPERATION_LAMINATION => 'Lamination',
        self::OPERATION_QUALITY_CHECK => 'Quality Check',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';

    const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_IN_PROGRESS => 'In Progress',
        self::STATUS_COMPLETED => 'Completed',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($workOrder) {
            if (empty($workOrder->work_order_number)) {
                $workOrder->work_order_number = 'WO-' . date('Y') . '-' . strtoupper(Str::random(8));
            }
        });
    }

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class, 'machine_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function getOperationLabelAttribute()
    {
        return self::OPERATION_LABELS[$this->operation_type] ?? $this->operation_type;
    }

    public function getStatusLabelAttribute()
    {
        return self::STATUS_LABELS[$this->status] ?? 'Unknown';
    }

    public function getIsCompletedAttribute()
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
