<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Currency;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseRequest;
use App\Models\RequestForQuotation;
use App\Models\RfqQuote;
use App\Models\SupplierInvoice;
use App\Services\ProcurementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProcurementController extends Controller
{
    public function index()
    {
        $requests = PurchaseRequest::query()->with(['requestedBy', 'approvedBy', 'items'])->latest()->limit(20)->get();
        $rfqs = RequestForQuotation::query()->with(['request', 'quotes.supplier'])->latest()->limit(20)->get();
        $receipts = GoodsReceipt::query()->with(['purchase', 'receivedBy'])->latest()->limit(20)->get();
        $invoices = SupplierInvoice::query()->with(['purchase.supplier', 'approvedBy'])->latest()->limit(20)->get();
        $pendingPurchases = Purchase::query()
            ->with('supplier')
            ->where('status', 'draft')
            ->where('approval_status', '!=', 'approved')
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.procurement.index', compact(
            'requests', 'rfqs', 'receipts', 'invoices', 'pendingPurchases'
        ));
    }

    public function createRequest()
    {
        $products = Product::query()->where('is_active', true)->where('type', 'raw_material')->orderBy('name')->get();

        return view('admin.procurement.requests.create', compact('products'));
    }

    public function storeRequest(Request $request, ProcurementService $procurement)
    {
        $validated = $request->validate([
            'needed_by' => 'nullable|date',
            'justification' => 'required|string|min:5|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|distinct|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.000001',
            'items.*.unit' => 'required|string|max:30',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        $purchaseRequest = DB::transaction(function () use ($validated, $procurement) {
            $purchaseRequest = PurchaseRequest::query()->create([
                'request_no' => $procurement->nextNumber('request'),
                'status' => 'draft',
                'needed_by' => $validated['needed_by'] ?? null,
                'justification' => $validated['justification'],
                'requested_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                $purchaseRequest->items()->create($item);
            }

            return $purchaseRequest;
        });

        return redirect()->route('admin.procurement.index')
            ->with('success', "Purchase request {$purchaseRequest->request_no} created.");
    }

    public function submitRequest(PurchaseRequest $purchaseRequest, ProcurementService $procurement)
    {
        try {
            $procurement->submitRequest($purchaseRequest);
            return back()->with('success', 'Purchase request submitted for approval.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approveRequest(Request $request, PurchaseRequest $purchaseRequest, ProcurementService $procurement)
    {
        $validated = $request->validate(['approval_note' => 'nullable|string|max:1000']);

        try {
            $procurement->approveRequest($purchaseRequest, Auth::user(), $validated['approval_note'] ?? null);
            return back()->with('success', 'Purchase request approved.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function createRfq(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->load('items.product');

        return view('admin.procurement.rfqs.create', compact('purchaseRequest'));
    }

    public function storeRfq(Request $request, PurchaseRequest $purchaseRequest, ProcurementService $procurement)
    {
        $validated = $request->validate([
            'due_date' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($purchaseRequest->status !== 'approved') {
            return back()->with('error', 'Purchase request must be approved before creating an RFQ.');
        }

        $rfq = RequestForQuotation::query()->create([
            'purchase_request_id' => $purchaseRequest->id,
            'rfq_no' => $procurement->nextNumber('rfq'),
            'status' => 'draft',
            'due_date' => $validated['due_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.procurement.rfqs.show', $rfq)
            ->with('success', 'RFQ created. Add supplier quotations and open it.');
    }

    public function showRfq(RequestForQuotation $rfq)
    {
        $rfq->load(['request.items.product', 'quotes.items.product', 'quotes.supplier', 'quotes.currency']);
        $suppliers = Account::query()->where('account_type', 'supplier')->where('is_active', true)->orderBy('name')->get();
        $currencies = Currency::query()->where('is_active', true)->orderBy('code')->get();

        return view('admin.procurement.rfqs.show', compact('rfq', 'suppliers', 'currencies'));
    }

    public function openRfq(RequestForQuotation $rfq, ProcurementService $procurement)
    {
        try {
            $procurement->openRfq($rfq);
            return back()->with('success', 'RFQ opened for supplier quotations.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function storeQuote(Request $request, RequestForQuotation $rfq)
    {
        $rfq->load('request.items.product');

        $validated = $request->validate([
            'supplier_id' => 'required|exists:accounts,id',
            'currency_id' => 'required|exists:currencies,id',
            'quote_no' => 'nullable|string|max:100',
            'exchange_rate' => 'required|numeric|min:0.000001',
            'quoted_at' => 'nullable|date',
            'valid_until' => 'nullable|date',
            'terms' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.000001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($rfq, $validated) {
                $quote = RfqQuote::query()->create([
                    'request_for_quotation_id' => $rfq->id,
                    'supplier_id' => $validated['supplier_id'],
                    'currency_id' => $validated['currency_id'],
                    'quote_no' => $validated['quote_no'] ?? null,
                    'exchange_rate' => $validated['exchange_rate'],
                    'status' => 'submitted',
                    'quoted_at' => $validated['quoted_at'] ?? now()->toDateString(),
                    'valid_until' => $validated['valid_until'] ?? null,
                    'terms' => $validated['terms'] ?? null,
                    'created_by' => Auth::id(),
                ]);

                $total = 0.0;
                foreach ($validated['items'] as $line) {
                    $lineTotal = (float) $line['quantity'] * (float) $line['unit_price'];
                    $quote->items()->create([
                        'product_id' => $line['product_id'],
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                        'total' => $lineTotal,
                    ]);
                    $total += $lineTotal;
                }

                $quote->update(['total' => $total]);
            });

            return back()->with('success', 'Supplier quotation recorded.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function selectQuote(RfqQuote $quote, ProcurementService $procurement)
    {
        try {
            $quote = $procurement->selectQuote($quote);
            $purchase = $procurement->convertSelectedQuoteToPurchase($quote);

            return redirect()->route('admin.purchase-orders.show', $purchase->id)
                ->with('success', 'Supplier quote awarded and draft purchase order created.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function createReceipt(Purchase $purchase)
    {
        $purchase->load(['items.product', 'supplier']);

        return view('admin.procurement.receipts.create', compact('purchase'));
    }

    public function storeReceipt(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.purchase_item_id' => 'required|integer|distinct|exists:purchase_items,id',
            'items.*.quantity_received' => 'required|numeric|min:0',
            'items.*.quantity_rejected' => 'required|numeric|min:0',
            'items.*.unit' => 'required|string|max:30',
        ]);

        $receipt = DB::transaction(function () use ($purchase, $validated) {
            $receipt = GoodsReceipt::query()->create([
                'purchase_id' => $purchase->id,
                'receipt_no' => app(ProcurementService::class)->nextNumber('receipt'),
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $line) {
                $receipt->items()->create($line);
            }

            return $receipt;
        });

        return redirect()->route('admin.procurement.index')
            ->with('success', "Goods receipt {$receipt->receipt_no} created in draft.");
    }

    public function postReceipt(GoodsReceipt $receipt, ProcurementService $procurement)
    {
        try {
            $procurement->postGoodsReceipt($receipt, Auth::user());
            return back()->with('success', 'Goods receipt posted.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function createInvoice(Purchase $purchase)
    {
        $purchase->load(['supplier', 'currency']);

        return view('admin.procurement.invoices.create', compact('purchase'));
    }

    public function storeInvoice(Request $request, Purchase $purchase, ProcurementService $procurement)
    {
        $validated = $request->validate([
            'invoice_no' => 'required|string|max:100',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'total' => 'required|numeric|min:0',
            'usd_total' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $invoice = SupplierInvoice::query()->create([
            'purchase_id' => $purchase->id,
            'currency_id' => $purchase->currency_id,
            'invoice_no' => $validated['invoice_no'],
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'] ?? null,
            'subtotal' => $purchase->subtotal,
            'expense_total' => $purchase->expense_total,
            'total' => $validated['total'],
            'usd_total' => $validated['usd_total'],
            'status' => 'draft',
            'three_way_match_status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        $procurement->matchInvoice($invoice);

        return redirect()->route('admin.procurement.index')
            ->with('success', 'Supplier invoice saved and three-way matching executed.');
    }

    public function rematchInvoice(SupplierInvoice $invoice, ProcurementService $procurement)
    {
        try {
            $procurement->matchInvoice($invoice);
            return back()->with('success', 'Three-way match recalculated.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approveInvoice(SupplierInvoice $invoice, ProcurementService $procurement)
    {
        try {
            $procurement->approveInvoice($invoice, Auth::user());
            return back()->with('success', 'Supplier invoice approved.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function payInvoice(Request $request, SupplierInvoice $invoice, ProcurementService $procurement)
    {
        $validated = $request->validate([
            'payment_reference' => 'required|string|min:3|max:255',
        ]);

        try {
            $procurement->markInvoicePaid($invoice, Auth::user(), $validated['payment_reference']);
            return back()->with('success', 'Supplier invoice marked paid.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
