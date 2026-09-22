<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BOM;
use App\Services\BOMGovernanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BOMGovernanceController extends Controller
{
    public function revise(BOM $bom, BOMGovernanceService $governance)
    {
        try {
            $revision = $governance->createRevision($bom, Auth::user());

            return redirect()
                ->route('bom.edit', $revision)
                ->with('success', "Draft revision {$revision->version} created. Edit and approve it when ready.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approve(Request $request, BOM $bom, BOMGovernanceService $governance)
    {
        $validated = $request->validate([
            'effective_from' => 'nullable|date',
        ]);

        try {
            $revision = $governance->approveRevision(
                $bom,
                Auth::user(),
                $validated['effective_from'] ?? now()
            );

            return redirect()
                ->route('bom.show', $revision)
                ->with('success', "BOM {$revision->version} approved, effective and locked.");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
