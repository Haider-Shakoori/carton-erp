<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\Purchase;
use App\Models\PurchaseRequest;
use App\Models\PurchaseItem;
use App\Models\RequestForQuotation;
use App\Models\RfqQuote;
use App\Models\SupplierInvoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProcurementService
{
    private const EPSILON = 0.000001;

    public function nextNumber(string $type): string
    {
        $map = [
            'request' => ['PR-', PurchaseRequest::class, 'request_no'],
            'rfq' => ['RFQ-', RequestForQuotation::class, 'rfq_no'],
            'receipt' => ['GRN-', GoodsReceipt::class, 'receipt_no'],
        ];

        if (! isset($map[$type])) {
            throw new RuntimeException('Unsupported procurement sequence.');
        }

        [$prefix, $model, $column] = $map[$type];
        $prefix .= now()->format('Ym').'-';

        $last = $model::query()
            ->withoutGlobalScope('business_unit')
            ->where($column, 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value($column);

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function submitRequest(PurchaseRequest $request): PurchaseRequest
    {
        if ($request->status !== 'draft') {
            throw new RuntimeException('Only draft purchase requests can be submitted.');
        }

        if (! $request->items()->exists()) {
            throw new RuntimeException('A purchase request must contain at least one item.');
        }

        $request->update(['status' => 'submitted']);

        return $request->fresh();
    }

    public function approveRequest(
        PurchaseRequest $request,
        User $user,
        ?string $note = null
    ): PurchaseRequest {
        return DB::transaction(function () use ($request, $user, $note) {
            $request = PurchaseRequest::query()->lockForUpdate()->findOrFail($request->id);

            if ($request->status !== 'submitted') {
                throw new RuntimeException('Only submitted purchase requests can be approved.');
            }

            if ((int) $request->requested_by === (int) $user->id) {
                throw new RuntimeException('The requester cannot approve their own purchase request.');
            }

            $request->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'approval_note' => $note,
            ]);

            return $request->fresh();
        });
    }

    public function openRfq(RequestForQuotation $rfq): RequestForQuotation
    {
        $rfq->loadMissing('request');

        if ($rfq->request?->status !== 'approved') {
            throw new RuntimeException('The purchase request must be approved before opening an RFQ.');
        }

        if ($rfq->status !== 'draft') {
            throw new RuntimeException('Only draft RFQs can be opened.');
        }

        $rfq->update(['status' => 'open']);

        return $rfq->fresh();
    }

    public function selectQuote(RfqQuote $quote): RfqQuote
    {
        return DB::transaction(function () use ($quote) {
            $quote = RfqQuote::query()
                ->with('rfq')
                ->lockForUpdate()
                ->findOrFail($quote->id);

            if (! in_array($quote->rfq?->status, ['open', 'closed'], true)) {
                throw new RuntimeException('Only quotes from an open/closed RFQ can be selected.');
            }

            RfqQuote::query()
                ->where('request_for_quotation_id', $quote->request_for_quotation_id)
                ->whereKeyNot($quote->id)
                ->update(['status' => 'rejected']);

            $quote->update(['status' => 'selected']);
            $quote->rfq->update(['status' => 'awarded']);

            return $quote->fresh();
        });
    }

    public function convertSelectedQuoteToPurchase(RfqQuote $quote): Purchase
    {
        return DB::transaction(function () use ($quote) {
            $quote = RfqQuote::query()
                ->with(['rfq.request', 'items', 'currency'])
                ->lockForUpdate()
                ->findOrFail($quote->id);

            if ($quote->status !== 'selected') {
                throw new RuntimeException('Only the selected supplier quote can be converted to a purchase order.');
            }

            $existing = Purchase::query()
                ->where('rfq_quote_id', $quote->id)
                ->first();

            if ($existing) {
                return $existing;
            }

            if ($quote->items->isEmpty()) {
                throw new RuntimeException('The selected quote has no line items.');
            }

            $rate = max((float) $quote->exchange_rate, self::EPSILON);

            $purchase = Purchase::query()->create([
                'purchase_no' => Purchase::generateNumber(),
                'supplier_id' => $quote->supplier_id,
                'currency_id' => $quote->currency_id,
                'exchange_rate' => $rate,
                'purchase_date' => now()->toDateString(),
                'status' => 'draft',
                'approval_status' => 'pending',
                'purchase_request_id' => $quote->rfq?->purchase_request_id,
                'rfq_quote_id' => $quote->id,
            ]);

            foreach ($quote->items as $line) {
                $quantity = (float) $line->quantity;
                $unitPrice = (float) $line->unit_price;

                $purchase->items()->create([
                    'product_id' => $line->product_id,
                    'purchase_currency_id' => $quote->currency_id,
                    'qty' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $quantity * $unitPrice,
                    'rate' => $rate,
                    'usd_unit_price' => $unitPrice / $rate,
                    'usd_total' => ($quantity * $unitPrice) / $rate,
                    'unit' => $line->product?->unit ?? 'unit',
                ]);
            }

            $purchase->recalculateTotals();

            if ($quote->rfq?->request) {
                $quote->rfq->request->update(['status' => 'converted']);
            }

            return $purchase->fresh(['items']);
        });
    }

    public function approvePurchase(Purchase $purchase, User $user): Purchase
    {
        return DB::transaction(function () use ($purchase, $user) {
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);

            if ($purchase->status !== 'draft') {
                throw new RuntimeException('Only draft purchase orders can be approved.');
            }

            if ($purchase->items()->count() === 0) {
                throw new RuntimeException('A purchase order must contain items before approval.');
            }

            $purchase->update([
                'approval_status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            return $purchase->fresh();
        });
    }

    public function ensureLegacyGoodsReceipt(Purchase $purchase): GoodsReceipt
    {
        $existing = GoodsReceipt::query()
            ->where('purchase_id', $purchase->id)
            ->where('status', 'posted')
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($purchase) {
            $purchase->loadMissing('items');
            $receipt = GoodsReceipt::query()->create([
                'purchase_id' => $purchase->id,
                'receipt_no' => $this->nextNumber('receipt'),
                'status' => 'posted',
                'received_by' => auth()->id(),
                'received_at' => $purchase->arrival_date ?: now(),
                'notes' => 'Auto-created from the existing arrived purchase workflow.',
            ]);

            foreach ($purchase->items as $item) {
                $receipt->items()->create([
                    'purchase_item_id' => $item->id,
                    'quantity_received' => $item->availableInventoryQuantity() > 0
                        ? $item->availableInventoryQuantity()
                        : (float) $item->qty,
                    'quantity_rejected' => 0,
                    'unit' => $item->inventoryCostBasisUnit(),
                ]);
            }

            return $receipt;
        });
    }

    public function postGoodsReceipt(GoodsReceipt $receipt, User $user): GoodsReceipt
    {
        return DB::transaction(function () use ($receipt, $user) {
            $receipt = GoodsReceipt::query()
                ->with('items.purchaseItem')
                ->lockForUpdate()
                ->findOrFail($receipt->id);

            if ($receipt->status !== 'draft') {
                throw new RuntimeException('Only draft goods receipts can be posted.');
            }

            if ($receipt->items->isEmpty()) {
                throw new RuntimeException('Goods receipt must contain at least one item.');
            }

            foreach ($receipt->items as $line) {
                if ((float) $line->quantity_received < 0 || (float) $line->quantity_rejected < 0) {
                    throw new RuntimeException('Receipt quantities cannot be negative.');
                }

                if ((float) $line->quantity_received + (float) $line->quantity_rejected <= self::EPSILON) {
                    throw new RuntimeException('Each goods receipt line must record received or rejected quantity.');
                }
            }

            $receipt->update([
                'status' => 'posted',
                'received_by' => $user->id,
                'received_at' => now(),
            ]);

            return $receipt->fresh();
        });
    }

    public function matchInvoice(SupplierInvoice $invoice, float $tolerance = 0.01): SupplierInvoice
    {
        return DB::transaction(function () use ($invoice, $tolerance) {
            $invoice = SupplierInvoice::query()
                ->with(['purchase.items', 'purchase'])
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            $purchase = $invoice->purchase;
            if (! $purchase) {
                throw new RuntimeException('Supplier invoice is not linked to a purchase order.');
            }

            $received = GoodsReceipt::query()
                ->where('purchase_id', $purchase->id)
                ->where('status', 'posted')
                ->with('items')
                ->get()
                ->flatMap->items
                ->groupBy('purchase_item_id')
                ->map(fn ($rows) => (float) $rows->sum('quantity_received'));

            $qtyMismatch = false;
            foreach ($purchase->items as $item) {
                $expected = $item->inventoryCostBasisUnit() === 'kg'
                    ? (float) ($item->qty_kg ?? $item->total_weight_kg ?? 0)
                    : (float) $item->qty;
                $actual = (float) ($received[$item->id] ?? 0);

                if (abs($expected - $actual) > max($tolerance, self::EPSILON)) {
                    $qtyMismatch = true;
                    break;
                }
            }

            $poUsd = (float) $purchase->usd_grand_total;
            $invoiceUsd = (float) $invoice->usd_total;
            $valueMismatch = abs($poUsd - $invoiceUsd) > max($tolerance, $poUsd * 0.005);

            $matched = ! $qtyMismatch && ! $valueMismatch;

            $invoice->three_way_match_status = $matched ? 'matched' : 'exception';
            $invoice->status = $matched ? 'matched' : 'draft';
            $invoice->match_note = $matched
                ? 'PO, posted goods receipt and supplier invoice are within tolerance.'
                : trim(($qtyMismatch ? 'Quantity mismatch. ' : '').($valueMismatch ? 'Invoice value differs from purchase order.' : ''));
            $invoice->save();

            return $invoice->fresh();
        });
    }

    public function approveInvoice(SupplierInvoice $invoice, User $user): SupplierInvoice
    {
        if ($invoice->three_way_match_status !== 'matched') {
            throw new RuntimeException('Supplier invoice must pass three-way matching before approval.');
        }

        $invoice->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return $invoice->fresh();
    }

    public function markInvoicePaid(
        SupplierInvoice $invoice,
        User $user,
        string $reference
    ): SupplierInvoice {
        if ($invoice->status !== 'approved') {
            throw new RuntimeException('Only approved supplier invoices can be marked paid.');
        }

        $invoice->update([
            'status' => 'paid',
            'paid_by' => $user->id,
            'paid_at' => now(),
            'payment_reference' => $reference,
        ]);

        return $invoice->fresh();
    }
}
