<?php
namespace App\Models;
use App\Models\Concerns\BelongsToBusinessUnit;
use Illuminate\Database\Eloquent\Model;
class GoodsReceipt extends Model {
    use BelongsToBusinessUnit;
    protected $fillable=['business_unit_id','purchase_id','receipt_no','status','received_by','received_at','notes'];
    protected $casts=['received_at'=>'datetime'];
    public function purchase(){return $this->belongsTo(Purchase::class);}
    public function items(){return $this->hasMany(GoodsReceiptItem::class);}
    public function receivedBy(){return $this->belongsTo(User::class,'received_by');}
}
