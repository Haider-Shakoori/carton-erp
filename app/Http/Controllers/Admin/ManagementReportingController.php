<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ManagementReportingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ManagementReportingController extends Controller
{
    public function index(Request $request, ManagementReportingService $reporting)
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'scope' => 'nullable|in:active,consolidated',
        ]);

        $from = Carbon::parse($validated['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($validated['to'] ?? now()->toDateString())->endOfDay();
        $consolidated = ($validated['scope'] ?? 'active') === 'consolidated';

        if ($consolidated && ! $request->user()->can('consolidated management reporting')) {
            abort(403, 'You do not have permission to view consolidated business reporting.');
        }

        $report = $reporting->overview($from, $to, $consolidated, $request->user());

        return view('admin.reports.management', compact('report', 'from', 'to', 'consolidated'));
    }
}
