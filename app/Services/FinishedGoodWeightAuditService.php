<?php

namespace App\Services;

use App\Models\BOM;
use App\Models\Product;

class FinishedGoodWeightAuditService
{
    public function __construct(private readonly FactoryCartonReferenceService $factoryReference)
    {
    }

    /**
     * Compare measured gross carton mass with BOM theoretical PAPER mass.
     *
     * A finished carton also contains adhesive, ink and moisture. This is a
     * plausibility check, not an authorization to rescale BOM inventory issues.
     */
    public function assess(Product $product, ?BOM $bom): array
    {
        $measured = $product->finished_weight_g !== null && (float) $product->finished_weight_g > 0
            ? (float) $product->finished_weight_g : null;
        $measuredSource = $measured !== null ? 'product' : 'missing';

        if ($measured === null) {
            $reference = $this->factoryReference->match($product);

            if ($reference !== null) {
                $measured = (float) $reference['net_weight_g'];
                $measuredSource = 'factory_sheet_reference';
            }
        }

        if ($measured === null || $measured <= 0) {
            return ['measured_g' => null, 'measured_source' => 'missing', 'paper_g' => null, 'difference_g' => null,
                'status' => 'Missing weight', 'materials' => []];
        }

        if (! $bom || $bom->items->isEmpty()) {
            return ['measured_g' => $measured, 'measured_source' => $measuredSource, 'paper_g' => null, 'difference_g' => null,
                'status' => 'Missing BOM', 'materials' => []];
        }

        $paper = [];
        $unverified = false;

        foreach ($bom->items as $item) {
            if ($item->resolvedComponentType() !== 'paper') {
                continue;
            }

            $kgBased = (bool) $item->is_formula_based
                || strtolower((string) $item->stock_consumption_unit) === 'kg'
                || strtolower((string) $item->unit) === 'kg';

            $perUnitKg = $kgBased ? $item->calculateStockKgPerUnit() : 0;
            if (! $kgBased || $perUnitKg <= 0) {
                $unverified = true;
                continue;
            }

            $paper[] = [
                'name' => $item->material?->name ?? 'Unknown paper',
                'weight_g' => round($perUnitKg * 1000, 2),
            ];
        }

        $totalPaper = array_sum(array_column($paper, 'weight_g'));
        if ($totalPaper <= 0 || $unverified) {
            return ['measured_g' => $measured, 'measured_source' => $measuredSource, 'paper_g' => null, 'difference_g' => null,
                'status' => 'BOM review required', 'materials' => $paper];
        }

        $difference = round($measured - $totalPaper, 2);
        $status = match (true) {
            str_starts_with((string) $bom->description, '[REVIEW REQUIRED]') => 'BOM review required',
            $totalPaper > $measured * 1.05 => 'Paper exceeds carton weight',
            $totalPaper < $measured * 0.70 => 'Large weight gap',
            $bom->status !== 'active' || ! $bom->is_active => 'Draft BOM — review',
            $measuredSource === 'factory_sheet_reference' => 'Factory reference — review',
            default => 'Indicative match',
        };

        return [
            'measured_g' => $measured, 'measured_source' => $measuredSource, 'paper_g' => round($totalPaper, 2),
            'difference_g' => $difference, 'status' => $status,
            'materials' => $paper,
        ];
    }
}
