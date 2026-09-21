<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Services\ProductionOrderGovernanceService;
use Illuminate\Http\Request;

class ProductionGovernanceController extends Controller
{
    public function approve(
        Request $request,
        ProductionOrder $productionOrder,
        ProductionOrderGovernanceService $service
    ) {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $service->approve($productionOrder, $request->user(), $validated['notes'] ?? null);

            return back()->with('success', 'Production order approved.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function close(
        Request $request,
        ProductionOrder $productionOrder,
        ProductionOrderGovernanceService $service
    ) {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:2000',
        ]);

        try {
            $service->close($productionOrder, $request->user(), $validated['reason'] ?? null);

            return back()->with('success', 'Production order closed and locked from ordinary editing.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reopen(
        Request $request,
        ProductionOrder $productionOrder,
        ProductionOrderGovernanceService $service
    ) {
        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:2000',
        ]);

        try {
            $service->reopen($productionOrder, $validated['reason'], $request->user());

            return back()->with('success', 'Production order reopened for controlled review.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function reverse(
        Request $request,
        ProductionOrder $productionOrder,
        ProductionOrderGovernanceService $service
    ) {
        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:2000',
        ]);

        try {
            $service->reverse($productionOrder, $validated['reason'], $request->user());

            return back()->with('success', 'Production order reversed. Actual raw-material consumption was restored to inventory.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
