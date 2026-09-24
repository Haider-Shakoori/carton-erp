<?php

namespace App\Services;

use App\Models\ProductionMaterialConsumption;
use App\Models\ProductionOrder;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProductionQuantityService
{
    private const EPSILON = 0.000001;

    public function __construct(
        private readonly StockDeductionService $stockService
    ) {
    }

    /**
     * Start production using as much of the original order quantity as current
     * raw material can support. A shortage no longer blocks production entirely.
     */
    public function start(
        ProductionOrder $order,
        ?Sale $sale = null,
        ?float $plannedQuantity = null
    ): array {
        return DB::transaction(function () use ($order, $sale, $plannedQuantity): array {
            $order->refresh()->load(['materials.product', 'bom.items.material', 'product']);

            if ($order->status !== ProductionOrder::STATUS_PENDING) {
                throw new RuntimeException(
                    'Production order must be pending before it can be started.'
                );
            }

            $approvalRequired = (bool) (Setting::query()->value('production_approval_required') ?? false);
            if ($approvalRequired && ! $order->approved_at) {
                throw new RuntimeException(
                    'Supervisor approval is required before production can start.'
                );
            }

            $sale ??= $order->sale()->with('items')->first();

            $orderedQty = (float) $order->quantity_ordered;
            if ($orderedQty <= self::EPSILON) {
                throw new RuntimeException('Production order quantity must be greater than zero.');
            }

            $plannedQty = $plannedQuantity ?? $orderedQty;
            if ($plannedQty <= self::EPSILON) {
                throw new RuntimeException('Planned production quantity must be greater than zero.');
            }

            $maxProducible = $this->maxProducibleQuantity($order);
            if ($maxProducible <= self::EPSILON) {
                throw new RuntimeException(
                    'Production cannot start because the available raw material cannot produce any finished quantity.'
                );
            }

            if ($plannedQty > $maxProducible + self::EPSILON) {
                throw new RuntimeException(sprintf(
                    'Requested production quantity %.2f exceeds the %.2f units supported by current raw material.',
                    $plannedQty,
                    $maxProducible
                ));
            }

            $requirements = $this->requirementsForQuantity($order, $plannedQty);
            $saleItem = $sale ? $this->resolveSaleItem($sale, $order) : null;

            if ($saleItem) {
                foreach ($requirements as &$row) {
                    $row['sale_item_id'] = $saleItem->id;
                }
                unset($row);
            }

            $availability = $this->stockService->checkAvailability($requirements);
            if (! $availability['available']) {
                $shortages = collect($availability['materials'])
                    ->filter(fn (array $row) => ! $row['available'])
                    ->map(fn (array $row) => sprintf(
                        '%s: need %.4f %s, available %.4f %s',
                        $row['material_name'] ?? ('Material #' . $row['material_id']),
                        $row['required_quantity'],
                        $row['unit'] ?? 'unit',
                        $row['available_quantity'],
                        $row['unit'] ?? 'unit'
                    ))
                    ->implode('; ');

                throw new RuntimeException('Unable to allocate raw material. ' . $shortages);
            }

            $consumptions = $this->stockService->deductMaterials(
                $order->id,
                $sale?->id,
                $requirements
            );

            $materialCostUsd = (float) $consumptions->sum('total_cost_usd');
            $laborPerUnitUsd = (float) $order->total_labor_cost / max($orderedQty, self::EPSILON);
            $overheadPerUnitUsd = (float) $order->total_overhead_cost / max($orderedQty, self::EPSILON);

            $order->quantity_planned = $plannedQty;
            $order->total_material_cost = $materialCostUsd;
            $order->total_labor_cost = $laborPerUnitUsd * $plannedQty;
            $order->total_overhead_cost = $overheadPerUnitUsd * $plannedQty;
            $order->total_cost = $materialCostUsd
                + (float) $order->total_labor_cost
                + (float) $order->total_overhead_cost;
            $order->status = ProductionOrder::STATUS_IN_PROGRESS;
            $order->start_date = $order->start_date ?: now()->toDateString();
            $order->reversed_by = null;
            $order->reversed_at = null;
            $order->save();

            app(AccountingService::class)->postProductionMaterialIssue($order);
            app(ProductionControlService::class)->recordStarted($order);

            return [
                'allocation_quantity' => $plannedQty,
                'planned_quantity' => $plannedQty,
                'ordered_quantity' => $orderedQty,
                'max_producible_quantity' => $maxProducible,
                'partial_start' => $plannedQty + self::EPSILON < $orderedQty,
                'over_order_start' => $plannedQty > $orderedQty + self::EPSILON,
                'variance_quantity' => $plannedQty - $orderedQty,
                'material_cost_usd' => $materialCostUsd,
                'consumption_count' => $consumptions->count(),
            ];
        });
    }

    /**
     * Complete production using real shop-floor quantities.
     *
     * quantity_produced remains the good/usable finished quantity for backward
     * compatibility with invoices and existing reports. quantity_manufactured
     * stores total physical output including rejected/scrap cartons.
     *
     * When actualMaterials is supplied, those quantities become authoritative
     * for FIFO stock and actual production cost. The BOM remains the planned
     * comparison baseline only.
     */
    public function complete(
        ProductionOrder $order,
        float $goodQuantity,
        ?Sale $sale = null,
        ?float $manufacturedQuantity = null,
        ?float $rejectedQuantity = null,
        ?array $actualMaterials = null
    ): array {
        if ($goodQuantity <= self::EPSILON) {
            throw new RuntimeException('Good/actual finished quantity must be greater than zero.');
        }

        $manufacturedQuantity ??= $goodQuantity;
        $rejectedQuantity ??= max($manufacturedQuantity - $goodQuantity, 0.0);

        if ($manufacturedQuantity <= self::EPSILON) {
            throw new RuntimeException('Manufactured quantity must be greater than zero.');
        }

        if ($rejectedQuantity < -self::EPSILON) {
            throw new RuntimeException('Rejected quantity cannot be negative.');
        }

        if (abs($manufacturedQuantity - ($goodQuantity + $rejectedQuantity)) > 0.01) {
            throw new RuntimeException(
                'Manufactured quantity must equal good/actual finished quantity plus rejected quantity.'
            );
        }

        return DB::transaction(function () use (
            $order,
            $goodQuantity,
            $sale,
            $manufacturedQuantity,
            $rejectedQuantity,
            $actualMaterials
        ): array {
            $order = ProductionOrder::query()
                ->lockForUpdate()
                ->findOrFail($order->id);
            $order->load(['materials.product', 'bom.items.material', 'product']);

            if ($order->status !== ProductionOrder::STATUS_IN_PROGRESS) {
                throw new RuntimeException('Only in-progress production orders can be completed.');
            }

            $sale ??= $order->sale()->with(['items', 'currency'])->first();
            $saleItem = $sale ? $this->resolveSaleItem($sale, $order) : null;

            app(ProductionControlService::class)
                ->snapshotBeforeCompletion($order, $sale, $saleItem);

            $materialResult = $actualMaterials !== null
                ? $this->reconcileMaterialsToActuals($order, $actualMaterials, $sale, $saleItem)
                : $this->reconcileMaterialsToQuantity($order, $goodQuantity, $sale, $saleItem);

            $costBasisQty = max(
                (float) ($order->quantity_planned ?: $order->quantity_ordered),
                self::EPSILON
            );
            $labourPerUnit = (float) $order->total_labor_cost / $costBasisQty;
            $overheadPerUnit = (float) $order->total_overhead_cost / $costBasisQty;

            $order->quantity_manufactured = $manufacturedQuantity;
            $order->quantity_produced = $goodQuantity;
            $order->quantity_rejected = $rejectedQuantity;
            $order->total_material_cost = $materialResult['material_cost_usd'];
            $order->total_labor_cost = $labourPerUnit * $manufacturedQuantity;
            $order->total_overhead_cost = $overheadPerUnit * $manufacturedQuantity;
            $order->total_cost = $order->total_material_cost
                + $order->total_labor_cost
                + $order->total_overhead_cost;
            $order->status = ProductionOrder::STATUS_COMPLETED;
            $order->completion_date = now();
            $order->save();

            $invoiceResult = null;
            if ($sale) {
                if (! $saleItem) {
                    throw new RuntimeException(
                        'The linked sale item could not be identified, so the final invoice was not changed.'
                    );
                }

                // Only good/usable cartons are invoiceable/deliverable.
                $invoiceResult = $this->syncFinalInvoice(
                    $sale,
                    $saleItem,
                    $order,
                    $goodQuantity,
                    $materialResult['material_cost_usd']
                );
            }

            $plannedQty = (float) ($order->quantity_planned ?: $order->quantity_ordered);

            app(AccountingService::class)->postProductionCompletion($order);

            app(ProductionControlService::class)->recordCompleted($order, [
                'manufactured_quantity' => $manufacturedQuantity,
                'good_quantity' => $goodQuantity,
                'rejected_quantity' => $rejectedQuantity,
                'material_cost_usd' => $materialResult['material_cost_usd'],
            ]);

            return [
                'ordered_quantity' => (float) $order->quantity_ordered,
                'planned_quantity' => $plannedQty,
                'manufactured_quantity' => $manufacturedQuantity,
                'actual_quantity' => $goodQuantity,
                'good_quantity' => $goodQuantity,
                'rejected_quantity' => $rejectedQuantity,
                'variance_quantity' => $goodQuantity - (float) $order->quantity_ordered,
                'manufactured_variance_quantity' => $manufacturedQuantity - $plannedQty,
                'yield_percentage' => $manufacturedQuantity > self::EPSILON
                    ? ($goodQuantity / $manufacturedQuantity) * 100
                    : 0.0,
                'material_cost_usd' => $materialResult['material_cost_usd'],
                'material_cost_afn' => $materialResult['material_cost_afn'],
                'invoice' => $invoiceResult,
            ];
        });
    }

    public function maxProducibleQuantity(ProductionOrder $order): float
    {
        $requirements = $this->requirementsForQuantity($order, 1.0);
        if (empty($requirements)) {
            return 0.0;
        }

        $limits = [];
        foreach ($requirements as $row) {
            $perUnit = (float) $row['quantity'];
            if ($perUnit <= self::EPSILON) {
                continue;
            }

            $available = $this->stockService->availableProductionQuantity(
                (int) $row['material_id']
            );
            $limits[] = $available / $perUnit;
        }

        if (empty($limits)) {
            return 0.0;
        }

        return max(min($limits), 0.0);
    }

    /**
     * Material requirement in the production inventory unit for a finished qty.
     */
    public function requirementsForQuantity(ProductionOrder $order, float $quantity): array
    {
        if ($quantity <= self::EPSILON) {
            return [];
        }

        $order->loadMissing(['materials.product', 'bom.items.material']);
        $orderedQty = max((float) $order->quantity_ordered, self::EPSILON);
        $consumptionMetadata = $this->standardConsumptionMetadata($order);

        if ($order->materials->isNotEmpty()) {
            $ratio = $quantity / $orderedQty;

            return $order->materials
                ->map(function ($material) use ($ratio, $consumptionMetadata) {
                    $meta = $consumptionMetadata->get((int) $material->product_id, []);

                    return [
                        'material_id' => (int) $material->product_id,
                        'quantity' => (float) $material->required_quantity * $ratio,
                        'planned_quantity' => (float) $material->required_quantity * $ratio,
                        'wastage_quantity' => 0.0,
                        'unit' => $material->unit ?: 'unit',
                        'material_name' => $material->product->name
                            ?? ('Material #' . $material->product_id),
                        'is_formula_based' => (bool) ($meta['is_formula_based'] ?? false),
                        'formula_types' => $meta['formula_types'] ?? [],
                        'consumption_source' => (string) ($meta['consumption_source'] ?? 'manual'),
                    ];
                })
                ->groupBy('material_id')
                ->map(function ($group) {
                    $first = $group->first();

                    return [
                        'material_id' => $first['material_id'],
                        'quantity' => (float) $group->sum('quantity'),
                        'planned_quantity' => (float) $group->sum('planned_quantity'),
                        'wastage_quantity' => (float) $group->sum('wastage_quantity'),
                        'unit' => $first['unit'],
                        'material_name' => $first['material_name'],
                        'is_formula_based' => (bool) ($first['is_formula_based'] ?? false),
                        'formula_types' => $first['formula_types'] ?? [],
                        'consumption_source' => (string) ($first['consumption_source'] ?? 'manual'),
                    ];
                })
                ->values()
                ->all();
        }

        $bom = $order->bom;
        if (! $bom || $bom->items->isEmpty()) {
            return [];
        }

        return $bom->items
            ->map(function ($item) use ($quantity, $consumptionMetadata) {
                $meta = $consumptionMetadata->get((int) $item->material_id, []);

                return [
                    'material_id' => (int) $item->material_id,
                    'quantity' => $item->calculateStockRequirement($quantity, true),
                    'planned_quantity' => $item->calculateStockRequirement($quantity, false),
                    'wastage_quantity' => max(
                        $item->calculateStockRequirement($quantity, true)
                        - $item->calculateStockRequirement($quantity, false),
                        0
                    ),
                    'unit' => $item->material?->is_roll_based ? 'kg' : ($item->unit ?: 'unit'),
                    'material_name' => $item->material?->name
                        ?? ('Material #' . $item->material_id),
                    'is_formula_based' => (bool) ($meta['is_formula_based'] ?? false),
                    'formula_types' => $meta['formula_types'] ?? [],
                    'consumption_source' => (string) ($meta['consumption_source'] ?? 'manual'),
                ];
            })
            ->groupBy('material_id')
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'material_id' => $first['material_id'],
                    'quantity' => (float) $group->sum('quantity'),
                    'planned_quantity' => (float) $group->sum('planned_quantity'),
                    'wastage_quantity' => (float) $group->sum('wastage_quantity'),
                    'unit' => $first['unit'],
                    'material_name' => $first['material_name'],
                    'is_formula_based' => (bool) ($first['is_formula_based'] ?? false),
                    'formula_types' => $first['formula_types'] ?? [],
                    'consumption_source' => (string) ($first['consumption_source'] ?? 'manual'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Identify materials whose normal consumption comes from an approved BOM
     * formula. The frozen production-order quantities remain authoritative;
     * this metadata only controls the completion workflow.
     */
    private function standardConsumptionMetadata(ProductionOrder $order): \Illuminate\Support\Collection
    {
        $automaticFormulaTypes = collect((array) config(
            'carton.standard_consumption.automatic_formula_types',
            ['carton_3d', 'cut_roll', 'adhesive_mix', 'fixed_percentage', 'fixed_rate']
        ));

        if (! $order->bom || $order->bom->items->isEmpty()) {
            return collect();
        }

        return $order->bom->items
            ->groupBy(fn ($item) => (int) $item->material_id)
            ->map(function ($items) use ($automaticFormulaTypes): array {
                $formulaTypes = $items
                    ->filter(fn ($item) => (bool) $item->is_formula_based)
                    ->pluck('formula_type')
                    ->filter()
                    ->map(fn ($type) => (string) $type)
                    ->unique()
                    ->values();

                $allRowsFormulaBased = $items->isNotEmpty()
                    && $items->every(function ($item) use ($automaticFormulaTypes): bool {
                        return (bool) $item->is_formula_based
                            && $automaticFormulaTypes->contains((string) $item->formula_type);
                    });

                return [
                    'is_formula_based' => $allRowsFormulaBased,
                    'formula_types' => $formulaTypes->all(),
                    'consumption_source' => $allRowsFormulaBased
                        ? 'standard_formula'
                        : 'manual',
                ];
            });
    }

    /**
     * Reconcile the FIFO allocation made at production start to the operator's
     * real material declaration at completion.
     *
     * actual_quantity is the total quantity that left inventory, including
     * waste. wastage_quantity is only a classified subset of actual_quantity.
     */
    private function reconcileMaterialsToActuals(
        ProductionOrder $order,
        array $actualMaterials,
        ?Sale $sale,
        ?SaleItem $saleItem
    ): array {
        $rows = collect($actualMaterials)
            ->map(function ($row): array {
                $materialId = (int) ($row['material_id'] ?? 0);
                $actual = (float) ($row['actual_quantity'] ?? 0);
                $wastage = (float) ($row['wastage_quantity'] ?? 0);

                if ($materialId <= 0) {
                    throw new RuntimeException('Every actual material row requires a valid material_id.');
                }

                if ($actual < -self::EPSILON) {
                    throw new RuntimeException("Actual consumption for material #{$materialId} cannot be negative.");
                }

                if ($wastage < -self::EPSILON) {
                    throw new RuntimeException("Actual wastage for material #{$materialId} cannot be negative.");
                }

                if ($wastage > $actual + self::EPSILON) {
                    throw new RuntimeException(
                        "Actual wastage for material #{$materialId} cannot exceed actual consumption."
                    );
                }

                return [
                    'material_id' => $materialId,
                    'actual_quantity' => max($actual, 0.0),
                    'wastage_quantity' => max($wastage, 0.0),
                    'unit' => isset($row['unit']) ? (string) $row['unit'] : null,
                    'use_reel_selection' => (bool) ($row['use_reel_selection'] ?? false),
                    'reel_selections' => is_array($row['reels'] ?? null)
                        ? $row['reels']
                        : [],
                    'selection_note' => isset($row['selection_note'])
                        ? trim((string) $row['selection_note'])
                        : null,
                ];
            })
            ->groupBy('material_id')
            ->map(function ($group): array {
                $first = $group->first();

                return [
                    'material_id' => (int) $first['material_id'],
                    'actual_quantity' => (float) $group->sum('actual_quantity'),
                    'wastage_quantity' => (float) $group->sum('wastage_quantity'),
                    'unit' => $first['unit'],
                    'use_reel_selection' => (bool) $first['use_reel_selection'],
                    'reel_selections' => $first['reel_selections'],
                    'selection_note' => $first['selection_note'],
                ];
            })
            ->keyBy('material_id');

        $expectedMaterialIds = $order->materials
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->merge(
                ProductionMaterialConsumption::query()
                    ->where('production_order_id', $order->id)
                    ->pluck('material_id')
                    ->map(fn ($id) => (int) $id)
            )
            ->unique()
            ->sort()
            ->values();

        if ($expectedMaterialIds->isEmpty()) {
            throw new RuntimeException('Production order has no material plan to reconcile.');
        }

        $missing = $expectedMaterialIds->diff($rows->keys());
        if ($missing->isNotEmpty()) {
            throw new RuntimeException(
                'Actual consumption must be entered for every production material. Missing material IDs: '
                . $missing->implode(', ')
            );
        }

        $unexpected = $rows->keys()->diff($expectedMaterialIds);
        if ($unexpected->isNotEmpty()) {
            throw new RuntimeException(
                'Actual consumption contains materials that are not part of this production order: '
                . $unexpected->implode(', ')
            );
        }

        $plannedRunQty = (float) (
            $order->quantity_planned ?: $order->quantity_ordered
        );
        $plannedByMaterial = collect(
            $this->requirementsForQuantity($order, $plannedRunQty)
        )->keyBy('material_id');

        $current = ProductionMaterialConsumption::query()
            ->where('production_order_id', $order->id)
            ->selectRaw('material_id, SUM(actual_quantity) AS actual_quantity')
            ->groupBy('material_id')
            ->get()
            ->keyBy(fn ($row) => (int) $row->material_id);

        $additional = [];

        foreach ($expectedMaterialIds as $materialId) {
            $row = $rows->get((int) $materialId);
            $target = (float) $row['actual_quantity'];

            if ($row['use_reel_selection']) {
                if ($target <= self::EPSILON) {
                    throw new RuntimeException(
                        "Physical reel selection for material #{$materialId} requires positive actual consumption."
                    );
                }

                $selectionNote = $row['selection_note'];
                $selections = collect($row['reel_selections'])
                    ->map(function ($selection) use ($selectionNote): array {
                        $selection = is_array($selection)
                            ? $selection
                            : (array) $selection;

                        if (
                            ! isset($selection['note'])
                            || trim((string) $selection['note']) === ''
                        ) {
                            $selection['note'] = $selectionNote;
                        }

                        return $selection;
                    })
                    ->all();

                $this->stockService
                    ->replaceProductionMaterialWithReelSelections(
                        productionOrderId: $order->id,
                        saleId: $sale?->id,
                        saleItemId: $saleItem?->id,
                        materialId: (int) $materialId,
                        actualQuantity: $target,
                        plannedQuantity: (float) data_get(
                            $plannedByMaterial->get((int) $materialId),
                            'quantity',
                            0
                        ),
                        selections: $selections
                    );

                continue;
            }

            $consumed = (float) data_get($current->get((int) $materialId), 'actual_quantity', 0);
            $difference = $target - $consumed;

            if ($difference > self::EPSILON) {
                $additional[] = [
                    'material_id' => (int) $materialId,
                    'quantity' => $difference,
                    // This is real variance beyond the start allocation, not
                    // additional planned BOM quantity.
                    'planned_quantity' => 0.0,
                    'wastage_quantity' => 0.0,
                    'unit' => $row['unit']
                        ?: ($order->materials->firstWhere('product_id', $materialId)?->unit ?: 'unit'),
                    'sale_item_id' => $saleItem?->id,
                ];
            } elseif ($difference < -self::EPSILON) {
                $this->stockService->restoreProductionMaterialQuantity(
                    $order->id,
                    (int) $materialId,
                    abs($difference)
                );
            }
        }

        if ($additional !== []) {
            $availability = $this->stockService->checkAvailability($additional);

            if (! $availability['available']) {
                $shortages = collect($availability['materials'])
                    ->filter(fn (array $row) => ! $row['available'])
                    ->map(fn (array $row) => sprintf(
                        '%s: additional %.4f %s required, only %.4f available',
                        $row['material_name'] ?? ('Material #' . $row['material_id']),
                        $row['required_quantity'],
                        $row['unit'] ?? 'unit',
                        $row['available_quantity']
                    ))
                    ->implode('; ');

                throw new RuntimeException(
                    'Actual material consumption exceeds available stock. ' . $shortages
                );
            }

            $this->stockService->deductMaterials(
                $order->id,
                $sale?->id,
                $additional
            );
        }

        foreach ($expectedMaterialIds as $materialId) {
            $row = $rows->get((int) $materialId);

            $this->stockService->setProductionMaterialWastage(
                $order->id,
                (int) $materialId,
                (float) $row['wastage_quantity']
            );
        }

        return [
            'material_cost_usd' => (float) ProductionMaterialConsumption::query()
                ->where('production_order_id', $order->id)
                ->sum('total_cost_usd'),
            'material_cost_afn' => (float) ProductionMaterialConsumption::query()
                ->where('production_order_id', $order->id)
                ->sum('total_cost_afn'),
        ];
    }

    private function reconcileMaterialsToQuantity(
        ProductionOrder $order,
        float $actualQuantity,
        ?Sale $sale,
        ?SaleItem $saleItem
    ): array {
        $targets = collect($this->requirementsForQuantity($order, $actualQuantity))
            ->keyBy('material_id');

        $current = ProductionMaterialConsumption::query()
            ->where('production_order_id', $order->id)
            ->selectRaw('material_id, SUM(actual_quantity) AS actual_quantity')
            ->groupBy('material_id')
            ->get()
            ->keyBy('material_id');

        $materialIds = $targets->keys()->merge($current->keys())->unique()->values();

        $additional = [];

        foreach ($materialIds as $materialId) {
            $target = (float) data_get($targets->get($materialId), 'quantity', 0);
            $consumed = (float) data_get($current->get($materialId), 'actual_quantity', 0);
            $difference = $target - $consumed;

            if ($difference > self::EPSILON) {
                $targetRow = $targets->get($materialId);

                $additional[] = [
                    'material_id' => (int) $materialId,
                    'quantity' => $difference,
                    'planned_quantity' => $difference,
                    'wastage_quantity' => 0,
                    'unit' => $targetRow['unit'] ?? 'unit',
                    'sale_item_id' => $saleItem?->id,
                ];
            } elseif ($difference < -self::EPSILON) {
                $this->stockService->restoreProductionMaterialQuantity(
                    $order->id,
                    (int) $materialId,
                    abs($difference)
                );
            }
        }

        if (! empty($additional)) {
            $availability = $this->stockService->checkAvailability($additional);
            if (! $availability['available']) {
                $shortages = collect($availability['materials'])
                    ->filter(fn (array $row) => ! $row['available'])
                    ->map(fn (array $row) => sprintf(
                        '%s: additional %.4f %s required, only %.4f available',
                        $row['material_name'] ?? ('Material #' . $row['material_id']),
                        $row['required_quantity'],
                        $row['unit'] ?? 'unit',
                        $row['available_quantity']
                    ))
                    ->implode('; ');

                throw new RuntimeException(
                    'Actual output needs more raw material than is available. ' . $shortages
                );
            }

            $this->stockService->deductMaterials(
                $order->id,
                $sale?->id,
                $additional
            );
        }

        return [
            'material_cost_usd' => (float) ProductionMaterialConsumption::query()
                ->where('production_order_id', $order->id)
                ->sum('total_cost_usd'),
            'material_cost_afn' => (float) ProductionMaterialConsumption::query()
                ->where('production_order_id', $order->id)
                ->sum('total_cost_afn'),
        ];
    }

    private function syncFinalInvoice(
        Sale $sale,
        SaleItem $saleItem,
        ProductionOrder $order,
        float $actualQuantity,
        float $actualMaterialCostUsd
    ): array {
        $oldQty = max((float) $saleItem->qty, self::EPSILON);
        $ratio = $actualQuantity / $oldQty;

        if ($saleItem->ordered_qty === null) {
            $saleItem->ordered_qty = (float) $saleItem->qty;
        }

        $saleItem->qty = $actualQuantity;
        $saleItem->total = (float) $saleItem->unit_price * $actualQuantity;
        $saleItem->usd_total = (float) $saleItem->usd_unit_price * $actualQuantity;

        // discount/tax are persisted as line totals in this application.
        $saleItem->discount = (float) $saleItem->discount * $ratio;
        $saleItem->tax = (float) $saleItem->tax * $ratio;
        $saleItem->usd_discount = (float) $saleItem->usd_discount * $ratio;
        $saleItem->usd_tax = (float) $saleItem->usd_tax * $ratio;

        $saleItem->total_cost_usd = $actualMaterialCostUsd;
        $saleItem->cost_per_unit_usd = $actualMaterialCostUsd / $actualQuantity;
        $saleItem->calculateProfitUsd();
        $saleItem->save();

        $sale->unsetRelation('items');
        $sale->load('items');
        $sale->recalculateTotals();

        // A smaller real output can make prior payments exceed the final invoice.
        // Keep the customer credit in the ledger, but do not show a negative due.
        $sale->due_amount = max(
            (float) $sale->grand_total - (float) $sale->advance_payment,
            0
        );
        $sale->usd_due_amount = max(
            (float) $sale->usd_grand_total - (float) $sale->usd_advance_payment,
            0
        );
        $linkedOrders = ProductionOrder::query()
            ->where('sale_id', $sale->id)
            ->get(['id', 'status']);

        // Keep the sale pending until every linked sale-line production order
        // has actually completed. Legacy single-order sales without explicit
        // sale_id links retain the historical completion behavior.
        $sale->is_produced = $linkedOrders->isEmpty()
            ? true
            : $linkedOrders->every(
                fn (ProductionOrder $linkedOrder) =>
                    $linkedOrder->status === ProductionOrder::STATUS_COMPLETED
            );
        $sale->save();

        $currencySymbol = $sale->currency?->symbol ?? '';
        $invoiceTransaction = Transaction::query()
            ->where('type', 'sale')
            ->where('table_name', 'sales')
            ->where('table_row_id', $sale->id)
            ->where('transaction_type', 'debit')
            ->where('is_cash', false)
            ->where('status', 'active')
            ->orderBy('id')
            ->first();

        $transactionData = [
            'account_id' => $sale->customer_id,
            'currency_id' => $sale->currency_id,
            'amount' => (float) $sale->grand_total,
            'usd_amount' => (float) $sale->usd_grand_total,
            'exchange_rate' => (float) $sale->exchange_rate,
            'description' => sprintf(
                'Sale #%s - Final invoice from actual production: %s %s',
                $sale->sale_no,
                $currencySymbol,
                number_format((float) $sale->grand_total, 2)
            ),
        ];

        if ($invoiceTransaction) {
            $invoiceTransaction->update($transactionData);
        } else {
            Transaction::create($transactionData + [
                'type' => 'sale',
                'table_name' => 'sales',
                'table_row_id' => $sale->id,
                'transaction_type' => 'debit',
                'is_cash' => false,
                'is_visible' => true,
                'status' => 'active',
                'created_by' => Auth::id(),
            ]);
        }

        return [
            'sale_id' => $sale->id,
            'sale_item_id' => $saleItem->id,
            'ordered_quantity' => (float) $saleItem->ordered_qty,
            'invoice_quantity' => (float) $saleItem->qty,
            'grand_total' => (float) $sale->grand_total,
            'usd_grand_total' => (float) $sale->usd_grand_total,
            'due_amount' => (float) $sale->due_amount,
            'overpayment' => max(
                (float) $sale->advance_payment - (float) $sale->grand_total,
                0
            ),
        ];
    }

    private function resolveSaleItem(Sale $sale, ProductionOrder $order): ?SaleItem
    {
        if ($order->sale_item_id) {
            $item = $sale->items()->whereKey((int) $order->sale_item_id)->first();
            if ($item) {
                return $item;
            }
        }

        if (preg_match('/SaleItem ID:\s*(\d+)/i', (string) $order->notes, $matches)) {
            $item = $sale->items()->whereKey((int) $matches[1])->first();
            if ($item) {
                return $item;
            }
        }

        $query = $sale->items()->where('product_id', $order->product_id);

        if ($order->bom_id) {
            $query->where('bom_id', $order->bom_id);
        }

        return $query->orderBy('id')->first();
    }
}
