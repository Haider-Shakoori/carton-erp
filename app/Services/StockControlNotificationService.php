<?php

namespace App\Services;

use App\Jobs\DeliverStockControlNotification;
use App\Models\StockControlEscalation;
use App\Models\StockControlEscalationEvent;
use App\Models\StockControlReview;
use App\Models\StockNotificationDelivery;
use App\Models\StockNotificationPreference;
use App\Models\StockVarianceInvestigation;
use App\Models\StockVarianceInvestigationEvent;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class StockControlNotificationService
{
    public function syncSafely(): array
    {
        try {
            return $this->sync();
        } catch (Throwable $e) {
            Log::error('Stock-control notification synchronization failed.', [
                'exception' => $e::class,
                'error' => mb_substr($e->getMessage(), 0, 1000),
            ]);

            return [
                'created' => 0,
                'queued' => 0,
                'skipped' => 0,
                'failed' => true,
            ];
        }
    }

    public function sync(): array
    {
        $created = 0;
        $queued = 0;
        $skipped = 0;

        $todayLifecycleAlertEscalationIds = StockControlEscalationEvent::query()
            ->whereIn('event_type', ['reopened', 'signal_updated'])
            ->whereDate('created_at', today())
            ->get()
            ->filter(function (StockControlEscalationEvent $event): bool {
                if ($event->event_type === 'reopened') {
                    return true;
                }

                return (int) data_get($event->metadata, 'new_level', 0)
                    > (int) data_get($event->metadata, 'old_level', 0);
            })
            ->pluck('stock_control_escalation_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $activeEscalations = StockControlEscalation::query()
            ->with(['manager.account', 'product'])
            ->where('status', '!=', StockControlEscalation::STATUS_CLOSED)
            ->get();

        foreach ($activeEscalations as $escalation) {
            if ((int) $escalation->level >= 2) {
                $result = $this->dispatchEvent(
                    eventKey: 'stock-control:escalation:'.$escalation->id.':opened',
                    eventType: $escalation->source_type === 'post_corrective_recurrence'
                        ? 'recurrence_after_corrective_action'
                        : 'management_escalation',
                    title: $escalation->level === 3
                        ? 'Level 3 Stock Control Escalation'
                        : 'Level '.$escalation->level.' Stock Control Escalation',
                    message: $escalation->message,
                    url: route(
                        'admin.stock-reconciliations.management-control.escalations.show',
                        $escalation
                    ),
                    severity: $escalation->severity,
                    recipients: $this->escalationRecipients($escalation),
                    minimumLevel: (int) $escalation->level,
                    category: $escalation->source_type === 'post_corrective_recurrence'
                        ? 'recurrence'
                        : 'escalation',
                    entityType: StockControlEscalation::class,
                    entityId: $escalation->id
                );

                $created += $result['created'];
                $queued += $result['queued'];
                $skipped += $result['skipped'];
            }

            if (
                (int) $escalation->level === 3
                && $escalation->opened_at?->lt(today())
                && ! in_array(
                    (int) $escalation->id,
                    $todayLifecycleAlertEscalationIds,
                    true
                )
            ) {
                $result = $this->dispatchEvent(
                    eventKey: 'stock-control:escalation:'.$escalation->id
                        .':level3-reminder:'.today()->toDateString(),
                    eventType: 'level_3_reminder',
                    title: 'Level 3 Escalation Requires Attention',
                    message: $escalation->title.' remains '
                        .$escalation->status
                        .' and is due '
                        .($escalation->review_due_date?->format('d M Y') ?? 'without a deadline')
                        .'.',
                    url: route(
                        'admin.stock-reconciliations.management-control.escalations.show',
                        $escalation
                    ),
                    severity: 'critical',
                    recipients: $this->escalationRecipients($escalation),
                    minimumLevel: 3,
                    category: 'escalation',
                    entityType: StockControlEscalation::class,
                    entityId: $escalation->id
                );

                $created += $result['created'];
                $queued += $result['queued'];
                $skipped += $result['skipped'];
            }

            if ($escalation->is_overdue) {
                $result = $this->dispatchEvent(
                    eventKey: 'stock-control:escalation:'.$escalation->id
                        .':overdue:'.today()->toDateString(),
                    eventType: 'overdue_escalation',
                    title: 'Overdue Stock Control Escalation',
                    message: $escalation->title.' is overdue for management review.',
                    url: route(
                        'admin.stock-reconciliations.management-control.escalations.show',
                        $escalation
                    ),
                    severity: $escalation->level === 3 ? 'critical' : 'high',
                    recipients: $this->escalationRecipients($escalation),
                    minimumLevel: (int) $escalation->level,
                    category: 'overdue',
                    entityType: StockControlEscalation::class,
                    entityId: $escalation->id
                );

                $created += $result['created'];
                $queued += $result['queued'];
                $skipped += $result['skipped'];
            }
        }

        $lifecycleEvents = StockControlEscalationEvent::query()
            ->with(['escalation.product'])
            ->whereIn('event_type', ['reopened', 'signal_updated'])
            ->where('created_at', '>=', now()->subDays(14))
            ->get();

        foreach ($lifecycleEvents as $event) {
            if (! $event->escalation) {
                continue;
            }

            $escalation = $event->escalation;

            $isLevelUpgrade = $event->event_type === 'signal_updated'
                && (int) data_get($event->metadata, 'new_level', $escalation->level)
                    > (int) data_get($event->metadata, 'old_level', $escalation->level);

            if ($event->event_type === 'signal_updated' && ! $isLevelUpgrade) {
                continue;
            }

            $result = $this->dispatchEvent(
                eventKey: 'stock-control:escalation-event:'.$event->id,
                eventType: $event->event_type === 'reopened'
                    ? 'management_escalation_reopened'
                    : 'management_escalation_upgraded',
                title: $event->event_type === 'reopened'
                    ? 'Stock Control Escalation Reopened'
                    : 'Stock Control Escalation Severity Increased',
                message: $event->event_type === 'reopened'
                    ? $escalation->title.' has reopened because the management signal advanced.'
                    : $escalation->title.' has increased to Level '.$escalation->level.'.',
                url: route(
                    'admin.stock-reconciliations.management-control.escalations.show',
                    $escalation
                ),
                severity: $escalation->severity,
                recipients: $this->escalationRecipients($escalation),
                minimumLevel: (int) $escalation->level,
                category: 'escalation',
                entityType: StockControlEscalation::class,
                entityId: $escalation->id
            );

            $created += $result['created'];
            $queued += $result['queued'];
            $skipped += $result['skipped'];
        }

        $assignmentEvents = StockControlEscalationEvent::query()
            ->with(['escalation', 'user'])
            ->where('event_type', 'assignment_updated')
            ->where('created_at', '>=', now()->subDays(14))
            ->get();

        foreach ($assignmentEvents as $event) {
            $managerId = data_get($event->metadata, 'after.escalated_to');

            if (! $managerId || ! $event->escalation) {
                continue;
            }

            $manager = User::query()
                ->with('account')
                ->whereKey((int) $managerId)
                ->where('is_active', true)
                ->first();

            if (! $manager) {
                continue;
            }

            $result = $this->dispatchEvent(
                eventKey: 'stock-control:escalation:'.$event->stock_control_escalation_id
                    .':assignment:'.$event->id,
                eventType: 'escalation_assignment',
                title: 'Stock Control Escalation Assigned to You',
                message: $event->escalation->title
                    .' has been assigned to you for management follow-up.',
                url: route(
                    'admin.stock-reconciliations.management-control.escalations.show',
                    $event->escalation
                ),
                severity: $event->escalation->severity,
                recipients: collect([$manager]),
                minimumLevel: (int) $event->escalation->level,
                category: 'assignment',
                entityType: StockControlEscalation::class,
                entityId: $event->stock_control_escalation_id
            );

            $created += $result['created'];
            $queued += $result['queued'];
            $skipped += $result['skipped'];
        }

        $investigationAssignmentEvents = StockVarianceInvestigationEvent::query()
            ->with(['investigation.assignee'])
            ->whereIn('event_type', ['opened', 'updated'])
            ->where('created_at', '>=', now()->subDays(14))
            ->get();

        foreach ($investigationAssignmentEvents as $event) {
            $case = $event->investigation;

            if (! $case?->assignee) {
                continue;
            }

            if ($event->event_type === 'opened') {
                $assignedTo = data_get($event->metadata, 'assigned_to');
                if (! $assignedTo) {
                    continue;
                }
            } else {
                $assignedTo = data_get($event->metadata, 'after.assigned_to');
                $beforeAssignedTo = data_get($event->metadata, 'before.assigned_to');

                if (! $assignedTo || (string) $assignedTo === (string) $beforeAssignedTo) {
                    continue;
                }
            }

            if ((int) $assignedTo !== (int) $case->assigned_to) {
                continue;
            }

            $result = $this->dispatchEvent(
                eventKey: 'stock-control:investigation:'.$case->id
                    .':assignment:'.$event->id,
                eventType: 'investigation_assignment',
                title: 'Stock Variance Investigation Assigned to You',
                message: sprintf(
                    'INV-%06d has been assigned to you for investigation.',
                    $case->id
                ),
                url: route(
                    'admin.stock-reconciliations.investigations.show',
                    $case
                ),
                severity: 'high',
                recipients: collect([$case->assignee]),
                minimumLevel: 2,
                category: 'assignment',
                entityType: StockVarianceInvestigation::class,
                entityId: $case->id
            );

            $created += $result['created'];
            $queued += $result['queued'];
            $skipped += $result['skipped'];
        }

        $overdueInvestigations = StockVarianceInvestigation::query()
            ->with(['assignee.account', 'adjustmentItem.product'])
            ->where('status', '!=', StockVarianceInvestigation::STATUS_RESOLVED)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->get();

        foreach ($overdueInvestigations as $case) {
            $result = $this->dispatchEvent(
                eventKey: 'stock-control:investigation:'.$case->id
                    .':overdue:'.today()->toDateString(),
                eventType: 'overdue_investigation',
                title: 'Overdue Stock Variance Investigation',
                message: sprintf(
                    'INV-%06d for %s is overdue and remains %s.',
                    $case->id,
                    $case->adjustmentItem?->product?->name ?? 'a stock material',
                    $case->status
                ),
                url: route(
                    'admin.stock-reconciliations.investigations.show',
                    $case
                ),
                severity: 'high',
                recipients: $case->assignee
                    ? collect([$case->assignee])
                    : $this->adminRecipients(),
                minimumLevel: 2,
                category: 'overdue',
                entityType: StockVarianceInvestigation::class,
                entityId: $case->id
            );

            $created += $result['created'];
            $queued += $result['queued'];
            $skipped += $result['skipped'];
        }

        $openReviews = StockControlReview::query()
            ->with(['owner.account'])
            ->where('status', StockControlReview::STATUS_OPEN)
            ->get();

        foreach ($openReviews as $review) {
            $recipients = $review->owner
                ? collect([$review->owner])
                : $this->adminRecipients();

            $result = $this->dispatchEvent(
                eventKey: 'stock-control:review:'.$review->id.':created',
                eventType: 'weekly_review_created',
                title: 'Weekly Stock Control Review Ready',
                message: sprintf(
                    'The weekly stock control review for %s–%s is ready.',
                    $review->week_start->format('d M'),
                    $review->week_end->format('d M Y')
                ),
                url: route(
                    'admin.stock-reconciliations.management-control.reviews.show',
                    $review
                ),
                severity: 'normal',
                recipients: $recipients,
                minimumLevel: 1,
                category: 'weekly_review',
                entityType: StockControlReview::class,
                entityId: $review->id
            );

            $created += $result['created'];
            $queued += $result['queued'];
            $skipped += $result['skipped'];

            if ($review->due_date?->isTomorrow()) {
                $result = $this->dispatchEvent(
                    eventKey: 'stock-control:review:'.$review->id
                        .':due-soon:'.$review->due_date->toDateString(),
                    eventType: 'weekly_review_due_soon',
                    title: 'Weekly Stock Control Review Due Tomorrow',
                    message: 'The current stock control review is due tomorrow.',
                    url: route(
                        'admin.stock-reconciliations.management-control.reviews.show',
                        $review
                    ),
                    severity: 'high',
                    recipients: $recipients,
                    minimumLevel: 2,
                    category: 'weekly_review',
                    entityType: StockControlReview::class,
                    entityId: $review->id
                );

                $created += $result['created'];
                $queued += $result['queued'];
                $skipped += $result['skipped'];
            }

            if ($review->is_overdue) {
                $result = $this->dispatchEvent(
                    eventKey: 'stock-control:review:'.$review->id
                        .':overdue:'.today()->toDateString(),
                    eventType: 'weekly_review_overdue',
                    title: 'Weekly Stock Control Review Overdue',
                    message: 'The weekly stock control review is overdue and requires management completion.',
                    url: route(
                        'admin.stock-reconciliations.management-control.reviews.show',
                        $review
                    ),
                    severity: 'critical',
                    recipients: $recipients,
                    minimumLevel: 3,
                    category: 'weekly_review',
                    entityType: StockControlReview::class,
                    entityId: $review->id
                );

                $created += $result['created'];
                $queued += $result['queued'];
                $skipped += $result['skipped'];
            }
        }

        return compact('created', 'queued', 'skipped');
    }

    public function dispatchEvent(
        string $eventKey,
        string $eventType,
        string $title,
        string $message,
        string $url,
        string $severity,
        Collection $recipients,
        int $minimumLevel = 1,
        string $category = 'general',
        ?string $entityType = null,
        ?int $entityId = null
    ): array {
        $created = 0;
        $queued = 0;
        $skipped = 0;

        foreach ($recipients->unique('id') as $user) {
            if (! $user instanceof User || ! $user->is_active) {
                continue;
            }

            $preference = StockNotificationPreference::firstOrCreate(
                ['user_id' => $user->id],
                []
            );

            if (
                $minimumLevel < $preference->minimum_escalation_level
                && in_array($category, ['escalation', 'recurrence'], true)
            ) {
                continue;
            }

            if ($category === 'assignment' && ! $preference->assignment_alerts_enabled) {
                continue;
            }

            if ($category === 'overdue' && ! $preference->overdue_reminders_enabled) {
                continue;
            }

            if ($category === 'recurrence' && ! $preference->recurrence_alerts_enabled) {
                continue;
            }

            if ($category === 'weekly_review' && ! $preference->weekly_review_alerts_enabled) {
                continue;
            }

            $data = [
                'event_type' => $eventType,
                'title' => $title,
                'message' => $message,
                'url' => $url,
                'severity' => $severity,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'event_key' => $eventKey,
            ];

            if ($preference->in_app_enabled) {
                $result = $this->createInApp(
                    $user,
                    $eventKey,
                    $eventType,
                    $data
                );
                $created += $result ? 1 : 0;
            }

            foreach (['email', 'whatsapp'] as $channel) {
                $enabled = $channel === 'email'
                    ? $preference->email_enabled
                    : $preference->whatsapp_enabled;

                if (! $enabled) {
                    continue;
                }

                $recipient = $channel === 'email'
                    ? $this->emailFor($user)
                    : $this->whatsAppFor($user);

                if (! $recipient) {
                    $wasCreated = $this->recordSkipped(
                        $user,
                        $eventKey,
                        $eventType,
                        $channel,
                        $data,
                        'No recipient configured for this channel.'
                    );
                    $skipped += $wasCreated ? 1 : 0;
                    continue;
                }

                if ($this->queueExternal(
                    $user,
                    $eventKey,
                    $eventType,
                    $channel,
                    $recipient,
                    $data
                )) {
                    $queued++;
                }
            }
        }

        return compact('created', 'queued', 'skipped');
    }

    public function preferenceFor(User $user): StockNotificationPreference
    {
        return StockNotificationPreference::firstOrCreate(
            ['user_id' => $user->id],
            []
        );
    }

    private function createInApp(
        User $user,
        string $eventKey,
        string $eventType,
        array $data
    ): bool {
        return DB::transaction(function () use (
            $user,
            $eventKey,
            $eventType,
            $data
        ): bool {
            $delivery = StockNotificationDelivery::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'event_key' => $eventKey,
                    'channel' => 'in_app',
                ],
                [
                    'event_type' => $eventType,
                    'status' => StockNotificationDelivery::STATUS_PENDING,
                    'metadata' => $data,
                ]
            );

            if (! $delivery->wasRecentlyCreated) {
                return false;
            }

            $notificationId = (string) Str::uuid();

            DB::table('notifications')->insert([
                'id' => $notificationId,
                'type' => 'stock_control',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $delivery->update([
                'notification_id' => $notificationId,
                'status' => StockNotificationDelivery::STATUS_SENT,
                'sent_at' => now(),
            ]);

            return true;
        });
    }

    private function queueExternal(
        User $user,
        string $eventKey,
        string $eventType,
        string $channel,
        string $recipient,
        array $data
    ): bool {
        $delivery = StockNotificationDelivery::firstOrCreate(
            [
                'user_id' => $user->id,
                'event_key' => $eventKey,
                'channel' => $channel,
            ],
            [
                'event_type' => $eventType,
                'status' => StockNotificationDelivery::STATUS_QUEUED,
                'recipient' => StockNotificationDelivery::maskRecipient(
                    $recipient,
                    $channel
                ),
                'queued_at' => now(),
                'metadata' => $data,
            ]
        );

        if (! $delivery->wasRecentlyCreated) {
            return false;
        }

        DB::afterCommit(
            fn () => DeliverStockControlNotification::dispatch($delivery->id)
        );

        return true;
    }

    private function recordSkipped(
        User $user,
        string $eventKey,
        string $eventType,
        string $channel,
        array $data,
        string $reason
    ): bool {
        $delivery = StockNotificationDelivery::firstOrCreate(
            [
                'user_id' => $user->id,
                'event_key' => $eventKey,
                'channel' => $channel,
            ],
            [
                'event_type' => $eventType,
                'status' => StockNotificationDelivery::STATUS_SKIPPED,
                'last_error' => $reason,
                'metadata' => $data,
            ]
        );

        return $delivery->wasRecentlyCreated;
    }

    private function escalationRecipients(
        StockControlEscalation $escalation
    ): Collection {
        $users = collect();

        if ($escalation->manager?->is_active) {
            $users->push($escalation->manager);
        }

        if ((int) $escalation->level === 3 || $users->isEmpty()) {
            $users = $users->merge($this->adminRecipients());
        }

        return $users->filter()->unique('id')->values();
    }

    private function adminRecipients(): Collection
    {
        return User::query()
            ->with('account')
            ->where('is_active', true)
            ->where('account_type', 'admin')
            ->get();
    }

    private function emailFor(User $user): ?string
    {
        return $user->notificationEmail();
    }

    private function whatsAppFor(User $user): ?string
    {
        return $user->notificationPhone();
    }
}
