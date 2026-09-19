<?php
// app/Models/SaleItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sale_id',
        'product_id',
        'bom_id',
        'purchase_item_id',
        'sale_currency_id',
        'qty',
        'cost_per_unit_usd',
        'total_cost_usd',
        'unit_price',
        'total',
        'base_price',
        'discount_percentage',
        'discount_amount',
        'final_price',
        'price_adjustment_type',
        'price_adjustment_note',
        'original_unit_price',
        'original_total',
        'original_profit_usd',
        'discount',
        'tax',
        'usd_unit_price',
        'usd_total',
        'usd_discount',
        'usd_tax',
        'rate',
        'profit_usd',
        'profit_afn',
        'profit_percentage',
        'remarks',
        'manual_bom_snapshot',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'cost_per_unit_usd' => 'decimal:4',
        'total_cost_usd' => 'decimal:2',
        'unit_price' => 'decimal:4',
        'total' => 'decimal:2',
        'base_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'original_unit_price' => 'decimal:2',
        'original_total' => 'decimal:2',
        'original_profit_usd' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'usd_unit_price' => 'decimal:4',
        'usd_total' => 'decimal:2',
        'usd_discount' => 'decimal:2',
        'usd_tax' => 'decimal:2',
        'rate' => 'decimal:6',
        'profit_usd' => 'decimal:2',
        'profit_afn' => 'decimal:2',
        'profit_percentage' => 'decimal:2',
        'price_adjustment_type' => 'string',
        'manual_bom_snapshot' => 'array',
    ];

    protected $appends = ['profit_margin', 'is_price_adjusted'];

    // ─── RELATIONSHIPS ───

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function bom()
    {
        return $this->belongsTo(BOM::class);
    }

    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'sale_currency_id');
    }

    // ─── ACCESSORS ───

    public function getIsPriceAdjustedAttribute()
    {
        return $this->price_adjustment_type !== 'none';
    }

    public function getProfitMarginAttribute()
    {
        if ($this->total_cost_usd <= 0) {
            return 0;
        }
        return ($this->profit_usd / $this->total_cost_usd) * 100;
    }

    public function getOriginalProfitAttribute()
    {
        if ($this->original_profit_usd) {
            return $this->original_profit_usd;
        }
        return $this->profit_usd;
    }

    public function getDiscountDisplayAttribute()
    {
        if ($this->price_adjustment_type === 'discount_percent') {
            return $this->discount_percentage . '%';
        } elseif ($this->price_adjustment_type === 'discount_fixed') {
            return '$' . number_format($this->discount_amount, 2);
        } elseif ($this->price_adjustment_type === 'manual') {
            return 'Manual Price';
        }
        return 'No Discount';
    }

    // ─── HELPER METHODS ───

    /**
     * Calculate profit/loss in USD for this item
     */
    public function calculateProfitUsd()
    {
        $rate = max((float) ($this->rate ?? 1), 0.000001);
        $totalRevenue = (float) ($this->usd_total ?? 0);
        if ($totalRevenue <= 0) {
            $isUsd = optional($this->saleCurrency)->code === 'USD';
            $totalRevenue = $isUsd ? (float) ($this->total ?? 0) : (float) ($this->total ?? 0) / $rate;
        }
        $cost = $this->total_cost_usd ?? 0;
        $this->profit_usd = $totalRevenue - $cost;
        $this->profit_afn = $this->profit_usd * $rate;
        $this->profit_percentage = $totalRevenue > 0 ? ($this->profit_usd / $totalRevenue) * 100 : 0;
        return $this->profit_usd;
    }

    /**
     * Apply discount to the item
     */
    public function applyDiscount($discountType, $value)
    {
        $this->price_adjustment_type = $discountType;
        $this->original_unit_price = $this->unit_price;
        $this->original_total = $this->total;
        $this->original_profit_usd = $this->profit_usd;

        switch ($discountType) {
            case 'discount_percent':
                $this->discount_percentage = $value;
                $this->discount_amount = ($this->unit_price * $value) / 100;
                $this->final_price = $this->unit_price - $this->discount_amount;
                break;
            case 'discount_fixed':
                $this->discount_amount = $value;
                $this->discount_percentage = ($value / $this->unit_price) * 100;
                $this->final_price = $this->unit_price - $value;
                break;
            case 'manual':
                $this->final_price = $value;
                $this->discount_amount = $this->unit_price - $value;
                $this->discount_percentage = ($this->discount_amount / $this->unit_price) * 100;
                break;
            default:
                $this->final_price = $this->unit_price;
                $this->discount_amount = 0;
                $this->discount_percentage = 0;
                $this->price_adjustment_type = 'none';
        }

        // Update total
        $this->total = $this->final_price * $this->qty;
        $this->unit_price = $this->final_price;

        // Recalculate profit
        $this->calculateProfitUsd();

        return $this;
    }

    /**
     * Reset to base price
     */
    public function resetToBasePrice()
    {
        $this->price_adjustment_type = 'none';
        $this->discount_percentage = 0;
        $this->discount_amount = 0;
        $this->final_price = $this->base_price ?: $this->original_unit_price ?: $this->unit_price;
        $this->unit_price = $this->final_price;
        $this->total = $this->unit_price * $this->qty;
        $this->discount_amount = 0;
        $this->discount_percentage = 0;
        $this->calculateProfitUsd();

        return $this;
    }
    public function calculateCostFromBOM()
    {
        if (!$this->bom_id) {
            return $this;
        }

        $bom = BOM::with('items.material')->find($this->bom_id);
        if (!$bom) {
            return $this;
        }

        $exchangeRate = $this->rate ?? 85;
        $qty = $this->qty ?? 1;
        $totalCostUsd = 0;

        // Calculate material costs from BOM items
        foreach ($bom->items as $item) {
            $requiredQty = (float) $item->quantity * (1 + ((float) ($item->wastage_percentage ?? 0) / 100));
            $costPerUnitUsd = (float) ($item->cost_per_unit_usd ?? 0);

            // If cost is 0, try to get from inventory
            if ($costPerUnitUsd <= 0) {
                $inventory = $this->getLatestInventoryCost((int) $item->material_id, $exchangeRate);
                $costPerUnitUsd = $inventory['cost_usd'] ?? 0;
            }

            $totalCostUsd += $requiredQty * $costPerUnitUsd * $qty;
        }

        // Add labor and overhead
        $laborCostUsd = ((float) ($bom->labor_cost_per_unit ?? 0)) / $exchangeRate * $qty;
        $overheadCostUsd = ((float) ($bom->overhead_cost_per_unit ?? 0)) / $exchangeRate * $qty;
        $totalCostUsd += $laborCostUsd + $overheadCostUsd;

        // Update the sale item
        $this->total_cost_usd = $totalCostUsd;
        $this->cost_per_unit_usd = $qty > 0 ? $totalCostUsd / $qty : 0;

        // Recalculate profit
        $this->calculateProfitUsd();

        return $this;
    }

    private function getLatestInventoryCost(int $materialId, float $exchangeRate = 85): array
    {
        $purchaseItem = PurchaseItem::query()
            ->where('product_id', $materialId)
            ->where('qty_available', '>', 0)
            ->whereHas('purchase', function ($query) {
                $query->where('status', 'arrived');
            })
            ->latest('id')
            ->first();

        if (!$purchaseItem) {
            return [
                'found' => false,
                'cost_usd' => 0.0,
                'cost_afn' => 0.0,
            ];
        }

        $costUsd = (float) ($purchaseItem->usd_cost_per_item ?? 0);
        if ($costUsd <= 0 && (float) ($purchaseItem->qty ?? 0) > 0) {
            $costUsd = (float) ($purchaseItem->usd_total ?? 0) / (float) $purchaseItem->qty;
        }

        return [
            'found' => $costUsd > 0,
            'cost_usd' => $costUsd,
            'cost_afn' => $costUsd * $exchangeRate,
        ];
    }
}
