<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockControlEscalation;
use App\Models\StockControlReview;
use App\Models\User;
use App\Services\StockControlManagementService;
use App\Services\StockControlNotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class StockControlManagementController extends Controller
{
    public function __construct(
        private readonly StockControlManagementService $service,
        private readonly StockControlNotificationService $notifications
    ) {
    }

    public function index(Request $request)
    {
        $review = $this->service->ensureWeeklyReview();

        $escalations = StockControlEscalation::query()
            ->with(['product', 'manager', 'investigation'])
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('status', $request->string('status'))
            )
            ->when(
                $request->filled('level'),
                fn ($q) => $q->where('level', (int) $request->level)
            )
            ->when(
                $request->boolean('overdue'),
                fn ($q) => $q
                    ->where('status', '!=', StockControlEscalation::STATUS_CLOSED)
                    ->whereDate('review_due_date', '<', today())
            )
            ->when(
                $request->boolean('unassigned'),
                fn ($q) => $q->whereNull('escalated_to')
            )
            ->orderByRaw(
                "CASE WHEN status = ? THEN 1 WHEN status = ? THEN 2 ELSE 3 END",
                [
                    StockControlEscalation::STATUS_OPEN,
                    StockControlEscalation::STATUS_ACKNOWLEDGED,
                ]
            )
            ->orderByDesc('level')
            ->orderBy('review_due_date')
            ->paginate(25)
            ->withQueryString();

        $active = StockControlEscalation::query()
            ->where('status', '!=', StockControlEscalation::STATUS_CLOSED);

        $stats = [
            'active' => (clone $active)->count(),
            'level_3' => (clone $active)->where('level', 3)->count(),
            'level_2' => (clone $active)->where('level', 2)->count(),
            'level_1' => (clone $active)->where('level', 1)->count(),
            'overdue' => (clone $active)
                ->whereDate('review_due_date', '<', today())
                ->count(),
            'unassigned' => (clone $active)
                ->whereNull('escalated_to')
                ->count(),
            'active_value_usd' => (float) (clone $active)
                ->sum('absolute_value_usd'),
        ];

        $recentReviews = StockControlReview::query()
            ->with(['owner', 'completer'])
            ->latest('week_start')
            ->limit(12)
            ->get();

        $users = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view(
            'admin.stock-reconciliations.management-control.index',
            compact(
                'review',
                'escalations',
                'stats',
                'recentReviews',
                'users'
            )
        );
    }

    public function sync()
    {
        $result = $this->service->syncEscalations();
        $this->service->ensureWeeklyReview();
        $this->notifications->sync();

        return back()->with(
            'success',
            sprintf(
                'Management signals synchronized: %d created, %d updated, %d reopened.',
                $result['created'],
                $result['updated'],
                $result['reopened']
            )
        );
    }

    public function showEscalation(StockControlEscalation $escalation)
    {
        $escalation->load([
            'product',
            'investigation.adjustmentItem.product',
            'manager',
            'acknowledger',
            'closer',
            'events.user',
            'reviewItems.review',
        ]);

        $users = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view(
            'admin.stock-reconciliations.management-control.escalation',
            compact('escalation', 'users')
        );
    }

    public function assign(
        Request $request,
        StockControlEscalation $escalation
    ) {
        $validated = $request->validate([
            'escalated_to' => ['nullable', 'integer', 'exists:users,id'],
            'review_due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $this->service->assign(
                $escalation,
                isset($validated['escalated_to'])
                    ? (int) $validated['escalated_to']
                    : null,
                $validated['review_due_date'] ?? null,
                $validated['notes'] ?? null
            );
            $this->notifications->sync();

            return back()->with('success', 'Escalation ownership updated.');
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function acknowledge(
        Request $request,
        StockControlEscalation $escalation
    ) {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $this->service->acknowledge(
                $escalation,
                $validated['notes'] ?? null
            );

            return back()->with('success', 'Management escalation acknowledged.');
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function close(
        Request $request,
        StockControlEscalation $escalation
    ) {
        $validated = $request->validate([
            'resolution_notes' => ['required', 'string', 'max:10000'],
        ]);

        try {
            $this->service->close(
                $escalation,
                $validated['resolution_notes']
            );

            return back()->with('success', 'Management escalation closed.');
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function showReview(StockControlReview $review)
    {
        $review->load([
            'owner',
            'completer',
            'items.escalation.product',
            'items.escalation.manager',
            'items.escalation.investigation',
        ]);

        $users = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view(
            'admin.stock-reconciliations.management-control.review',
            compact('review', 'users')
        );
    }

    public function updateReview(
        Request $request,
        StockControlReview $review
    ) {
        $validated = $request->validate([
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'review_notes' => ['nullable', 'string', 'max:10000'],
        ]);

        try {
            $this->service->updateReview(
                $review,
                isset($validated['owner_id'])
                    ? (int) $validated['owner_id']
                    : null,
                $validated['review_notes'] ?? null
            );
            $this->notifications->sync();

            return back()->with('success', 'Weekly control review updated.');
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function completeReview(
        Request $request,
        StockControlReview $review
    ) {
        $validated = $request->validate([
            'review_notes' => ['required', 'string', 'max:10000'],
            'decisions' => ['required', 'string', 'max:10000'],
        ]);

        try {
            $this->service->completeReview(
                $review,
                $validated['review_notes'],
                $validated['decisions']
            );

            return back()->with('success', 'Weekly management control review completed.');
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
