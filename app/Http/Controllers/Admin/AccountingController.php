<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnit;
use App\Models\FiscalPeriod;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AccountingController extends Controller
{
    public function index(Request $request, AccountingService $accounting)
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'as_of' => 'nullable|date',
            'scope' => 'nullable|in:active,consolidated',
        ]);

        $from = Carbon::parse($validated['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($validated['to'] ?? now()->toDateString())->endOfDay();
        $asOf = Carbon::parse($validated['as_of'] ?? $to->toDateString())->endOfDay();
        $consolidated = ($validated['scope'] ?? 'active') === 'consolidated';

        if ($consolidated && ! $request->user()->can('consolidated management reporting')) {
            abort(403, 'Consolidated financial reporting is not permitted for this user.');
        }

        $allowedIds = $this->allowedBusinessUnitIds($request);

        $trialBalance = $accounting->trialBalance($asOf, $consolidated, $allowedIds);
        $profitAndLoss = $accounting->profitAndLoss($from, $to, $consolidated, $allowedIds);
        $balanceSheet = $accounting->balanceSheet($asOf, $consolidated, $allowedIds);
        $cashFlow = $accounting->cashFlow($from, $to, $consolidated, $allowedIds);
        $periods = FiscalPeriod::query()->orderByDesc('starts_on')->get();

        return view('admin.accounting.index', compact(
            'from',
            'to',
            'asOf',
            'consolidated',
            'trialBalance',
            'profitAndLoss',
            'balanceSheet',
            'cashFlow',
            'periods'
        ));
    }

    public function storePeriod(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after_or_equal:starts_on',
            'notes' => 'nullable|string|max:1000',
        ]);

        $overlap = FiscalPeriod::query()
            ->whereDate('starts_on', '<=', $validated['ends_on'])
            ->whereDate('ends_on', '>=', $validated['starts_on'])
            ->exists();

        if ($overlap) {
            return back()->withErrors([
                'starts_on' => 'Fiscal periods cannot overlap an existing accounting period.',
            ])->withInput();
        }

        FiscalPeriod::create($validated + ['status' => 'open']);

        return back()->with('success', 'Fiscal period created.');
    }

    public function closePeriod(Request $request, FiscalPeriod $period)
    {
        $validated = $request->validate(['reason' => 'required|string|min:3|max:1000']);

        return $this->transitionPeriod($period, function (FiscalPeriod $locked) use ($validated) {
            if ($locked->status !== 'open') {
                throw new RuntimeException('Only open fiscal periods can be closed.');
            }

            $locked->status = 'closed';
            $locked->closed_by = Auth::id();
            $locked->closed_at = now();
            $locked->notes = trim(($locked->notes ? $locked->notes."\n" : '').'Close: '.$validated['reason']);
            $locked->save();
        }, 'Fiscal period closed. Posting into the period is now blocked.');
    }

    public function lockPeriod(Request $request, FiscalPeriod $period)
    {
        $validated = $request->validate(['reason' => 'required|string|min:3|max:1000']);

        return $this->transitionPeriod($period, function (FiscalPeriod $locked) use ($validated) {
            if ($locked->status !== 'closed') {
                throw new RuntimeException('Only closed periods can be permanently locked.');
            }

            $locked->status = 'locked';
            $locked->locked_by = Auth::id();
            $locked->locked_at = now();
            $locked->notes = trim(($locked->notes ? $locked->notes."\n" : '').'Lock: '.$validated['reason']);
            $locked->save();
        }, 'Fiscal period permanently locked.');
    }

    public function reopenPeriod(Request $request, FiscalPeriod $period)
    {
        $validated = $request->validate(['reason' => 'required|string|min:3|max:1000']);

        return $this->transitionPeriod($period, function (FiscalPeriod $locked) use ($validated) {
            if ($locked->status === 'locked') {
                throw new RuntimeException('Locked fiscal periods cannot be reopened.');
            }

            if ($locked->status !== 'closed') {
                throw new RuntimeException('Only closed periods can be reopened.');
            }

            $locked->status = 'open';
            $locked->closed_by = null;
            $locked->closed_at = null;
            $locked->notes = trim(($locked->notes ? $locked->notes."\n" : '').'Reopen: '.$validated['reason']);
            $locked->save();
        }, 'Fiscal period reopened.');
    }

    private function transitionPeriod(
        FiscalPeriod $period,
        callable $transition,
        string $message
    ) {
        try {
            DB::transaction(function () use ($period, $transition) {
                $locked = FiscalPeriod::query()->lockForUpdate()->findOrFail($period->id);
                $transition($locked);
            });

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function allowedBusinessUnitIds(Request $request): array
    {
        $assigned = $request->user()
            ->businessUnits()
            ->pluck('business_units.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $assigned !== []
            ? $assigned
            : BusinessUnit::query()->active()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
