<?php
// app/Models/SaleReturn.php

namespace App\Models;

use App\Models\Concerns\BelongsToBusinessUnit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class SaleReturn extends Model
{
    use BelongsToBusinessUnit;
    protected $fillable = [
        'sale_id',
        'return_no',
        'customer_id',
        'currency_id',
        'return_date',
        'processed_at',
        'status',
        'exchange_rate',
        'subtotal',
        'discount_total',
        'grand_total',
        'usd_subtotal',
        'usd_discount_total',
        'usd_grand_total',
        'reason',
        'notes',
        'restocking_fee',
        'usd_restocking_fee',
        'refund_amount',
        'usd_refund_amount',
    ];

    protected $casts = [
        'return_date' => 'date',
        'processed_at' => 'datetime',
        'exchange_rate' => 'decimal:6',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'usd_subtotal' => 'decimal:2',
        'usd_discount_total' => 'decimal:2',
        'usd_grand_total' => 'decimal:2',
        'restocking_fee' => 'decimal:2',
        'usd_restocking_fee' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'usd_refund_amount' => 'decimal:2',
    ];

    /**
     * Generate a unique return number
     */
    public static function generateNumber(): string
    {
        $year = date('Y');
        $month = date('m');

        $lastReturn = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastReturn) {
            $lastNumber = intval(substr($lastReturn->return_no, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "SR-{$year}{$month}-{$newNumber}";
    }

    /**
     * Recalculate totals
     */
    public function recalculateTotals(): void
    {
        $items = $this->items;

        $this->subtotal = $items->sum('total');
        $this->discount_total = $items->sum('discount');
        $this->grand_total = $this->subtotal - $this->discount_total;

        $this->usd_subtotal = $items->sum('usd_total');
        $this->usd_discount_total = $items->sum('usd_discount');
        $this->usd_grand_total = $this->usd_subtotal - $this->usd_discount_total;

        // Calculate refund amount (grand total minus restocking fee)
        $this->refund_amount = $this->grand_total - $this->restocking_fee;
        $this->usd_refund_amount = $this->usd_grand_total - $this->usd_restocking_fee;

        $this->save();
    }

    /**
     * Get the sale that this return belongs to
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the customer for this return
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'customer_id');
    }

    /**
     * Get the currency for this return
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the items for this return
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    /**
     * Process the return (restock items and create reversal transactions)
     */
    public function process(): bool
    {
        if ($this->status !== 'approved') {
            throw new \Exception('Only approved returns can be processed.');
        }

        DB::beginTransaction();

        try {