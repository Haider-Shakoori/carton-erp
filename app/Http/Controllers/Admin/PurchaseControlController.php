<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseSupplierInvoice;
use App\Services\PurchaseControlService;
use Illuminate\Http\Request;

class PurchaseControlController extends Controller
{
    public function approve(Request $request, Purchase $purchase, PurchaseControlService $service)
    {
        $data = $request->validate(['notes' => 'nullable|string|max:2000']);

        try {
            $service->approve($purchase, $request->user(), $data['notes'] ?? null);
            return back()->with('success', 'Purchase order approved.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function receive(Request $request, Purchase $purchase, PurchaseControlService $service)
    {
        $data = $request->validate(['notes' => 'nullable|string|max:2000']);

        try {
            $receipt = $service->receive($purchase, $request->user(), $data['notes'] ?? null);
            return back()->with('success', 'Goods receipt '.$receipt->receipt_no.' posted.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function invoice(Request $request, Purchase $purchase, PurchaseControlService $service)
    {
        $data = $request->validate([
            'invoice_no' => 'required|string|max:120',
            'invoice_date' => 'required|date',
            'currency_id' => 'required|integer|exists:currencies,id',
            'exchange_rate' => 'required|numeric|min:0.000001',
            'expense_total' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.purchase_item_id' => 'required|integer|distinct|exists:purchase_items,id',
            'items.*.quantity' => 'required|numeric|min:0.000001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            $invoice = $service->recordSupplierInvoice($purchase, $data, $request->user());
            return back()->with('success', 'Supplier invoice '.$invoice->invoice_no.' recorded.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function match(
        Request $request,
        Purchase $purchase,
        PurchaseSupplierInvoice $invoice,
        PurchaseControlService $service
    ) {
        try {
            $result = $service->match($purchase, $invoice, $request->user());

            return back()->with(
                $result['matched'] ? 'success' : 'warning',
                $result['matched']
                    ? 'Three-way match passed.'
                    : 'Three-way match exceptions: '.implode(' ', $result['exceptions'])
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
