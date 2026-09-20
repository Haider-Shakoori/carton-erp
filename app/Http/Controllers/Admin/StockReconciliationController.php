<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockReconciliation;
use App\Models\StockReconciliationItem;
use App\Services\StockReconciliationService;
use Illuminate\Http\Request;
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

        return view('admin.stock-reconciliations.index', compact('reconciliations'));
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
            'items.product',
            'items.purchaseItem.purchase',
        ]);

        $reasonCodes = config('stock_reconciliation.reason_codes', []);

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
            compact('stockReconciliation', 'reasonCodes', 'summary')
        );
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
}
