<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseGoodsReceipt extends Model
{
    protected $fillable = [
        'purchase_id',
        'receipt_no',
        'status',
        'received_at',
        'received_by',
        'notes',
    ];

    protected $casts = ['received_at' => 'datetime'];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseGoodsReceiptItem::class);
    }
}
