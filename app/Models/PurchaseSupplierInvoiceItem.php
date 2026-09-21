<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseSupplierInvoiceItem extends Model
{
    protected $fillable = [
        'purchase_supplier_invoice_id',
        'purchase_item_id',
        'product_id',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'unit_price' => 'decimal:6',
        'line_total' => 'decimal:4',
    ];

    public function invoice()
    {
        return $this->belongsTo(PurchaseSupplierInvoice::class, 'purchase_supplier_invoice_id');
    }
}
