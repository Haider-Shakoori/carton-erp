<?php

use App\Jobs\DeliverManagementNotificationChannel;
use App\Models\ManagementNotificationDelivery;
use App\Models\ManagementNotificationPreference;
use App\Models\StockControlEscalation;
use App\Models\StockControlEscalationEvent;
use App\Models\User;
use App\Notifications\ManagementAlertNotification;
use App\Services\ManagementNotificationOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function notificationEscalation(
    ?User $manager = null,
    int $level = 3,
    string $sourceType = 'recurring_root_cause'
): StockControlEscalation {
    static $sequence = 1;

    return StockControlEscalation::create([
        'source_key' => 'notification-test-'.$sequence++,
        'source_type' => $sourceType,
        'severity' => $level >= 3 ? 'critical' : 'high',
        'level' => $level,
        'status' => StockControlEscalation::STATUS_OPEN,
        'title' => $level >= 3
            ? 'Critical stock recurrence'
            : 'High stock variance signal',
        'message' => 'Management review is required for this stock-control signal.',
        'occurrences' => 3,
        'absolute_value_usd' => 75,
        'escalated_to' => $manager?->id,
        'review_due_date' => now()->addDay()->toDateString(),
        'opened_at' => now(),
        'last_detected_at' => now(),
    ]);
}

function notificationEscalationEvent(
    StockControlEscalation $escalation,
    string $eventType = 'opened',
    array $metadata = []
): StockControlEscalationEvent {
    return StockControlEscalationEvent::create([
        'stock_control_escalation_id' => $escalation->id,
        'event_type' => $eventType,
        'user_id' => null,
        'from_status' => null,
        'to_status' => $escalation->status,
        'notes' => 'Notification test event.',
        'metadata' => $metadata ?: null,
        'created_at' => now(),
    ]);
}

it('creates notification schema and default opt-in state safely', function () {
    expect(Schema::hasTable('notifications'))->toBeTrue()
        ->and(Schema::hasTable('management_notification_preferences'))->toBeTrue()
        ->and(Schema::hasTable('management_notification_deliveries'))->toBeTrue();

    $user = User::factory()->create();
    $preference = ManagementNotificationPreference::firstOrCreate([
        'user_id' => $user->id,
    ]);

    expect($preference->in_app_enabled)->toBeTrue()
        ->and($preference->email_enabled)->toBeFalse()
        ->and($preference->whatsapp_enabled)->toBeFalse()
        ->and($preference->level_2_enabled)->toBeTrue()
        ->and($preference->level_3_enabled)->toBeTrue()
        ->and($preference->recurrence_enabled)->toBeTrue();
});

it('creates one in-app alert for a critical escalation and suppresses repeat sync duplicates', function () {
    Queue::fake();

    $admin = User::factory()->create([
        'account_type' => 'admin',
        'is_active' => true,
    ]);

    $escalation = notificationEscalation();
    notificationEscalationEvent($escalation);

    $first = app(ManagementNotificationOrchestrator::class)->sync();

    expect($first['in_app'])->toBe(1)
        ->and(DB::table('notifications')->where('notifiable_id', $admin->id)->count())->toBe(1)
        ->and(ManagementNotificationDelivery::where('channel', 'in_app')->count())->toBe(1)
        ->and(ManagementNotificationDelivery::first()->status)
        ->toBe(ManagementNotificationDelivery::STATUS_SENT);

    $second = app(ManagementNotificationOrchestrator::class)->sync();

    expect($second['in_app'])->toBe(0)
        ->and($second['skipped_duplicates'])->toBeGreaterThanOrEqual(1)
        ->and(DB::table('notifications')->where('notifiable_id', $admin->id)->count())->toBe(1)
        ->and(ManagementNotificationDelivery::where('channel', 'in_app')->count())->toBe(1);
});

