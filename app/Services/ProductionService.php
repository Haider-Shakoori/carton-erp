<?php
// app/Services/ProductionService.php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ProductionOrder;
use App\Models\BOM;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductionService
{
    private function cartonSpecification(): CartonSpecificationService
    {
        return app(CartonSpecificationService::class);
    }

    /**
     * Create production order from sale.
     */
    public function createProductionFromSale(Sale $sale): ?ProductionOrder
    {
        if ($sale->is_produced) {
            throw new \Exception('This sale has already been produced.');
        }

        $saleItems = $sale->items()->with(['product', 'bom'])->get();

        if ($saleItems->isEmpty()) {
            throw new \Exception('No items found in this sale.');
        }

        Log::info('Creating production from sale', [
            'sale_id' => $sale->id,
            'sale_no' => $sale->sale_no,
            'item_count' => $saleItems->count(),
        ]);

        // Validate BOMs here, but do not reject a sale because stock is below
        // the ordered quantity. Start Production now allocates the maximum quantity
        // supported by current raw material and completion records the real output.
        // A sale item carrying a frozen carton specification does not need a live
        // BOM: production consumes the frozen technical rows instead.
        foreach ($saleItems as $item) {
            if (! empty($this->cartonSpecification()->frozenRows($item))) {
                continue;
            }

            $bom = $item->bom;
            if (!$bom) {
                $bom = BOM::where('product_id', $item->product_id)
                    ->where('status', 'active')
                    ->where('is_active', true)
                    ->with(['items.material'])
                    ->first();
            }

            if (!$bom) {
                throw new \Exception("No active BOM found for product: {$item->product->name}");
            }
        }

        // ─── Create production orders for each item ───
        $productionOrders = [];
        foreach ($saleItems as $item) {
            $snapshotRows = $this->cartonSpecification()->frozenRows($item);

            if (! empty($snapshotRows)) {
                $productionOrder = $this->createProductionOrderFromFrozenSpec(
                    $item,
                    $item->bom,
                    $sale
                );

                if ($productionOrder) {
                    $productionOrders[] = $productionOrder;
                }

                continue;
            }

            $bom = $item->bom;
            if (!$bom) {
                $bom = BOM::where('product_id', $item->product_id)
                    ->where('status', 'active')
                    ->where('is_active', true)
                    ->with(['items.material'])
                    ->first();
            }

            if ($bom) {
                $productionOrder = $this->createProductionOrderFromSaleItem($item, $bom, $sale);
                if ($productionOrder) {
                    $productionOrders[] = $productionOrder;
                }
            }
        }

        if (empty($productionOrders)) {
            throw new \Exception('No production orders could be created.');
        }

        // ─── Link sale to production order ───
        $sale->production_order_id = $productionOrders[0]->id;
        $sale->is_produced = false;
        $sale->save();

        Log::info('Production orders created from sale', [
            'sale_id' => $sale->id,
            'production_order_ids' => array_column($productionOrders, 'id'),
        ]);

        return $productionOrders[0];
    }

    /**
     * Create individual production order from sale item.
     */
    public function createProductionOrderFromSaleItem($saleItem, $bom, $sale)
    {
        if ($sale->production_order_id) {
            return ProductionOrder::findOrFail($sale->production_order_id);
        }

        // ─── GET ALL DATA FROM SALEITEM ───
        $quantity = (float) $saleItem->qty;
        $exchangeRate = $sale->exchange_rate ?? 66;

        $bom->loadMissing('items.material');
        $availability = $this->checkMaterialAvailability($bom, $quantity);
        $materialDetails = collect($availability['requirements'])->map(function (array $material) {
            return [
                'material_id' => $material['material_id'],
                'required_quantity' => $material['total_required'],
                'available_stock' => $material['available_stock'],
                'unit' => $material['unit'],
                'cost_per_unit_usd' => $material['cost_per_unit'],
                'total_cost_usd' => $material['total_cost'],
            ];
        })->all();
        $materialCostUsd = (float) $availability['total_cost'];
        $laborCostUsd = ((float) ($bom->labor_cost_per_unit ?? 0) / max($exchangeRate, 0.000001)) * $quantity;
        $overheadCostUsd = ((float) ($bom->overhead_cost_per_unit ?? 0) / max($exchangeRate, 0.000001)) * $quantity;
        $costBreakdown = [
            'bom_id' => $bom->id,
            'total_material_cost_usd' => $materialCostUsd,
            'labor_cost_usd' => $laborCostUsd,
            'overhead_cost_usd' => $overheadCostUsd,
            'total_cost_usd' => $materialCostUsd + $laborCostUsd + $overheadCostUsd,
            'material_details' => $materialDetails,
        ];

        Log::info('Production order from SaleItem', [
            'sale_item_id' => $saleItem->id,
            'sale_id' => $sale->id,
            'quantity' => $quantity,
            'exchange_rate' => $exchangeRate,
            'cost_breakdown' => $costBreakdown,
        ]);

        // ─── CREATE PRODUCTION ORDER ───
        $productionOrder = ProductionOrder::create([
            'order_number' => 'PROD-' . date('Y') . '-' . strtoupper(Str::random(8)),
            'product_id' => $saleItem->product_id,
            'bom_id' => $costBreakdown['bom_id'],
            'quantity_ordered' => $quantity,
            'quantity_produced' => 0,
            'status' => 'pending',
            'start_date' => now(),
            'created_by' => auth()->id(),
            'total_material_cost' => $costBreakdown['total_material_cost_usd'],
            'total_labor_cost' => $costBreakdown['labor_cost_usd'],
            'total_overhead_cost' => $costBreakdown['overhead_cost_usd'],
            'total_cost' => $costBreakdown['total_cost_usd'],
            'notes' => "Created from Sale #{$sale->sale_no} - SaleItem ID: {$saleItem->id}",
        ]);

        Log::info('Production order created from SaleItem', [
            'production_order_id' => $productionOrder->id,
            'order_number' => $productionOrder->order_number,
            'total_material_cost' => $productionOrder->total_material_cost,
            'total_labor_cost' => $productionOrder->total_labor_cost,
            'total_overhead_cost' => $productionOrder->total_overhead_cost,
            'total_cost' => $productionOrder->total_cost,
        ]);

        // ─── CREATE PRODUCTION ORDER MATERIALS ───
        foreach ($costBreakdown['material_details'] as $material) {
            $productionOrder->materials()->create([
                'product_id' => $material['material_id'],
                'required_quantity' => $material['required_quantity'],
                'available_quantity' => $material['available_stock'] ?? 0,
                'shortage_quantity' => max(0, $material['required_quantity'] - ($material['available_stock'] ?? 0)),
                'unit' => $material['unit'] ?? 'unit',
                'cost_per_unit' => $material['cost_per_unit_usd'] ?? 0,
                'total_cost' => $material['total_cost_usd'] ?? 0,
                'consumed_quantity' => 0,
            ]);
        }

        return $productionOrder;
    }

    /**
     * Create a production order from a FROZEN carton specification.
     *
     * The accepted/frozen technical rows are authoritative: production never
     * falls back to today's live board profile defaults when a sale snapshot
     * exists. Requirements are aggregated per material before deduction so a
     * material appearing in several technical rows is only consumed once.
     */
    public function createProductionOrderFromFrozenSpec($saleItem, $bom, $sale): ?ProductionOrder
    {
        if ($sale->production_order_id) {
            return ProductionOrder::findOrFail($sale->production_order_id);
        }

        $snapshot = $saleItem->carton_spec_snapshot;

        if (! is_array($snapshot) || empty($snapshot['rows'])) {
            return null;
        }

        $quantity = (float) $saleItem->qty;
        $exchangeRate = max((float) ($sale->exchange_rate ?? 66), 0.000001);

        if ($quantity <= 0) {
            throw new \Exception('Sale item quantity must be greater than zero.');
        }

        $bom ??= BOM::where('product_id', $saleItem->product_id)
            ->where('status', 'active')
            ->where('is_active', true)
            ->first();

        if (! $bom) {
            throw new \Exception(
                "No BOM found for product: {$saleItem->product?->name}. A technical BOM is required for production."
            );
        }

        $requirements = $this->cartonSpecification()->requirementsFromSnapshot($snapshot, $quantity);
        $stockService = app(StockDeductionService::class);

        $materialDetails = [];
        $materialCostUsd = 0.0;
        $hasShortage = false;

        foreach ($requirements as $requirement) {
            $materialId = (int) $requirement['material_id'];
            $required = (float) $requirement['required_quantity'];
            $costPerUnitUsd = (float) $requirement['cost_per_unit_usd'];
            $available = $stockService->availableProductionQuantity($materialId);
            $shortage = max($required - $available, 0.0);

            if ($shortage > 0.000001) {
                $hasShortage = true;
            }

            $totalCostUsd = $required * $costPerUnitUsd;
            $materialCostUsd += $totalCostUsd;

            $materialDetails[] = [
                'material_id' => $materialId,
                'required_quantity' => $required,
                'available_quantity' => $available,
                'shortage_quantity' => $shortage,
                'unit' => $requirement['unit'] ?? 'kg',
                'cost_per_unit_usd' => $costPerUnitUsd,
                'total_cost_usd' => $totalCostUsd,
            ];
        }

        $laborCostUsd = ((float) ($bom->labor_cost_per_unit ?? 0) / $exchangeRate) * $quantity;
        $overheadCostUsd = ((float) ($bom->overhead_cost_per_unit ?? 0) / $exchangeRate) * $quantity;

        Log::info('Production order from frozen carton specification', [
            'sale_item_id' => $saleItem->id,
            'sale_id' => $sale->id,
            'quantity' => $quantity,
            'material_count' => count($materialDetails),
            'has_shortage' => $hasShortage,
        ]);

        $productionOrder = ProductionOrder::create([
            'order_number' => 'PROD-' . date('Y') . '-' . strtoupper(Str::random(8)),
            'product_id' => $saleItem->product_id,
            'bom_id' => $bom->id,
            'quantity_ordered' => $quantity,
            'quantity_produced' => 0,
            'status' => 'pending',
            'start_date' => now(),
            'created_by' => auth()->id(),
            'total_material_cost' => $materialCostUsd,
            'total_labor_cost' => $laborCostUsd,
            'total_overhead_cost' => $overheadCostUsd,
            'total_cost' => $materialCostUsd + $laborCostUsd + $overheadCostUsd,
            'notes' => "Created from frozen carton specification - Sale #{$sale->sale_no} - SaleItem ID: {$saleItem->id}",
        ]);

        foreach ($materialDetails as $material) {
            $productionOrder->materials()->create([
                'product_id' => $material['material_id'],
                'required_quantity' => $material['required_quantity'],
                'available_quantity' => $material['available_quantity'],
                'shortage_quantity' => $material['shortage_quantity'],
                'unit' => $material['unit'],
                'cost_per_unit' => $material['cost_per_unit_usd'],
                'total_cost' => $material['total_cost_usd'],
                'consumed_quantity' => 0,
            ]);
        }

        return $productionOrder;
    }

    /**
     * Calculate weighted average cost for a product.
     */
    private function calculateWeightedAverageCost($productId)
    {
        // ─── DEBUG: Log the product being calculated ───
        Log::info('Calculating weighted average cost', ['product_id' => $productId]);

        $purchaseItems = PurchaseItem::where('product_id', $productId)
            ->where('qty_available', '>', 0)
            ->whereHas('purchase', function($q) {
                $q->where('status', 'arrived');
            })
            ->get();

        // ─── DEBUG: Log what was found ───
        Log::info('Purchase items found', [
            'product_id' => $productId,
            'count' => $purchaseItems->count(),
            'items' => $purchaseItems->map(function($item) {
                return [
                    'id' => $item->id,
                    'qty_available' => $item->qty_available,
                    'cost_per_unit' => $item->cost_per_unit,
                    'usd_unit_price' => $item->usd_unit_price,
                    'usd_total' => $item->usd_total,
                ];
            }),
        ]);

        if ($purchaseItems->isEmpty()) {
            Log::warning('No purchase items found for product', ['product_id' => $productId]);
            return 0;
        }

        $totalCost = 0;
        $totalQty = 0;

        foreach ($purchaseItems as $item) {
            // ─── FIX: Use the correct cost field ───
            // cost_per_unit is the correct field for USD cost per unit
            $costPerUnit = (float) $item->cost_per_unit;

            // If cost_per_unit is 0, try usd_unit_price
            if ($costPerUnit <= 0) {
                $costPerUnit = (float) $item->usd_unit_price;
            }

            // If still 0, calculate from usd_total / qty
            if ($costPerUnit <= 0 && $item->qty > 0) {
                $costPerUnit = (float) $item->usd_total / (float) $item->qty;
            }

            // Roll-based paper batches are costed per kg (physical weight).
            $quantityUsedForWeightedAvg = $item->isRollBatch()
                ? (float) $item->qty_kg_available
                : (float) $item->qty_available;

            if ($item->isRollBatch()) {
                $costPerUnit = $item->landedCostPerKg();
            }

            $totalCost += $quantityUsedForWeightedAvg * $costPerUnit;
            $totalQty += $quantityUsedForWeightedAvg;

            Log::info('Item cost calculation', [
                'item_id' => $item->id,
                'is_roll' => $item->isRollBatch(),
                'qty_used_for_avg' => $quantityUsedForWeightedAvg,
                'cost_per_unit' => $costPerUnit,
                'total_cost' => $quantityUsedForWeightedAvg * $costPerUnit,
            ]);
        }

        if ($totalQty <= 0) {
            Log::warning('Total quantity is 0 for product', ['product_id' => $productId]);
            return 0;
        }

        $weightedAvg = $totalCost / $totalQty;

        Log::info('Weighted average cost calculated', [
            'product_id' => $productId,
            'total_cost' => $totalCost,
            'total_qty' => $totalQty,
            'weighted_avg' => $weightedAvg,
        ]);

        return $weightedAvg;
    }


    private function debugPurchaseItems($productId)
    {
        $purchaseItems = PurchaseItem::where('product_id', $productId)
            ->where('qty_available', '>', 0)
            ->whereHas('purchase', function($q) {
                $q->where('status', 'arrived');
            })
            ->with(['purchase', 'purchase.currency'])
            ->get();

        Log::info('Purchase Items Debug', [
            'product_id' => $productId,
            'total_items' => $purchaseItems->count(),
            'items' => $purchaseItems->map(function($item) {
                return [
                    'id' => $item->id,
                    'purchase_id' => $item->purchase_id,
                    'purchase_no' => $item->purchase->purchase_no ?? 'N/A',
                    'product_id' => $item->product_id,
                    'qty' => $item->qty,
                    'qty_available' => $item->qty_available,
                    'cost_per_unit' => $item->cost_per_unit,
                    'usd_unit_price' => $item->usd_unit_price,
                    'usd_total' => $item->usd_total,
                    'rate' => $item->rate,
                    'batch_no' => $item->batch_no,
                ];
            }),
        ]);

        return $purchaseItems;
    }

    /**
     * Start and complete production.
     */
    public function startAndCompleteProduction(
        $productionOrder,
        $sale = null,
        ?float $actualQuantity = null
    ) {
        if ($actualQuantity === null) {
            throw new \RuntimeException(
                'Automatic production completion is disabled. Enter the real produced quantity when production ends.'
            );
        }

        $quantityService = app(\App\Services\ProductionQuantityService::class);
        $quantityService->start($productionOrder, $sale);

        return $quantityService->complete(
            $productionOrder->fresh(),
            $actualQuantity,
            $sale
        );
    }

    public function startProduction($productionOrder)
    {
        return app(\App\Services\ProductionQuantityService::class)
            ->start($productionOrder, $productionOrder->sale()->with('items')->first());
    }

    public function completeProduction(
        $productionOrder,
        $sale = null,
        ?float $actualQuantity = null
    ) {
        if ($actualQuantity === null) {
            throw new \RuntimeException(
                'Actual produced quantity is required to complete production.'
            );
        }

        app(\App\Services\ProductionQuantityService::class)
            ->complete($productionOrder, $actualQuantity, $sale);

        return $productionOrder->fresh();
    }

    private function consumeMaterial($material)
    {
        $purchaseItems = PurchaseItem::where('product_id', $material->product_id)
            ->where('qty_available', '>', 0)
            ->whereHas('purchase', function($q) {
                $q->where('status', 'arrived');
            })
            ->orderBy('created_at', 'asc')
            ->get();

        if ($purchaseItems->isEmpty()) {
            throw new \Exception("No stock available for material: {$material->product->name}");
        }

        $rollBatches = $purchaseItems->filter(fn ($item) => $item->isRollBatch());
        $isRoll = $rollBatches->isNotEmpty();

        $remainingRequired = (float) $material->required_quantity;
        $consumed = 0;

        foreach ($purchaseItems as $purchaseItem) {
            if ($remainingRequired <= 0) break;

            if ($isRoll && $purchaseItem->isRollBatch()) {
                $availableKg = (float) $purchaseItem->qty_kg_available;
                $toUseKg = min($availableKg, $remainingRequired);
                if ($toUseKg <= 0) continue;

                $toUseRolls = $toUseKg / max((float) $purchaseItem->kg_per_roll, 0.000001);

                $purchaseItem->qty_kg_used = ($purchaseItem->qty_kg_used ?? 0) + $toUseKg;
                $purchaseItem->qty_kg_available = $availableKg - $toUseKg;
                $purchaseItem->qty_used = ($purchaseItem->qty_used ?? 0) + $toUseRolls;
                $purchaseItem->qty_available = (float) $purchaseItem->qty_available - $toUseRolls;
                $purchaseItem->save();

                $remainingRequired -= $toUseKg;
                $consumed += $toUseKg;
            } else {
                $available = (float) $purchaseItem->qty_available;
                $toUse = min($available, $remainingRequired);

                $purchaseItem->qty_used = ($purchaseItem->qty_used ?? 0) + $toUse;
                $purchaseItem->qty_available = $purchaseItem->qty_available - $toUse;
                $purchaseItem->save();

                $remainingRequired -= $toUse;
                $consumed += $toUse;
            }
        }

        if ($remainingRequired > 0) {
            throw new \Exception("Insufficient stock for: {$material->product->name}. Shortage: {$remainingRequired} {$material->unit}");
        }

        $material->consumed_quantity = $consumed;
        $material->save();

        Log::info('Material consumed', [
            'material_id' => $material->id,
            'product_id' => $material->product_id,
            'is_roll' => $isRoll,
            'consumed_quantity' => $consumed,
        ]);

        return $material;
    }

    /**
     * Check material availability for a BOM.
     */
    private function checkMaterialAvailability($bom, $quantity)
    {
        $requirements = [];
        $shortages = [];
        $hasShortage = false;
        $totalCost = 0;

        foreach ($bom->items as $item) {
            $wastagePercent = (float) $item->wastage_percentage / 100;
            $requiredQty = $item->calculateStockRequirement((float) $quantity, false);
            $totalRequired = $item->calculateStockRequirement((float) $quantity, true);
            $wastageQty = $totalRequired - $requiredQty;

            // Roll-based paper materials are stocked/consumed in kg, matching the
            // kg output of calculateStockRequirement. Everything else uses native stock.
            $materialIsRoll = (bool) ($item->material->is_roll_based ?? false);
            $availableStock = $materialIsRoll
                ? (float) ($item->material->current_stock_kg ?? 0)
                : (float) ($item->material->current_stock ?? 0);
            $shortage = max(0, $totalRequired - $availableStock);

            if ($shortage > 0) {
                $hasShortage = true;
                $shortages[] = [
                    'material_id' => $item->material_id,
                    'material_name' => $item->material->name ?? 'Unknown',
                    'total_required' => $totalRequired,
                    'available_stock' => $availableStock,
                    'shortage' => $shortage,
                    'unit' => $item->unit,
                ];
            }

            // For roll-based materials the physical requirement and cost are on a
            // kg basis (landed USD/kg from the purchase batch), matching inventory
            // and the authoritative FIFO consumption. All other materials keep the
            // BOM unit and BOM per-unit commercial cost exactly as before.
            if ($materialIsRoll) {
                $rollBatch = PurchaseItem::where('product_id', $item->material_id)
                    ->where('unit', 'roll')
                    ->where('qty_kg_available', '>', 0)
                    ->whereHas('purchase', function ($q) { $q->where('status', 'arrived'); })
                    ->orderBy('created_at', 'asc')
                    ->first();
                $costPerUnit = $rollBatch ? (float) $rollBatch->landedCostPerKg() : 0;
                $unitForReq = 'kg';
            } else {
                $costPerUnit = (float) $item->cost_per_unit_usd;
                $unitForReq = $item->unit;
            }
            $itemTotalCost = $totalRequired * $costPerUnit;
            $totalCost += $itemTotalCost;

            $requirements[] = [
                'material_id' => $item->material_id,
                'material_name' => $item->material->name ?? 'Unknown',
                'required_quantity' => $requiredQty,
                'wastage_quantity' => $wastageQty,
                'total_required' => $totalRequired,
                'available_stock' => $availableStock,
                'shortage' => $shortage,
                'unit' => $unitForReq,
                'cost_per_unit' => $costPerUnit,
                'total_cost' => $itemTotalCost,
            ];
        }

        return [
            'has_shortage' => $hasShortage,
            'shortages' => $shortages,
            'requirements' => $requirements,
            'total_cost' => $totalCost,
        ];
    }
}
