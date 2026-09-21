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
