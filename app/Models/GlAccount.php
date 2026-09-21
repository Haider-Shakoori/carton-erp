<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlAccount extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'normal_balance',
        'system_key',
        'parent_id',
        'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function lines()
    {
        return $this->hasMany(JournalLine::class);
    }
}
