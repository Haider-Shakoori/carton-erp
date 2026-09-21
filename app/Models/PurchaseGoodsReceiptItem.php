<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseGoodsReceiptItem extends Model
{
    protected $fillable = [
        'purchase_goods_receipt_id',
        'purchase_item_id',
        'product_id',
        'ordered_quantity',
        'received_quantity',
        'received_quantity_kg',
        'unit',
    ];

    protected $casts = [
        'ordered_quantity' => 'decimal:6',
        'received_quantity' => 'decimal:6',
        'received_quantity_kg' => 'decimal:6',
    ];

    public function receipt()
    {
        return $this->belongsTo(PurchaseGoodsReceipt::class, 'purchase_goods_receipt_id');
    }
}
