<?php

namespace App\Services;

use App\Models\ProductionMaterialConsumption;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderControlEvent;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProductionOrderGovernanceService
{
    public function __construct(
        private readonly StockDeductionService $stockService
    ) {
    }

    public function state(ProductionOrder $order): string
    {
        if ($order->reversed_at) {
            return 'reversed';
        }

        if ($order->status === ProductionOrder::STATUS_CANCELLED) {
            return 'cancelled';
        }

        if ($order->closed_at) {
            return 'closed';
        }

        if ($order->status === ProductionOrder::STATUS_COMPLETED) {
            return 'completed';
        }

        if ($order->status === ProductionOrder::STATUS_IN_PROGRESS) {
            return 'in_progress';
        }

        if ($order->status === ProductionOrder::STATUS_PENDING && $order->approved_at) {
            return 'approved';
        }

        return 'draft';
    }

    public function approve(
        ProductionOrder $order,
        ?User $actor = null,
        ?string $notes = null
    ): ProductionOrder {
        return DB::transaction(function () use ($order, $actor, $notes): ProductionOrder {
            $order = ProductionOrder::query()->lockForUpdate()->findOrFail($order->id);
            $from = $this->state($order);

            if ($order->status !== ProductionOrder::STATUS_PENDING || $order->approved_at) {
                throw new RuntimeException('Only an unapproved pending production order can be approved.');
            }

            $actor ??= Auth::user();

            $order->approved_by = $actor?->id;
            $order->approved_at = now();
            $order->approval_notes = $notes;
            $order->save();

            $this->record($order, 'approved', $from, $this->state($order), $notes, $actor);

            return $order->refresh();
        });
    }

    public function close(
        ProductionOrder $order,
        ?User $actor = null,
        ?string $reason = null
    ): ProductionOrder {
        return DB::transaction(function () use ($order, $actor, $reason): ProductionOrder {
            $order = ProductionOrder::query()->lockForUpdate()->findOrFail($order->id);
            $from = $this->state($order);

            if ($order->status !== ProductionOrder::STATUS_COMPLETED || $order->closed_at) {
                throw new RuntimeException('Only a completed, open production order can be closed.');
            }

            $actor ??= Auth::user();

            $order->closed_at = now();
            $order->closed_by = $actor?->id;
            $order->save();

            $this->record($order, 'closed', $from, $this->state($order), $reason, $actor);

            return $order->refresh();
        });
    }

    public function reopen(
        ProductionOrder $order,
        string $reason,
        ?User $actor = null
    ): ProductionOrder {
        $reason = trim($reason);
        if ($reason === '') {
            throw new RuntimeException('A reason is required to reopen a closed production order.');
        }

        return DB::transaction(function () use ($order, $reason, $actor): ProductionOrder {
            $order = ProductionOrder::query()->lockForUpdate()->findOrFail($order->id);
            $from = $this->state($order);

            if ($order->status !== ProductionOrder::STATUS_COMPLETED || ! $order->closed_at) {
                throw new RuntimeException('Only a closed production order can be reopened.');
            }

            $actor ??= Auth::user();

            $order->closed_at = null;
            $order->closed_by = null;
            $order->reopened_at = now();
            $order->reopened_by = $actor?->id;
            $order->save();

            $this->record($order, 'reopened', $from, $this->state($order), $reason, $actor);

            return $order->refresh();
        });
    }

    public function cancel(
        ProductionOrder $order,
        string $reason,
        ?User $actor = null
    ): ProductionOrder {
        $reason = trim($reason);
        if ($reason === '') {
            throw new RuntimeException('A reason is required to cancel a production order.');
        }

        return DB::transaction(function () use ($order, $reason, $actor): ProductionOrder {
            $order = ProductionOrder::query()->lockForUpdate()->findOrFail($order->id);
            $from = $this->state($order);

            if (! in_array($order->status, [
                ProductionOrder::STATUS_PENDING,
                ProductionOrder::STATUS_IN_PROGRESS,
            ], true)) {
                throw new RuntimeException('Only pending or in-progress production orders can be cancelled.');
            }

            if ($order->status === ProductionOrder::STATUS_IN_PROGRESS) {
                $this->stockService->restoreProductionStock($order->id);
            }

            $actor ??= Auth::user();

            $order->status = ProductionOrder::STATUS_CANCELLED;
            $order->save();

            $sale = $order->sale()->first();
            if ($sale) {
                $sale->is_produced = false;
                $sale->save();
            }

            $this->record($order, 'cancelled', $from, $this->state($order), $reason, $actor);

            return $order->refresh();
        });
    }

    public function reverse(
        ProductionOrder $order,
        string $reason,
        ?User $actor = null
    ): ProductionOrder {
        $reason = trim($reason);
        if ($reason === '') {
            throw new RuntimeException('A reason is required to reverse a production order.');
        }

        return DB::transaction(function () use ($order, $reason, $actor): ProductionOrder {
            $order = ProductionOrder::query()
                ->with('sale.items')
                ->lockForUpdate()
                ->findOrFail($order->id);

            $from = $this->state($order);

            if ($order->status !== ProductionOrder::STATUS_COMPLETED || $order->reversed_at) {
                throw new RuntimeException('Only a completed production order that has not already been reversed can be reversed.');
            }

            $sale = $order->sale;
            if ($sale) {
                if (in_array($sale->status, ['shipped', 'delivered'], true)) {
                    throw new RuntimeException('Production cannot be reversed after the linked sale has been shipped or delivered.');
                }

                if ((float) $sale->advance_payment > 0.000001 || (float) $sale->usd_advance_payment > 0.000001) {
                    throw new RuntimeException('Production cannot be reversed while the linked sale has customer payments. Reverse or reallocate the payment first.');
                }
            }

            $this->stockService->restoreProductionStock($order->id);

            if ($sale) {
                $this->restoreSaleToOrderedQuantity($sale, $order);
            }

            $actor ??= Auth::user();

            $order->status = ProductionOrder::STATUS_CANCELLED;
            $order->reversed_at = now();
            $order->reversed_by = $actor?->id;
            $order->reversal_reason = $reason;
            $order->closed_at = null;
            $order->closed_by = null;
            $order->save();

            $this->record(
                $order,
                'reversed',
                $from,
                $this->state($order),
                $reason,
                $actor,
                [
                    'restored_inventory' => true,
                    'linked_sale_id' => $sale?->id,
                ]
            );

            return $order->refresh();
        });
    }

    private function restoreSaleToOrderedQuantity(Sale $sale, ProductionOrder $order): void
    {
        $item = $this->resolveSaleItem($sale, $order);
        if (! $item) {
            throw new RuntimeException('The linked sale item could not be identified, so production reversal was stopped.');
        }

        $ordered = (float) ($item->ordered_qty ?? 0);
        if ($ordered <= 0) {
            throw new RuntimeException('The original ordered sale quantity is unavailable, so production reversal was stopped.');
        }

        $current = max((float) $item->qty, 0.000001);
        $ratio = $ordered / $current;

        $item->qty = $ordered;
        $item->total = (float) $item->unit_price * $ordered;
        $item->usd_total = (float) $item->usd_unit_price * $ordered;
        $item->discount = (float) $item->discount * $ratio;
        $item->tax = (float) $item->tax * $ratio;
        $item->usd_discount = (float) $item->usd_discount * $ratio;
        $item->usd_tax = (float) $item->usd_tax * $ratio;
        $item->calculateCostFromBOM();
        $item->save();

        $sale->is_produced = false;
        $sale->recalculateTotals();
        $sale->refresh();

        Transaction::query()
            ->where('type', 'sale')
            ->where('table_name', 'sales')
            ->where('table_row_id', $sale->id)
            ->where('transaction_type', 'debit')
            ->where('is_cash', false)
            ->where('status', 'active')
            ->update([
                'amount' => (float) $sale->grand_total,
                'usd_amount' => (float) $sale->usd_grand_total,
                'exchange_rate' => (float) $sale->exchange_rate,
                'description' => sprintf(
                    'Sale #%s - Invoice restored after production reversal',
                    $sale->sale_no
                ),
                'updated_at' => now(),
            ]);
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

    private function record(
        ProductionOrder $order,
        string $event,
        ?string $from,
        ?string $to,
        ?string $reason,
        ?User $actor,
        array $metadata = []
    ): void {
        ProductionOrderControlEvent::create([
            'production_order_id' => $order->id,
            'event' => $event,
            'from_state' => $from,
            'to_state' => $to,
            'reason' => $reason,
            'metadata' => $metadata ?: null,
            'actor_id' => $actor?->id,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
