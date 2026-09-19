<?php
// app/Models/Machine.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Machine extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'model',
        'manufacturer',
        'year',
        'serial_number',
        'status',
        'hourly_rate',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'hourly_rate' => 'decimal:2',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_INACTIVE = 'inactive';

    const STATUS_LABELS = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_MAINTENANCE => 'Under Maintenance',
        self::STATUS_INACTIVE => 'Inactive',
    ];

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function getStatusLabelAttribute()
    {
        return self::STATUS_LABELS[$this->status] ?? 'Unknown';
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
