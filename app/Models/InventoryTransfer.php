<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusinessUnit;
use Illuminate\Database\Eloquent\Model;

class InventoryTransfer extends Model
{
    use BelongsToBusinessUnit;

    protected $fillable = [
        'business_unit_id',
        'transfer_no',
        'from_location_id',
        'to_location_id',
        'status',
        'reason',
        'requested_by',
        'approved_by',
        'completed_by',
        'approved_at',
        'completed_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function fromLocation()
    {
        return $this->belongsTo(WarehouseLocation::class, 'from_location_id');
    }

    public function toLocation()
    {
        return $this->belongsTo(WarehouseLocation::class, 'to_location_id');
    }

    public function items()
    {
        return $this->hasMany(InventoryTransferItem::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
