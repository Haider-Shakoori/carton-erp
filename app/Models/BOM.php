<?php
// app/Models/BOM.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BOM extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'boms';

    protected $fillable = [
        'name',
        'code',
        'product_id',
        'version',
        'status',
        'description',
        'is_active',
        // Formula Defaults & Pricing
        'work_percentage',
        'profit_margin_percentage',
        'exchange_rate',
        'exchange_rate_updated_at',
        // Calculated Totals
        'total_material_cost_usd',
        'total_material_cost_afn',
        'total_cost_afn',
        'selling_price_afn',
        'profit_afn',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'work_percentage' => 'decimal:2',
        'profit_margin_percentage' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'exchange_rate_updated_at' => 'datetime',
        'is_active' => 'boolean',
        'total_material_cost_usd' => 'decimal:4',
        'total_material_cost_afn' => 'decimal:4',
        'total_cost_afn' => 'decimal:4',
        'selling_price_afn' => 'decimal:4',
        'profit_afn' => 'decimal:4',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bom) {
            if (empty($bom->code)) {
                $bom->code = 'BOM-' . strtoupper(Str::random(8));
            }
            if (empty($bom->version)) {
                $bom->version = '1.0';
            }
            if (empty($bom->work_percentage)) {
                $bom->work_percentage = 40;
            }
            if (empty($bom->profit_margin_percentage)) {
                $bom->profit_margin_percentage = 0;
            }
            if (empty($bom->exchange_rate)) {
                $bom->exchange_rate = $bom->getDefaultExchangeRate();
                $bom->exchange_rate_updated_at = now();
            }
        });

        static::updating(function ($bom) {
            if ($bom->isDirty('exchange_rate')) {
                $bom->exchange_rate_updated_at = now();
            }
        });

        // Auto-calculate on save
        static::saving(function ($bom) {
            $bom->calculateTotals();
        });
    }

    // ─── RELATIONSHIPS ───

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function items()
    {
        return $this->hasMany(BOMItem::class, 'bom_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function productionOrders()
    {
        return $this->hasMany(ProductionOrder::class, 'bom_id');
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'bom_id');
    }

    // ─── SCOPES ───

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('is_active', true);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    // ─── EXCHANGE RATE METHODS ───

    public function getDefaultExchangeRate()
    {
        $afn = Currency::where('code', 'AFN')->first();
        $usd = Currency::where('code', 'USD')->first();

        if ($afn && $usd && $usd->exchange_rate > 0) {
            return $afn->exchange_rate / $usd->exchange_rate;
        }

        $setting = Setting::first();
        return $setting->default_exchange_rate ?? 85;
    }

    public function getUSDtoAFNRate()
    {
        if ($this->exchange_rate && $this->exchange_rate > 0) {
            return $this->exchange_rate;
        }
        return $this->getDefaultExchangeRate();
    }

    public function getExchangeRateSourceAttribute()
    {
        if ($this->exchange_rate && $this->exchange_rate > 0) {
            return 'Manual';
        }
        return 'Auto (Default)';
    }

    // ─── CALCULATION METHODS ───

    /**
     * Calculate totals for the BOM
     */
    public function calculateTotals()
    {
        $exchangeRate = $this->getUSDtoAFNRate();
        $workPercentage = ($this->work_percentage ?? 40) / 100;
        $profitMargin = ($this->profit_margin_percentage ?? 0) / 100;

        // Sum material costs from all items
        $materialCostUsd = 0;
        $materialCostAfn = 0;

        foreach ($this->items as $item) {
            $materialCostUsd += $item->total_cost_usd ?? 0;
            $materialCostAfn += $item->total_cost_afn ?? 0;
        }

        // Calculate work cost
        $workCostUsd = $materialCostUsd * $workPercentage;
        $workCostAfn = $materialCostAfn * $workPercentage;

        // Calculate total cost
        $totalCostUsd = $materialCostUsd + $workCostUsd;
        $totalCostAfn = $materialCostAfn + $workCostAfn;

        // Calculate selling price with profit margin
        $sellingPriceUsd = $totalCostUsd * (1 + $profitMargin);
        $sellingPriceAfn = $totalCostAfn * (1 + $profitMargin);

        // Calculate profit
        $profitUsd = $sellingPriceUsd - $totalCostUsd;
        $profitAfn = $sellingPriceAfn - $totalCostAfn;

        // Store calculated values
        $this->total_material_cost_usd = $materialCostUsd;
        $this->total_material_cost_afn = $materialCostAfn;
        $this->total_cost_afn = $totalCostAfn;
        $this->selling_price_afn = $sellingPriceAfn;
        $this->profit_afn = $profitAfn;

        return $this;
    }

    public function recalculateAfnValues()
    {
        $exchangeRate = $this->getUSDtoAFNRate();
        $materialCostUsd = $this->total_material_cost_usd ?? 0;
        $workPercentage = ($this->work_percentage ?? 40) / 100;
        $profitMargin = ($this->profit_margin_percentage ?? 0) / 100;

        $workCostUsd = $materialCostUsd * $workPercentage;
        $totalCostUsd = $materialCostUsd + $workCostUsd;
        $sellingPriceUsd = $totalCostUsd * (1 + $profitMargin);
        $profitUsd = $sellingPriceUsd - $totalCostUsd;

        $this->total_material_cost_afn = $materialCostUsd * $exchangeRate;
        $this->total_cost_afn = $totalCostUsd * $exchangeRate;
        $this->selling_price_afn = $sellingPriceUsd * $exchangeRate;
        $this->profit_afn = $profitUsd * $exchangeRate;

        $this->saveQuietly();

        return $this;
    }

    /**
     * Force recalculate and save the BOM
     */
    public function recalculateAndSave()
    {
        $this->calculateTotals();
        $this->saveQuietly();
        return $this;
    }

    // ─── GETTERS ───

    public function getStatusLabelAttribute()
    {
        $labels = [
            'draft' => 'Draft',
            'active' => 'Active',
            'archived' => 'Archived',
        ];
        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'draft' => 'warning',
            'active' => 'success',
            'archived' => 'secondary',
        ];
        return $badges[$this->status] ?? 'secondary';
    }

    public function getFormattedNetRateAttribute()
    {
        $netRate = $this->total_cost_afn / max(1, $this->items->count());
        return number_format($netRate, 8);
    }

    /**
     * Get cost breakdown
     */
    public function getCostBreakdownAttribute()
    {
        $exchangeRate = $this->getUSDtoAFNRate();
        $materialCostUsd = $this->total_material_cost_usd ?? 0;
        $workPercentage = ($this->work_percentage ?? 40) / 100;
        $profitMargin = ($this->profit_margin_percentage ?? 0) / 100;

        $workCostUsd = $materialCostUsd * $workPercentage;
        $totalCostUsd = $materialCostUsd + $workCostUsd;
        $sellingPriceUsd = $totalCostUsd * (1 + $profitMargin);
        $profitUsd = $sellingPriceUsd - $totalCostUsd;

        return [
            'material_cost_usd' => $materialCostUsd,
            'material_cost_afn' => $materialCostUsd * $exchangeRate,
            'work_cost_usd' => $workCostUsd,
            'work_cost_afn' => $workCostUsd * $exchangeRate,
            'total_cost_usd' => $totalCostUsd,
            'total_cost_afn' => $totalCostUsd * $exchangeRate,
            'selling_price_usd' => $sellingPriceUsd,
            'selling_price_afn' => $sellingPriceUsd * $exchangeRate,
            'profit_usd' => $profitUsd,
            'profit_afn' => $profitUsd * $exchangeRate,
            'profit_margin_percentage' => $this->profit_margin_percentage ?? 0,
            'work_percentage' => $this->work_percentage ?? 40,
            'exchange_rate' => $exchangeRate,
            'exchange_rate_source' => $this->exchange_rate_source,
        ];
    }

    // app/Models/BOM.php - Add this method

    /**
     * Get formula breakdown for display
     */
    public function getFormulaBreakdown()
    {
        // ─── GET DIMENSIONS FROM BOM ITEMS (PRIMARY SOURCE) ───
        $firstItem = $this->items->first();
        $length = $firstItem ? ($firstItem->length_inch ?? 0) : 0;
        $width = $firstItem ? ($firstItem->width_inch ?? 0) : 0;
        $height = $firstItem ? ($firstItem->height_inch ?? 0) : 0;
        $paperGsm = $firstItem ? ($firstItem->paper_gsm ?? 0) : 0;
        $perGramRate = $firstItem ? ($firstItem->per_gram_rate ?? 0) : 0;
        $multiplicationLayer = $firstItem ? ($firstItem->multiplication_layer ?? 1) : 1;
        $constant = $firstItem ? ($firstItem->formula_constant ?? 1550000) : 1550000;
        $printCost = $firstItem ? ($firstItem->print ?? 0) : 0;
        $workPercentage = $firstItem ? ($firstItem->work_percentage ?? 40) : ($this->work_percentage ?? 40);

        $breakdown = [];

        if ($this->formula_type === 'cut_roll') {
            // ─── CUT/ROLL FORMULA ───
            $cutLength = $this->cut_length_inch ?? 0;
            $cutWidth = $this->cut_width_inch ?? 0;
            $grh = $this->grh ?? 0;
            $ply = $this->ply ?? 1;
            $multiplicationMethod = $this->multiplication_method ?? 'multiply';

            // Calculate
            if ($multiplicationMethod === 'divide') {
                $multiplicationValue = ($cutLength * $cutWidth * $constant) / 1000;
            } else {
                $multiplicationValue = $cutLength * $cutWidth * $constant;
            }

            $paperRate = ($multiplicationValue * $perGramRate * $grh * $ply) / $constant;
            $paperRateByLayers = $multiplicationLayer * $paperRate;
            $workAmount = $paperRateByLayers * ((float) $workPercentage / 100);
            $netRate = $printCost + $paperRateByLayers + $workAmount;

            $breakdown = [
                'step1' => [
                    'label' => 'Step 1: Multiplication Value',
                    'formula' => $multiplicationMethod === 'divide'
                        ? '({cut_length} × {cut_width} × {constant}) / 1000'
                        : '{cut_length} × {cut_width} × {constant}',
                    'values' => $multiplicationMethod === 'divide'
                        ? number_format($cutLength, 2) . ' × ' . number_format($cutWidth, 2) . ' × ' . number_format($constant) . ' / 1000'
                        : number_format($cutLength, 2) . ' × ' . number_format($cutWidth, 2) . ' × ' . number_format($constant),
                    'result' => number_format($multiplicationValue, 2)
                ],
                'step2' => [
                    'label' => 'Step 2: Paper Rate',
                    'formula' => '({multiplication} × {per_gram_rate} × {grh} × {ply}) / {constant}',
                    'values' => number_format($multiplicationValue, 2) . ' × ' . number_format($perGramRate, 2) . ' × ' . $grh . ' × ' . $ply . ' / ' . number_format($constant),
                    'result' => number_format($paperRate, 8)
                ],
                'step3' => [
                    'label' => 'Step 3: Paper Rate × Layers',
                    'formula' => '{paper_rate} × {multiplication_layer}',
                    'values' => number_format($paperRate, 8) . ' × ' . $multiplicationLayer,
                    'result' => number_format($paperRateByLayers, 8)
                ],
                'step4' => [
                    'label' => 'Step 4: Work Amount',
                    'formula' => '({paper_rate_by_layers} × {work_percentage}) / 100',
                    'values' => number_format($paperRateByLayers, 8) . ' × ' . $workPercentage . ' / 100',
                    'result' => number_format($workAmount, 8)
                ],
                'step5' => [
                    'label' => 'Step 5: Net Rate',
                    'formula' => '{paper_rate_by_layers} + {work_amount}',
                    'values' => number_format($paperRateByLayers, 8) . ' + ' . number_format($workAmount, 8),
                    'result' => number_format($netRate, 8)
                ]
            ];
        } else {
            // ─── 3D CARTON FORMULA ───
            $reelLength = (($length + $width) * 2) + 4;
            $reelHeight = $width + $height + 1;
            $divisionValue = $reelLength * $reelHeight * $paperGsm * $perGramRate;
            $paperRate = $divisionValue / $constant;
            $paperRateByLayers = $multiplicationLayer * $paperRate;
            $workAmount = $paperRateByLayers * ((float) $workPercentage / 100);
            $netRate = $printCost + $paperRateByLayers + $workAmount;

            $breakdown = [
                'step1' => [
                    'label' => 'Step 1: Reel Length & Height',
                    'formula' => 'Reel Length = ((L + W) × 2) + 4, Reel Height = W + H + 1',
                    'values' => "Reel Length = (({$length} + {$width}) × 2) + 4 = {$reelLength}, Reel Height = {$width} + {$height} + 1 = {$reelHeight}",
                    'result' => number_format($reelLength, 2) . ' × ' . number_format($reelHeight, 2) . ' inches'
                ],
                'step2' => [
                    'label' => 'Step 2: Division Value',
                    'formula' => 'Reel Length × Reel Height × GSM × Per Gram Rate',
                    'values' => number_format($reelLength, 2) . ' × ' . number_format($reelHeight, 2) . ' × ' . $paperGsm . ' × ' . number_format($perGramRate, 2),
                    'result' => number_format($divisionValue, 2)
                ],
                'step3' => [
                    'label' => 'Step 3: Paper Rate',
                    'formula' => 'Division Value ÷ Formula Constant',
                    'values' => number_format($divisionValue, 2) . ' ÷ ' . number_format($constant),
                    'result' => number_format($paperRate, 8)
                ],
                'step4' => [
                    'label' => 'Step 4: Paper Rate × Layers',
                    'formula' => 'Multiplication Layer × Paper Rate',
                    'values' => $multiplicationLayer . ' × ' . number_format($paperRate, 8),
                    'result' => number_format($paperRateByLayers, 8)
                ],
                'step5' => [
                    'label' => 'Step 5: Work Amount',
                    'formula' => 'Paper Rate × Layers × (Work Percentage / 100)',
                    'values' => number_format($paperRateByLayers, 8) . ' × ' . $workPercentage . ' / 100',
                    'result' => number_format($workAmount, 8)
                ],
                'step6' => [
                    'label' => 'Step 6: Row Net Rate',
                    'formula' => 'Print Cost + Paper Rate × Layers + Work Amount',
                    'values' => number_format($printCost, 2) . ' + ' . number_format($paperRateByLayers, 8) . ' + ' . number_format($workAmount, 8),
                    'result' => number_format($netRate, 8)
                ]
            ];
        }

        return $breakdown;
    }
}
