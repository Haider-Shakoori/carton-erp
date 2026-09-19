<?php
// app/Models/PurchaseExpense.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseExpense extends Model
{
    protected $table = 'purchase_expenses';

    protected $fillable = [
        'purchase_id',
        'agent_id',
        'currency_id',
        'description',
        'amount',
        'usd_amount',
        'rate',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'usd_amount' => 'decimal:2',
        'rate' => 'decimal:6',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'agent_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
