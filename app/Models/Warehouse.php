<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusinessUnit;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use BelongsToBusinessUnit;

    protected $fillable = [
        'business_unit_id',
        'code',
        'name',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function locations()
    {
        return $this->hasMany(WarehouseLocation::class);
    }
}
