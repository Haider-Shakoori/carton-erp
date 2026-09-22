<?php
namespace App\Models;
use App\Models\Concerns\BelongsToBusinessUnit;
use Illuminate\Database\Eloquent\Model;
class JournalEntry extends Model {
    use BelongsToBusinessUnit;
    protected $fillable=['business_unit_id','fiscal_period_id','entry_no','entry_date','source_type','source_id','source_root_key','source_version','description','status','reversal_of_id','posted_by','posted_at','reversal_reason'];
    protected $casts=['entry_date'=>'date','posted_at'=>'datetime','source_version'=>'integer'];
    public function lines(){return $this->hasMany(JournalLine::class);}
    public function period(){return $this->belongsTo(FiscalPeriod::class,'fiscal_period_id');}
    public function reversalOf(){return $this->belongsTo(self::class,'reversal_of_id');}
    public function postedBy(){return $this->belongsTo(User::class,'posted_by');}
}
