<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GatePassItem extends Model
{
    protected $fillable = [
        'gate_pass_id',
        'sale_item_id',
        'item_name',
        'description',
        'quantity',
        'unit',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function gatePass()
    {
        return $this->belongsTo(GatePass::class);
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }
}
