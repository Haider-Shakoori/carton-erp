<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseGoodsReceipt;
use App\Models\PurchaseGoodsReceiptItem;
use App\Models\PurchaseSupplierInvoice;
use App\Models\PurchaseSupplierInvoiceItem;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PurchaseControlService
{
    private const QTY_EPSILON = 0.000001;

    public function approve(Purchase $purchase, ?User $actor = null, ?string $notes = null): Purchase
    {
        return DB::transaction(function () use ($purchase, $actor, $notes): Purchase {
            $purchase = Purchase::query()->with('items')->lockForUpdate()->findOrFail($purchase->id);

            if ($purchase->status !== 'draft') {
                throw new RuntimeException('Only a draft purchase order can be approved.');
            }

            if ($purchase->approved_at) {
                throw new RuntimeException('This purchase order is already approved.');
            }

            if ($purchase->items->isEmpty()) {
                throw new RuntimeException('A purchase order must contain at least one item before approval.');
            }

            $purchase->recalculateTotals();
            $actor ??= Auth::user();

            $purchase->approved_by = $actor?->id;
            $purchase->approved_at = now();
            $purchase->approval_notes = $notes;
            $purchase->save();

            activity('purchase-control')
                ->performedOn($purchase)
                ->causedBy($actor)
                ->withProperties(['approval_notes' => $notes])
                ->log('Purchase order approved');

            return $purchase->refresh();
        });
    }

    public function assertCanShip(Purchase $purchase): void
    {
        if (! $purchase->approved_at) {
            throw new RuntimeException('Purchase order must be approved before it can be marked as shipping.');
        }

        if ($purchase->status !== 'draft') {
            throw new RuntimeException('Only an approved draft purchase order can be marked as shipping.');
        }
    }

    public function receive(
        Purchase $purchase,
        ?User $actor = null,
        ?string $notes = null
    ): PurchaseGoodsReceipt {
        return DB::transaction(function () use ($purchase, $actor, $notes): PurchaseGoodsReceipt {
            $purchase = Purchase::query()->with('items')->lockForUpdate()->findOrFail($purchase->id);

            if (! $purchase->approved_at) {
                throw new RuntimeException('Purchase order must be approved before goods can be received.');
            }

            if (! in_array($purchase->status, ['shipping', 'arrived'], true)) {
                throw new RuntimeException('Only a shipping purchase order can be received.');
            }

            $existing = PurchaseGoodsReceipt::query()
                ->where('purchase_id', $purchase->id)
                ->where('status', 'posted')
                ->first();

            if ($existing) {
                return $existing;
            }

            if ($purchase->items->isEmpty()) {
                throw new RuntimeException('Purchase order has no items to receive.');
            }

            $actor ??= Auth::user();

            $receipt = PurchaseGoodsReceipt::create([
                'purchase_id' => $purchase->id,
                'receipt_no' => sprintf('GRN-%06d-%s', $purchase->id, now()->format('His')),
                'status' => 'posted',
                'received_at' => now(),
                'received_by' => $actor?->id,
                'notes' => $notes,
            ]);

            foreach ($purchase->items as $item) {
                PurchaseGoodsReceiptItem::create([
                    'purchase_goods_receipt_id' => $receipt->id,
                    'purchase_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'ordered_quantity' => (float) $item->qty,
                    'received_quantity' => (float) $item->qty,
                    'received_quantity_kg' => $item->isRollBatch()
                        ? (float) $item->totalKg()
                        : 0,
                    'unit' => $item->unit ?: 'unit',
                ]);

                app(WarehouseInventoryService::class)->ensureBatchBalance($item);
            }

            $purchase->status = 'arrived';
            $purchase->arrival_date = $purchase->arrival_date ?: now()->toDateString();
            $purchase->received_by = $actor?->id;
            $purchase->received_at = now();
            $purchase->invoice_match_status = $purchase->supplierInvoices()->exists()
                ? 'pending'
                : 'not_received';
            $purchase->save();

            activity('purchase-control')
                ->performedOn($purchase)
                ->causedBy($actor)
                ->withProperties(['goods_receipt_id' => $receipt->id])
                ->log('Purchase goods received');

            return $receipt->load('items');
        });
    }

    public function recordSupplierInvoice(
        Purchase $purchase,
        array $data,
        ?User $actor = null
    ): PurchaseSupplierInvoice {
        return DB::transaction(function () use ($purchase, $data, $actor): PurchaseSupplierInvoice {
            $purchase = Purchase::query()->with('items')->lockForUpdate()->findOrFail($purchase->id);

            if (! $purchase->approved_at) {
                throw new RuntimeException('Purchase order must be approved before a supplier invoice can be recorded.');
            }

            $invoiceNo = trim((string) ($data['invoice_no'] ?? ''));
            if ($invoiceNo === '') {
                throw new RuntimeException('Supplier invoice number is required.');
            }

            if (PurchaseSupplierInvoice::query()
                ->where('purchase_id', $purchase->id)
                ->where('invoice_no', $invoiceNo)
                ->exists()) {
                throw new RuntimeException('This supplier invoice number is already recorded for the purchase order.');
            }

            $submittedLines = collect($data['items'] ?? [])->keyBy(
                fn (array $row) => (int) ($row['purchase_item_id'] ?? 0)
            );

            if ($submittedLines->count() !== $purchase->items->count()) {
                throw new RuntimeException('Supplier invoice must contain exactly the purchase-order item lines.');
            }

            $subtotal = 0.0;
            $prepared = [];

            foreach ($purchase->items as $purchaseItem) {
                $row = $submittedLines->get((int) $purchaseItem->id);
                if (! $row) {
                    throw new RuntimeException('Supplier invoice is missing a purchase-order item.');
                }

                $quantity = (float) ($row['quantity'] ?? 0);
                $unitPrice = (float) ($row['unit_price'] ?? 0);

                if ($quantity <= self::QTY_EPSILON || $unitPrice < 0) {
                    throw new RuntimeException('Supplier invoice quantities must be positive and prices cannot be negative.');
                }

                $lineTotal = round($quantity * $unitPrice, 4);
                $subtotal += $lineTotal;

                $prepared[] = [
                    'purchase_item_id' => $purchaseItem->id,
                    'product_id' => $purchaseItem->product_id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            $expenseTotal = max((float) ($data['expense_total'] ?? 0), 0);
            $grandTotal = $subtotal + $expenseTotal;
            $exchangeRate = max((float) ($data['exchange_rate'] ?? $purchase->exchange_rate ?? 1), self::QTY_EPSILON);
            $actor ??= Auth::user();

            $invoice = PurchaseSupplierInvoice::create([
                'purchase_id' => $purchase->id,
                'invoice_no' => $invoiceNo,
                'invoice_date' => $data['invoice_date'] ?? now()->toDateString(),
                'currency_id' => (int) ($data['currency_id'] ?? $purchase->currency_id),
                'exchange_rate' => $exchangeRate,
                'subtotal' => $subtotal,
                'expense_total' => $expenseTotal,
                'grand_total' => $grandTotal,
                'usd_grand_total' => $grandTotal / $exchangeRate,
                'match_status' => 'pending',
                'created_by' => $actor?->id,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($prepared as $row) {
                $invoice->items()->create($row);
            }

            $purchase->invoice_match_status = 'pending';
            $purchase->save();

            activity('purchase-control')
                ->performedOn($purchase)
                ->causedBy($actor)
                ->withProperties(['supplier_invoice_id' => $invoice->id])
                ->log('Supplier invoice recorded');

            return $invoice->load('items');
        });
    }

    public function match(
        Purchase $purchase,
        PurchaseSupplierInvoice $invoice,
        ?User $actor = null,
        float $amountTolerance = 0.01
    ): array {
        return DB::transaction(function () use ($purchase, $invoice, $actor, $amountTolerance): array {
            $purchase = Purchase::query()
                ->with(['items', 'goodsReceipts.items'])
                ->lockForUpdate()
                ->findOrFail($purchase->id);

            $invoice = PurchaseSupplierInvoice::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            if ((int) $invoice->purchase_id !== (int) $purchase->id) {
                throw new RuntimeException('Supplier invoice does not belong to this purchase order.');
            }

            $postedReceipts = $purchase->goodsReceipts->where('status', 'posted');
            if ($postedReceipts->isEmpty()) {
                throw new RuntimeException('Goods receipt is required before three-way matching.');
            }

            $invoiceLines = $invoice->items->keyBy('purchase_item_id');
            $exceptions = [];

            foreach ($purchase->items as $poItem) {
                $received = (float) $postedReceipts
                    ->flatMap->items
                    ->where('purchase_item_id', $poItem->id)
                    ->sum('received_quantity');

                $invoiceLine = $invoiceLines->get($poItem->id);
                $invoiced = $invoiceLine ? (float) $invoiceLine->quantity : 0;

                if (abs($received - (float) $poItem->qty) > self::QTY_EPSILON) {
                    $exceptions[] = sprintf(
                        'Receipt quantity for purchase item #%d is %.6f versus ordered %.6f.',
                        $poItem->id,
                        $received,
                        (float) $poItem->qty
                    );
                }

                if (abs($invoiced - $received) > self::QTY_EPSILON) {
                    $exceptions[] = sprintf(
                        'Invoice quantity for purchase item #%d is %.6f versus received %.6f.',
                        $poItem->id,
                        $invoiced,
                        $received
                    );
                }

                if ($invoiceLine && abs((float) $invoiceLine->unit_price - (float) $poItem->unit_price) > $amountTolerance) {
                    $exceptions[] = sprintf(
                        'Invoice unit price for purchase item #%d differs from the PO.',
                        $poItem->id
                    );
                }
            }

            $amountVariance = round((float) $invoice->subtotal - (float) $purchase->subtotal, 4);
            if (abs($amountVariance) > $amountTolerance) {
                $exceptions[] = sprintf(
                    'Supplier invoice subtotal differs from PO subtotal by %.4f.',
                    $amountVariance
                );
            }

            $actor ??= Auth::user();
            $matched = $exceptions === [];

            $invoice->match_status = $matched ? 'matched' : 'exception';
            $invoice->match_variance = $amountVariance;
            $invoice->matched_by = $actor?->id;
            $invoice->matched_at = now();
            $invoice->save();

            $purchase->invoice_match_status = $invoice->match_status;
            $purchase->matched_by = $actor?->id;
            $purchase->matched_at = now();
            $purchase->save();

            activity('purchase-control')
                ->performedOn($purchase)
                ->causedBy($actor)
                ->withProperties([
                    'supplier_invoice_id' => $invoice->id,
                    'match_status' => $invoice->match_status,
                    'exceptions' => $exceptions,
                ])
                ->log('Purchase three-way match evaluated');

            return [
                'matched' => $matched,
                'status' => $invoice->match_status,
                'amount_variance' => $amountVariance,
                'exceptions' => $exceptions,
            ];
        });
    }
}
