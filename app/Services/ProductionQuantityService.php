<?php

namespace App\Services;

use App\Models\ProductionMaterialConsumption;
use App\Models\ProductionOrder;
use App\Models\Sale;
use App\Models\SaleItem;
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
    public function start(ProductionOrder $order, ?Sale $sale = null): array
    {
        return DB::transaction(function () use ($order, $sale): array {
            $order->refresh()->load(['materials.product', 'bom.items.material', 'product']);

            if ($order->status !== ProductionOrder::STATUS_PENDING) {
                throw new RuntimeException(
                    'Production order must be pending before it can be started.'
                );
            }

            $sale ??= $order->sale()->with('items')->first();

            $orderedQty = (float) $order->quantity_ordered;
            if ($orderedQty <= self::EPSILON) {
                throw new RuntimeException('Production order quantity must be greater than zero.');
            }

            $maxProducible = $this->maxProducibleQuantity($order);
            $allocationQty = min($orderedQty, $maxProducible);

            if ($allocationQty <= self::EPSILON) {
                throw new RuntimeException(
                    'Production cannot start because the available raw material cannot produce any finished quantity.'
                );
            }

            $requirements = $this->requirementsForQuantity($order, $allocationQty);
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

            $ratio = $allocationQty / $orderedQty;
            foreach ($order->materials as $material) {
                $material->consumed_quantity = (float) $material->required_quantity * $ratio;
                $material->save();
            }

            $materialCostUsd = (float) $consumptions->sum('total_cost_usd');
            $order->total_material_cost = $materialCostUsd;
            $order->total_cost = $materialCostUsd
                + (float) $order->total_labor_cost
                + (float) $order->total_overhead_cost;
            $order->status = ProductionOrder::STATUS_IN_PROGRESS;
            $order->save();

            return [
                'allocation_quantity' => $allocationQty,
                'ordered_quantity' => $orderedQty,
                'max_producible_quantity' => $maxProducible,
                'partial_start' => $allocationQty + self::EPSILON < $orderedQty,
                'material_cost_usd' => $materialCostUsd,
                'consumption_count' => $consumptions->count(),
            ];
        });
    }

    /**
     * Complete production at the real quantity entered by the operator.
     *
     * Material consumption is reconciled to that real output. Extra output
     * consumes additional FIFO stock; lower output restores unused stock back
     * to the exact source batches. The linked sale invoice is then resized to
     * the actual quantity produced.
     */
    public function complete(
        ProductionOrder $order,
        float $actualQuantity,
        ?Sale $sale = null
    ): array {
        if ($actualQuantity <= self::EPSILON) {
            throw new RuntimeException('Actual produced quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($order, $actualQuantity, $sale): array {
            $order->refresh()->load(['materials.product', 'bom.items.material', 'product']);

            if ($order->status !== ProductionOrder::STATUS_IN_PROGRESS) {
                throw new RuntimeException('Only in-progress production orders can be completed.');
            }

            $sale ??= $order->sale()->with(['items', 'currency'])->first();
            $saleItem = $sale ? $this->resolveSaleItem($sale, $order) : null;

            $materialResult = $this->reconcileMaterialsToQuantity(
                $order,
                $actualQuantity,
                $sale,
                $saleItem
            );

            $orderedQty = max((float) $order->quantity_ordered, self::EPSILON);
            $labourPerUnit = (float) $order->total_labor_cost / $orderedQty;
            $overheadPerUnit = (float) $order->total_overhead_cost / $orderedQty;

            $order->quantity_produced = $actualQuantity;
            $order->total_material_cost = $materialResult['material_cost_usd'];
            $order->total_labor_cost = $labourPerUnit * $actualQuantity;
            $order->total_overhead_cost = $overheadPerUnit * $actualQuantity;
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

                $invoiceResult = $this->syncFinalInvoice(
                    $sale,
                    $saleItem,
                    $order,
                    $actualQuantity,
                    $materialResult['material_cost_usd']
                );
            }

            return [
                'ordered_quantity' => (float) $order->quantity_ordered,
                'actual_quantity' => $actualQuantity,
                'variance_quantity' => $actualQuantity - (float) $order->quantity_ordered,
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

        if ($order->materials->isNotEmpty()) {
            $ratio = $quantity / $orderedQty;

            return $order->materials
                ->map(function ($material) use ($ratio) {
                    return [
                        'material_id' => (int) $material->product_id,
                        'quantity' => (float) $material->required_quantity * $ratio,
                        'planned_quantity' => (float) $material->required_quantity * $ratio,
                        'wastage_quantity' => 0.0,
                        'unit' => $material->unit ?: 'unit',
                        'material_name' => $material->product->name
                            ?? ('Material #' . $material->product_id),
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
            ->map(function ($item) use ($quantity) {
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
                ];
            })
            ->values()
            ->all();
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

        $ratio = $actualQuantity / max((float) $order->quantity_ordered, self::EPSILON);
        foreach ($order->materials as $material) {
            $material->consumed_quantity = (float) $material->required_quantity * $ratio;
            $material->save();
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
        $sale->is_produced = true;
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
