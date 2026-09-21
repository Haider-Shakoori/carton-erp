<?php

use App\Models\StockControlEscalation;
use App\Models\StockControlReview;
use App\Models\User;
use App\Services\InventoryPreventionIntelligenceService;
use App\Services\StockControlManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function managementSignalService(array $flags): array
{
    $fake = new class($flags) extends InventoryPreventionIntelligenceService {
        public array $flags;

        public function __construct(array $flags)
        {
            $this->flags = $flags;
        }

        public function analyze(
            ?string $fromDate = null,
            ?string $toDate = null,
            ?int $productId = null,
            ?string $rootCauseCode = null
        ): array {
            return [
                'from_date' => $fromDate ?: now()->subDays(180)->toDateString(),
                'to_date' => $toDate ?: today()->toDateString(),
                'flags' => collect($this->flags),
            ];
        }
    };

    return [$fake, new StockControlManagementService($fake)];
}

function criticalManagementFlag(
    int $occurrences = 2,
    float $value = 50.0
): array {
    return [
        'type' => 'post_corrective_recurrence',
        'severity' => 'critical',
        'title' => 'Recurrence after corrective action',
        'material_name' => 'Kraft Paper',
        'message' => 'Kraft Paper repeated a confirmed production recording gap.',
        'product_id' => null,
        'root_cause_code' => 'production_recording_gap',
        'investigation_id' => 101,
        'occurrences' => $occurrences,
        'absolute_value_usd' => $value,
    ];
}

it('creates durable management escalation tables and suppresses duplicate signals', function () {
    expect(Schema::hasTable('stock_control_escalations'))->toBeTrue()
        ->and(Schema::hasTable('stock_control_escalation_events'))->toBeTrue()
        ->and(Schema::hasTable('stock_control_reviews'))->toBeTrue()
        ->and(Schema::hasTable('stock_control_review_items'))->toBeTrue();

    [$fake, $service] = managementSignalService([
        criticalManagementFlag(),
    ]);

    $first = $service->syncEscalations();

    expect($first)->toBe([
        'created' => 1,
        'updated' => 0,
        'reopened' => 0,
    ])->and(StockControlEscalation::count())->toBe(1);

    $escalation = StockControlEscalation::firstOrFail();

    expect($escalation->level)->toBe(3)
        ->and($escalation->status)->toBe(StockControlEscalation::STATUS_OPEN)
        ->and($escalation->review_due_date?->toDateString())
        ->toBe(now()->addDay()->toDateString())
        ->and($escalation->events()->count())->toBe(1);

    $second = $service->syncEscalations();

    expect($second)->toBe([
        'created' => 0,
        'updated' => 0,
        'reopened' => 0,
    ])->and(StockControlEscalation::count())->toBe(1)
        ->and($escalation->fresh()->events()->count())->toBe(1);
});

it('does not reopen a closed escalation for stale history but reopens when the signal advances', function () {
    $user = User::factory()->create(['is_active' => true]);
    Auth::login($user);

    [$fake, $service] = managementSignalService([
        criticalManagementFlag(2, 50),
    ]);

    $service->syncEscalations();
    $escalation = StockControlEscalation::firstOrFail();

    $service->close(
        $escalation,
        'Management accepted the corrective controls and closed monitoring.'
    );

    expect($escalation->fresh()->status)
        ->toBe(StockControlEscalation::STATUS_CLOSED);

    $stale = $service->syncEscalations();

    expect($stale['reopened'])->toBe(0)
        ->and($escalation->fresh()->status)
        ->toBe(StockControlEscalation::STATUS_CLOSED);

    $fake->flags = [criticalManagementFlag(3, 75)];

    $advanced = $service->syncEscalations();
    $reopened = $escalation->fresh();

    expect($advanced['reopened'])->toBe(1)
        ->and($reopened->status)->toBe(StockControlEscalation::STATUS_OPEN)
        ->and($reopened->occurrences)->toBe(3)
        ->and((float) $reopened->absolute_value_usd)->toBe(75.0)
        ->and($reopened->events()->where('event_type', 'reopened')->count())
        ->toBe(1);
});

