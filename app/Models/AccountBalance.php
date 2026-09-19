<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountBalance extends Model
{
    protected $fillable = [
        'account_id', 'currency_id', 'credit', 'debit', 'balance',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
