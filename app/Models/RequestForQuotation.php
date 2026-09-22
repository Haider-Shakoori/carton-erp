<?php
namespace App\Models;
use App\Models\Concerns\BelongsToBusinessUnit;
use Illuminate\Database\Eloquent\Model;
class RequestForQuotation extends Model {
    use BelongsToBusinessUnit;
    protected $fillable=['business_unit_id','purchase_request_id','rfq_no','status','due_date','notes','created_by'];
    protected $casts=['due_date'=>'date'];
    public function request(){return $this->belongsTo(PurchaseRequest::class,'purchase_request_id');}
    public function quotes(){return $this->hasMany(RfqQuote::class);}
}
