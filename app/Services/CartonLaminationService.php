<?php

namespace App\Services;

use App\Models\BOM;
use App\Models\BOMItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Optional lamination finishing for carton sale lines.
 *
 * The master BOM is never modified. When lamination is selected on a sale,
 * this service creates a private order-specific BOM snapshot, copies the base
 * material rows and appends one formula-driven Lamination Plastic row.
 */
class CartonLaminationService
{
    public function __construct(
        private readonly BOMCostingService $costing
    ) {
    }

    /**
     * Build the canonical lamination formula for a board area.
     *
     * kg/carton = area(m2) * film GSM * sides / 1000
     * physical kg = base kg * (1 + wastage%)
     */
    public function formula(float $boardAreaM2): array
    {
        if ($boardAreaM2 <= 0) {
            throw new RuntimeException('Lamination requires a carton BOM with a valid board area.');
        }

        $filmGsm = max((float) config('carton.lamination.film_gsm', 20), 0.000001);
        $sides = max((int) config('carton.lamination.sides', 1), 1);
        $wastage = max((float) config('carton.lamination.wastage_percentage', 5), 0.0);

        $baseKg = $boardAreaM2 * $filmGsm * $sides / 1000;
        $physicalKg = $baseKg * (1 + ($wastage / 100));

        return [
            'board_area_m2' => $boardAreaM2,
            'film_gsm' => $filmGsm,
            'sides' => $sides,
            'wastage_percentage' => $wastage,
            'kg_per_unit' => $baseKg,
            'kg_with_wastage' => $physicalKg,
            'formula' => 'BoardAreaM2 × FilmGSM × Sides ÷ 1000 × (1 + Waste%)',
        ];
    }

    public function material(): Product
    {
        $name = (string) config('carton.lamination.material_name', 'Lamination Plastic');

        $material = Product::query()
            ->where('name', $name)
            ->where('type', Product::TYPE_RAW_MATERIAL)
            ->first();

        if (! $material) {
            throw new RuntimeException(
                "Lamination raw material [{$name}] is missing. Run the client raw-material seeder first."
            );
        }

        return $material;
    }

    /**
     * Technical row shape shared with CartonSpecificationService.
     */
    public function technicalRow(float $boardAreaM2, int $sortOrder = 999): array
    {
        $material = $this->material();
        $formula = $this->formula($boardAreaM2);

        return [
            'sort_order' => $sortOrder,
            'component_type' => CartonSpecificationService::COMPONENT_AUXILIARY,
            'role' => 'lamination',
            'material_id' => (int) $material->id,
            'material_name' => $material->name,
            'formula_type' => 'fixed_rate',
            'is_formula_based' => true,
            'length_inch' => null,
            'width_inch' => null,
            'height_inch' => null,
            'reel_length_inch' => null,
            'reel_height_inch' => null,
            'paper_gsm' => null,
            'multiplication_layer' => 1,
            'formula_constant' => null,
            'wastage_percentage' => (float) $formula['wastage_percentage'],
            'work_percentage' => 0.0,
            'apply_work_percentage' => false,
            'print' => 0.0,
            'kg_per_unit' => (float) $formula['kg_per_unit'],
            'kg_with_wastage' => (float) $formula['kg_with_wastage'],
            'unit' => 'kg',
            'rate_per_unit' => (float) $formula['kg_per_unit'],
            'rate_base_units' => 1,
            'stock_consumption_override' => (float) $formula['kg_per_unit'],
            'formula_data' => [
                'board_area_m2' => (float) $formula['board_area_m2'],
                'film_gsm' => (float) $formula['film_gsm'],
                'sides' => (int) $formula['sides'],
                'wastage_percentage' => (float) $formula['wastage_percentage'],
                'formula' => $formula['formula'],
            ],
        ];
    }

    /**
     * Preview lamination usage and direct selling-price impact without writing.
     */
    public function previewForBom(BOM $bom, float $exchangeRate): array
    {
        $boardAreaM2 = $this->boardAreaFromBom($bom);
        $row = $this->technicalRow($boardAreaM2);
        $latest = $this->costing->latestInventoryCost((int) $row['material_id'], $exchangeRate);

        $costUsd = $latest['found'] ? (float) $latest['cost_usd'] : 0.0;
        $costAfn = $costUsd * $exchangeRate;
        $baseCostAfn = (float) $row['kg_per_unit'] * $costAfn;
        $physicalCostAfn = (float) $row['kg_with_wastage'] * $costAfn;
        $profitMargin = max((float) ($bom->profit_margin_percentage ?? 0), 0.0);

        return array_merge($row['formula_data'], [
            'available' => (bool) $latest['found'] && $costUsd > 0,
            'material_id' => (int) $row['material_id'],
            'material_name' => $row['material_name'],
            'kg_per_unit' => (float) $row['kg_per_unit'],
            'kg_with_wastage' => (float) $row['kg_with_wastage'],
            'landed_cost_usd_per_kg' => $costUsd,
            'landed_cost_afn_per_kg' => $costAfn,
            'base_cost_afn_per_unit' => $baseCostAfn,
            'physical_cost_afn_per_unit' => $physicalCostAfn,
            'selling_price_addition_afn_per_unit' => $baseCostAfn * (1 + ($profitMargin / 100)),
        ]);
    }

