<?php

use App\Http\Controllers\Admin\StockNotificationController;
use App\Jobs\DeliverStockControlNotification;
use App\Models\StockControlEscalation;
use App\Models\StockControlEscalationEvent;
use App\Models\StockControlReview;
use App\Models\StockNotificationDelivery;
use App\Models\StockNotificationPreference;
use App\Models\User;
use App\Services\StockControlNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates notification schema and defaults to safe in-app-only delivery', function () {
    expect(Schema::hasTable('notifications'))->toBeTrue()
        ->and(Schema::hasTable('stock_notification_preferences'))->toBeTrue()
        ->and(Schema::hasTable('stock_notification_deliveries'))->toBeTrue();

    $user = User::factory()->create([
        'is_active' => true,
        'account_type' => 'admin',
        'email' => 'manager@example.test',
    ]);

    $service = app(StockControlNotificationService::class);
    $preference = $service->preferenceFor($user);

    expect($preference->in_app_enabled)->toBeTrue()
        ->and($preference->email_enabled)->toBeFalse()
        ->and($preference->whatsapp_enabled)->toBeFalse()
        ->and($preference->minimum_escalation_level)->toBe(2);

    $first = $service->dispatchEvent(
        eventKey: 'test:stock-control:1',
        eventType: 'management_escalation',
        title: 'Test stock escalation',
        message: 'A test stock-control event requires management attention.',
        url: '/admin/stock-reconciliations/management-control',
        severity: 'high',
        recipients: collect([$user]),
        minimumLevel: 2,
        category: 'escalation'
    );

    expect($first)->toBe([
        'created' => 1,
        'queued' => 0,
        'skipped' => 0,
    ])->and($user->fresh()->notifications()->count())->toBe(1)
        ->and(StockNotificationDelivery::where('channel', 'in_app')->count())
        ->toBe(1);

    $second = $service->dispatchEvent(
        eventKey: 'test:stock-control:1',
        eventType: 'management_escalation',
        title: 'Test stock escalation',
        message: 'A test stock-control event requires management attention.',
        url: '/admin/stock-reconciliations/management-control',
        severity: 'high',
        recipients: collect([$user]),
        minimumLevel: 2,
        category: 'escalation'
    );

    expect($second)->toBe([
        'created' => 0,
        'queued' => 0,
        'skipped' => 0,
    ])->and($user->fresh()->notifications()->count())->toBe(1);
});

it('queues enabled email delivery and records missing WhatsApp as skipped without blocking in-app alerts', function () {
    $user = User::factory()->create([
        'is_active' => true,
        'account_type' => 'admin',
        'email' => 'manager@example.test',
    ]);

    StockNotificationPreference::create([
        'user_id' => $user->id,
        'in_app_enabled' => true,
        'email_enabled' => true,
        'whatsapp_enabled' => true,
        'minimum_escalation_level' => 1,
        'overdue_reminders_enabled' => true,
        'recurrence_alerts_enabled' => true,
        'weekly_review_alerts_enabled' => true,
    ]);

    $result = app(StockControlNotificationService::class)->dispatchEvent(
        eventKey: 'test:channels:1',
        eventType: 'weekly_review_created',
        title: 'Weekly review ready',
        message: 'The weekly stock-control review is ready.',
        url: '/admin/stock-reconciliations/management-control',
        severity: 'normal',
        recipients: collect([$user]),
        minimumLevel: 1,
        category: 'weekly_review'
    );

    expect($result)->toBe([
        'created' => 1,
        'queued' => 1,
        'skipped' => 1,
    ]);

    $email = StockNotificationDelivery::where('channel', 'email')->firstOrFail();
    $whatsapp = StockNotificationDelivery::where('channel', 'whatsapp')->firstOrFail();

    expect($email->status)->toBe(StockNotificationDelivery::STATUS_QUEUED)
        ->and($email->recipient)->toBe('manager@example.test')
        ->and($whatsapp->status)->toBe(StockNotificationDelivery::STATUS_SKIPPED)
        ->and($whatsapp->last_error)->toContain('No recipient configured');

});

