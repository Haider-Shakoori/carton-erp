<?php

namespace App\Services;

use App\Jobs\DeliverManagementNotificationChannel;
use App\Models\ManagementNotificationDelivery;
use App\Models\ManagementNotificationPreference;
use App\Models\StockControlEscalation;
use App\Models\StockControlEscalationEvent;
use App\Models\StockControlReview;
use App\Models\StockVarianceInvestigation;
use App\Models\StockVarianceInvestigationEvent;
use App\Models\User;
use App\Notifications\ManagementAlertNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class ManagementNotificationOrchestrator
{
    public function sync(): array
    {
        $stats = [
            'logical_events' => 0,
            'in_app' => 0,
            'queued_external' => 0,
            'skipped_duplicates' => 0,
        ];

        $lookbackDays = max(
            (int) config(
                'stock_reconciliation.notifications.event_lookback_days',
                7
            ),
            1
        );
        $since = now()->subDays($lookbackDays);

        $escalationEvents = StockControlEscalationEvent::query()
            ->with(['escalation.product', 'escalation.manager'])
            ->where('created_at', '>=', $since)
            ->whereIn('event_type', [
                'opened',
                'reopened',
                'assignment_updated',
            ])
            ->orderBy('id')
            ->get();

        foreach ($escalationEvents as $event) {
            $escalation = $event->escalation;
            if (! $escalation) {
                continue;
            }

            if ($event->event_type === 'assignment_updated') {
                if (! $escalation->manager) {
                    continue;
                }

                $this->emit(
                    baseKey: "escalation-event:{$event->id}:assignment",
                    eventType: 'escalation_assignment',
                    title: 'Stock control escalation assigned',
                    message: sprintf(
                        '%s has been assigned to you for management follow-up.',
                        $escalation->title
                    ),
                    severity: $escalation->severity,
                    url: route(
                        'admin.stock-reconciliations.management-control.escalations.show',
                        $escalation
                    ),
                    recipients: collect([$escalation->manager]),
                    relatedType: StockControlEscalation::class,
                    relatedId: $escalation->id,
                    level: (int) $escalation->level,
                    stats: $stats
                );

                continue;
            }

            $isRecurrence = $escalation->source_type === 'post_corrective_recurrence';
            $eventType = $isRecurrence
                ? 'recurrence_after_corrective_action'
                : 'escalation_opened';

            $recipients = $this->escalationRecipients($escalation);

            $this->emit(
                baseKey: "escalation-event:{$event->id}:{$eventType}",
                eventType: $eventType,
                title: $isRecurrence
                    ? 'Critical recurrence after corrective action'
                    : 'New stock management escalation',
                message: $escalation->message,
                severity: $escalation->severity,
                url: route(
                    'admin.stock-reconciliations.management-control.escalations.show',
                    $escalation
                ),
                recipients: $recipients,
                relatedType: StockControlEscalation::class,
                relatedId: $escalation->id,
                level: (int) $escalation->level,
                stats: $stats
            );
        }

        $investigationEvents = StockVarianceInvestigationEvent::query()
            ->with(['investigation.assignee'])
            ->where('created_at', '>=', $since)
            ->whereIn('event_type', ['opened', 'updated'])
            ->orderBy('id')
            ->get();

        foreach ($investigationEvents as $event) {
            $case = $event->investigation;
            if (! $case?->assignee) {
                continue;
            }

            $assignedTo = $event->metadata['after']['assigned_to']
                ?? $event->metadata['assigned_to']
                ?? null;
            $previousAssignedTo = $event->metadata['before']['assigned_to']
                ?? null;

            if (
                $event->event_type === 'updated'
                && (string) $assignedTo === (string) $previousAssignedTo
            ) {
                continue;
            }

            if (
                $assignedTo !== null
                && (int) $assignedTo !== (int) $case->assigned_to
            ) {
                continue;
            }

            $this->emit(
                baseKey: "investigation-event:{$event->id}:assignment",
                eventType: 'investigation_assignment',
                title: 'Stock variance investigation assigned',
                message: sprintf(
                    'INV-%06d has been assigned to you for investigation.',
                    $case->id
                ),
                severity: 'high',
                url: route(
                    'admin.stock-reconciliations.investigations.show',
                    $case
                ),
                recipients: collect([$case->assignee]),
                relatedType: StockVarianceInvestigation::class,
                relatedId: $case->id,
                level: 2,
                stats: $stats
            );
        }

        $todayKey = today()->toDateString();

        $overdueEscalations = StockControlEscalation::query()
            ->with(['manager', 'product'])
            ->where('status', '!=', StockControlEscalation::STATUS_CLOSED)
            ->whereDate('review_due_date', '<', today())
            ->get();

        foreach ($overdueEscalations as $escalation) {
            $this->emit(
                baseKey: "escalation:{$escalation->id}:overdue:{$todayKey}",
                eventType: 'escalation_overdue',
                title: 'Overdue stock management escalation',
                message: sprintf(
                    '%s is overdue for management review.',
                    $escalation->title
                ),
                severity: $escalation->severity,
                url: route(
                    'admin.stock-reconciliations.management-control.escalations.show',
                    $escalation
                ),
                recipients: $this->escalationRecipients($escalation),
                relatedType: StockControlEscalation::class,
                relatedId: $escalation->id,
                level: (int) $escalation->level,
                stats: $stats
            );
        }

        $overdueInvestigations = StockVarianceInvestigation::query()
            ->with(['assignee', 'adjustmentItem.product'])
            ->where('status', '!=', StockVarianceInvestigation::STATUS_RESOLVED)
            ->whereDate('due_date', '<', today())
            ->get();

        foreach ($overdueInvestigations as $case) {
            $recipients = collect();
            if ($case->assignee) {
                $recipients->push($case->assignee);
            }
            $recipients = $recipients->merge($this->managementUsers());

            $this->emit(
                baseKey: "investigation:{$case->id}:overdue:{$todayKey}",
                eventType: 'investigation_overdue',
                title: 'Overdue stock variance investigation',
                message: sprintf(
                    'INV-%06d%s is overdue and still %s.',
                    $case->id,
                    $case->adjustmentItem?->product
                        ? ' · '.$case->adjustmentItem->product->name
                        : '',
                    $case->status
                ),
                severity: 'high',
                url: route(
                    'admin.stock-reconciliations.investigations.show',
                    $case
                ),
                recipients: $recipients,
                relatedType: StockVarianceInvestigation::class,
                relatedId: $case->id,
                level: 2,
                stats: $stats
            );
        }

        $reviews = StockControlReview::query()
            ->with('owner')
            ->where(function ($q) use ($since): void {
                $q->where('generated_at', '>=', $since)
                    ->orWhere('status', StockControlReview::STATUS_OPEN);
            })
            ->get();

        $dueSoonDays = max(
            (int) config(
                'stock_reconciliation.notifications.review_due_soon_days',
                1
            ),
            0
        );

        foreach ($reviews as $review) {
            $recipients = collect();
            if ($review->owner) {
                $recipients->push($review->owner);
            }
            $recipients = $recipients->merge($this->managementUsers());

            $this->emit(
                baseKey: "review:{$review->id}:created",
                eventType: 'weekly_review_created',
                title: 'Weekly stock control review ready',
                message: sprintf(
                    'The stock control review for %s–%s is ready.',
                    $review->week_start->format('d M'),
                    $review->week_end->format('d M Y')
                ),
                severity: 'normal',
                url: route(
                    'admin.stock-reconciliations.management-control.reviews.show',
                    $review
                ),
                recipients: $recipients,
                relatedType: StockControlReview::class,
                relatedId: $review->id,
                level: 1,
                stats: $stats
            );

            if ($review->status === StockControlReview::STATUS_COMPLETED) {
                continue;
            }

            if ($review->due_date->isBefore(today())) {
                $this->emit(
                    baseKey: "review:{$review->id}:overdue:{$todayKey}",
                    eventType: 'weekly_review_overdue',
                    title: 'Weekly stock control review overdue',
                    message: sprintf(
                        'The weekly stock control review due %s is overdue.',
                        $review->due_date->format('d M Y')
                    ),
                    severity: 'high',
                    url: route(
                        'admin.stock-reconciliations.management-control.reviews.show',
                        $review
                    ),
                    recipients: $recipients,
                    relatedType: StockControlReview::class,
                    relatedId: $review->id,
                    level: 2,
                    stats: $stats
                );
            } elseif (
                $review->due_date->lessThanOrEqualTo(
                    today()->addDays($dueSoonDays)
                )
            ) {
                $this->emit(
                    baseKey: "review:{$review->id}:due-soon:{$todayKey}",
                    eventType: 'weekly_review_due_soon',
                    title: 'Weekly stock control review due soon',
                    message: sprintf(
                        'The weekly stock control review is due %s.',
                        $review->due_date->format('d M Y')
                    ),
                    severity: 'normal',
                    url: route(
                        'admin.stock-reconciliations.management-control.reviews.show',
                        $review
                    ),
                    recipients: $recipients,
                    relatedType: StockControlReview::class,
                    relatedId: $review->id,
                    level: 1,
                    stats: $stats
                );
            }
        }

        return $stats;
    }

    private function emit(
        string $baseKey,
        string $eventType,
        string $title,
        string $message,
        string $severity,
        string $url,
        Collection $recipients,
        string $relatedType,
        int $relatedId,
        int $level,
        array &$stats
    ): void {
        $recipients = $recipients
            ->filter(fn ($user) => $user instanceof User && $user->is_active)
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        $stats['logical_events']++;

        foreach ($recipients as $recipient) {
            $preference = ManagementNotificationPreference::firstOrCreate([
                'user_id' => $recipient->id,
            ]);

            if (! $this->wantsEvent(
                $preference,
                $eventType,
                $level
            )) {
                continue;
            }

            $payload = [
                'event_type' => $eventType,
                'title' => $title,
                'message' => $message,
                'severity' => $severity,
                'url' => $url,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'occurred_at' => now()->toIso8601String(),
            ];

            if ($preference->in_app_enabled) {
                $created = $this->deliverInApp(
                    $baseKey,
                    $recipient,
                    $eventType,
                    $relatedType,
                    $relatedId,
                    $payload
                );

                $created
                    ? $stats['in_app']++
                    : $stats['skipped_duplicates']++;
            }

            foreach ([
                'email' => $preference->email_enabled,
                'whatsapp' => $preference->whatsapp_enabled,
            ] as $channel => $enabled) {
                if (! $enabled) {
                    continue;
                }

                $created = $this->queueExternal(
                    $baseKey,
                    $recipient,
                    $channel,
                    $eventType,
                    $relatedType,
                    $relatedId,
                    $payload
                );

                $created
                    ? $stats['queued_external']++
                    : $stats['skipped_duplicates']++;
            }
        }
    }

    private function deliverInApp(
        string $baseKey,
        User $recipient,
        string $eventType,
        string $relatedType,
        int $relatedId,
        array $payload
    ): bool {
        $eventKey = $this->deliveryKey(
            $baseKey,
            $recipient->id,
            'in_app'
        );

        try {
            $created = false;

            DB::transaction(function () use (
                $eventKey,
                $recipient,
                $eventType,
                $relatedType,
                $relatedId,
                $payload,
                &$created
            ): void {
                $delivery = ManagementNotificationDelivery::firstOrCreate(
                    ['event_key' => $eventKey],
                    [
                        'event_type' => $eventType,
                        'recipient_user_id' => $recipient->id,
                        'channel' => 'in_app',
                        'related_type' => $relatedType,
                        'related_id' => $relatedId,
                        'status' => ManagementNotificationDelivery::STATUS_PENDING,
                        'payload' => $payload,
                    ]
                );

                if (! $delivery->wasRecentlyCreated) {
                    return;
                }

                $created = true;

                $recipient->notifyNow(
                    new ManagementAlertNotification($payload)
                );

                $delivery->update([
                    'status' => ManagementNotificationDelivery::STATUS_SENT,
                    'attempts' => 1,
                    'last_attempt_at' => now(),
                    'sent_at' => now(),
                ]);
            });

            return $created;
        } catch (Throwable $e) {
            ManagementNotificationDelivery::query()
                ->where('event_key', $eventKey)
                ->update([
                    'status' => ManagementNotificationDelivery::STATUS_FAILED,
                    'attempts' => 1,
                    'last_attempt_at' => now(),
                    'failed_at' => now(),
                    'last_error' => mb_substr($e->getMessage(), 0, 1000),
                ]);

            return false;
        }
    }

    private function queueExternal(
        string $baseKey,
        User $recipient,
        string $channel,
        string $eventType,
        string $relatedType,
        int $relatedId,
        array $payload
    ): bool {
        $eventKey = $this->deliveryKey(
            $baseKey,
            $recipient->id,
            $channel
        );

        try {
            $delivery = ManagementNotificationDelivery::firstOrCreate(
                ['event_key' => $eventKey],
                [
                    'event_type' => $eventType,
                    'recipient_user_id' => $recipient->id,
                    'channel' => $channel,
                    'related_type' => $relatedType,
                    'related_id' => $relatedId,
                    'status' => ManagementNotificationDelivery::STATUS_PENDING,
                    'payload' => $payload,
                ]
            );

            if (! $delivery->wasRecentlyCreated) {
                return false;
            }

            DeliverManagementNotificationChannel::dispatch($delivery->id);

            return true;
        } catch (Throwable $e) {
            ManagementNotificationDelivery::query()
                ->where('event_key', $eventKey)
                ->update([
                    'status' => ManagementNotificationDelivery::STATUS_FAILED,
                    'failed_at' => now(),
                    'last_error' => mb_substr($e->getMessage(), 0, 1000),
                ]);

            return false;
        }
    }

    private function wantsEvent(
        ManagementNotificationPreference $preference,
        string $eventType,
        int $level
    ): bool {
        if ($eventType === 'recurrence_after_corrective_action') {
            return $preference->recurrence_enabled;
        }

        if (str_contains($eventType, 'assignment')) {
            return $preference->assignment_enabled;
        }

        if (str_contains($eventType, 'overdue')) {
            return $preference->overdue_enabled;
        }

        if (str_starts_with($eventType, 'weekly_review')) {
            return $preference->weekly_review_enabled;
        }

        if ($level >= 3) {
            return $preference->level_3_enabled;
        }

        if ($level === 2) {
            return $preference->level_2_enabled;
        }

        return true;
    }

    private function escalationRecipients(
        StockControlEscalation $escalation
    ): Collection {
        $recipients = collect();

        if ($escalation->manager) {
            $recipients->push($escalation->manager);
        }

        if ((int) $escalation->level >= 3 || ! $escalation->manager) {
            $recipients = $recipients->merge($this->managementUsers());
        }

        return $recipients->unique('id')->values();
    }

    private function managementUsers(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->get()
            ->filter(function (User $user): bool {
                if ($user->account_type === 'admin') {
                    return true;
                }

                try {
                    return $user->can('review stock reconciliations');
                } catch (Throwable) {
                    return false;
                }
            })
            ->values();
    }

    private function deliveryKey(
        string $baseKey,
        int $userId,
        string $channel
    ): string {
        return hash(
            'sha256',
            $baseKey.'|user:'.$userId.'|channel:'.$channel
        );
    }
}
