<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinishedGoodSpecification extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'customer_id',
        'source_key',
        'source_row',
        'source_customer_label',
        'source_size_raw',
        'source_unit',
        'source_layer_raw',
        'length',
        'width',
        'height',
        'weight',
        'fold_count',
        'flute_type',
        'ply',
        'color_count',
        'printing_type',
        'finish_type',
        'print_spec',
        'reel_cut',
        'pack_description',
        'pieces_per_carton',
        'carton_size',
        'unit_price',
        'historical_rate_note',
        'source_remark',
        'source_extra',
        'minimum_order_quantity',
    ];

    protected $casts = [
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'carton_size' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'minimum_order_quantity' => 'decimal:2',
        'source_row' => 'integer',
        'ply' => 'integer',
        'fold_count' => 'integer',
        'color_count' => 'integer',
        'pieces_per_carton' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(Account::class, 'customer_id');
    }

    public function scopeImportedClientCartons($query)
    {
        return $query->where('source_key', 'like', 'client-carton-order:%');
    }
}
