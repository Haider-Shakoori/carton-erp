<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GoodsReceiptItem extends Model {
    protected $fillable=['goods_receipt_id','purchase_item_id','quantity_received','quantity_rejected','unit'];
    protected $casts=['quantity_received'=>'decimal:6','quantity_rejected'=>'decimal:6'];
    public function receipt(){return $this->belongsTo(GoodsReceipt::class,'goods_receipt_id');}
    public function purchaseItem(){return $this->belongsTo(PurchaseItem::class);}
}