it('routes assignment notifications only to the assigned manager', function () {
    Queue::fake();

    $manager = User::factory()->create([
        'account_type' => 'employee',
        'is_active' => true,
    ]);
    $other = User::factory()->create([
        'account_type' => 'employee',
        'is_active' => true,
    ]);

    $escalation = notificationEscalation($manager, 2);
    notificationEscalationEvent(
        $escalation,
        'assignment_updated',
        [
            'before' => ['escalated_to' => null],
            'after' => ['escalated_to' => $manager->id],
        ]
    );

    app(ManagementNotificationOrchestrator::class)->sync();

    expect(DB::table('notifications')->where('notifiable_id', $manager->id)->count())->toBe(1)
        ->and(DB::table('notifications')->where('notifiable_id', $other->id)->count())->toBe(0);

    $data = json_decode(
        DB::table('notifications')
            ->where('notifiable_id', $manager->id)
            ->value('data'),
        true
    );

    expect($data['event_type'])->toBe('escalation_assignment');
});

it('honors channel opt-in and queues external delivery without blocking the event sync', function () {
    Queue::fake();

    $admin = User::factory()->create([
        'account_type' => 'admin',
        'is_active' => true,
    ]);

    ManagementNotificationPreference::create([
        'user_id' => $admin->id,
        'in_app_enabled' => false,
        'email_enabled' => true,
        'whatsapp_enabled' => false,
        'level_2_enabled' => true,
        'level_3_enabled' => true,
        'assignment_enabled' => true,
        'overdue_enabled' => true,
        'recurrence_enabled' => true,
        'weekly_review_enabled' => true,
    ]);

    $escalation = notificationEscalation();
    notificationEscalationEvent($escalation);

    $result = app(ManagementNotificationOrchestrator::class)->sync();

    expect($result['in_app'])->toBe(0)
        ->and($result['queued_external'])->toBe(1)
        ->and(DB::table('notifications')->count())->toBe(0);

    $delivery = ManagementNotificationDelivery::firstOrFail();

    expect($delivery->channel)->toBe('email')
        ->and($delivery->status)->toBe(ManagementNotificationDelivery::STATUS_PENDING);

    Queue::assertPushed(
        DeliverManagementNotificationChannel::class,
        fn ($job) => $job->deliveryId === $delivery->id
    );
});

it('marks a WhatsApp delivery skipped when the recipient has no configured phone', function () {
    Queue::fake();

    $user = User::factory()->create([
        'account_id' => null,
        'is_active' => true,
    ]);

    $delivery = ManagementNotificationDelivery::create([
        'event_key' => hash('sha256', 'missing-whatsapp-phone'),
        'event_type' => 'escalation_assignment',
        'recipient_user_id' => $user->id,
        'channel' => 'whatsapp',
        'related_type' => StockControlEscalation::class,
        'related_id' => null,
        'status' => ManagementNotificationDelivery::STATUS_PENDING,
        'payload' => [
            'title' => 'Management alert',
            'message' => 'Test message.',
            'url' => route('admin.management-notifications.index'),
        ],
    ]);

    (new DeliverManagementNotificationChannel($delivery->id))->handle();

    $delivery->refresh();

    expect($delivery->status)->toBe(ManagementNotificationDelivery::STATUS_SKIPPED)
        ->and($delivery->attempts)->toBe(1)
        ->and($delivery->last_error)->toContain('no WhatsApp-capable phone');
});

it('supports notification read state and mark-all-read from the real admin routes', function () {
    $user = User::factory()->create();

    $payload = [
        'event_type' => 'weekly_review_created',
        'title' => 'Weekly review ready',
        'message' => 'The review is ready.',
        'severity' => 'normal',
        'url' => route('admin.management-notifications.index'),
    ];

    $user->notifyNow(new ManagementAlertNotification($payload));
    $user->notifyNow(new ManagementAlertNotification($payload));

    expect($user->fresh()->unreadNotifications()->count())->toBe(2);

    $response = $this
        ->actingAs($user)
        ->post(route('admin.management-notifications.mark-all-read'));

    $response->assertRedirect();

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

it('registers the management notification synchronization command', function () {
    $exitCode = Artisan::call('management-notifications:sync');

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())
        ->toContain('Management notifications synchronized');
});
