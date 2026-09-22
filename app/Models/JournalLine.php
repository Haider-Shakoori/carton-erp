<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class JournalLine extends Model {
    protected $fillable=['journal_entry_id','gl_account_id','currency_id','debit_usd','credit_usd','original_amount','exchange_rate','description'];
    protected $casts=['debit_usd'=>'decimal:6','credit_usd'=>'decimal:6','original_amount'=>'decimal:6','exchange_rate'=>'decimal:8'];
    public function entry(){return $this->belongsTo(JournalEntry::class,'journal_entry_id');}
    public function account(){return $this->belongsTo(GlAccount::class,'gl_account_id');}
    public function currency(){return $this->belongsTo(Currency::class);}
}
