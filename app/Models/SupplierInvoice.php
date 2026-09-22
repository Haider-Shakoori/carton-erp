<?php
namespace App\Models;
use App\Models\Concerns\BelongsToBusinessUnit;
use Illuminate\Database\Eloquent\Model;
class SupplierInvoice extends Model {
    use BelongsToBusinessUnit;
    protected $fillable=['business_unit_id','purchase_id','currency_id','invoice_no','invoice_date','due_date','subtotal','expense_total','total','usd_total','status','three_way_match_status','match_note','approved_by','approved_at','paid_by','paid_at','payment_reference','notes'];
    protected $casts=['invoice_date'=>'date','due_date'=>'date','subtotal'=>'decimal:2','expense_total'=>'decimal:2','total'=>'decimal:2','usd_total'=>'decimal:2','approved_at'=>'datetime','paid_at'=>'datetime'];
    public function purchase(){return $this->belongsTo(Purchase::class);}
    public function currency(){return $this->belongsTo(Currency::class);}
    public function approvedBy(){return $this->belongsTo(User::class,'approved_by');}
    public function paidBy(){return $this->belongsTo(User::class,'paid_by');}
}
