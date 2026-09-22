<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'company_name',
        'logo',
        'contact',
        'email',
        'address',
        'note_en',
        'note_fa',
        'note_ps',
        'default_language',
        'currency',
        'separate_business_units_enabled',
        'default_business_unit_id',
        'production_approval_required',
    ];

    protected $casts = [
        'separate_business_units_enabled' => 'boolean',
        'default_business_unit_id' => 'integer',
        'production_approval_required' => 'boolean',
    ];

    public function defaultBusinessUnit()
    {
        return $this->belongsTo(BusinessUnit::class, 'default_business_unit_id');
    }
}