it('marks queued email delivery as sent while keeping delivery outside stock transactions', function () {
    Mail::fake();

    $user = User::factory()->create([
        'is_active' => true,
        'email' => 'manager@example.test',
    ]);

    $delivery = StockNotificationDelivery::create([
        'user_id' => $user->id,
        'event_key' => 'test:email-job:1',
        'event_type' => 'management_escalation',
        'channel' => 'email',
        'status' => StockNotificationDelivery::STATUS_QUEUED,
        'recipient' => 'manager@example.test',
        'queued_at' => now(),
        'metadata' => [
            'title' => 'Stock Control Alert',
            'message' => 'A management escalation requires review.',
            'url' => 'https://erp.example.test/admin/stock-reconciliations/management-control',
        ],
    ]);

    (new DeliverStockControlNotification($delivery->id))->handle();

    $sent = $delivery->fresh();

    expect($sent->status)->toBe(StockNotificationDelivery::STATUS_SENT)
        ->and($sent->attempts)->toBe(1)
        ->and($sent->sent_at)->not->toBeNull()
        ->and($sent->last_error)->toBeNull();
});

it('creates one new Level 3 alert and suppresses same-day reminder spam', function () {
    $admin = User::factory()->create([
        'is_active' => true,
        'account_type' => 'admin',
    ]);

    $escalation = StockControlEscalation::create([
        'source_key' => 'test:level3:1',
        'source_type' => 'post_corrective_recurrence',
        'severity' => 'critical',
        'level' => 3,
        'status' => StockControlEscalation::STATUS_OPEN,
        'title' => 'Recurrence after corrective action',
        'message' => 'Kraft Paper repeated a confirmed root cause.',
        'occurrences' => 2,
        'absolute_value_usd' => 75,
        'review_due_date' => now()->addDay()->toDateString(),
        'opened_at' => now(),
        'last_detected_at' => now(),
    ]);

    $service = app(StockControlNotificationService::class);
    $first = $service->sync();

    expect($first['created'])->toBe(1)
        ->and($admin->fresh()->notifications()->count())->toBe(1)
        ->and(
            data_get(
                $admin->fresh()->notifications()->first()->data,
                'event_type'
            )
        )->toBe('recurrence_after_corrective_action');

    $second = $service->sync();

    expect($second['created'])->toBe(0)
        ->and($admin->fresh()->notifications()->count())->toBe(1);

    // Move to the next day and simulate the daily management sync having
    // refreshed last_detected_at today. The reminder must still be created.
    $this->travel(1)->day();

    $escalation->update([
        'last_detected_at' => now(),
    ]);

    $third = $service->sync();

    expect($third['created'])->toBe(1)
        ->and($admin->fresh()->notifications()->count())->toBe(2)
        ->and(
            $admin->fresh()->notifications()->get()
                ->filter(
                    fn ($notification) =>
                        data_get($notification->data, 'event_type')
                        === 'level_3_reminder'
                )
                ->count()
        )->toBe(1);
});

it('notifies weekly review owners and respects weekly review preference switches', function () {
    $owner = User::factory()->create([
        'is_active' => true,
        'account_type' => 'employee',
    ]);

    $review = StockControlReview::create([
        'week_start' => now()->startOfWeek()->toDateString(),
        'week_end' => now()->endOfWeek()->toDateString(),
        'status' => StockControlReview::STATUS_OPEN,
        'owner_id' => $owner->id,
        'due_date' => now()->addDays(3)->toDateString(),
        'generated_at' => now(),
        'summary_snapshot' => [],
    ]);

    $service = app(StockControlNotificationService::class);
    $service->sync();

    expect($owner->fresh()->notifications()->count())->toBe(1)
        ->and(data_get($owner->fresh()->notifications()->first()->data, 'event_type'))
        ->toBe('weekly_review_created');

    $preference = $service->preferenceFor($owner);
    $preference->update(['weekly_review_alerts_enabled' => false]);

    $review->update(['due_date' => now()->addDay()->toDateString()]);
    $service->sync();

    expect($owner->fresh()->notifications()->count())->toBe(1);
});

it('marks database notifications read and registers the notification sync command', function () {
    $user = User::factory()->create([
        'is_active' => true,
        'account_type' => 'admin',
    ]);
    Auth::login($user);

    app(StockControlNotificationService::class)->dispatchEvent(
        eventKey: 'test:read:1',
        eventType: 'management_escalation',
        title: 'Read test',
        message: 'Read-state test notification.',
        url: '/admin/stock-notifications',
        severity: 'high',
        recipients: collect([$user]),
        minimumLevel: 2,
        category: 'escalation'
    );

    /** @var DatabaseNotification $notification */
    $notification = $user->fresh()->notifications()->firstOrFail();

    $controller = app(StockNotificationController::class);
    $controller->markRead($notification);

    expect($notification->fresh()->read_at)->not->toBeNull();

    $exitCode = Artisan::call('stock-notifications:sync');

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->toContain('Stock notifications synchronized');
});


