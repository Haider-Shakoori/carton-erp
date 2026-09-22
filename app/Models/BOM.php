<?php
// app/Models/BOM.php

namespace App\Models;

use App\Models\Concerns\BelongsToBusinessUnit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BOM extends Model
{
    use HasFactory, SoftDeletes, BelongsToBusinessUnit;

    protected $table = 'boms';

    protected $fillable = [
        'name',
        'code',
        'product_id',
        'version',
        'revision_sequence',
        'status',
        'effective_from',
        'effective_to',
        'supersedes_bom_id',
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
        'updated_by',
        'approved_by',
        'approved_at',
        'locked_by',
        'locked_at',
    ];

    protected $casts = [
        'work_percentage' => 'decimal:2',
        'profit_margin_percentage' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'exchange_rate_updated_at' => 'datetime',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'approved_at' => 'datetime',
        'locked_at' => 'datetime',
        'revision_sequence' => 'integer',
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

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function supersedes()
    {
        return $this->belongsTo(self::class, 'supersedes_bom_id');
    }

    public function revisions()
    {
        return $this->hasMany(self::class, 'supersedes_bom_id');
    }

    public function getIsLockedAttribute(): bool
    {
        return $this->locked_at !== null;
    }

    public function getIsEffectiveAttribute(): bool
    {
        $today = now()->toDateString();

        return $this->status === 'active'
            && $this->is_active
            && (! $this->effective_from || $this->effective_from->toDateString() <= $today)
            && (! $this->effective_to || $this->effective_to->toDateString() >= $today);
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
        $summary = app(\App\Services\BOMCostingService::class)->summarize($this);

        $this->total_material_cost_usd = $summary['physical_material_cost_usd'];
        $this->total_material_cost_afn = $summary['physical_material_cost_afn'];
        $this->total_cost_afn = $summary['physical_production_cost_afn'];
        $this->selling_price_afn = $summary['selling_price_afn'];
        $this->profit_afn = $summary['expected_profit_afn'];

        return $this;
    }

    public function recalculateAfnValues()
    {
        $this->calculateTotals();
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
        return number_format((float) ($this->selling_price_afn ?? 0), 8);
    }

    /**
     * Get cost breakdown
     */
    public function getCostBreakdownAttribute()
    {
        $summary = app(\App\Services\BOMCostingService::class)->summarize($this);
        $exchangeRate = max((float) $summary['exchange_rate'], 0.000001);

        return [
            'material_cost_usd' => $summary['physical_material_cost_usd'],
            'material_cost_afn' => $summary['physical_material_cost_afn'],
            'base_material_cost_usd' => $summary['base_material_cost_usd'],
            'base_material_cost_afn' => $summary['base_material_cost_afn'],
            'wastage_cost_usd' => $summary['wastage_cost_usd'],
            'wastage_cost_afn' => $summary['wastage_cost_afn'],
            // Backward-compatible key: this is the standard commercial
            // work/profit component, not a production labour expense.
            'work_cost_usd' => $summary['standard_work_profit_afn'] / $exchangeRate,
            'work_cost_afn' => $summary['standard_work_profit_afn'],
            'print_cost_afn' => $summary['print_cost_afn'],
            'commercial_base_afn' => $summary['commercial_base_afn'],
            'additional_markup_afn' => $summary['additional_markup_afn'],
            'total_cost_usd' => $summary['physical_production_cost_usd'],
            'total_cost_afn' => $summary['physical_production_cost_afn'],
            'selling_price_usd' => $summary['selling_price_usd'],
            'selling_price_afn' => $summary['selling_price_afn'],
            'profit_usd' => $summary['expected_profit_usd'],
            'profit_afn' => $summary['expected_profit_afn'],
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