it('tracks manager assignment acknowledgement and closure in an immutable audit timeline', function () {
    $user = User::factory()->create(['is_active' => true]);
    $manager = User::factory()->create(['is_active' => true]);
    Auth::login($user);

    [, $service] = managementSignalService([
        [
            'type' => 'recurring_root_cause',
            'severity' => 'high',
            'title' => 'Recurring root cause',
            'material_name' => 'Corn Flour',
            'message' => 'Corn Flour has repeated counting errors.',
            'product_id' => null,
            'root_cause_code' => 'counting_error',
            'investigation_id' => null,
            'occurrences' => 3,
            'absolute_value_usd' => 30,
        ],
    ]);

    $service->syncEscalations();
    $escalation = StockControlEscalation::firstOrFail();

    expect($escalation->level)->toBe(2);

    $service->assign(
        $escalation,
        $manager->id,
        now()->addDays(2)->toDateString(),
        'Warehouse manager owns this recurring control issue.'
    );
    $service->acknowledge(
        $escalation->fresh(),
        'Management reviewed the recurrence evidence.'
    );
    $closed = $service->close(
        $escalation->fresh(),
        'Cycle-count procedure and batch sign-off were reinforced.'
    );

    expect((int) $closed->escalated_to)->toBe((int) $manager->id)
        ->and($closed->status)->toBe(StockControlEscalation::STATUS_CLOSED)
        ->and((int) $closed->acknowledged_by)->toBe((int) $user->id)
        ->and((int) $closed->closed_by)->toBe((int) $user->id)
        ->and($closed->events()->pluck('event_type')->all())
        ->toBe([
            'opened',
            'assignment_updated',
            'acknowledged',
            'closed',
        ]);
});

it('generates one weekly review and freezes the completed review snapshot', function () {
    $user = User::factory()->create(['is_active' => true]);
    Auth::login($user);

    [$fake, $service] = managementSignalService([
        criticalManagementFlag(2, 50),
        [
            'type' => 'recurring_root_cause',
            'severity' => 'high',
            'title' => 'Recurring root cause',
            'material_name' => 'Corn Flour',
            'message' => 'Corn Flour has repeated counting errors.',
            'product_id' => null,
            'root_cause_code' => 'counting_error',
            'investigation_id' => null,
            'occurrences' => 3,
            'absolute_value_usd' => 30,
        ],
    ]);

    $review = $service->ensureWeeklyReview(today()->toDateString(), $user->id);
    $sameReview = $service->ensureWeeklyReview(today()->toDateString(), $user->id);

    expect($review->id)->toBe($sameReview->id)
        ->and(StockControlReview::count())->toBe(1)
        ->and($review->items)->toHaveCount(2)
        ->and($review->summary_snapshot['active_escalations'])->toBe(2)
        ->and($review->summary_snapshot['level_3'])->toBe(1)
        ->and($review->summary_snapshot['level_2'])->toBe(1);

    $completed = $service->completeReview(
        $review,
        'Reviewed all active stock-control escalations.',
        'Level 3 remains under daily follow-up; warehouse manager owns Level 2.'
    );

    $frozenItemCount = $completed->items()->count();
    $frozenSummary = $completed->summary_snapshot;

    $fake->flags[] = [
        'type' => 'overdue_investigation',
        'severity' => 'high',
        'title' => 'Overdue investigation',
        'material_name' => 'Borax',
        'message' => 'A Borax investigation is overdue.',
        'product_id' => null,
        'root_cause_code' => 'counting_error',
        'investigation_id' => 999,
        'occurrences' => 1,
        'absolute_value_usd' => 10,
    ];

    $service->syncEscalations();
    $after = $service->ensureWeeklyReview(today()->toDateString());

    expect($after->id)->toBe($completed->id)
        ->and($after->status)->toBe(StockControlReview::STATUS_COMPLETED)
        ->and($after->items()->count())->toBe($frozenItemCount)
        ->and($after->summary_snapshot)->toBe($frozenSummary);
});

it('registers the shared-hosting management control console commands', function () {
    $exitCode = Artisan::call('stock-control:weekly-review');

    expect($exitCode)->toBe(0)
        ->and(StockControlReview::count())->toBe(1)
        ->and(Artisan::output())->toContain('Weekly stock control review ready');
});
