<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductionReelConsumption;
use App\Models\PurchaseItem;
use App\Models\PurchaseItemReel;
use App\Services\ReelInventoryService;
use App\Services\StockReconciliationService;
use Illuminate\Http\Request;
use RuntimeException;

class ReelInventoryController extends Controller
{
    public function __construct(
        private readonly ReelInventoryService $service
    ) {
    }

    public function index(Request $request)
    {
        $query = PurchaseItem::query()
            ->whereRaw('LOWER(unit) = ?', ['roll'])
            ->whereHas(
                'purchase',
                fn ($q) => $q->where('status', 'arrived')
            )
            ->with([
                'product',
                'purchase',
                'reels.measuredBy',
            ])
            ->orderByDesc('id');

        $tracking = $request->string('tracking')->toString();

        if ($tracking === 'tracked') {
            $query->whereHas('reels');
        } elseif ($tracking === 'untracked') {
            $query->whereDoesntHave('reels');
        }

        $batches = $query->paginate(25)->withQueryString();

        $summaries = [];
        foreach ($batches as $batch) {
            $summaries[$batch->id] = $this->service->summary($batch);
        }

        if ($tracking === 'out_of_sync') {
            $filtered = $batches->getCollection()
                ->filter(
                    fn ($batch) =>
                        ($summaries[$batch->id]['tracked'] ?? false)
                        && ! ($summaries[$batch->id]['healthy'] ?? true)
                )
                ->values();
            $batches->setCollection($filtered);
        }

        $allRollBatches = PurchaseItem::query()
            ->whereRaw('LOWER(unit) = ?', ['roll'])
            ->whereHas(
                'purchase',
                fn ($q) => $q->where('status', 'arrived')
            )
            ->with('reels')
            ->get();

        $measurementStates = $allRollBatches
            ->filter(fn ($batch) => $batch->reels->isNotEmpty())
            ->mapWithKeys(fn ($batch) => [
                $batch->id => $this->service->measurementReadiness(
                    $batch,
                    $batch->reels
                ),
            ]);

        $stats = [
            'batches' => $allRollBatches->count(),
            'tracked' => $allRollBatches
                ->filter(fn ($batch) => $batch->reels->isNotEmpty())
                ->count(),
            'untracked' => $allRollBatches
                ->filter(fn ($batch) => $batch->reels->isEmpty())
                ->count(),
            'out_of_sync' => $allRollBatches
                ->filter(function ($batch): bool {
                    if ($batch->reels->isEmpty()) {
                        return false;
                    }

                    $tracked = (float) $batch->reels->sum(
                        'system_remaining_weight_kg'
                    );

                    return abs($tracked - $batch->availableKg()) > 0.05;
                })
                ->count(),
            'open_reels' => $allRollBatches
                ->flatMap->reels
                ->where('status', PurchaseItemReel::STATUS_OPEN)
                ->count(),
            'blocked_reels' => $allRollBatches
                ->flatMap->reels
                ->whereIn('status', [
                    PurchaseItemReel::STATUS_DAMAGED,
                    PurchaseItemReel::STATUS_QUARANTINED,
                ])
                ->count(),
            'weighing_incomplete' => $measurementStates
                ->where('state', 'incomplete')
                ->count(),
            'measurements_stale' => $measurementStates
                ->where('state', 'stale')
                ->count(),
            'ready_to_reconcile' => $measurementStates
                ->where('state', 'reconcile')
                ->count(),
            'measurements_aligned' => $measurementStates
                ->where('state', 'aligned')
                ->count(),
            'inventory_value_usd' => (float) $allRollBatches->sum(
                fn ($batch) => $batch->availableKg()
                    * $batch->landedCostPerKg()
            ),
        ];

        return view(
            'admin.stock-reels.index',
            compact('batches', 'summaries', 'stats')
        );
    }

