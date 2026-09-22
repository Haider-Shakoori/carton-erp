<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GlAccount extends Model {
    protected $fillable=['code','name','type','parent_id','is_control','is_active'];
    protected $casts=['is_control'=>'boolean','is_active'=>'boolean'];
    public function lines(){return $this->hasMany(JournalLine::class);}
}
