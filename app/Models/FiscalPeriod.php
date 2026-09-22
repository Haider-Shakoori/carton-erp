<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FiscalPeriod extends Model {
    protected $fillable=['name','starts_on','ends_on','status','closed_by','closed_at','locked_by','locked_at','notes'];
    protected $casts=['starts_on'=>'date','ends_on'=>'date','closed_at'=>'datetime','locked_at'=>'datetime'];
    public function entries(){return $this->hasMany(JournalEntry::class);}
}