    public function show(PurchaseItem $purchaseItem)
    {
        $this->ensureRollBatch($purchaseItem);

        $purchaseItem->load([
            'product',
            'purchase',
            'reels.measuredBy',
            'reels.measurements.measurer',
            'reels.statusChangedBy',
            'reels.statusEvents.changedBy',
        ]);

        $summary = $this->service->summary($purchaseItem);
        $summary['reels']->load([
            'measurements.measurer',
            'statusEvents.changedBy',
            'statusChangedBy',
        ]);

        $recentConsumptions = ProductionReelConsumption::query()
            ->with([
                'reel',
                'selectedBy',
                'materialConsumption.productionOrder',
            ])
            ->whereHas(
                'reel',
                fn ($q) => $q->where(
                    'purchase_item_id',
                    $purchaseItem->id
                )
            )
            ->latest('consumed_at')
            ->limit(50)
            ->get();

        return view(
            'admin.stock-reels.show',
            compact(
                'purchaseItem',
                'summary',
                'recentConsumptions'
            )
        );
    }

    public function initialize(
        Request $request,
        PurchaseItem $purchaseItem
    ) {
        $this->ensureRollBatch($purchaseItem);

        $validated = $request->validate([
            'current_reel_weights' => [
                'nullable',
                'string',
                'max:10000',
            ],
        ]);

        $weights = $this->parseWeights(
            $validated['current_reel_weights'] ?? null
        );

        try {
            $this->service->initializeBatch(
                $purchaseItem,
                $weights ?: null
            );

            return redirect()
                ->route('admin.stock-reels.show', $purchaseItem)
                ->with(
                    'success',
                    'Physical reel tracking initialized. Batch kg inventory was not changed.'
                );
        } catch (RuntimeException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function measure(
        Request $request,
        PurchaseItemReel $reel
    ) {
        $validated = $request->validate([
            'measured_weight_kg' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999',
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $this->service->recordMeasurement(
                $reel,
                (float) $validated['measured_weight_kg'],
                $validated['notes'] ?? null
            );

            return back()->with(
                'success',
                'Reel remnant measurement recorded. Inventory quantity was not changed.'
            );
        } catch (RuntimeException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function status(
        Request $request,
        PurchaseItemReel $reel
    ) {
        $validated = $request->validate([
            'action' => [
                'required',
                'string',
                'in:damaged,quarantined,release',
            ],
            'reason' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $updated = $this->service->changeControlStatus(
                $reel,
                $validated['action'],
                $validated['reason']
            );

            return back()->with(
                'success',
                sprintf(
                    'Reel %s status changed to %s. Inventory quantity was not changed.',
                    $updated->reel_code,
                    $updated->status
                )
            );
        } catch (RuntimeException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function startReconciliation(
        PurchaseItem $purchaseItem,
        StockReconciliationService $reconciliationService
    ) {
        $this->ensureRollBatch($purchaseItem);

        try {
            $reconciliation = $reconciliationService
                ->createFromReelMeasurements($purchaseItem);

            return redirect()
                ->route('admin.stock-reconciliations.show', $reconciliation)
                ->with(
                    'success',
                    'Draft reconciliation created from the latest reel measurements. Inventory has not changed; normal submission, approval and posting are still required.'
                );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function rebaseline(PurchaseItem $purchaseItem)
    {
        $this->ensureRollBatch($purchaseItem);

        try {
            $this->service
                ->rebaselineFromLatestMeasurements($purchaseItem);

            return back()->with(
                'success',
                'Physical reel system weights re-baselined to the latest measurements. The authoritative batch kg balance was not changed.'
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function ensureRollBatch(PurchaseItem $purchaseItem): void
    {
        if (! $purchaseItem->isRollBatch()) {
            abort(404);
        }
    }

    private function parseWeights(?string $raw): array
    {
        if (! $raw || trim($raw) === '') {
            return [];
        }

        return collect(
            preg_split('/[\s,;]+/', trim($raw)) ?: []
        )
            ->filter(fn ($value) => $value !== '')
            ->map(fn ($value) => (float) $value)
            ->values()
            ->all();
    }
}