it('sends the daily Level 3 reminder even when management sync refreshed last_detected_at today', function () {
    $admin = User::factory()->create([
        'is_active' => true,
        'account_type' => 'admin',
    ]);

    $escalation = StockControlEscalation::create([
        'source_key' => 'test:level3:reliable-reminder',
        'source_type' => 'recurring_root_cause',
        'severity' => 'critical',
        'level' => 3,
        'status' => StockControlEscalation::STATUS_OPEN,
        'title' => 'Critical recurring stock variance',
        'message' => 'A critical stock-control issue remains active.',
        'occurrences' => 4,
        'absolute_value_usd' => 120,
        'review_due_date' => today()->toDateString(),
        'opened_at' => now()->subDays(2),
        'last_detected_at' => now(),
    ]);

    $service = app(StockControlNotificationService::class);

    // Simulate the original opening alert having already been delivered.
    $service->dispatchEvent(
        eventKey: 'stock-control:escalation:'.$escalation->id.':opened',
        eventType: 'management_escalation',
        title: 'Level 3 Stock Control Escalation',
        message: $escalation->message,
        url: '/admin/stock-reconciliations/management-control/escalations/'.$escalation->id,
        severity: 'critical',
        recipients: collect([$admin]),
        minimumLevel: 3,
        category: 'escalation'
    );

    $result = $service->sync();

    expect($result['created'])->toBeGreaterThanOrEqual(1)
        ->and(
            $admin->fresh()->notifications()->get()
                ->filter(
                    fn ($notification) =>
                        data_get($notification->data, 'event_type')
                        === 'level_3_reminder'
                )
                ->count()
        )->toBe(1);
});

it('notifies management when a closed escalation genuinely reopens without duplicating a same-day Level 3 reminder', function () {
    $admin = User::factory()->create([
        'is_active' => true,
        'account_type' => 'admin',
    ]);

    $escalation = StockControlEscalation::create([
        'source_key' => 'test:reopened:lifecycle',
        'source_type' => 'post_corrective_recurrence',
        'severity' => 'critical',
        'level' => 3,
        'status' => StockControlEscalation::STATUS_OPEN,
        'title' => 'Recurrence after corrective action',
        'message' => 'The same stock-control issue has returned.',
        'occurrences' => 3,
        'absolute_value_usd' => 90,
        'review_due_date' => now()->addDay()->toDateString(),
        'opened_at' => now()->subDays(30),
        'last_detected_at' => now(),
    ]);

    StockControlEscalationEvent::create([
        'stock_control_escalation_id' => $escalation->id,
        'event_type' => 'reopened',
        'from_status' => StockControlEscalation::STATUS_CLOSED,
        'to_status' => StockControlEscalation::STATUS_OPEN,
        'notes' => 'Signal advanced and reopened the escalation.',
        'metadata' => ['new_occurrences' => 3],
        'created_at' => now(),
    ]);

    $service = app(StockControlNotificationService::class);

    // Preserve the historical opening notification so only the lifecycle event
    // should create a fresh alert today.
    $service->dispatchEvent(
        eventKey: 'stock-control:escalation:'.$escalation->id.':opened',
        eventType: 'recurrence_after_corrective_action',
        title: 'Level 3 Stock Control Escalation',
        message: $escalation->message,
        url: '/admin/stock-reconciliations/management-control/escalations/'.$escalation->id,
        severity: 'critical',
        recipients: collect([$admin]),
        minimumLevel: 3,
        category: 'recurrence'
    );

    $service->sync();

    $notifications = $admin->fresh()->notifications()->get();

    expect(
        $notifications->filter(
            fn ($notification) =>
                data_get($notification->data, 'event_type')
                === 'management_escalation_reopened'
        )->count()
    )->toBe(1)
        ->and(
            $notifications->filter(
                fn ($notification) =>
                    data_get($notification->data, 'event_type')
                    === 'level_3_reminder'
            )->count()
        )->toBe(0);
});

it('registers a scheduler-friendly command that drains queued external notification jobs', function () {
    expect(array_key_exists('stock-notifications:drain', Artisan::all()))
        ->toBeTrue();

    $exitCode = Artisan::call('stock-notifications:drain');

    expect($exitCode)->toBe(0);
});
