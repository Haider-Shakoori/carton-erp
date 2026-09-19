<?php
// app/Models/LeaveType.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = [
        'name',
        'days_allowed',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
