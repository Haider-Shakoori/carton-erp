<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingPeriod extends Model
{
    protected $fillable = [
        'name',
        'starts_on',
        'ends_on',
        'status',
        'closed_by',
        'closed_at',
        'close_reason',
        'reopened_by',
        'reopened_at',
        'reopen_reason',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
    ];

    public function entries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
}
