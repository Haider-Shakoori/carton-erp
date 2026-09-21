<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockAdjustmentItem;
use App\Models\StockVarianceInvestigation;
use App\Models\User;
use App\Services\StockVarianceInvestigationService;
use App\Services\InventoryPreventionIntelligenceService;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class StockVarianceInvestigationController extends Controller
{
    public function __construct(
        private readonly StockVarianceInvestigationService $service
    ) {
    }

    public function index(Request $request)
    {
        $query = StockVarianceInvestigation::query()
            ->with([
                'adjustmentItem.product',
                'adjustmentItem.adjustment.reconciliation',
                'assignee',
                'opener',
                'resolver',
            ])
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('status', $request->string('status'))
            )
            ->when(
                $request->get('assigned_to') === 'unassigned',
                fn ($q) => $q->whereNull('assigned_to')
            )
            ->when(
                $request->filled('assigned_to') && $request->get('assigned_to') !== 'unassigned',
                fn ($q) => $q->where('assigned_to', (int) $request->assigned_to)
            )
            ->when(
                $request->boolean('overdue'),
                fn ($q) => $q
                    ->where('status', '!=', StockVarianceInvestigation::STATUS_RESOLVED)
                    ->whereDate('due_date', '<', today())
            );

        $this->applyAgingFilter($query, $request->string('age_bucket')->toString());

        $investigations = $query
            ->orderByRaw(
                "CASE WHEN status = ? THEN 1 WHEN status = ? THEN 2 ELSE 3 END",
                [
                    StockVarianceInvestigation::STATUS_OPEN,
                    StockVarianceInvestigation::STATUS_INVESTIGATING,
                ]
            )
            ->orderBy('due_date')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $active = StockVarianceInvestigation::query()
            ->whereIn('status', [
                StockVarianceInvestigation::STATUS_OPEN,
                StockVarianceInvestigation::STATUS_INVESTIGATING,
            ]);

        $stats = [
            'open' => StockVarianceInvestigation::where(
                'status',
                StockVarianceInvestigation::STATUS_OPEN
            )->count(),
            'investigating' => StockVarianceInvestigation::where(
                'status',
                StockVarianceInvestigation::STATUS_INVESTIGATING
            )->count(),
            'overdue' => (clone $active)
                ->whereDate('due_date', '<', today())
                ->count(),
            'unassigned' => (clone $active)
                ->whereNull('assigned_to')
                ->count(),
            'resolved_30d' => StockVarianceInvestigation::where(
                'status',
                StockVarianceInvestigation::STATUS_RESOLVED
            )->where('resolved_at', '>=', now()->subDays(30))->count(),
            'active_value_usd' => (float) StockVarianceInvestigation::query()
                ->join(
                    'stock_adjustment_items',
                    'stock_adjustment_items.id',
                    '=',
                    'stock_variance_investigations.stock_adjustment_item_id'
                )
                ->whereIn('stock_variance_investigations.status', [
                    StockVarianceInvestigation::STATUS_OPEN,
                    StockVarianceInvestigation::STATUS_INVESTIGATING,
                ])
                ->selectRaw('COALESCE(SUM(ABS(stock_adjustment_items.adjustment_value_usd)), 0) AS value')
                ->value('value'),
        ];

        $users = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view(
            'admin.stock-reconciliations.investigations.index',
            compact('investigations', 'stats', 'users')
        );
    }

    public function intelligence(
        Request $request,
        InventoryPreventionIntelligenceService $intelligence
    ) {
        $rootCauseCodes = config(
            'stock_reconciliation.investigation.root_cause_codes',
            []
        );

        $validated = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'root_cause_code' => [
                'nullable',
                Rule::in(array_keys($rootCauseCodes)),
            ],
        ]);

        $analysis = $intelligence->analyze(
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null,
            isset($validated['product_id'])
                ? (int) $validated['product_id']
                : null,
            $validated['root_cause_code'] ?? null
        );

        $products = Product::query()
            ->where('is_active', true)
            ->where('type', Product::TYPE_RAW_MATERIAL)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view(
            'admin.stock-reconciliations.investigations.intelligence',
            compact('analysis', 'products', 'rootCauseCodes')
        );
    }

    public function show(StockVarianceInvestigation $investigation)
    {
        $investigation->load([
            'adjustmentItem.product',
            'adjustmentItem.purchaseItem.purchase',
            'adjustmentItem.adjustment.reconciliation',
            'assignee',
            'opener',
            'resolver',
            'events.user',
        ]);

        $users = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $rootCauseCodes = config(
            'stock_reconciliation.investigation.root_cause_codes',
            []
        );

        return view(
            'admin.stock-reconciliations.investigations.show',
            compact('investigation', 'users', 'rootCauseCodes')
        );
    }

    public function store(
        Request $request,
        StockAdjustmentItem $stockAdjustmentItem
    ) {
        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $investigation = $this->service->openForItem(
                $stockAdjustmentItem->load('adjustment'),
                isset($validated['assigned_to']) ? (int) $validated['assigned_to'] : null,
                $validated['notes'] ?? null
            );

            return redirect()
                ->route(
                    'admin.stock-reconciliations.investigations.show',
                    $investigation
                )
                ->with('success', 'Variance investigation opened.');
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function update(
        Request $request,
        StockVarianceInvestigation $investigation
    ) {
        $rootCauseCodes = array_keys(config(
            'stock_reconciliation.investigation.root_cause_codes',
            []
        ));

        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'root_cause_code' => ['nullable', Rule::in($rootCauseCodes)],
            'root_cause_details' => ['nullable', 'string', 'max:10000'],
            'investigation_notes' => ['nullable', 'string', 'max:10000'],
            'corrective_action' => ['nullable', 'string', 'max:10000'],
            'event_note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->service->update($investigation, $validated);

            return back()->with('success', 'Investigation details updated.');
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function resolve(
        Request $request,
        StockVarianceInvestigation $investigation
    ) {
        $rootCauseCodes = array_keys(config(
            'stock_reconciliation.investigation.root_cause_codes',
            []
        ));

        $validated = $request->validate([
            'root_cause_code' => ['required', Rule::in($rootCauseCodes)],
            'root_cause_details' => ['required', 'string', 'max:10000'],
            'corrective_action' => ['required', 'string', 'max:10000'],
            'resolution_notes' => ['required', 'string', 'max:10000'],
        ]);

        try {
            $this->service->resolve($investigation, $validated);

            return back()->with('success', 'Variance investigation resolved.');
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    private function applyAgingFilter($query, string $bucket): void
    {
        $today = today();

        match ($bucket) {
            '0-7' => $query->where('opened_at', '>=', $today->copy()->subDays(7)),
            '8-14' => $query->whereBetween('opened_at', [
                $today->copy()->subDays(14),
                $today->copy()->subDays(8)->endOfDay(),
            ]),
            '15-30' => $query->whereBetween('opened_at', [
                $today->copy()->subDays(30),
                $today->copy()->subDays(15)->endOfDay(),
            ]),
            '31+' => $query->where('opened_at', '<', $today->copy()->subDays(30)),
            default => null,
        };
    }
}
