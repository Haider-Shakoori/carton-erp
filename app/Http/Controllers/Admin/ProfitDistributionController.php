<?php
// app/Http/Controllers/Admin/ProfitDistributionController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfitDistribution;
use App\Models\ProfitDistributionItem;
use App\Models\Shareholder;
use App\Models\Currency;
use App\Services\ProfitSharingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProfitDistributionController extends Controller
{
    protected $profitService;

    public function __construct(ProfitSharingService $profitService)
    {
        $this->profitService = $profitService;
    }

    /**
     * Display a listing of profit distributions.
     */
    public function index(Request $request)
    {
        $query = ProfitDistribution::with(['createdBy', 'approvedBy']);

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->start_date) {
            $query->where('period_start', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->where('period_end', '<=', $request->end_date);
        }

        $distributions = $query->latest()->paginate(15);
        $defaultCurrency = Currency::where('is_default', true)->first();

        return view('admin.profit-distributions.index', compact('distributions', 'defaultCurrency'));
    }

    /**
     * Show the form for creating a new distribution.
     */
    public function create()
    {
        $shareholders = Shareholder::where('is_active', true)->get();
        $defaultCurrency = Currency::where('is_default', true)->first();

        // Check if shares total 100%
        $totalPercentage = $shareholders->sum('share_percentage');
        if (round($totalPercentage, 2) != 100) {
            return redirect()->route('admin.shareholder-settings.index')
                ->with('error', "Shareholders total {$totalPercentage}%. Please adjust to 100% before creating a distribution.");
        }

        // Get current month
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        // Check if distribution already exists for this period
        $existing = ProfitDistribution::where('period_start', $startDate)
            ->where('period_end', $endDate)
            ->whereIn('status', ['approved', 'distributed'])
            ->first();

        if ($existing) {
            return redirect()->route('admin.profit-distributions.index')
                ->with('warning', 'A distribution already exists for this period.');
        }

        // Calculate profit preview
        $profitData = $this->profitService->calculatePeriodProfit($startDate, $endDate);

        return view('admin.profit-distributions.create', compact(
            'shareholders',
            'defaultCurrency',
            'startDate',
            'endDate',
            'profitData'
        ));
    }

    /**
     * Calculate profit for a period (AJAX).
     */
    public function calculateProfit(Request $request)
    {
        try {
            $validated = $request->validate([
                'period_start' => 'required|date',
                'period_end' => 'required|date|after_or_equal:period_start',
            ]);

            $profitData = $this->profitService->calculatePeriodProfit(
                Carbon::parse($validated['period_start']),
                Carbon::parse($validated['period_end'])
            );

            $shareholders = Shareholder::where('is_active', true)
                ->orderBy('id')
                ->get();

            // Allocate at currency precision and assign the rounding remainder
            // to the final shareholder. This mirrors ProfitSharingService so
            // the preview always totals exactly to the distributable profit.
            $shareDistribution = [];
            $totalProfit = round((float) $profitData['total_profit'], 2);
            $allocated = 0.0;
            $lastIndex = $shareholders->count() - 1;

            foreach ($shareholders->values() as $index => $shareholder) {
                $amount = $index === $lastIndex
                    ? round($totalProfit - $allocated, 2)
                    : round($totalProfit * ((float) $shareholder->share_percentage / 100), 2);

                $shareDistribution[] = [
                    'shareholder_id' => $shareholder->id,
                    'name' => $shareholder->name,
                    'share_percentage' => $shareholder->share_percentage,
                    'amount' => $amount,
                ];

                $allocated += $amount;
            }

            return response()->json([
                'success' => true,
                'data' => array_merge($profitData, [
                    // Keep the nested payload for backward compatibility while
                    // also exposing the fields expected by the current Blade JS.
                    'profit_data' => $profitData,
                    'shareholders' => $shareDistribution,
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created distribution.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'period_start' => 'required|date',
                'period_end' => 'required|date|after_or_equal:period_start',
                'notes' => 'nullable|string',
            ]);

            $startDate = Carbon::parse($validated['period_start']);
            $endDate = Carbon::parse($validated['period_end']);

            // Create distribution using the service
            $distribution = $this->profitService->distributeProfitLoss(
                $startDate,
                $endDate,
                $validated['notes'] ?? null,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Profit distribution created successfully.',
                'data' => $distribution
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified distribution.
     */
    public function show(ProfitDistribution $distribution)
    {
        $distribution->load(['items.shareholder', 'createdBy', 'approvedBy']);
        $defaultCurrency = Currency::where('is_default', true)->first();

        $summary = $this->profitService->getDistributionSummary($distribution->id);

        return view('admin.profit-distributions.show', compact('distribution', 'defaultCurrency', 'summary'));
    }

    /**
     * Delete a distribution.
     */
    public function destroy(ProfitDistribution $distribution)
    {
        try {
            $this->profitService->deleteDistribution($distribution->id);

            return response()->json([
                'success' => true,
                'message' => 'Distribution deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get distribution summary (AJAX).
     */
    public function getSummary(ProfitDistribution $distribution)
    {
        $summary = $this->profitService->getDistributionSummary($distribution->id);

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Show the form for editing a draft distribution.
     */
    public function edit(ProfitDistribution $distribution)
    {
        if ($distribution->status !== ProfitDistribution::STATUS_DRAFT) {
            return redirect()->route('admin.profit-distributions.index')
                ->with('warning', 'Only draft distributions can be edited.');
        }

        $distribution->load(['createdBy', 'items.shareholder']);

        return view('admin.profit-distributions.edit', compact('distribution'));
    }

    /**
     * Update the period/notes of a draft distribution. Monetary values are
     * not recalculated here; they stay as originally computed.
     */
    public function update(Request $request, ProfitDistribution $distribution)
    {
        try {
            if ($distribution->status !== ProfitDistribution::STATUS_DRAFT) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft distributions can be updated.'
                ], 422);
            }

            $validated = $request->validate([
                'period_start' => 'required|date',
                'period_end' => 'required|date|after_or_equal:period_start',
                'notes' => 'nullable|string',
            ]);

            $distribution->update([
                'period_start' => Carbon::parse($validated['period_start']),
                'period_end' => Carbon::parse($validated['period_end']),
                'notes' => $validated['notes'] ?? null,
            ]);

            return redirect()->route('admin.profit-distributions.index')
                ->with('success', 'Distribution updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'An error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }
}
