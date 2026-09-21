<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $fillable = [
        'transfer_no',
        'from_warehouse_id',
        'to_warehouse_id',
        'status',
        'reason',
        'requested_by',
        'posted_by',
        'posted_at',
    ];

    protected $casts = ['posted_at' => 'datetime'];

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }
}
