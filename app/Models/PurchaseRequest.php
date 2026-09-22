<?php
namespace App\Models;
use App\Models\Concerns\BelongsToBusinessUnit;
use Illuminate\Database\Eloquent\Model;
class PurchaseRequest extends Model {
    use BelongsToBusinessUnit;
    protected $fillable=['business_unit_id','request_no','status','needed_by','justification','requested_by','approved_by','approved_at','approval_note'];
    protected $casts=['needed_by'=>'date','approved_at'=>'datetime'];
    public function items(){return $this->hasMany(PurchaseRequestItem::class);}
    public function requestedBy(){return $this->belongsTo(User::class,'requested_by');}
    public function approvedBy(){return $this->belongsTo(User::class,'approved_by');}
    public function rfqs(){return $this->hasMany(RequestForQuotation::class);}
}
