<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionOrderControlEvent extends Model
{
    protected $fillable = [
        'production_order_id',
        'event',
        'from_state',
        'to_state',
        'reason',
        'metadata',
        'actor_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
