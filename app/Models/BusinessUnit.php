<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessUnit extends Model
{
    protected $fillable = [
        'code',
        'name',
        'icon',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class, 'default_business_unit_id');
    }
}
