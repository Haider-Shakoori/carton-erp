<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseSupplierInvoice extends Model
{
    protected $fillable = [
        'purchase_id',
        'invoice_no',
        'invoice_date',
        'currency_id',
        'exchange_rate',
        'subtotal',
        'expense_total',
        'grand_total',
        'usd_grand_total',
        'match_status',
        'match_variance',
        'created_by',
        'matched_by',
        'matched_at',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'subtotal' => 'decimal:4',
        'expense_total' => 'decimal:4',
        'grand_total' => 'decimal:4',
        'usd_grand_total' => 'decimal:4',
        'match_variance' => 'decimal:4',
        'matched_at' => 'datetime',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseSupplierInvoiceItem::class);
    }
}
