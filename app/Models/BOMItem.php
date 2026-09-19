<?php
// app/Models/BOMItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BOMItem extends Model
{
    use HasFactory;

    protected $table = 'bom_items';

    protected $fillable = [
        'bom_id',
        'material_id',
        'quantity',
        'unit',
        'wastage_percentage',
        'cost_per_unit_usd',
        'cost_per_unit_afn',
        'total_cost_usd',
        'total_cost_afn',
        'purchase_currency',
        'purchase_currency_id',
        'roll_weight',
        'notes',
        'sort_order',
        // ─── Formula Configuration ───
        'formula_type',
        'formula_data',
        'is_formula_based',
        // ─── 3D Carton Formula Fields ───
        'length_inch',
        'width_inch',
        'height_inch',
        'reel_length_inch',
        'reel_height_inch',
        'paper_gsm',
        'layers',
        'division_factor',
        // ─── Cut/Roll Formula Fields ───
        'cut_length_inch',
        'cut_width_inch',
        'grh',
        'ply',
        'print',
        // ─── Common Formula Fields ───
        'per_gram_rate',
        'multiplication_layer',
        'formula_constant',
        'work_percentage',
        'multiplication_method',
        // ─── Fixed Percentage Fields ───
        'base_material_id',
        'percentage_of_base',
        // ─── Fixed Rate Fields ───
        'rate_per_unit',
        'rate_base_units',
        // ─── Stock Consumption ───
        'stock_consumption_override',
        'stock_consumption_unit',
    ];

    protected $casts = [
        'quantity' => 'decimal:8',
        'wastage_percentage' => 'decimal:2',
        'cost_per_unit_usd' => 'decimal:8',
        'cost_per_unit_afn' => 'decimal:8',
        'total_cost_usd' => 'decimal:8',
        'total_cost_afn' => 'decimal:8',
        'roll_weight' => 'decimal:8',
        'stock_consumption_override' => 'decimal:8',
        'formula_data' => 'array',
        'is_formula_based' => 'boolean',
        'length_inch' => 'decimal:2',
        'width_inch' => 'decimal:2',
        'height_inch' => 'decimal:2',
        'reel_length_inch' => 'decimal:2',
        'reel_height_inch' => 'decimal:2',
        'paper_gsm' => 'integer',
        'layers' => 'integer',
        'division_factor' => 'decimal:2',
        'cut_length_inch' => 'decimal:2',
        'cut_width_inch' => 'decimal:2',
        'grh' => 'integer',
        'ply' => 'integer',
        'print' => 'decimal:4',
        'per_gram_rate' => 'decimal:8',
        'multiplication_layer' => 'integer',
        'formula_constant' => 'integer',
        'work_percentage' => 'decimal:2',
        'percentage_of_base' => 'decimal:2',
        'rate_per_unit' => 'decimal:8',
        'rate_base_units' => 'integer',
    ];

    // ─── RELATIONSHIPS ───

    public function bom()
    {
        return $this->belongsTo(BOM::class, 'bom_id');
    }

    public function material()
    {
        return $this->belongsTo(Product::class, 'material_id');
    }

    public function baseMaterial()
    {
        return $this->belongsTo(Product::class, 'base_material_id');
    }

    // ─── FORMULA HELPERS ───

    public function getFormulaLabelAttribute()
    {
        $labels = [
            'fixed' => 'Fixed Quantity',
            'carton_3d' => '3D Carton Formula',
            'cut_roll' => 'Cut/Roll Formula',
            'fixed_percentage' => 'Percentage of Base Material',
            'fixed_rate' => 'Fixed Rate',
        ];
        return $labels[$this->formula_type] ?? 'Unknown';
    }

    public function getFormulaTypeIconAttribute()
    {
        $icons = [
            'fixed' => 'bi-input-cursor',
            'carton_3d' => 'bi-box',
            'cut_roll' => 'bi-scissors',
            'fixed_percentage' => 'bi-percent',
            'fixed_rate' => 'bi-clock',
        ];
        return $icons[$this->formula_type] ?? 'bi-question';
    }

    public function getCurrencyBadgeAttribute()
    {
        return $this->purchase_currency === 'USD' ? 'usd' : 'afn';
    }

    // ─── FORMULA CALCULATION ───

    /**
     * Calculate required quantity for this BOM item.
     */
    public function calculateRequiredQuantity($productionQuantity)
    {
        return $this->calculateStockRequirement((float) $productionQuantity, true);
    }


    /**
     * 3D Carton Formula - CORRECTED based on client's Excel
     */
    private function calculateCarton3D($productionQuantity)
    {
        $baseKg = $this->calculateStockKgPerUnit() * (float) $productionQuantity;
        return $baseKg * (1 + ((float) $this->wastage_percentage / 100));
    }

    /**
     * Calculate theoretical paper consumption in kilograms for one finished carton.
     * Area in square inches is converted to square metres using 0.00064516.
     */
    public function calculateStockKgPerUnit(): float
    {
        if ($this->stock_consumption_override !== null && (float) $this->stock_consumption_override > 0) {
            return (float) $this->stock_consumption_override;
        }

        if (!$this->is_formula_based) {
            return (float) $this->quantity;
        }

        if ($this->formula_type === 'carton_3d') {
            $length = (float) ($this->length_inch ?? 0);
            $width = (float) ($this->width_inch ?? 0);
            $height = (float) ($this->height_inch ?? 0);
            $reelLength = (float) ($this->reel_length_inch ?: ((($length + $width) * 2) + 4));
            $reelHeight = (float) ($this->reel_height_inch ?: ($width + $height + 1));
            $gsm = (float) ($this->paper_gsm ?? 0);
            $layers = (float) ($this->multiplication_layer ?? $this->layers ?? 1);

            if ($reelLength <= 0 || $reelHeight <= 0 || $gsm <= 0 || $layers <= 0) {
                return 0.0;
            }

            $constant = max((float) ($this->formula_constant ?? 1550000), 0.000001);

            // Client Excel conversion: area(in²) × GSM × layers ÷ constant = kg.
            return $reelLength * $reelHeight * $gsm * $layers / $constant;
        }

        if ($this->formula_type === 'cut_roll') {
            $length = (float) ($this->cut_length_inch ?? 0);
            $width = (float) ($this->cut_width_inch ?? 0);
            $gsm = (float) ($this->grh ?? 0);
            $ply = max((float) ($this->ply ?? 1), 1);
            $layers = max((float) ($this->multiplication_layer ?? 1), 1);

            if ($length <= 0 || $width <= 0 || $gsm <= 0) {
                return 0.0;
            }

            $constant = max((float) ($this->formula_constant ?? 1550000), 0.000001);

            return $length * $width * $gsm * $ply * $layers / $constant;
        }

        return (float) $this->quantity;
    }


    /**
     * Calculate roll weight for display
     */
    public function calculateRollWeight(): float
    {
        if ($this->formula_type === 'carton_3d') {
            $length = (float) ($this->length_inch ?? 0);
            $width = (float) ($this->width_inch ?? 0);
            $height = (float) ($this->height_inch ?? 0);
            $gsm = (float) ($this->paper_gsm ?? 0);

            if ($length > 0 && $width > 0 && $height > 0 && $gsm > 0) {
                $reelLength = (($length + $width) * 2) + 4;
                $reelHeight = $width + $height + 1;
                $quantity = (float) ($this->quantity ?? 1);
                return $quantity * ($reelLength * $reelHeight * $gsm) / 1000;
            }
        } elseif ($this->formula_type === 'cut_roll') {
            $cutLength = (float) ($this->cut_length_inch ?? 0);
            $cutWidth = (float) ($this->cut_width_inch ?? 0);
            $grh = (float) ($this->grh ?? 0);

            if ($cutLength > 0 && $cutWidth > 0 && $grh > 0) {
                $quantity = (float) ($this->quantity ?? 1);
                return $quantity * ($cutLength * $cutWidth * $grh) / 1000;
            }
        }

        return (float) ($this->quantity ?? 0);
    }

    public function calculateStockRequirement(float $productionQuantity, bool $includeWastage = true): float
    {
        if ($productionQuantity <= 0) {
            return 0.0;
        }

        if ($this->stock_consumption_override !== null && (float) $this->stock_consumption_override > 0) {
            $required = (float) $this->stock_consumption_override * $productionQuantity;
        } elseif ($this->is_formula_based && in_array($this->formula_type, ['carton_3d', 'cut_roll'], true)) {
            $required = $this->calculateStockKgPerUnit() * $productionQuantity;
        } elseif ($this->is_formula_based && $this->formula_type === 'fixed_percentage') {
            $baseItem = $this->base_material_id
                ? BOMItem::where('bom_id', $this->bom_id)
                    ->where('material_id', $this->base_material_id)
                    ->where('id', '!=', $this->id ?? 0)
                    ->first()
                : null;

            $baseRequired = $baseItem
                ? $baseItem->calculateStockRequirement($productionQuantity, false)
                : ((float) $this->quantity * $productionQuantity);

            $required = $baseRequired * ((float) ($this->percentage_of_base ?? 0) / 100);
        } elseif ($this->is_formula_based && $this->formula_type === 'fixed_rate') {
            $rate = (float) ($this->rate_per_unit ?? 0);
            $baseUnits = max((float) ($this->rate_base_units ?? 100), 0.000001);
            $required = ($productionQuantity / $baseUnits) * $rate;
        } else {
            $required = (float) $this->quantity * $productionQuantity;
        }

        if (!$includeWastage) {
            return max($required, 0.0);
        }

        return max($required, 0.0) * (1 + ((float) $this->wastage_percentage / 100));
    }


    /**
     * Cut/Roll Formula
     */
    private function calculateCutRoll($productionQuantity)
    {
        $length = $this->cut_length_inch ?? 0;
        $width = $this->cut_width_inch ?? 0;
        $grh = $this->grh ?? 0;
        $perGramRate = $this->per_gram_rate ?? 0;
        $ply = $this->ply ?? 1;
        $constant = $this->formula_constant ?? 1550000;
        $multiplicationMethod = $this->multiplication_method ?? 'multiply';

        if ($multiplicationMethod === 'divide') {
            $multiplicationValue = ($length * $width * $constant) / 1000;
        } else {
            $multiplicationValue = $length * $width * $constant;
        }

        $paperRate = ($multiplicationValue * $perGramRate * $grh * $ply) / $constant;
        $paperPerCarton = $paperRate / ($perGramRate / 1000);
        $paperPerRoll = $paperPerCarton * 10;

        $totalPaperNeeded = $paperPerCarton * $productionQuantity;
        $rollsNeeded = $totalPaperNeeded / $paperPerRoll;
        $rollsWithWastage = $rollsNeeded * (1 + ($this->wastage_percentage / 100));

        $this->cost_per_unit_usd = $paperRate;

        return [
            'rolls_needed' => $rollsWithWastage,
            'paper_per_carton' => $paperPerCarton,
            'paper_per_roll' => $paperPerRoll,
            'total_paper_needed' => $totalPaperNeeded,
            'rolls_available' => 0,
        ];
    }

    private function calculateFixedPercentage($productionQuantity)
    {
        if (!$this->base_material_id) {
            return $this->quantity * $productionQuantity;
        }

        $baseItem = BOMItem::where('bom_id', $this->bom_id)
            ->where('material_id', $this->base_material_id)
            ->first();

        if (!$baseItem) {
            return $this->quantity * $productionQuantity;
        }

        $baseQuantity = $baseItem->calculateRequiredQuantity($productionQuantity);
        $required = $baseQuantity * ($this->percentage_of_base / 100);

        return $required * (1 + ($this->wastage_percentage / 100));
    }

    private function calculateFixedRate($productionQuantity)
    {
        $rate = $this->rate_per_unit ?? 0;
        $perUnit = $this->rate_base_units ?? 100;
        $required = ($productionQuantity / $perUnit) * $rate;

        return $required * (1 + ($this->wastage_percentage / 100));
    }

    /**
     * Get formula parameters for display.
     */
    public function getFormulaParameters()
    {
        if (!$this->is_formula_based) {
            return [
                'type' => 'Fixed Quantity',
                'params' => [
                    'quantity' => $this->quantity,
                    'unit' => $this->unit,
                ],
            ];
        }

        switch ($this->formula_type) {
            case 'carton_3d':
                return [
                    'type' => '3D Carton Formula',
                    'params' => [
                        'Length' => $this->length_inch . ' in',
                        'Width' => $this->width_inch . ' in',
                        'Height' => $this->height_inch . ' in',
                        'Reel Length' => $this->reel_length_inch . ' in',
                        'Reel Height' => $this->reel_height_inch . ' in',
                        'Paper GSM' => $this->paper_gsm,
                        'Layers' => $this->layers,
                        'Per Gram Rate' => $this->per_gram_rate,
                        'Multiplication Layer' => $this->multiplication_layer,
                        'Formula Constant' => $this->formula_constant,
                        'Division Factor' => $this->division_factor,
                    ],
                ];
            case 'cut_roll':
                return [
                    'type' => 'Cut/Roll Formula',
                    'params' => [
                        'Cut Length' => $this->cut_length_inch . ' in',
                        'Cut Width' => $this->cut_width_inch . ' in',
                        'GRH' => $this->grh,
                        'Per Gram Rate' => $this->per_gram_rate,
                        'Ply' => $this->ply,
                        'Print' => $this->print,
                        'Multiplication Layer' => $this->multiplication_layer,
                        'Formula Constant' => $this->formula_constant,
                        'Multiplication Method' => $this->multiplication_method,
                    ],
                ];
            case 'fixed_percentage':
                return [
                    'type' => 'Percentage of Base Material',
                    'params' => [
                        'Base Material' => $this->baseMaterial?->name ?? 'Not set',
                        'Percentage' => $this->percentage_of_base . '%',
                    ],
                ];
            case 'fixed_rate':
                return [
                    'type' => 'Fixed Rate',
                    'params' => [
                        'Rate' => $this->rate_per_unit . ' per ' . $this->rate_base_units . ' units',
                    ],
                ];
            default:
                return [
                    'type' => 'Unknown',
                    'params' => [],
                ];
        }
    }

    /**
     * Calculate total cost in USD
     */
    public function calculateTotalCostUsd()
    {
        $this->total_cost_usd = $this->quantity * $this->cost_per_unit_usd;
        return $this;
    }

    /**
     * Calculate total cost in AFN
     */
    public function calculateTotalCostAfn()
    {
        $this->total_cost_afn = $this->quantity * $this->cost_per_unit_afn;
        return $this;
    }

    /**
     * Calculate both costs
     */
    public function calculateCosts()
    {
        $this->calculateTotalCostUsd();
        $this->calculateTotalCostAfn();
        return $this;
    }
}
