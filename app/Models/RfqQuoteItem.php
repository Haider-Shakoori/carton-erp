<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RfqQuoteItem extends Model {
    protected $fillable=['rfq_quote_id','product_id','quantity','unit_price','total'];
    protected $casts=['quantity'=>'decimal:6','unit_price'=>'decimal:6','total'=>'decimal:2'];
    public function quote(){return $this->belongsTo(RfqQuote::class,'rfq_quote_id');}
    public function product(){return $this->belongsTo(Product::class);}
}
