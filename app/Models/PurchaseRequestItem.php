<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PurchaseRequestItem extends Model {
    protected $fillable=['purchase_request_id','product_id','quantity','unit','notes'];
    protected $casts=['quantity'=>'decimal:6'];
    public function request(){return $this->belongsTo(PurchaseRequest::class,'purchase_request_id');}
    public function product(){return $this->belongsTo(Product::class);}
}
