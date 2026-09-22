<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RfqQuote extends Model {
    protected $fillable=['request_for_quotation_id','supplier_id','currency_id','quote_no','exchange_rate','total','status','quoted_at','valid_until','terms','created_by'];
    protected $casts=['exchange_rate'=>'decimal:6','total'=>'decimal:2','quoted_at'=>'date','valid_until'=>'date'];
    public function rfq(){return $this->belongsTo(RequestForQuotation::class,'request_for_quotation_id');}
    public function supplier(){return $this->belongsTo(Account::class,'supplier_id');}
    public function currency(){return $this->belongsTo(Currency::class);}
    public function items(){return $this->hasMany(RfqQuoteItem::class);}
}