    /**
     * Create a hidden, order-specific BOM variant with lamination activated.
     */
    public function createOrderSpecificBom(BOM $source, ?int $userId, float $exchangeRate): array
    {
        return DB::transaction(function () use ($source, $userId, $exchangeRate): array {
            $source = BOM::query()
                ->withoutGlobalScope('business_unit')
                ->with(['items.material'])
                ->lockForUpdate()
                ->findOrFail($source->id);

            $boardAreaM2 = $this->boardAreaFromBom($source);
            $row = $this->technicalRow($boardAreaM2, $source->items->count() + 1);
            $latest = $this->costing->latestInventoryCost((int) $row['material_id'], $exchangeRate);

            if (! $latest['found'] || (float) $latest['cost_usd'] <= 0) {
                throw new RuntimeException(
                    'Lamination Plastic has no valid landed stock rate. Receive lamination stock before using this option.'
                );
            }

            $clone = $source->replicate([
                'id',
                'code',
                'status',
                'is_active',
                'effective_from',
                'effective_to',
                'approved_by',
                'approved_at',
                'locked_by',
                'locked_at',
                'created_at',
                'updated_at',
            ]);

            $clone->name = $source->name . ' + Lamination';
            $clone->code = $source->code . '-LAM-' . Str::upper(Str::random(6));
            $clone->version = (string) $source->version . '-LAM';
            $clone->status = 'draft';
            $clone->is_active = false;
            $clone->effective_from = null;
            $clone->effective_to = null;
            $clone->supersedes_bom_id = $source->id;
            $clone->approved_by = null;
            $clone->approved_at = null;
            $clone->locked_by = null;
            $clone->locked_at = null;
            $clone->created_by = $userId ?: $source->created_by;
            $clone->updated_by = $userId ?: $source->updated_by;
            $clone->description = trim((string) $source->description)
                . "\n[ORDER OPTION] Lamination enabled with automatic board-area consumption.";
            $clone->saveQuietly();

            foreach ($source->items as $item) {
                $copy = $item->replicate(['id', 'bom_id', 'created_at', 'updated_at']);
                $copy->bom_id = $clone->id;
                $copy->saveQuietly();
            }

            $costUsd = (float) $latest['cost_usd'];
            $costAfn = $costUsd * $exchangeRate;

            BOMItem::create([
                'bom_id' => $clone->id,
                'material_id' => $row['material_id'],
                'quantity' => $row['kg_per_unit'],
                'unit' => 'kg',
                'component_type' => CartonSpecificationService::COMPONENT_AUXILIARY,
                'wastage_percentage' => $row['wastage_percentage'],
                'cost_per_unit_usd' => $costUsd,
                'cost_per_unit_afn' => $costAfn,
                'total_cost_usd' => $row['kg_with_wastage'] * $costUsd,
                'total_cost_afn' => $row['kg_with_wastage'] * $costAfn,
                'purchase_currency' => $latest['purchase_currency'] ?? 'USD',
                'purchase_currency_id' => $latest['purchase_currency_id'] ?? null,
                'notes' => 'Order-specific lamination formula',
                'sort_order' => $row['sort_order'],
                'formula_type' => 'fixed_rate',
                'formula_data' => $row['formula_data'],
                'is_formula_based' => true,
                'rate_per_unit' => $row['rate_per_unit'],
                'rate_base_units' => 1,
                'work_percentage' => 0,
                'apply_work_percentage' => false,
                'stock_consumption_override' => $row['stock_consumption_override'],
                'stock_consumption_unit' => 'kg',
            ]);

            $clone->unsetRelation('items');
            $clone->load('items.material');
            $clone->calculateTotals();
            $clone->saveQuietly();

            return [
                'bom' => $clone->fresh(['items.material']),
                'lamination' => array_merge($row['formula_data'], [
                    'material_id' => (int) $row['material_id'],
                    'material_name' => $row['material_name'],
                    'kg_per_unit' => (float) $row['kg_per_unit'],
                    'kg_with_wastage' => (float) $row['kg_with_wastage'],
                    'landed_cost_usd_per_kg' => $costUsd,
                    'landed_cost_afn_per_kg' => $costAfn,
                ]),
            ];
        });
    }

    public function boardAreaFromBom(BOM $bom): float
    {
        $bom->loadMissing('items');

        foreach ($bom->items as $item) {
            $reelLength = (float) ($item->reel_length_inch ?? 0);
            $reelHeight = (float) ($item->reel_height_inch ?? 0);

            if ($reelLength > 0 && $reelHeight > 0) {
                return $reelLength * $reelHeight
                    * (float) config('carton.sq_inch_to_m2', 0.00064516);
            }

            if ($item->formula_type === 'carton_3d') {
                $length = (float) ($item->length_inch ?? 0);
                $width = (float) ($item->width_inch ?? 0);
                $height = (float) ($item->height_inch ?? 0);

                if ($length > 0 && $width > 0 && $height > 0) {
                    $reelLength = (($length + $width) * 2) + 4;
                    $reelHeight = $width + $height + 1;

                    return $reelLength * $reelHeight
                        * (float) config('carton.sq_inch_to_m2', 0.00064516);
                }
            }

            if ($item->formula_type === 'cut_roll') {
                $cutLength = (float) ($item->cut_length_inch ?? 0);
                $cutWidth = (float) ($item->cut_width_inch ?? 0);

                if ($cutLength > 0 && $cutWidth > 0) {
                    return $cutLength * $cutWidth
                        * (float) config('carton.sq_inch_to_m2', 0.00064516);
                }
            }
        }

        throw new RuntimeException(
            'This BOM has no usable carton dimensions, so automatic lamination usage cannot be calculated.'
        );
    }
}
