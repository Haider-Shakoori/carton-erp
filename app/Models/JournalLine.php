<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalLine extends Model
{
    protected $fillable = [
        'journal_entry_id',
        'gl_account_id',
        'currency_id',
        'exchange_rate',
        'debit',
        'credit',
        'debit_usd',
        'credit_usd',
        'memo',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
        'debit_usd' => 'decimal:4',
        'credit_usd' => 'decimal:4',
    ];

    public function entry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account()
    {
        return $this->belongsTo(GlAccount::class, 'gl_account_id');
    }
}
