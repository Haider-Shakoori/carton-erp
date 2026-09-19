<?php
// app/Models/Designation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    protected $fillable = [
        'name',
        'description',
        'department_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
