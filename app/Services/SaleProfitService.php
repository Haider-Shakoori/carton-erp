<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Product;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SaleProfitService
{
    public function calculate(Sale $sale): array
    {
        $sale->loadMissing([
            'items',
            'items.bom.items',
            'currency',
            'productionOrder',
            'productionOrder.bom',
        ]);

        $exchangeRate = max((float) ($sale->exchange_rate ?? 1), 0.000001);
        $grossSalesAfn = (float) ($sale->grand_total ?? $sale->items->sum('total'));
        $grossSalesUsd = (float) ($sale->usd_grand_total ?? ($grossSalesAfn / $exchangeRate));

        // The original SaleItem cost is the quotation/BOM commercial cost basis.
        // Keep it visible, but do not use it as estimated physical production cost.
        $quotationBomCostUsd = (float) $sale->items->sum('total_cost_usd');
        $quotationBomCostAfn = $quotationBomCostUsd * $exchangeRate;

        // Planned production cost uses the physical BOM requirement. 5% (or the
        // configured wastage percentage) is already included by calculateStockRequirement().
        $plannedBaseMaterialUsd = 0.0;
        $plannedWastageUsd = 0.0;
        $plannedWorkUsd = 0.0;
        $weightedWorkBaseUsd = 0.0;
        $weightedWorkPercentValue = 0.0;
        $rollWeightGap = false;

        foreach ($sale->items as $saleItem) {
            $bom = $saleItem->bom;
            if (!$bom) {
                continue;
            }

            $itemPlannedMaterialUsd = 0.0;

            foreach ($this->physicalCostRows($saleItem, $bom) as $row) {
                if (!empty($row['roll_weight_missing'])) {
                    $rollWeightGap = true;
                }
                $baseQty = $row['kg_per_unit'] * (float) $saleItem->qty;
                $withWastageQty = $baseQty * (1 + ($row['wastage_percentage'] / 100));
                $costPerUnitUsd = (float) $row['cost_per_unit_usd'];

                $baseCostUsd = $baseQty * $costPerUnitUsd;
                $wastageCostUsd = max(0, $withWastageQty - $baseQty) * $costPerUnitUsd;

                $plannedBaseMaterialUsd += $baseCostUsd;
                $plannedWastageUsd += $wastageCostUsd;
                $itemPlannedMaterialUsd += $baseCostUsd + $wastageCostUsd;
            }

            // The configured work_percentage (default 40%) is the client's commercial
            // STANDARD WORK / PROFIT markup embedded in the Excel Net Rate. It is NOT
            // a production expense. Estimated production cost must contain only genuine
            // production costs: physical material (incl. 5% wastage) × landed cost.
            // Track the weighted work percentage for standard-profit reporting only.
            $workPercentage = (float) ($bom->work_percentage ?? 40);
            $weightedWorkBaseUsd += $itemPlannedMaterialUsd;
            $weightedWorkPercentValue += $itemPlannedMaterialUsd * $workPercentage;
        }

        $plannedMaterialUsd = $plannedBaseMaterialUsd + $plannedWastageUsd;
        $effectiveWorkPercentage = $weightedWorkBaseUsd > 0
            ? $weightedWorkPercentValue / $weightedWorkBaseUsd
            : (float) ($sale->productionOrder?->bom?->work_percentage ?? 40);

        // If an existing production order already has snapshotted planned material
        // costs, prefer that snapshot because it is the exact plan used for production.
        if ($sale->production_order_id && Schema::hasTable('production_order_materials')) {
            $snapshottedPlannedMaterialUsd = (float) DB::table('production_order_materials')
                ->where('production_order_id', $sale->production_order_id)
                ->sum('total_cost');

            if ($snapshottedPlannedMaterialUsd > 0) {
                $plannedMaterialUsd = $snapshottedPlannedMaterialUsd;
                // Do NOT add work_percentage here. The 40% is commercial profit,
                // not a production expense. Estimated production cost = material only.
                $plannedWorkUsd = 0.0;

                // Keep base/wastage detail from the BOM calculation when available.
                // If not available, all snapshotted material cost is treated as base.
                if (($plannedBaseMaterialUsd + $plannedWastageUsd) <= 0) {
                    $plannedBaseMaterialUsd = $plannedMaterialUsd;
                    $plannedWastageUsd = 0.0;
                }
            }
        }

        $estimatedProductionCostUsd = $plannedMaterialUsd + $plannedWorkUsd;
        $estimatedProductionCostAfn = $estimatedProductionCostUsd * $exchangeRate;
        $estimatedProfitUsd = $grossSalesUsd - $estimatedProductionCostUsd;
        $estimatedProfitAfn = $grossSalesAfn - $estimatedProductionCostAfn;

        // Actual FIFO material consumption is canonical in USD.
        $materialUsd = 0.0;
        $hasConsumption = false;

        if (Schema::hasTable('production_material_consumptions')) {
            $query = DB::table('production_material_consumptions')->where('sale_id', $sale->id);

            if (!(clone $query)->exists() && $sale->production_order_id) {
                $query = DB::table('production_material_consumptions')
                    ->where('production_order_id', $sale->production_order_id);
            }

            $hasConsumption = (clone $query)->exists();
            $materialUsd = (float) (clone $query)->sum('total_cost_usd');
        }

        $materialAfn = $materialUsd * $exchangeRate;
        $labourAfn = 0.0;
        $overheadAfn = 0.0;
        $otherAfn = 0.0;

        if ($sale->production_order_id && Schema::hasTable('production_orders')) {
            $production = DB::table('production_orders')->where('id', $sale->production_order_id)->first();
            if ($production) {
                $labourAfn = (float) ($production->actual_labour_cost_afn ?? 0);
                $overheadAfn = (float) ($production->actual_overhead_cost_afn ?? 0);
                $otherAfn = (float) ($production->other_direct_cost_afn ?? 0);
            }
        }

        // Standard Work / Profit is the client's commercial PROFIT embedded in the
        // Excel Net Rate. Authoritative source = the CLIENT COMMERCIAL BOM calculation:
        //   Net Rate = Paper Rate By Layers + (Paper Rate By Layers × work%) + Print
        // The work% applies ONLY to Paper Rate By Layers — NOT to Print.
        // Therefore Standard Profit must NEVER be reconstructed from total revenue
        // (revenue × work/(100+work) is only valid when Print = 0), from FIFO material,
        // or from estimated production material.
        // The commercial basis is computed from the BOM formula rows (each row uses
        // its own work_percentage, paper rate by layers, and print) and summed.
        $commercial = $this->commercialBomCalculation($sale, $grossSalesAfn, $effectiveWorkPercentage);

        // For MANUAL-BOM sales the persisted manual net rate IS the canonical Excel
        // quotation total (net rate × qty). The USD-rounded total_cost_usd (stored at
        // 2dp) inflates the AFN reconstruction (e.g. 9.35 × 66 = 617.10 vs 616.80), so
        // for manual items use the snapshot-based net rate to keep the quotation total
        // aligned with the sale revenue.
        $hasManualSnapshot = $sale->items->contains(function ($saleItem) {
            return is_array($saleItem->manual_bom_snapshot ?? null) && count($saleItem->manual_bom_snapshot) > 0;
        });
        if ($hasManualSnapshot && $commercial['net_rate'] > 0) {
            $quotationBomCostAfn = $commercial['net_rate'];
            $quotationBomCostUsd = $quotationBomCostAfn / $exchangeRate;
        }

        // Actual production cost uses only genuine costs. Standard profit is NOT a
        // production expense.
        $usesStandardActualWork = $hasConsumption && abs($labourAfn) < 0.0001 && abs($overheadAfn) < 0.0001;
        $standardActualWorkAfn = $usesStandardActualWork
            ? $commercial['standard_work_profit']
            : 0.0;

        $actualWorkAfn = $labourAfn + $overheadAfn;

        $actualAvailable = $hasConsumption;
        $actualCostAfn = $materialAfn + $actualWorkAfn + $otherAfn;
        $actualCostUsd = $actualCostAfn / $exchangeRate;

        $actualProfitAfn = $actualAvailable ? $grossSalesAfn - $actualCostAfn : 0;
        $actualProfitUsd = $actualAvailable ? $grossSalesUsd - $actualCostUsd : 0;
        $actualMargin = ($actualAvailable && $grossSalesAfn > 0)
            ? ($actualProfitAfn / $grossSalesAfn) * 100
            : 0;

        // Positive variance means actual production cost exceeded estimate.
        $varianceAfn = $actualAvailable ? $actualCostAfn - $estimatedProductionCostAfn : 0;
        $varianceUsd = $actualAvailable ? $actualCostUsd - $estimatedProductionCostUsd : 0;

        // Build per-sale-item production financials for the Sale Items table.
        // Actual FIFO material is linked directly through sale_item_id where available.
        // Production-level labour/overhead/other costs are allocated by actual material cost.
        $itemFinancials = [];
        $actualMaterialByItemUsd = [];

        if ($hasConsumption && Schema::hasTable('production_material_consumptions')) {
            $itemConsumptionQuery = DB::table('production_material_consumptions')
                ->select('sale_item_id', DB::raw('SUM(total_cost_usd) as total_cost_usd'))
                ->whereNotNull('sale_item_id');

            if (DB::table('production_material_consumptions')->where('sale_id', $sale->id)->exists()) {
                $itemConsumptionQuery->where('sale_id', $sale->id);
            } elseif ($sale->production_order_id) {
                $itemConsumptionQuery->where('production_order_id', $sale->production_order_id);
            }

            $actualMaterialByItemUsd = $itemConsumptionQuery
                ->groupBy('sale_item_id')
                ->pluck('total_cost_usd', 'sale_item_id')
                ->map(fn ($value) => (float) $value)
                ->all();
        }

        // Legacy/fallback: if there is one sale item and consumptions were recorded
        // without sale_item_id, the whole actual material cost belongs to that item.
        if ($hasConsumption && count($actualMaterialByItemUsd) === 0 && $sale->items->count() === 1) {
            $onlyItem = $sale->items->first();
            $actualMaterialByItemUsd[(int) $onlyItem->id] = $materialUsd;
        }

        $totalMappedActualMaterialUsd = array_sum($actualMaterialByItemUsd);

        foreach ($sale->items as $saleItem) {
            $itemId = (int) $saleItem->id;
            $itemRevenueAfn = (float) ($saleItem->total ?? 0);
            $itemRevenueUsd = (float) ($saleItem->usd_total ?? ($itemRevenueAfn / $exchangeRate));
            $itemWorkPercentage = (float) ($saleItem->bom?->work_percentage ?? $effectiveWorkPercentage);

            $itemActualMaterialUsd = (float) ($actualMaterialByItemUsd[$itemId] ?? 0);
            $itemActualMaterialAfn = $itemActualMaterialUsd * $exchangeRate;
            $allocationShare = $totalMappedActualMaterialUsd > 0
                ? $itemActualMaterialUsd / $totalMappedActualMaterialUsd
                : 0.0;

            if ($usesStandardActualWork) {
                // Standard work/profit is commercial profit, not an actual expense.
                // Nothing genuine to allocate, so actual item work is zero.
                $itemActualWorkAfn = 0;
            } else {
                $itemActualWorkAfn = ($labourAfn + $overheadAfn) * $allocationShare;
            }

            $itemOtherAfn = $otherAfn * $allocationShare;
            $itemActualCostAfn = $itemActualMaterialAfn + $itemActualWorkAfn + $itemOtherAfn;
            $itemActualCostUsd = $itemActualCostAfn / $exchangeRate;
            $itemActualProfitAfn = $itemRevenueAfn - $itemActualCostAfn;
            $itemActualProfitUsd = $itemRevenueUsd - $itemActualCostUsd;
            $itemActualMargin = $itemRevenueAfn > 0
                ? ($itemActualProfitAfn / $itemRevenueAfn) * 100
                : 0.0;

            $itemFinancials[$itemId] = [
                'actual_available' => $hasConsumption && array_key_exists($itemId, $actualMaterialByItemUsd),
                'actual_material_cost_afn' => round($itemActualMaterialAfn, 2),
                'actual_material_cost_usd' => round($itemActualMaterialUsd, 2),
                'actual_work_cost_afn' => round($itemActualWorkAfn, 2),
                'actual_work_cost_usd' => round($itemActualWorkAfn / $exchangeRate, 2),
                'actual_cost_afn' => round($itemActualCostAfn, 2),
                'actual_cost_usd' => round($itemActualCostUsd, 2),
                'actual_profit_afn' => round($itemActualProfitAfn, 2),
                'actual_profit_usd' => round($itemActualProfitUsd, 2),
                'actual_margin_percentage' => round($itemActualMargin, 2),
            ];
        }

        return [
            'actual_available' => $actualAvailable,
            'gross_sales_afn' => round($grossSalesAfn, 2),
            'gross_sales_usd' => round($grossSalesUsd, 2),

            'quotation_bom_cost_afn' => round($quotationBomCostAfn, 2),
            'quotation_bom_cost_usd' => round($quotationBomCostUsd, 2),

            'planned_base_material_cost_afn' => round($plannedBaseMaterialUsd * $exchangeRate, 2),
            'planned_base_material_cost_usd' => round($plannedBaseMaterialUsd, 2),
            'planned_wastage_cost_afn' => round($plannedWastageUsd * $exchangeRate, 2),
            'planned_wastage_cost_usd' => round($plannedWastageUsd, 2),
            'estimated_material_cost_afn' => round($plannedMaterialUsd * $exchangeRate, 2),
            'estimated_material_cost_usd' => round($plannedMaterialUsd, 2),
            'estimated_work_cost_afn' => round($plannedWorkUsd * $exchangeRate, 2),
            'estimated_work_cost_usd' => round($plannedWorkUsd, 2),
            'work_percentage' => round($effectiveWorkPercentage, 2),

            'estimated_cost_afn' => round($estimatedProductionCostAfn, 2),
            'estimated_cost_usd' => round($estimatedProductionCostUsd, 2),
            'estimated_profit_afn' => round($estimatedProfitAfn, 2),
            'estimated_profit_usd' => round($estimatedProfitUsd, 2),
            // True when a roll-based material has no valid inventory kg_per_roll
            // basis, so a monetary estimated production cost cannot be trusted.
            'estimated_cost_unavailable' => $rollWeightGap,

            'actual_material_cost_afn' => round($materialAfn, 2),
            'actual_material_cost_usd' => round($materialUsd, 2),
            'actual_labour_cost_afn' => round($labourAfn, 2),
            'actual_overhead_cost_afn' => round($overheadAfn, 2),
            'standard_actual_work_cost_afn' => round($standardActualWorkAfn, 2),
            'standard_actual_work_cost_usd' => round($standardActualWorkAfn / $exchangeRate, 2),
            'uses_standard_actual_work' => $usesStandardActualWork,
            'other_direct_cost_afn' => round($otherAfn, 2),
            'actual_production_cost_afn' => round($actualCostAfn, 2),
            'actual_production_cost_usd' => round($actualCostUsd, 2),
            'actual_profit_afn' => round($actualProfitAfn, 2),
            'actual_profit_usd' => round($actualProfitUsd, 2),
            'actual_margin_percentage' => round($actualMargin, 2),
            'cost_variance_afn' => round($varianceAfn, 2),
            'cost_variance_usd' => round($varianceUsd, 2),

            // Standard work/profit (client Excel) is a commercial markup embedded
            // in the Net Rate, reported separately and never counted as an actual cost.
            // Source = Commercial Paper Rate By Layers × work% (excludes Print).
            'commercial_paper_basis_afn' => round($commercial['paper_basis'], 2),
            'commercial_print_afn' => round($commercial['print_total'], 2),
            'commercial_net_rate_afn' => round($commercial['net_rate'], 2),
            'standard_work_profit_afn' => round($commercial['standard_work_profit'], 2),
            'standard_profit_on_material_percentage' => round($effectiveWorkPercentage, 2),
            'standard_profit_margin_on_revenue_percentage' => (($commercial['net_rate'] ?? $grossSalesAfn) > 0 && $standardActualWorkAfn > 0)
                ? round(($standardActualWorkAfn / $commercial['net_rate']) * 100, 2)
                : 0,
            'item_financials' => $itemFinancials,
        ];
    }

    /**
     * Canonical commercial calculation that mirrors the client Excel quotation:
     *
     *   Net Rate = Paper Rate By Layers + (Paper Rate By Layers × work%) + Print
     *
     * The configured work_percentage applies ONLY to "Paper Rate By Layers",
     * NOT to Print. Each BOM row may carry its own work_percentage.
     *
     * For the 3D-carton formula (and cut/roll), a single shared reel length/height
     * is taken from the first BOM item, while each material row contributes its own
     * gsm, per_gram_rate, multiplication_layer, work_percentage and print.
     *
     * @return array{paper_basis:float, standard_work_profit:float, print_total:float, net_rate:float}
     */
    private function commercialBomCalculation(Sale $sale, float $grossSalesAfn, float $effectiveWorkPercentage): array
    {
        $paperBasis = 0.0;
        $standardProfit = 0.0;
        $printTotal = 0.0;
        $netRate = 0.0;
        $hasCommercialRows = false;

        foreach ($sale->items as $saleItem) {
            $bom = $saleItem->bom;
            if (!$bom || !$bom->items || $bom->items->isEmpty()) {
                continue;
            }

            $hasCommercialRows = true;

            $manual = $this->manualCommercialCalculation($saleItem);
            if ($manual !== null) {
                $paperBasis += $manual['paper_basis'];
                $standardProfit += $manual['standard_work_profit'];
                $printTotal += $manual['print_total'];
                $netRate += $manual['net_rate'];
                continue;
            }

            // Shared reel dimensions from the first BOM item (3D-carton / cut-roll).
            $firstItem = $bom->items->first();
            $length = (float) ($firstItem->length_inch ?? 0);
            $width = (float) ($firstItem->width_inch ?? 0);
            $height = (float) ($firstItem->height_inch ?? 0);
            $reelLength = (($length + $width) * 2) + 4;
            $reelHeight = $width + $height + 1;

            $isCutRoll = ($bom->formula_type ?? '') === 'cut_roll';

            foreach ($bom->items as $bomItem) {
                $gsm = (float) ($bomItem->paper_gsm ?? 0);
                $perGramRate = (float) ($bomItem->per_gram_rate ?? 0);
                $multiplicationLayer = (float) ($bomItem->multiplication_layer ?? 1);
                $print = (float) ($bomItem->print ?? 0);
                $constant = (float) ($bomItem->formula_constant ?? 1550000);
                $workPct = (float) ($bomItem->work_percentage ?? $bom->work_percentage ?? 40);

                $paperRate = 0.0;
                if ($isCutRoll) {
                    $cutLength = (float) ($bom->cut_length_inch ?? 0);
                    $cutWidth = (float) ($bom->cut_width_inch ?? 0);
                    $grh = (float) ($bom->grh ?? 0);
                    $ply = (float) ($bom->ply ?? 1);
                    $multiplicationMethod = (string) ($bom->multiplication_method ?? 'multiply');
                    $multiplicationValue = $multiplicationMethod === 'divide'
                        ? ($cutLength * $cutWidth * $constant) / 1000
                        : $cutLength * $cutWidth * $constant;
                    if ($constant > 0) {
                        $paperRate = ($multiplicationValue * $perGramRate * $grh * $ply) / $constant;
                    }
                } else {
                    // 3D carton formula.
                    if ($constant > 0) {
                        $divisionValue = $reelLength * $reelHeight * $gsm * $perGramRate;
                        $paperRate = $divisionValue / $constant;
                    }
                }

                $paperRateByLayers = $multiplicationLayer * $paperRate;
                $workAmount = $paperRateByLayers * ((float) $workPct / 100);

                // These are per-unit rates from the client Excel formula. Scale by the
                // sale item quantity to get the commercial totals for the whole order.
                $qty = (float) ($saleItem->qty ?? 1);
                $paperBasis += $paperRateByLayers * $qty;
                $standardProfit += $workAmount * $qty;
                $printTotal += $print * $qty;
                $netRate += ($print + $paperRateByLayers + $workAmount) * $qty;
            }
        }

        // Fallback: if no commercial row could be computed (e.g. non-formula BOM),
        // fall back to the revenue-derived paper basis (only valid when Print = 0).
        if (!$hasCommercialRows && $grossSalesAfn > 0) {
            $paper = $grossSalesAfn / (1 + ($effectiveWorkPercentage / 100));
            $paperBasis = $paper;
            $standardProfit = $paper * ($effectiveWorkPercentage / 100);
            $printTotal = 0.0;
            $netRate = $grossSalesAfn;
        }

        return [
            'paper_basis' => $paperBasis,
            'standard_work_profit' => $standardProfit,
            'print_total' => $printTotal,
            'net_rate' => $netRate,
        ];
    }

    /**
     * Effective physical material rows for a sale item.
     *
     * For MANUAL-BOM sale items the persisted manual_bom_snapshot is the
     * authoritative source of physical parameters (geometry, gsm, layers,
     * wastage) — so the physical quantity reflects the EXACT manual inputs used
     * at quotation time rather than the stale template BOM rows. The landed
     * cost_per_unit_usd is still taken from the corresponding template BOM row
     * (frozen landed-cost source). Non-manual items fall back to the template
     * BOM rows unchanged.
     *
     * @return array<int, array{kg_per_unit:float, wastage_percentage:float, cost_per_unit_usd:float}>
     */
    private function physicalCostRows($saleItem, $bom): array
    {
        $snapshot = is_array($saleItem->manual_bom_snapshot ?? null) ? $saleItem->manual_bom_snapshot : [];
        $bomItems = $bom->items ?? collect();
        $rows = [];

        if (count($snapshot) > 0) {
            $bomItemArray = array_values($bomItems->all());
            foreach ($snapshot as $index => $row) {
                $length = (float) ($row['length'] ?? 0);
                $width = (float) ($row['width'] ?? 0);
                $height = (float) ($row['height'] ?? 0);
                $gsm = (float) ($row['paper_gsm'] ?? 0);
                $layers = (float) ($row['multiplication_layer'] ?? ($row['layers'] ?? 1));
                $wastage = (float) ($row['wastage'] ?? 0);

                $rollWeightMissing = false;
                $costPerUnitUsd = 0.0;
                $materialId = (int) ($row['material_id'] ?? 0);

                if (isset($bomItemArray[$index]) && $bomItemArray[$index]) {
                    $costPerUnitUsd = (float) ($bomItemArray[$index]->cost_per_unit_usd ?? 0);
                    $materialId = $materialId ?: (int) ($bomItemArray[$index]->material_id ?? 0);
                }
                if ($costPerUnitUsd <= 0 && $materialId > 0) {
                    foreach ($bomItemArray as $bi) {
                        if ((int) ($bi->material_id ?? 0) === $materialId
                            && (float) ($bi->cost_per_unit_usd ?? 0) > 0) {
                            $costPerUnitUsd = (float) $bi->cost_per_unit_usd;
                            break;
                        }
                    }
                }

                $costPerUnitUsd = $this->resolveRollCostPerKg($materialId, $costPerUnitUsd);
                $rollWeightMissing = $this->rollWeightBasisMissing($materialId);

                $reelLength = (($length + $width) * 2) + 4;
                $reelHeight = $width + $height + 1;
                $kgPerUnit = ($reelLength > 0 && $reelHeight > 0 && $gsm > 0 && $layers > 0)
                    ? $reelLength * $reelHeight * 0.00064516 * $gsm / 1000 * $layers
                    : 0.0;

                $rows[] = [
                    'kg_per_unit' => $kgPerUnit,
                    'wastage_percentage' => $wastage,
                    'cost_per_unit_usd' => $costPerUnitUsd,
                    'roll_weight_missing' => $rollWeightMissing,
                ];
            }

            return $rows;
        }

        foreach ($bomItems as $bomItem) {
            $materialId = (int) ($bomItem->material_id ?? 0);
            $costPerUnitUsd = (float) ($bomItem->cost_per_unit_usd ?? 0);

            $costPerUnitUsd = $this->resolveRollCostPerKg($materialId, $costPerUnitUsd);
            $rollWeightMissing = $this->rollWeightBasisMissing($materialId);

            $rows[] = [
                'kg_per_unit' => (float) $bomItem->calculateStockKgPerUnit(),
                'wastage_percentage' => (float) ($bomItem->wastage_percentage ?? 0),
                'cost_per_unit_usd' => $costPerUnitUsd,
                'roll_weight_missing' => $rollWeightMissing,
            ];
        }

        return $rows;
    }

    /**
     * Request-scoped memo of per-material lookups keyed by material (product) id.
     * These lookups are deterministic for a given material during a single request
     * (they read purchase/unit/default metadata that never changes mid-render), so
     * memoizing them removes repeated queries without changing any result.
     *
     * @var array<int, array<string, mixed>>
     */
    private $materialMemo = [];

    private function memo(string $key, int $materialId, callable $resolver)
    {
        if (isset($this->materialMemo[$materialId][$key])) {
            return $this->materialMemo[$materialId][$key]['value'];
        }
        $value = $resolver();
        $this->materialMemo[$materialId][$key] = ['value' => $value];
        return $value;
    }

    private function materialProduct(int $materialId)
    {
        return $this->memo('product', $materialId, function () use ($materialId) {
            return Product::find($materialId);
        });
    }

    /**
     * True when the given product is roll-based but has no valid inventory
     * batch with a kg_per_roll, meaning a monetary KG estimate is unavailable.
     */
    private function rollWeightBasisMissing(int $materialId): bool
    {
        if ($materialId <= 0) {
            return false;
        }

        return $this->memo('roll_weight_missing', $materialId, function () use ($materialId) {
            $product = $this->materialProduct($materialId);
            if (!$product || strtolower((string) $product->unit) !== 'roll') {
                return false;
            }

            // A monetary KG estimate is unavailable only when there is NO valid
            // kg basis at all:
            //   1) an arrived roll batch carrying an explicit kg_per_roll, OR
            //   2) an arrived roll batch with a product default_kg_per_roll that can
            //      serve as an ESTIMATION fallback (roll qty x product default).
            $hasKgBatch = PurchaseItem::where('product_id', $materialId)
                ->where('unit', 'roll')
                ->where('kg_per_roll', '>', 0)
                ->whereHas('purchase', function ($q) {
                    $q->where('status', 'arrived');
                })
                ->exists();

            if ($hasKgBatch) {
                return false;
            }

            $hasRollBatchWithoutKg = PurchaseItem::where('product_id', $materialId)
                ->where('unit', 'roll')
                ->where(function ($q) {
                    // Batches whose physical weight was never captured can fall back
                    // to the product default for ESTIMATION only.
                    $q->whereNull('kg_per_roll')->orWhere('kg_per_roll', '<=', 0);
                })
                ->whereHas('purchase', function ($q) {
                    $q->where('status', 'arrived');
                })
                ->exists();

            if ($hasRollBatchWithoutKg && (float) $product->default_kg_per_roll > 0) {
                return false;
            }

            return true;
        });
    }

    /**
     * For roll-based materials, resolve the correct USD/kg from inventory
     * batches instead of using the BOM's USD/roll rate.
     *
     * When a roll product has no valid inventory batch with a kg_per_roll,
     * return 0.0 (no viable KG basis). The UI surfaces this as
     * "Estimated cost unavailable — roll weight required". This prevents the
     * invalid kg × USD/roll calculation.
     *
     * @return float cost_per_unit_usd (USD/kg for roll, original for non-roll)
     */
    private function resolveRollCostPerKg(int $materialId, float $bomCostUsd): float
    {
        if ($materialId <= 0) {
            return $bomCostUsd;
        }

        $product = $this->materialProduct($materialId);
        if (!$product || strtolower((string) $product->unit) !== 'roll') {
            return $bomCostUsd;
        }

        // For roll products the resolved USD/kg depends only on the material's
        // inventory batches, not on the BOM rate, so memoize the heavy lookup by id.
        return $this->memo('roll_cost_per_kg', $materialId, function () use ($materialId, $product) {
            $batch = PurchaseItem::where('product_id', $materialId)
                ->where('unit', 'roll')
                ->where('kg_per_roll', '>', 0)
                ->whereHas('purchase', function ($q) {
                    $q->where('status', 'arrived');
                })
                ->orderByRaw('COALESCE(
                    (SELECT purchase_date FROM purchases WHERE purchases.id = purchase_items.purchase_id),
                    purchase_items.created_at
                ) ASC')
                ->orderBy('purchase_items.id')
                ->first();

            if ($batch) {
                return $batch->landedCostPerKg();
            }

            // ESTIMATION fallback for a roll batch whose physical weight was never
            // captured: use the product's configured default_kg_per_roll as the
            // per-roll weight so a trustworthy USD/kg can be derived:
            //   est. total kg            = batch roll qty x product default kg/roll
            //   est. landed USD/kg       = (batch landed total USD) / est. total kg
            //
            // This only fires for a batch explicitly stored as unit='roll' (so the
            // qty genuinely represents rolls) and with a positive product default.
            // It is an ESTIMATE for pre-production costing only. Actual FIFO
            // consumption still requires a real batch kg_per_roll snapshot.
            $defaultKg = (float) $product->default_kg_per_roll;
            if ($defaultKg > 0) {
                $rollBatch = PurchaseItem::where('product_id', $materialId)
                    ->where('unit', 'roll')
                    ->where(function ($q) {
                        $q->whereNull('kg_per_roll')->orWhere('kg_per_roll', '<=', 0);
                    })
                    ->whereHas('purchase', function ($q) {
                        $q->where('status', 'arrived');
                    })
                    ->orderByRaw('COALESCE(
                        (SELECT purchase_date FROM purchases WHERE purchases.id = purchase_items.purchase_id),
                        purchase_items.created_at
                    ) ASC')
                    ->orderBy('purchase_items.id')
                    ->first();

                if ($rollBatch) {
                    $rolls = (float) $rollBatch->qty;
                    $landedTotalUsd = (float) $rollBatch->usd_total + (float) $rollBatch->expense_per_item;
                    $estKg = $rolls > 0 ? $rolls * $defaultKg : 0.0;
                    if ($estKg > 0) {
                        return $landedTotalUsd / $estKg;
                    }
                }
            }

            // Roll product with no valid kg basis: block the kg × USD/roll mismatch.
            return 0.0;
        });
    }

    /**
     * Commercial calculation from the persisted MANUAL-BOM snapshot.
     *
     * When a sale item was created in manual mode its exact per-row manual inputs
     * are stored in manual_bom_snapshot. This reproduces the client Excel
     * quotation from those rows:
     *   Net Rate = (Paper Rate By Layers) + (Paper Rate By Layers × work%) + Print
     * returning the order-level commercial totals (scaled by the sale quantity).
     *
     * @return array{paper_basis:float, standard_work_profit:float, print_total:float, net_rate:float}|null
     */
    private function manualCommercialCalculation($saleItem): ?array
    {
        $snapshot = is_array($saleItem->manual_bom_snapshot ?? null) ? $saleItem->manual_bom_snapshot : [];
        if (count($snapshot) === 0) {
            return null;
        }

        $qty = (float) ($saleItem->qty ?? 1);
        $paperBasis = 0.0;
        $standardProfit = 0.0;
        $printTotal = 0.0;
        $netRate = 0.0;

        foreach ($snapshot as $row) {
            $length = (float) ($row['length'] ?? 0);
            $width = (float) ($row['width'] ?? 0);
            $height = (float) ($row['height'] ?? 0);
            $gsm = (float) ($row['paper_gsm'] ?? 0);
            $perGramRate = (float) ($row['per_gram_rate'] ?? 0);
            $multiplicationLayer = (float) ($row['multiplication_layer'] ?? 1);
            $formulaConstant = (float) ($row['formula_constant'] ?? 1550000);
            $workPct = (float) ($row['work_percentage'] ?? 40);
            $print = (float) ($row['print_cost'] ?? 0);

            if ($length <= 0 || $width <= 0 || $height <= 0 || $gsm <= 0 || $perGramRate <= 0 || $formulaConstant <= 0) {
                continue;
            }

            $reelLength = (($length + $width) * 2) + 4;
            $reelHeight = $width + $height + 1;
            $divisionValue = $reelLength * $reelHeight * $gsm * $perGramRate;
            $paperRate = $divisionValue / $formulaConstant;
            $paperRateByLayers = $multiplicationLayer * $paperRate;
            $workAmount = $paperRateByLayers * ($workPct / 100);

            // Per-unit rates scaled by the sale quantity => order-level commercial totals.
            $paperBasis += $paperRateByLayers * $qty;
            $standardProfit += $workAmount * $qty;
            $printTotal += $print * $qty;
            $netRate += ($print + $paperRateByLayers + $workAmount) * $qty;
        }

        return [
            'paper_basis' => $paperBasis,
            'standard_work_profit' => $standardProfit,
            'print_total' => $printTotal,
            'net_rate' => $netRate,
        ];
    }

    /**
     * Set-based ACTUAL FIFO production cost per sale for sales that already have
     * material consumption ("realized" cost), fully consistent with calculate():
     * material is summed in USD (matched by sale_id first, else the production
     * order) and the production order's actual labour/overhead/other costs are
     * added in AFN. One set of queries for all given sales, no per-sale calls.
     *
     * @param iterable<int, Sale|object> $sales Sales carrying id, exchange_rate
     *                                          and production_order_id.
     * @return array<int, array{cost_afn: float, cost_usd: float}> Keyed by sale id.
     */
    public static function realizedCostBySales(iterable $sales): array
    {
        $sales = collect($sales);
        $saleIds = $sales->pluck('id')->filter()->map(fn ($id) => (int) $id)->values()->all();
        $poIds = $sales->pluck('production_order_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();

        if (empty($saleIds) || !Schema::hasTable('production_material_consumptions')) {
            return [];
        }

        $consumptions = DB::table('production_material_consumptions')
            ->where(function ($q) use ($saleIds, $poIds) {
                $q->whereIn('sale_id', $saleIds);
                if (!empty($poIds)) {
                    $q->orWhereIn('production_order_id', $poIds);
                }
            })
            ->selectRaw('sale_id, production_order_id, SUM(total_cost_usd) as cost_usd')
            ->groupBy('sale_id', 'production_order_id')
            ->get();

        if ($consumptions->isEmpty()) {
            return [];
        }

        $workAfn = [];
        if (!empty($poIds) && Schema::hasTable('production_orders')) {
            $workAfn = DB::table('production_orders')
                ->whereIn('id', $poIds)
                ->selectRaw('id, COALESCE(actual_labour_cost_afn,0) + COALESCE(actual_overhead_cost_afn,0) + COALESCE(other_direct_cost_afn,0) as work_afn')
                ->pluck('work_afn', 'id')
                ->map(fn ($value) => (float) $value)
                ->all();
        }

        $materialBySale = [];
        $materialByPo = [];
        foreach ($consumptions as $row) {
            $amount = (float) $row->cost_usd;
            $saleId = (int) ($row->sale_id ?? 0);
            $poId = (int) ($row->production_order_id ?? 0);
            if ($saleId > 0) {
                $materialBySale[$saleId] = ($materialBySale[$saleId] ?? 0.0) + $amount;
            }
            if ($poId > 0) {
                $materialByPo[$poId] = ($materialByPo[$poId] ?? 0.0) + $amount;
            }
        }

        $realized = [];
        foreach ($sales as $sale) {
            $saleId = (int) $sale->id;
            $poId = $sale->production_order_id ? (int) $sale->production_order_id : 0;

            $materialUsd = $materialBySale[$saleId] ?? null;
            if ($materialUsd === null && $poId > 0) {
                $materialUsd = $materialByPo[$poId] ?? null;
            }
            if ($materialUsd === null) {
                continue;
            }

            $rate = max((float) ($sale->exchange_rate ?? 1), 0.000001);
            $work = $poId > 0 ? (float) ($workAfn[$poId] ?? 0.0) : 0.0;
            $costAfn = ($materialUsd * $rate) + $work;

            $realized[$saleId] = [
                'cost_afn' => $costAfn,
                'cost_usd' => $costAfn / $rate,
            ];
        }

        return $realized;
    }

    /**
     * Calculate production material requirements for a sale, aligned with the
     * authoritative physical costing rules used by SaleProfitService::calculate().
     *
     * Uses the same physicalCostRows() / resolveRollCostPerKg() chain so that
     * Production Create and Sale show identical estimated material costs.
     *
     * @return array<int, array{material_id:int, material_name:string, category:string, total_required:float, base_kg:float, wastage_kg:float, wastage_percentage:float, unit:string, cost_per_unit_usd:float, cost_per_unit_afn:float, total_cost_usd:float, total_cost_afn:float, available_stock:float, shortage:float, is_available:bool, roll_weight_missing:bool}>
     */
    public function productionMaterialRequirements(Sale $sale, float $quantity): array
    {
        $sale->loadMissing([
            'items',
            'items.bom.items',
            'items.bom.items.material',
            'items.product',
            'currency',
        ]);

        $exchangeRate = max((float) ($sale->exchange_rate ?? 1), 0.000001);
        $requirements = [];

        foreach ($sale->items as $saleItem) {
            $bom = $saleItem->bom;
            if (!$bom) {
                continue;
            }

            $bomItems = $bom->items ?? collect();
            $bomItemArray = array_values($bomItems->all());
            $snapshot = is_array($saleItem->manual_bom_snapshot ?? null)
                ? $saleItem->manual_bom_snapshot
                : [];

            $costRows = $this->physicalCostRows($saleItem, $bom);

            foreach ($costRows as $index => $row) {
                // ─── Resolve material_id from snapshot or BOM item ───
                $materialId = 0;
                if (count($snapshot) > 0 && isset($snapshot[$index])) {
                    $materialId = (int) ($snapshot[$index]['material_id'] ?? 0);
                    if ($materialId <= 0 && isset($bomItemArray[$index])) {
                        $materialId = (int) ($bomItemArray[$index]->material_id ?? 0);
                    }
                } elseif (isset($bomItemArray[$index])) {
                    $materialId = (int) ($bomItemArray[$index]->material_id ?? 0);
                }

                if ($materialId <= 0) {
                    continue;
                }

                $material = Product::find($materialId);
                if (!$material) {
                    continue;
                }

                $kgPerUnit = (float) $row['kg_per_unit'];
                $wastage = (float) $row['wastage_percentage'];
                $costPerUnitUsd = (float) $row['cost_per_unit_usd'];
                $rollWeightMissing = (bool) ($row['roll_weight_missing'] ?? false);

                $baseKg = $kgPerUnit * $quantity;
                $withWastageKg = $baseKg * (1 + $wastage / 100);
                $wastageKg = max(0, $withWastageKg - $baseKg);

                $isRoll = strtolower((string) $material->unit) === 'roll';
                $availableStock = $isRoll
                    ? (float) $material->current_stock_kg
                    : (float) $material->current_stock;

                $totalCostUsd = $withWastageKg * $costPerUnitUsd;
                $totalCostAfn = $totalCostUsd * $exchangeRate;
                $shortage = max(0, $withWastageKg - $availableStock);

                $requirements[] = [
                    'material_id' => $materialId,
                    'material_name' => $material->name ?? 'Unknown',
                    'category' => $material->category->name ?? 'Uncategorized',
                    'total_required' => round($withWastageKg, 4),
                    'base_kg' => round($baseKg, 4),
                    'wastage_kg' => round($wastageKg, 4),
                    'wastage_percentage' => $wastage,
                    'unit' => 'kg',
                    'cost_per_unit_usd' => round($costPerUnitUsd, 6),
                    'cost_per_unit_afn' => round($costPerUnitUsd * $exchangeRate, 4),
                    'total_cost_usd' => round($totalCostUsd, 4),
                    'total_cost_afn' => round($totalCostAfn, 4),
                    'available_stock' => round($availableStock, 4),
                    'shortage' => round($shortage, 4),
                    'is_available' => $shortage <= 0,
                    'roll_weight_missing' => $rollWeightMissing,
                ];
            }
        }

        return $requirements;
    }
}
