<?php
// app/Models/Product.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    // Product Types
    const TYPE_RAW_MATERIAL = 'raw_material';
    const TYPE_FINISHED_GOOD = 'finished_good';
    const TYPE_EQUIPMENT = 'equipment';
    const TYPE_SERVICE = 'service';

    // Type labels
    const TYPE_LABELS = [
        self::TYPE_RAW_MATERIAL => 'Raw Material',
        self::TYPE_FINISHED_GOOD => 'Finished Good',
        self::TYPE_EQUIPMENT => 'Equipment',
        self::TYPE_SERVICE => 'Service',
    ];

    protected $fillable = [
        'name',
        'slug',
        'unit',
        'default_kg_per_roll',
        'min_stock_alert',
        'image',
        'category_id',
        'type',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_stock_alert' => 'integer',
        'default_kg_per_roll' => 'decimal:4',
    ];

    protected $appends = ['current_stock', 'is_low_stock', 'weighted_avg_cost'];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    /**
     * Request-scoped memoization for DB-backed computed attributes.
     * Cleared whenever the model is (re)saved so fresh data is never masked.
     *
     * @var array<string, bool>
     */
    protected $resolvedComputed = [];

    protected function computedValue($key, callable $resolver)
    {
        if (isset($this->resolvedComputed[$key])) {
            return $this->resolvedComputed[$key]['value'];
        }
        $value = $resolver();
        $this->resolvedComputed[$key] = ['value' => $value];
        return $value;
    }

    protected function clearComputedCache()
    {
        $this->resolvedComputed = [];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class, 'product_id');
    }

    public function bomItems()
    {
        return $this->hasMany(BOMItem::class, 'material_id');
    }

    public function boms()
    {
        return $this->hasMany(BOM::class, 'product_id');
    }

    public function finishedGoodSpecifications()
    {
        return $this->hasMany(FinishedGoodSpecification::class, 'product_id');
    }

    // ============================================================
    // STOCK & COST CALCULATIONS
    // ============================================================

    /**
     * Get current stock quantity.
     */
    public function getCurrentStockAttribute()
    {
        return $this->computedValue('current_stock', function () {
            return $this->purchaseItems()
                ->whereHas('purchase', function($q) {
                    $q->where('status', 'arrived');
                })
                ->sum('qty_available') ?? 0;
        });
    }

    /**
     * Get current physical stock weight (kg) for roll-based raw materials.
     */
    public function getCurrentStockKgAttribute()
    {
        return $this->computedValue('current_stock_kg', function () {
            return $this->purchaseItems()
                ->whereHas('purchase', function($q) {
                    $q->where('status', 'arrived');
                })
                ->sum('qty_kg_available') ?? 0;
        });
    }

    /**
     * Check if the material is tracked in rolls (any arrived roll batch exists).
     */
    public function getIsRollBasedAttribute()
    {
        return $this->computedValue('is_roll_based', function () {
            return $this->purchaseItems()
                ->where('unit', 'roll')
                ->whereHas('purchase', function($q) {
                    $q->where('status', 'arrived');
                })
                ->exists();
        });
    }

    /**
     * Check if product is low stock.
     */
    public function getIsLowStockAttribute()
    {
        if ($this->min_stock_alert <= 0) {
            return false;
        }
        return $this->current_stock <= $this->min_stock_alert;
    }

    /**
     * Get weighted average cost from all available batches.
     */
    public function getWeightedAvgCostAttribute()
    {
        return $this->computedValue('weighted_avg_cost', function () {
            $purchaseItems = $this->purchaseItems()
                ->where('qty_available', '>', 0)
                ->whereHas('purchase', function($q) {
                    $q->where('status', 'arrived');
                })
                ->get();

            if ($purchaseItems->isEmpty()) {
                return 0;
            }

            $totalCost = 0.0;
            $totalQty = 0.0;

            foreach ($purchaseItems as $item) {
                $basisQty = $item->availableInventoryQuantity();
                $costPerUnit = $item->landedCostPerInventoryUnitUsd();

                $totalCost += $basisQty * $costPerUnit;
                $totalQty += $basisQty;
            }

            if ($totalQty <= 0) {
                return 0;
            }

            return $totalCost / $totalQty;
        });
    }


    /**
     * Get all available batches with details.
     */
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
                $basisQty = $item->availableInventoryQuantity();
                $basisCost = $item->landedCostPerInventoryUnitUsd();

                return [
                    'id' => $item->id,
                    'batch_no' => $item->batch_no ?? 'N/A',
                    'purchase_no' => $item->purchase->purchase_no ?? 'N/A',
                    'purchase_date' => $item->purchase->purchase_date ?? $item->created_at,
                    'quantity' => $item->qty,
                    'qty_available' => $item->qty_available,
                    'qty_available_basis' => $basisQty,
                    'cost_basis_unit' => $item->inventoryCostBasisUnit(),
                    'cost_per_unit' => $basisCost,
                    'total_cost' => $basisCost * $basisQty,
                    'currency' => 'USD',
                ];
            });
    }

    /**
     * Get batch breakdown for display.
     */
    public function getBatchBreakdownAttribute()
    {
        return $this->computedValue('batch_breakdown', function () {
            $batches = $this->getAvailableBatches();
            $totalQty = collect($batches)->sum('qty_available');
            $totalCost = collect($batches)->sum('total_cost');

            return [
                'batches' => $batches,
                'total_qty' => $totalQty,
                'total_cost' => $totalCost,
                'weighted_avg' => $totalQty > 0 ? $totalCost / $totalQty : 0,
            ];
        });
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeRawMaterials($query)
    {
        return $query->where('type', self::TYPE_RAW_MATERIAL);
    }

    public function scopeFinishedGoods($query)
    {
        return $query->where('type', self::TYPE_FINISHED_GOOD);
    }

    /**
     * Limit finished-good choices to the active production business while
     * keeping the underlying Product master shared.
     */
    public function scopeForActiveBusiness(Builder $query): Builder
    {
        $activeBusinessUnitId = (int) session(
            \App\Support\Business\BusinessUnitContext::SESSION_KEY,
            0
        );

        if ($activeBusinessUnitId <= 0) {
            return $query;
        }

        $businessCode = BusinessUnit::query()
            ->whereKey($activeBusinessUnitId)
            ->value('code');

        if ($businessCode === 'syrup_pack') {
            return $query->where(function (Builder $productQuery): void {
                $productQuery
                    ->whereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('name', 'Syrup Boxes'))
                    ->orWhere('name', 'like', '%syrup%');
            });
        }

        if ($businessCode === '3d_carton') {
            return $query->where(function (Builder $productQuery): void {
                $productQuery
                    ->whereDoesntHave('category', fn (Builder $categoryQuery) => $categoryQuery->where('name', 'Syrup Boxes'))
                    ->where('name', 'not like', '%syrup%');
            });
        }

        return $query;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    public function isRawMaterial()
    {
        return $this->type === self::TYPE_RAW_MATERIAL;
    }

    public function isFinishedGood()
    {
        return $this->type === self::TYPE_FINISHED_GOOD;
    }

    public function intendedBusinessUnitCode(): ?string
    {
        if (! $this->isFinishedGood()) {
            return null;
        }

        $this->loadMissing('category');

        if (
            $this->category?->name === 'Syrup Boxes'
            || str_contains(strtolower((string) $this->name), 'syrup')
        ) {
            return 'syrup_pack';
        }

        return '3d_carton';
    }

    public function getTypeLabelAttribute()
    {
        $labels = [
            self::TYPE_RAW_MATERIAL => 'Raw Material',
            self::TYPE_FINISHED_GOOD => 'Finished Good',
            self::TYPE_EQUIPMENT => 'Equipment',
            self::TYPE_SERVICE => 'Service',
        ];
        return $labels[$this->type] ?? 'Unknown';
    }

    // ============================================================
    // BOOT METHOD
    // ============================================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            $product->slug = Str::slug($product->name . '-' . uniqid());
        });

        static::updating(function ($product) {
            if ($product->isDirty('name')) {
                $product->slug = Str::slug($product->name . '-' . uniqid());
            }
            $product->clearComputedCache();
        });

        static::saved(function ($product) {
            $product->clearComputedCache();
        });
    }
}
