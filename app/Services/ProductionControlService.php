<?php

namespace App\Services;

use App\Models\ProductionOrder;
use App\Models\ProductionOrderEvent;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProductionControlService
{
    public function __construct(
        private readonly StockDeductionService $stockService
    ) {
    }

    public function approve(ProductionOrder $order, User $user, ?string $reason = null): ProductionOrder
    {
        return DB::transaction(function () use ($order, $user, $reason) {
            $order = ProductionOrder::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->status !== ProductionOrder::STATUS_PENDING) {
                throw new RuntimeException('Only pending production orders can be approved.');
            }

            if ($order->approved_at) {
                return $order;
            }

            $order->approved_by = $user->id;
            $order->approved_at = now();
            $order->save();

            $this->event($order, 'approved', $order->status, $order->status, $reason, [
                'approved_by' => $user->id,
            ]);

            return $order->fresh();
        });
    }

    public function close(ProductionOrder $order, User $user, string $reason): ProductionOrder
    {
        return DB::transaction(function () use ($order, $user, $reason) {
            $order = ProductionOrder::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->status !== ProductionOrder::STATUS_COMPLETED) {
                throw new RuntimeException('Only completed production orders can be closed.');
            }

            if ($order->closed_at) {
                throw new RuntimeException('This production order is already closed.');
            }

            $order->closed_by = $user->id;
            $order->closed_at = now();
            $order->save();

            $this->event($order, 'closed', 'completed', 'closed', $reason);

            return $order->fresh();
        });
    }

    public function reopen(ProductionOrder $order, User $user, string $reason): ProductionOrder
    {
        return DB::transaction(function () use ($order, $user, $reason) {
            $order = ProductionOrder::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->status !== ProductionOrder::STATUS_COMPLETED || ! $order->closed_at) {
                throw new RuntimeException('Only closed completed production orders can be reopened.');
            }

            $metadata = [
                'previous_closed_at' => $order->closed_at?->toISOString(),
                'previous_closed_by' => $order->closed_by,
            ];

            $order->closed_by = null;
            $order->closed_at = null;
            $order->reopen_count = (int) $order->reopen_count + 1;
            $order->save();

            $this->event($order, 'reopened', 'closed', 'completed', $reason, $metadata);

            return $order->fresh();
        });
    }

    public function snapshotBeforeCompletion(
        ProductionOrder $order,
        ?Sale $sale,
        ?SaleItem $saleItem
    ): void {
        $metadata = [
            'order' => [
                'status' => $order->status,
                'quantity_planned' => $order->quantity_planned,
                'quantity_manufactured' => $order->quantity_manufactured,
                'quantity_produced' => $order->quantity_produced,
                'quantity_rejected' => $order->quantity_rejected,
                'total_material_cost' => $order->total_material_cost,
                'total_labor_cost' => $order->total_labor_cost,
                'total_overhead_cost' => $order->total_overhead_cost,
                'total_cost' => $order->total_cost,
                'completion_date' => optional($order->completion_date)->toDateString(),
            ],
            'sale' => $sale ? [
                'id' => $sale->id,
                'grand_total' => $sale->grand_total,
                'usd_grand_total' => $sale->usd_grand_total,
                'due_amount' => $sale->due_amount,
                'usd_due_amount' => $sale->usd_due_amount,
                'is_produced' => $sale->is_produced,
            ] : null,
            'sale_item' => $saleItem ? [
                'id' => $saleItem->id,
                'qty' => $saleItem->qty,
                'ordered_qty' => $saleItem->ordered_qty,
                'total' => $saleItem->total,
                'usd_total' => $saleItem->usd_total,
                'discount' => $saleItem->discount,
                'tax' => $saleItem->tax,
                'usd_discount' => $saleItem->usd_discount,
                'usd_tax' => $saleItem->usd_tax,
                'cost_per_unit_usd' => $saleItem->cost_per_unit_usd,
                'total_cost_usd' => $saleItem->total_cost_usd,
                'profit_usd' => $saleItem->profit_usd,
                'profit_afn' => $saleItem->profit_afn,
                'profit_percentage' => $saleItem->profit_percentage,
            ] : null,
        ];

        if ($sale) {
            $invoice = Transaction::query()
                ->where('type', 'sale')
                ->where('table_name', 'sales')
                ->where('table_row_id', $sale->id)
                ->where('transaction_type', 'debit')
                ->where('is_cash', false)
                ->where('status', 'active')
                ->orderBy('id')
                ->first();

            $metadata['invoice_transaction'] = $invoice ? [
                'id' => $invoice->id,
                'amount' => $invoice->amount,
                'usd_amount' => $invoice->usd_amount,
                'exchange_rate' => $invoice->exchange_rate,
                'description' => $invoice->description,
            ] : null;
        }

        $this->event(
            $order,
            'completion_snapshot',
            $order->status,
            ProductionOrder::STATUS_COMPLETED,
            null,
            $metadata
        );
    }

    public function reverseCompletion(
        ProductionOrder $order,
        User $user,
        string $reason
    ): ProductionOrder {
        return DB::transaction(function () use ($order, $user, $reason) {
            $order = ProductionOrder::query()
                ->lockForUpdate()
                ->with(['sale.items'])
                ->findOrFail($order->id);

            if ($order->status !== ProductionOrder::STATUS_COMPLETED) {
                throw new RuntimeException('Only completed production orders can be reversed.');
            }

            if ($order->closed_at) {
                throw new RuntimeException('Reopen the closed production order before reversing completion.');
            }

            $sale = $order->sale;
            if ($sale && in_array($sale->status, ['delivered'], true)) {
                throw new RuntimeException(
                    'Delivered sales cannot be production-reversed. Reverse the delivery/gate-pass workflow first.'
                );
            }

            $snapshot = ProductionOrderEvent::query()
                ->where('production_order_id', $order->id)
                ->where('event_type', 'completion_snapshot')
                ->latest('id')
                ->first();

            if (! $snapshot) {
                throw new RuntimeException(
                    'This production completion has no immutable pre-completion snapshot and cannot be safely reversed.'
                );
            }

            $snapshotData = $snapshot->metadata ?? [];

            app(AccountingService::class)->reverseProductionEntries(
                $order,
                'Production completion reversed: '.$reason
            );

            $this->stockService->restoreStock($order->id);

            if ($sale && is_array($snapshotData['sale_item'] ?? null)) {
                $itemData = $snapshotData['sale_item'];
                $saleItem = SaleItem::query()->lockForUpdate()->find($itemData['id']);

                if ($saleItem) {
                    $saleItem->fill([
                        'qty' => $itemData['qty'],
                        'ordered_qty' => $itemData['ordered_qty'],
                        'total' => $itemData['total'],
                        'usd_total' => $itemData['usd_total'],
                        'discount' => $itemData['discount'],
                        'tax' => $itemData['tax'],
                        'usd_discount' => $itemData['usd_discount'],
                        'usd_tax' => $itemData['usd_tax'],
                        'cost_per_unit_usd' => $itemData['cost_per_unit_usd'],
                        'total_cost_usd' => $itemData['total_cost_usd'],
                        'profit_usd' => $itemData['profit_usd'],
                        'profit_afn' => $itemData['profit_afn'],
                        'profit_percentage' => $itemData['profit_percentage'],
                    ]);
                    $saleItem->save();
                }

                $saleData = $snapshotData['sale'] ?? null;
                if (is_array($saleData)) {
                    $sale->fill([
                        'grand_total' => $saleData['grand_total'],
                        'usd_grand_total' => $saleData['usd_grand_total'],
                        'due_amount' => $saleData['due_amount'],
                        'usd_due_amount' => $saleData['usd_due_amount'],
                        'is_produced' => $saleData['is_produced'],
                    ]);
                    $sale->save();
                }

                $invoiceData = $snapshotData['invoice_transaction'] ?? null;
                if (is_array($invoiceData) && ! empty($invoiceData['id'])) {
                    Transaction::query()->whereKey($invoiceData['id'])->update([
                        'amount' => $invoiceData['amount'],
                        'usd_amount' => $invoiceData['usd_amount'],
                        'exchange_rate' => $invoiceData['exchange_rate'],
                        'description' => $invoiceData['description'],
                    ]);
                }
            }

            $before = [
                'quantity_manufactured' => $order->quantity_manufactured,
                'quantity_produced' => $order->quantity_produced,
                'quantity_rejected' => $order->quantity_rejected,
                'total_cost' => $order->total_cost,
            ];

            $orderSnapshot = $snapshotData['order'] ?? [];
            $order->status = ProductionOrder::STATUS_PENDING;
            $order->quantity_planned = $orderSnapshot['quantity_planned'] ?? null;
            $order->quantity_manufactured = null;
            $order->quantity_produced = 0;
            $order->quantity_rejected = 0;
            $order->total_material_cost = $orderSnapshot['total_material_cost'] ?? 0;
            $order->total_labor_cost = $orderSnapshot['total_labor_cost'] ?? 0;
            $order->total_overhead_cost = $orderSnapshot['total_overhead_cost'] ?? 0;
            $order->total_cost = $orderSnapshot['total_cost'] ?? 0;
            $order->completion_date = null;
            $order->reversed_by = $user->id;
            $order->reversed_at = now();
            $order->save();

            $this->event(
                $order,
                'completion_reversed',
                ProductionOrder::STATUS_COMPLETED,
                ProductionOrder::STATUS_PENDING,
                $reason,
                ['completed_values' => $before, 'restored_snapshot_event_id' => $snapshot->id]
            );

            return $order->fresh();
        });
    }

    public function recordStarted(ProductionOrder $order): void
    {
        $this->event($order, 'started', ProductionOrder::STATUS_PENDING, ProductionOrder::STATUS_IN_PROGRESS);
    }

    public function recordCompleted(ProductionOrder $order, array $metadata = []): void
    {
        $this->event($order, 'completed', ProductionOrder::STATUS_IN_PROGRESS, ProductionOrder::STATUS_COMPLETED, null, $metadata);
    }

    private function event(
        ProductionOrder $order,
        string $type,
        ?string $from,
        ?string $to,
        ?string $reason = null,
        array $metadata = []
    ): ProductionOrderEvent {
        return ProductionOrderEvent::create([
            'production_order_id' => $order->id,
            'event_type' => $type,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
            'metadata' => $metadata ?: null,
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);
    }
}
