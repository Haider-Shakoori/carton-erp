<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockReconciliation;
use App\Models\StockReconciliationItem;
use App\Models\StockAdjustmentItem;
use App\Models\StockVarianceInvestigation;
use App\Models\Product;
use App\Services\StockReconciliationService;
use App\Services\StockCycleCountPlanningService;
use App\Services\InventoryControlAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

class StockReconciliationController extends Controller
{
    public function __construct(private StockReconciliationService $service)
    {
    }

    public function index(Request $request)
    {
        $reconciliations = StockReconciliation::query()
            ->with(['creator', 'submitter'])
            ->withCount('items')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('from_date'), fn ($q) => $q->whereDate('count_date', '>=', $request->date('from_date')))
            ->when($request->filled('to_date'), fn ($q) => $q->whereDate('count_date', '<=', $request->date('to_date')))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'counting' => StockReconciliation::where('status', StockReconciliation::STATUS_COUNTING)->count(),
            'awaiting_approval' => StockReconciliation::where('status', StockReconciliation::STATUS_SUBMITTED)->count(),
            'awaiting_post' => StockReconciliation::where('status', StockReconciliation::STATUS_APPROVED)->count(),
            'last_posted_at' => StockReconciliation::where('status', StockReconciliation::STATUS_POSTED)->max('posted_at'),
            'negative_variance_30d_usd' => abs((float) StockAdjustmentItem::query()
                ->where('adjustment_value_usd', '<', 0)
                ->whereHas('adjustment', fn ($q) => $q->where('posted_at', '>=', now()->subDays(30)))
                ->sum('adjustment_value_usd')),
            'unresolved_count' => StockVarianceInvestigation::query()
                ->where('status', '!=', StockVarianceInvestigation::STATUS_RESOLVED)
                ->count(),
            'unresolved_value_usd' => (float) StockVarianceInvestigation::query()
                ->join(
                    'stock_adjustment_items',
                    'stock_adjustment_items.id',
                    '=',
                    'stock_variance_investigations.stock_adjustment_item_id'
                )
                ->where(
                    'stock_variance_investigations.status',
                    '!=',
                    StockVarianceInvestigation::STATUS_RESOLVED
                )
                ->selectRaw('COALESCE(SUM(ABS(stock_adjustment_items.adjustment_value_usd)), 0) AS value')
                ->value('value'),
        ];

        return view('admin.stock-reconciliations.index', compact('reconciliations', 'stats'));
    }

    public function planning(Request $request, StockCycleCountPlanningService $planning)
    {
        $validated = $request->validate([
            'as_of' => ['nullable', 'date'],
        ]);

        $plan = $planning->plan($validated['as_of'] ?? null);

        return view('admin.stock-reconciliations.planning', compact('plan'));
    }

    public function startPlannedCount(Request $request)
    {
        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'count_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $reconciliation = $this->service->createSnapshot(
                $validated['count_date'],
                $validated['notes'] ?? 'ABC / targeted cycle count',
                $validated['product_ids']
            );

            return redirect()
                ->route('admin.stock-reconciliations.show', $reconciliation)
                ->with(
                    'success',
                    'Targeted cycle count created for the selected materials. Inventory has not been changed.'
                );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function trends(Request $request, InventoryControlAnalysisService $analysis)
    {
        $validated = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'interval' => ['nullable', Rule::in(['week', 'month'])],
        ]);

        $trend = $analysis->varianceTrend(
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null,
            $validated['interval'] ?? 'week'
        );

        return view('admin.stock-reconciliations.trends', compact('trend'));
    }

    public function controlAnalysis(Request $request, InventoryControlAnalysisService $analysis)
    {
        $validated = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);

        $control = $analysis->controlAnalysis(
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null,
            isset($validated['product_id']) ? (int) $validated['product_id'] : null
        );

        $products = Product::query()
            ->where('is_active', true)
            ->where('type', Product::TYPE_RAW_MATERIAL)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view(
            'admin.stock-reconciliations.control-analysis',
            compact('control', 'products')
        );
    }

    public function report(Request $request)
    {
        $query = $this->reportQuery($request);

        $allRows = (clone $query)->get();
        $rows = $query->paginate(30)->withQueryString();

        $unresolvedReasonCodes = config(
            'stock_reconciliation.unresolved_reason_codes',
            ['unknown']
        );

        $unresolvedRows = $allRows->filter(function ($row) use ($unresolvedReasonCodes): bool {
            return in_array($row->reason_code, $unresolvedReasonCodes, true)
                && (
                    ! $row->investigation
                    || $row->investigation->status !== StockVarianceInvestigation::STATUS_RESOLVED
                );
        });

        $summary = [
            'lines' => $allRows->count(),
            'positive_value_usd' => (float) $allRows->where('adjustment_value_usd', '>', 0)->sum('adjustment_value_usd'),
            'negative_value_usd' => (float) $allRows->where('adjustment_value_usd', '<', 0)->sum('adjustment_value_usd'),
            'net_value_usd' => (float) $allRows->sum('adjustment_value_usd'),
            'unresolved_lines' => $unresolvedRows->count(),
            'unresolved_value_usd' => (float) $unresolvedRows
                ->sum(fn ($row) => abs((float) $row->adjustment_value_usd)),
        ];

        $topMaterials = $allRows
            ->groupBy('product_id')
            ->map(function ($items) {
                $first = $items->first();
                return [
                    'name' => $first->product?->name ?? ('Product #' . $first->product_id),
                    'occurrences' => $items->count(),
                    'absolute_value_usd' => (float) $items->sum(fn ($row) => abs((float) $row->adjustment_value_usd)),
                    'net_value_usd' => (float) $items->sum('adjustment_value_usd'),
                ];
            })
            ->sortByDesc('absolute_value_usd')
            ->take(10)
            ->values();

        $products = Product::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $reasonCodes = config('stock_reconciliation.reason_codes', []);

        return view(
            'admin.stock-reconciliations.report',
            compact(
                'rows',
                'summary',
                'topMaterials',
                'products',
                'reasonCodes',
                'unresolvedReasonCodes'
            )
        );
    }

    public function exportCsv(Request $request)
    {
        $rows = $this->reportQuery($request)->get();
        $reasonCodes = config('stock_reconciliation.reason_codes', []);

        return response()->streamDownload(function () use ($rows, $reasonCodes): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Adjustment',
                'Count Reference',
                'Date',
                'Material',
                'Batch ID',
                'Unit',
                'Before',
                'Adjustment',
                'After',
                'Value USD',
                'Reason',
            ]);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->adjustment?->adjustment_no,
                    $row->adjustment?->reconciliation?->reconciliation_no,
                    $row->adjustment?->adjustment_date?->toDateString(),
                    $row->product?->name,
                    $row->purchase_item_id,
                    $row->inventory_unit,
                    $row->before_quantity,
                    $row->adjustment_quantity,
                    $row->after_quantity,
                    $row->adjustment_value_usd,
                    $reasonCodes[$row->reason_code] ?? $row->reason_code,
                ]);
            }

            fclose($out);
        }, 'stock-reconciliation-variance-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function print(StockReconciliation $stockReconciliation, Request $request)
    {
        $stockReconciliation->load([
            'creator',
            'submitter',
            'approver',
            'poster',
            'items.product',
            'items.purchaseItem.purchase',
        ]);

        $blind = $request->boolean('blind');
        $reasonCodes = config('stock_reconciliation.reason_codes', []);

        return view(
            'admin.stock-reconciliations.print',
            compact('stockReconciliation', 'blind', 'reasonCodes')
        );
    }

    public function create()
    {
        return view('admin.stock-reconciliations.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'count_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $reconciliation = $this->service->createSnapshot(
                $validated['count_date'],
                $validated['notes'] ?? null
            );

            return redirect()
                ->route('admin.stock-reconciliations.show', $reconciliation)
                ->with('success', 'Stock count created. System quantities are frozen as the reconciliation baseline.');
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(StockReconciliation $stockReconciliation)
    {
        $stockReconciliation->load([
            'creator',
            'submitter',
            'approver',
            'poster',
            'rejecter',
            'adjustment.items.product',
            'adjustment.items.purchaseItem',
            'items.product',
            'items.purchaseItem.purchase',
        ]);

        $reasonCodes = config('stock_reconciliation.reason_codes', []);
        $requiresIndependentApproval = $this->service
            ->requiresIndependentApproval($stockReconciliation);
        $independentApprovalThresholdUsd = (float) config(
            'stock_reconciliation.independent_approval_required_above_usd',
            100.00
        );

        $summary = [
            'lines' => $stockReconciliation->items->count(),
            'counted' => $stockReconciliation->items->whereNotNull('physical_quantity')->count(),
            'positive_variance_usd' => (float) $stockReconciliation->items
                ->where('variance_value_usd', '>', 0)
                ->sum('variance_value_usd'),
            'negative_variance_usd' => (float) $stockReconciliation->items
                ->where('variance_value_usd', '<', 0)
                ->sum('variance_value_usd'),
            'net_variance_usd' => (float) $stockReconciliation->items->sum('variance_value_usd'),
        ];

        return view(
            'admin.stock-reconciliations.show',
            compact(
                'stockReconciliation',
                'reasonCodes',
                'summary',
                'requiresIndependentApproval',
                'independentApprovalThresholdUsd'
            )
        );
    }

    public function updateCounts(Request $request, StockReconciliation $stockReconciliation)
    {
        $reasonCodes = array_keys(config('stock_reconciliation.reason_codes', []));

        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.physical_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999999.999999'],
            'items.*.reason_code' => ['nullable', Rule::in($reasonCodes)],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            DB::transaction(function () use ($validated, $stockReconciliation): void {
                foreach ($validated['items'] as $itemId => $row) {
                    if (! array_key_exists('physical_quantity', $row)
                        || $row['physical_quantity'] === null
                        || $row['physical_quantity'] === '') {
                        continue;
                    }

                    $item = StockReconciliationItem::query()
                        ->where('stock_reconciliation_id', $stockReconciliation->id)
                        ->findOrFail((int) $itemId);

                    $this->service->updateCount(
                        $stockReconciliation,
                        $item,
                        (float) $row['physical_quantity'],
                        $row['reason_code'] ?? null,
                        $row['notes'] ?? null
                    );
                }
            });

            return back()->with('success', 'Physical counts saved. Inventory has not been changed.');
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function updateItem(
        Request $request,
        StockReconciliation $stockReconciliation,
        StockReconciliationItem $item
    ) {
        $reasonCodes = array_keys(config('stock_reconciliation.reason_codes', []));

        $validated = $request->validate([
            'physical_quantity' => ['required', 'numeric', 'min:0', 'max:999999999999.999999'],
            'reason_code' => ['nullable', Rule::in($reasonCodes)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->service->updateCount(
                $stockReconciliation,
                $item,
                (float) $validated['physical_quantity'],
                $validated['reason_code'] ?? null,
                $validated['notes'] ?? null
            );

            return back()->with('success', 'Physical count saved.');
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function cancel(StockReconciliation $stockReconciliation)
    {
        try {
            $this->service->cancel($stockReconciliation);

            return redirect()
                ->route('admin.stock-reconciliations.index')
                ->with('success', 'Stock reconciliation cancelled. Inventory was not changed.');
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approve(StockReconciliation $stockReconciliation)
    {
        try {
            $this->service->approve($stockReconciliation);

            return back()->with('success', 'Stock reconciliation approved. No stock has changed yet; posting is still required.');
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, StockReconciliation $stockReconciliation)
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $this->service->reject($stockReconciliation, $validated['rejection_reason']);

            return back()->with('success', 'Stock reconciliation rejected without changing inventory.');
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function post(StockReconciliation $stockReconciliation)
    {
        try {
            $adjustment = $this->service->post($stockReconciliation);

            return redirect()
                ->route('admin.stock-reconciliations.show', $stockReconciliation)
                ->with('success', 'Stock reconciliation posted as '.$adjustment->adjustment_no.'. FIFO batch balances are now adjusted with a full audit trail.');
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function submit(StockReconciliation $stockReconciliation)
    {
        try {
            $this->service->submit($stockReconciliation);

            return redirect()
                ->route('admin.stock-reconciliations.show', $stockReconciliation)
                ->with('success', 'Stock reconciliation submitted for approval. Inventory has not been changed.');
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
    private function reportQuery(Request $request)
    {
        return StockAdjustmentItem::query()
            ->with(['product', 'adjustment.reconciliation', 'investigation.assignee'])
            ->whereHas('adjustment', function ($query) use ($request): void {
                $query->when(
                    $request->filled('from_date'),
                    fn ($q) => $q->whereDate('adjustment_date', '>=', $request->date('from_date'))
                )->when(
                    $request->filled('to_date'),
                    fn ($q) => $q->whereDate('adjustment_date', '<=', $request->date('to_date'))
                );
            })
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', (int) $request->product_id))
            ->when($request->filled('reason_code'), fn ($q) => $q->where('reason_code', $request->reason_code))
            ->when(
                $request->boolean('unresolved'),
                function ($q): void {
                    $q->whereIn(
                        'reason_code',
                        config('stock_reconciliation.unresolved_reason_codes', ['unknown'])
                    )->where(function ($caseQuery): void {
                        $caseQuery
                            ->whereDoesntHave('investigation')
                            ->orWhereHas(
                                'investigation',
                                fn ($investigation) => $investigation->where(
                                    'status',
                                    '!=',
                                    StockVarianceInvestigation::STATUS_RESOLVED
                                )
                            );
                    });
                }
            )
            ->when($request->get('direction') === 'shortage', fn ($q) => $q->where('adjustment_quantity', '<', 0))
            ->when($request->get('direction') === 'surplus', fn ($q) => $q->where('adjustment_quantity', '>', 0))
            ->latest('id');
    }

}
