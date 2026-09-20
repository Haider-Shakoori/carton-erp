<?php
// app/Models/PurchaseItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $table = 'purchase_items';

    protected $fillable = [
        'purchase_id',
        'product_id',
        'qty',
        'purchase_currency_id',
        'unit_price',
        'total',
        'rate',
        'usd_unit_price',
        'batch_no',
        'usd_total',
        'remarks',
        'expense_per_item',
        'usd_expense_per_item',
        'cost_per_unit',
        'total_cost',
        'qty_available',
        'qty_sold',
        'qty_used',
        'qty_wasted',
        'qty_adjusted',
        'unit',
        'kg_per_roll',
        'total_weight_kg',
        'qty_kg',
        'qty_kg_sold',
        'qty_kg_used',
        'qty_kg_wasted',
        'qty_kg_adjusted',
        'qty_kg_available',
        'landed_cost_per_kg',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:4',
        'total' => 'decimal:2',
        'rate' => 'decimal:6',
        'usd_unit_price' => 'decimal:4',
        'usd_total' => 'decimal:2',
        'expense_per_item' => 'decimal:4',
        'usd_expense_per_item' => 'decimal:4',
        'cost_per_unit' => 'decimal:4',
        'total_cost' => 'decimal:2',
        'qty_available' => 'decimal:2',
        'qty_sold' => 'decimal:2',
        'qty_used' => 'decimal:2',
        'qty_wasted' => 'decimal:2',
        'qty_adjusted' => 'decimal:6',
        'kg_per_roll' => 'decimal:4',
        'total_weight_kg' => 'decimal:4',
        'qty_kg' => 'decimal:4',
        'qty_kg_sold' => 'decimal:4',
        'qty_kg_used' => 'decimal:4',
        'qty_kg_wasted' => 'decimal:4',
        'qty_kg_adjusted' => 'decimal:6',
        'qty_kg_available' => 'decimal:4',
        'landed_cost_per_kg' => 'decimal:6',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'purchase_item_id');
    }

    public function getAvailableQuantityAttribute()
    {
        return $this->qty - ($this->qty_sold ?? 0) - ($this->qty_used ?? 0) - ($this->qty_wasted ?? 0);
    }

    public function getWeightedAvgCostAttribute()
    {
        // Get all purchase items with available stock
        $items = $this->purchaseItems()
            ->where('qty_available', '>', 0)
            ->whereHas('purchase', function($q) {
                $q->where('status', 'arrived');
            })
            ->get();

        if ($items->isEmpty()) {
            return 0;
        }

        $totalCost = 0;
        $totalQty = 0;

        foreach ($items as $item) {
            // Use cost_per_unit if set, otherwise use usd_unit_price
            $costPerUnit = $item->cost_per_unit ?? $item->usd_unit_price ?? 0;
            $totalCost += $item->qty_available * $costPerUnit;
            $totalQty += $item->qty_available;
        }

        if ($totalQty <= 0) {
            return 0;
        }

        return $totalCost / $totalQty;
    }

    public function getAvailableBatches()
    {
        return $this->purchaseItems()
            ->where('qty_available', '>', 0)
            ->whereHas('purchase', function($q) {
                $q->where('status', 'arrived');
            })
            ->with(['purchase', 'purchase.currency'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($item) {
                // Use cost_per_unit if set, otherwise use usd_unit_price
                $costPerUnit = $item->cost_per_unit ?? $item->usd_unit_price ?? 0;

                return [
                    'id' => $item->id,
                    'batch_no' => $item->batch_no ?? 'N/A',
                    'purchase_no' => $item->purchase->purchase_no ?? 'N/A',
                    'purchase_date' => $item->purchase->purchase_date ?? $item->created_at,
                    'quantity' => $item->qty,
                    'qty_available' => $item->qty_available,
                    'cost_per_unit' => $costPerUnit,
                    'total_cost' => $costPerUnit * $item->qty_available,
                    'currency' => $item->purchase->currency->code ?? 'USD',
                ];
            });
    }
    public function getHasAvailableStockAttribute()
    {
        return $this->qty_available > 0;
    }

    /**
     * Whether this batch is a roll-based paper purchase requiring kg conversion.
     */
    public function isRollBatch(): bool
    {
        return strtolower((string) $this->unit) === 'roll'
            && (float) $this->kg_per_roll > 0;
    }

    /**
     * Total purchased weight (kg). For roll batches computed from kg_per_roll,
     * otherwise the explicitly provided total_weight_kg.
     */
    public function totalKg(): float
    {
        if ($this->isRollBatch()) {
            return (float) $this->qty * (float) $this->kg_per_roll;
        }

        return (float) ($this->total_weight_kg ?? 0);
    }

    /**
     * Remaining available weight (kg) for this batch.
     */
    public function availableKg(): float
    {
        return max((float) ($this->qty_kg_available ?? 0), 0);
    }

    /**
     * Effective landed USD cost per kg for this batch.
     * Source of truth is the stored landed_cost_per_kg, falling back to
     * (base + allocated expenses) / total kg when it has not been written yet.
     */
    public function landedCostPerKg(): float
    {
        $stored = (float) ($this->landed_cost_per_kg ?? 0);
        if ($stored > 0) {
            return $stored;
        }

        $totalKg = $this->totalKg();
        if ($totalKg <= 0) {
            return 0;
        }

        $baseCostUsd = (float) ($this->usd_total ?? 0);
        $allocatedExpenseUsd = (float) ($this->usd_expense_per_item ?? 0) * max((float) $this->qty, 0);
        $totalCostUsd = $baseCostUsd + $allocatedExpenseUsd;

        return $totalCostUsd / $totalKg;
    }

    /**
     * Inventory cost basis used by production/BOM costing.
     *
     * Roll batches are consumed in kilograms, so their authoritative landed
     * cost is USD/kg. Other batches are consumed in their native purchase unit.
     */
    public function inventoryCostBasisUnit(): string
    {
        return $this->isRollBatch()
            ? 'kg'
            : (strtolower((string) ($this->unit ?? '')) ?: 'unit');
    }

    /**
     * Remaining quantity in the same unit as inventoryCostBasisUnit().
     */
    public function availableInventoryQuantity(): float
    {
        return $this->isRollBatch()
            ? $this->availableKg()
            : max((float) ($this->qty_available ?? 0), 0);
    }

    /**
     * Landed USD cost in the inventory consumption unit.
     */
    public function landedCostPerInventoryUnitUsd(): float
    {
        if ($this->isRollBatch()) {
            return $this->landedCostPerKg();
        }

        $landed = (float) ($this->usd_cost_per_item ?? 0);
        if ($landed > 0) {
            return $landed;
        }

        $base = (float) ($this->cost_per_unit ?? 0);
        if ($base > 0) {
            return $base;
        }

        return max((float) ($this->usd_unit_price ?? 0), 0);
    }

    public function getUsagePercentageAttribute()
    {
        if ($this->qty <= 0) {
            return 0;
        }
        $used = ($this->qty_sold ?? 0) + ($this->qty_used ?? 0) + ($this->qty_wasted ?? 0);
        return ($used / $this->qty) * 100;
    }

    public function getIsFullyUsedAttribute()
    {
        return $this->qty_available <= 0;
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // BEFORE saving, calculate qty_available
        static::saving(function (PurchaseItem $purchaseItem) {
            // Calculate qty_available
            $purchaseItem->qty_available = $purchaseItem->qty
                - ($purchaseItem->qty_sold ?? 0)
                - ($purchaseItem->qty_used ?? 0)
                - ($purchaseItem->qty_wasted ?? 0)
                + ($purchaseItem->qty_adjusted ?? 0);

            // Roll -> kg conversion on save. Only roll-based paper batches
            // derive physical weight; all other units keep kg columns at 0.
            if ($purchaseItem->isRollBatch()) {
                $kgPerRoll = (float) $purchaseItem->kg_per_roll;
                $rollCount = (float) $purchaseItem->qty;
                $totalKg = $rollCount * $kgPerRoll;

                $purchaseItem->qty_kg = $totalKg;
                $purchaseItem->total_weight_kg = $totalKg;
                $purchaseItem->qty_kg_available = $totalKg
                    - (float) ($purchaseItem->qty_kg_sold ?? 0)
                    - (float) ($purchaseItem->qty_kg_used ?? 0)
                    - (float) ($purchaseItem->qty_kg_wasted ?? 0)
                    + (float) ($purchaseItem->qty_kg_adjusted ?? 0);

                // Recompute landed USD per kg from current cost fields.
                $purchaseItem->landed_cost_per_kg = $totalKg > 0
                    ? ((float) ($purchaseItem->usd_total ?? 0)
                       + ((float) ($purchaseItem->usd_expense_per_item ?? 0) * $rollCount)) / $totalKg
                    : 0;
            } else {
                $purchaseItem->qty_kg = 0;
                $purchaseItem->total_weight_kg = 0;
                $purchaseItem->qty_kg_sold = 0;
                $purchaseItem->qty_kg_used = 0;
                $purchaseItem->qty_kg_wasted = 0;
                $purchaseItem->qty_kg_adjusted = 0;
                $purchaseItem->qty_kg_available = 0;
                $purchaseItem->landed_cost_per_kg = 0;
            }

            // Log for debugging
            \Log::info('PurchaseItem::saving event fired', [
                'id' => $purchaseItem->id,
                'qty' => $purchaseItem->qty,
                'unit' => $purchaseItem->unit,
                'kg_per_roll' => $purchaseItem->kg_per_roll,
                'qty_kg' => $purchaseItem->qty_kg,
                'qty_kg_available' => $purchaseItem->qty_kg_available,
                'landed_cost_per_kg' => $purchaseItem->landed_cost_per_kg,
                'qty_available' => $purchaseItem->qty_available,
                'qty_used' => $purchaseItem->qty_used,
            ]);
        });
    }
}
