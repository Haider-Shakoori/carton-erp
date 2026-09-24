<?php

namespace App\Services;

use App\Models\BOM;
use App\Models\BOMItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Builds an order-specific BOM variant when optional lamination is requested.
 *
 * The master BOM is never mutated. The derived BOM copies the approved recipe
 * and adds one frozen Lamination Plastic row whose stock requirement is driven
 * by the carton blank area.
 */
class LaminationAddonService
{
    public function __construct(private readonly BOMCostingService $costing)
    {
    }

    public function derive(BOM $source, User $user): BOM
    {
        return DB::transaction(function () use ($source, $user): BOM {
            $source = BOM::query()
                ->withoutGlobalScope('business_unit')
                ->with(['items.material'])
                ->lockForUpdate()
                ->findOrFail($source->id);

            $existing = $source->items->first(fn (BOMItem $item) => ($item->notes ?? null) === 'lamination');
            if ($existing) {
                return $source;
            }

            $formulaSource = $source->items->first(function (BOMItem $item) {
                return (float) ($item->reel_length_inch ?? 0) > 0
                    && (float) ($item->reel_height_inch ?? 0) > 0;
            });

            if (! $formulaSource) {
                throw new RuntimeException(
                    'This BOM has no carton blank dimensions, so lamination consumption cannot be calculated automatically.'
                );
            }

            $areaM2 = (float) $formulaSource->reel_length_inch
                * (float) $formulaSource->reel_height_inch
                * (float) config('carton.sq_inch_to_m2', 0.00064516);

            $filmGsm = max((float) config('carton.lamination.film_gsm', 20), 0.0);
            $sides = max((int) config('carton.lamination.sides', 1), 1);
            $wastage = max((float) config('carton.lamination.wastage_percentage', 5), 0.0);
            $baseKgPerCarton = $areaM2 * $filmGsm * $sides / 1000;

            if ($areaM2 <= 0 || $baseKgPerCarton <= 0) {
                throw new RuntimeException('The lamination formula produced zero material usage for this BOM.');
            }

            $materialName = (string) config('carton.lamination.material_name', 'Lamination Plastic');
            $material = Product::query()
                ->where('name', $materialName)
                ->where('type', Product::TYPE_RAW_MATERIAL)
                ->first();

            if (! $material) {
                throw new RuntimeException(
                    "Lamination material [{$materialName}] is missing. Add it to stock before enabling lamination."
                );
            }

            $exchangeRate = max((float) $source->getUSDtoAFNRate(), 0.000001);
            $latest = $this->costing->latestInventoryCost((int) $material->id, $exchangeRate);

            if (! $latest['found'] || (float) $latest['cost_usd'] <= 0) {
                throw new RuntimeException(
                    "No arrived landed cost is available for [{$materialName}]. Receive lamination stock before quoting it."
                );
            }

            $derived = $source->replicate([
                'id',
                'code',
                'status',
                'is_active',
                'approved_by',
                'approved_at',
                'locked_by',
                'locked_at',
                'created_at',
                'updated_at',
            ]);

            $derived->name = $source->name . ' + Lamination';
            $derived->code = $source->code . '-LAM-' . Str::upper(Str::random(6));
            $derived->version = $source->version . '-LAM';
            $derived->status = 'draft';
            $derived->is_active = false;
            $derived->supersedes_bom_id = $source->id;
            $derived->description = trim((string) $source->description)
                . ' | Order-specific optional lamination add-on.';
            $derived->created_by = $user->id;
            $derived->updated_by = $user->id;
            $derived->approved_by = null;
            $derived->approved_at = null;
            $derived->locked_by = null;
            $derived->locked_at = null;
            $derived->saveQuietly();

            foreach ($source->items as $item) {
                $copy = $item->replicate(['id', 'bom_id', 'created_at', 'updated_at']);
                $copy->bom_id = $derived->id;
                $copy->save();
            }

            $costUsd = (float) $latest['cost_usd'];
            $costAfn = $costUsd * $exchangeRate;
            $withWaste = $baseKgPerCarton * (1 + ($wastage / 100));

            BOMItem::create([
                'bom_id' => $derived->id,
                'material_id' => $material->id,
                'quantity' => $baseKgPerCarton,
                'unit' => 'kg',
                'component_type' => CartonSpecificationService::COMPONENT_AUXILIARY,
                'wastage_percentage' => $wastage,
                'cost_per_unit_usd' => $costUsd,
                'cost_per_unit_afn' => $costAfn,
                'total_cost_usd' => $withWaste * $costUsd,
                'total_cost_afn' => $withWaste * $costAfn,
                'purchase_currency' => $latest['purchase_currency'] ?? 'USD',
                'purchase_currency_id' => $latest['purchase_currency_id'],
                'notes' => 'lamination',
                'sort_order' => ((int) $source->items->max('sort_order')) + 1,
                'formula_type' => 'fixed_rate',
                'is_formula_based' => true,
                'formula_data' => [
                    'formula' => 'board_area_m2 × film_gsm × sides ÷ 1000 × (1 + wastage%)',
                    'board_area_m2' => $areaM2,
                    'film_gsm' => $filmGsm,
                    'sides' => $sides,
                    'wastage_percentage' => $wastage,
                    'source_bom_id' => $source->id,
                ],
                'apply_work_percentage' => false,
                'work_percentage' => 0,
                'stock_consumption_override' => $baseKgPerCarton,
                'stock_consumption_unit' => 'kg',
                'rate_per_unit' => $baseKgPerCarton,
                'rate_base_units' => 1,
            ]);

            return $this->costing->refreshBomMaterialCosts($derived);
        });
    }
}
