<?php

use App\Http\Controllers\Admin\StockNotificationController;
use App\Jobs\DeliverStockControlNotification;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockControlEscalation;
use App\Models\StockControlEscalationEvent;
use App\Models\StockControlReview;
use App\Models\StockNotificationDelivery;
use App\Models\StockNotificationPreference;
use App\Models\StockVarianceInvestigation;
use App\Models\StockVarianceInvestigationEvent;
use App\Models\User;
use App\Services\StockControlNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function stockNotificationAssignedInvestigation(
    User $manager,
    User $creator
): StockVarianceInvestigation {
    static $sequence = 1;

    $now = now();
    $suffix = $sequence++;

    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Notification Investigation '.$suffix,
        'slug' => 'notification-investigation-'.$suffix,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $materialId = DB::table('products')->insertGetId([
        'name' => 'Notification Material '.$suffix,
        'slug' => 'notification-material-'.$suffix,
        'unit' => 'kg',
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $currencyId = DB::table('currencies')->insertGetId([
        'name' => 'Notification USD '.$suffix,
        'code' => 'NU'.$suffix,
        'symbol' => '
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
        ->and($preference->minimum_escalation_level)->toBe(2)
        ->and($preference->assignment_alerts_enabled)->toBeTrue();

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
        ->and($email->recipient)->toBe('m******@example.test')
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
        'recipient' => null,
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
        ->and($sent->recipient)->toBe('m******@example.test')
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

    $escalation->update([
        'last_detected_at' => now()->subDay(),
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

it('keeps assignment alerts distinct from the original escalation notification', function () {
    $manager = User::factory()->create([
        'is_active' => true,
        'account_type' => 'employee',
    ]);

    $escalation = StockControlEscalation::create([
        'source_key' => 'test:assignment:distinct',
        'source_type' => 'recurring_root_cause',
        'severity' => 'high',
        'level' => 2,
        'status' => StockControlEscalation::STATUS_OPEN,
        'title' => 'Assigned stock control issue',
        'message' => 'A recurring stock-control issue requires follow-up.',
        'occurrences' => 3,
        'absolute_value_usd' => 25,
        'escalated_to' => $manager->id,
        'review_due_date' => now()->addDays(2)->toDateString(),
        'opened_at' => now(),
        'last_detected_at' => now(),
    ]);

    StockControlEscalationEvent::create([
        'stock_control_escalation_id' => $escalation->id,
        'event_type' => 'assignment_updated',
        'user_id' => null,
        'from_status' => $escalation->status,
        'to_status' => $escalation->status,
        'notes' => 'Assigned to manager.',
        'metadata' => [
            'before' => ['escalated_to' => null],
            'after' => ['escalated_to' => $manager->id],
        ],
        'created_at' => now(),
    ]);

    app(StockControlNotificationService::class)->sync();

    $eventTypes = $manager->fresh()->notifications()
        ->get()
        ->pluck('data')
        ->map(fn ($data) => $data['event_type'] ?? null)
        ->filter()
        ->values()
        ->all();

    expect($eventTypes)->toContain('management_escalation')
        ->and($eventTypes)->toContain('escalation_assignment')
        ->and($manager->fresh()->notifications()->count())->toBe(2);
});

it('honors assignment opt-out independently from the minimum escalation level', function () {
    $manager = User::factory()->create([
        'is_active' => true,
        'account_type' => 'employee',
    ]);

    StockNotificationPreference::create([
        'user_id' => $manager->id,
        'in_app_enabled' => true,
        'email_enabled' => false,
        'whatsapp_enabled' => false,
        'minimum_escalation_level' => 3,
        'assignment_alerts_enabled' => false,
        'overdue_reminders_enabled' => true,
        'recurrence_alerts_enabled' => true,
        'weekly_review_alerts_enabled' => true,
    ]);

    $escalation = StockControlEscalation::create([
        'source_key' => 'test:assignment:disabled',
        'source_type' => 'recurring_root_cause',
        'severity' => 'high',
        'level' => 2,
        'status' => StockControlEscalation::STATUS_OPEN,
        'title' => 'Disabled assignment alert test',
        'message' => 'This assignment should not notify the manager.',
        'occurrences' => 3,
        'absolute_value_usd' => 20,
        'escalated_to' => $manager->id,
        'review_due_date' => now()->addDays(2)->toDateString(),
        'opened_at' => now(),
        'last_detected_at' => now(),
    ]);

    StockControlEscalationEvent::create([
        'stock_control_escalation_id' => $escalation->id,
        'event_type' => 'assignment_updated',
        'user_id' => null,
        'from_status' => $escalation->status,
        'to_status' => $escalation->status,
        'metadata' => [
            'before' => ['escalated_to' => null],
            'after' => ['escalated_to' => $manager->id],
        ],
        'created_at' => now(),
    ]);

    app(StockControlNotificationService::class)->sync();

    expect($manager->fresh()->notifications()->count())->toBe(0);
});

it('creates a fresh notification when a closed escalation is genuinely reopened', function () {
    $admin = User::factory()->create([
        'is_active' => true,
        'account_type' => 'admin',
    ]);

    $escalation = StockControlEscalation::create([
        'source_key' => 'test:reopened:notification',
        'source_type' => 'post_corrective_recurrence',
        'severity' => 'critical',
        'level' => 3,
        'status' => StockControlEscalation::STATUS_OPEN,
        'title' => 'Corrective-action recurrence',
        'message' => 'The same confirmed root cause has returned.',
        'occurrences' => 2,
        'absolute_value_usd' => 60,
        'review_due_date' => now()->addDay()->toDateString(),
        'opened_at' => now()->subDays(5),
        'last_detected_at' => now(),
    ]);

    $service = app(StockControlNotificationService::class);
    $service->sync();

    expect($admin->fresh()->notifications()->count())->toBe(1);

    StockControlEscalationEvent::create([
        'stock_control_escalation_id' => $escalation->id,
        'event_type' => 'reopened',
        'user_id' => null,
        'from_status' => StockControlEscalation::STATUS_CLOSED,
        'to_status' => StockControlEscalation::STATUS_OPEN,
        'notes' => 'Signal advanced after closure.',
        'metadata' => ['new_occurrences' => 3],
        'created_at' => now(),
    ]);

    $service->sync();

    expect($admin->fresh()->notifications()->count())->toBe(2)
        ->and(
            $admin->fresh()->notifications()->get()
                ->filter(
                    fn ($notification) =>
                        data_get($notification->data, 'event_key')
                        === 'stock-control:escalation:'.$escalation->id
                            .':reopened:'
                            .StockControlEscalationEvent::where('event_type', 'reopened')->value('id')
                )
                ->count()
        )->toBe(1);
});

it('notifies the assigned user when a variance investigation is assigned', function () {
    $creator = User::factory()->create([
        'is_active' => true,
        'account_type' => 'employee',
    ]);
    $manager = User::factory()->create([
        'is_active' => true,
        'account_type' => 'employee',
    ]);

    $case = stockNotificationAssignedInvestigation($manager, $creator);

    app(StockControlNotificationService::class)->sync();

    expect($manager->fresh()->notifications()->count())->toBe(1)
        ->and(data_get(
            $manager->fresh()->notifications()->first()->data,
            'event_type'
        ))->toBe('investigation_assignment')
        ->and(data_get(
            $manager->fresh()->notifications()->first()->data,
            'entity_id'
        ))->toBe($case->id);
});

it('blocks external notification redirects and allows manual retry of failed delivery', function () {
    Queue::fake();

    $user = User::factory()->create([
        'is_active' => true,
        'account_type' => 'admin',
        'email' => 'manager@example.test',
    ]);
    Auth::login($user);

    app(StockControlNotificationService::class)->dispatchEvent(
        eventKey: 'test:unsafe-url',
        eventType: 'management_escalation',
        title: 'Unsafe URL test',
        message: 'This notification contains an invalid external URL.',
        url: 'https://evil.example.test/steal',
        severity: 'high',
        recipients: collect([$user]),
        minimumLevel: 2,
        category: 'escalation'
    );

    $notification = $user->fresh()->notifications()->firstOrFail();
    $response = app(StockNotificationController::class)->open($notification);

    expect($response->getTargetUrl())
        ->toBe(route('admin.stock-notifications.index'));

    $delivery = StockNotificationDelivery::create([
        'user_id' => $user->id,
        'event_key' => 'test:retry:1',
        'event_type' => 'management_escalation',
        'channel' => 'email',
        'status' => StockNotificationDelivery::STATUS_FAILED,
        'recipient' => StockNotificationDelivery::maskRecipient(
            $user->notificationEmail(),
            'email'
        ),
        'attempts' => 3,
        'failed_at' => now(),
        'last_error' => 'Delivery failed after retries.',
        'metadata' => [
            'title' => 'Retry test',
            'message' => 'Retry this notification.',
            'url' => route('admin.stock-notifications.index'),
        ],
    ]);

    app(StockNotificationController::class)->retryDelivery($delivery);

    expect($delivery->fresh()->status)
        ->toBe(StockNotificationDelivery::STATUS_QUEUED)
        ->and($delivery->fresh()->last_error)->toBeNull();

    Queue::assertPushed(
        DeliverStockControlNotification::class,
        fn ($job) => $job->deliveryId === $delivery->id
    );
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
,
        'exchange_rate' => 1,
        'is_default' => 0,
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $supplierId = DB::table('accounts')->insertGetId([
        'name' => 'Notification Supplier '.$suffix,
        'code' => 'NT-SUP-'.str_pad((string) $suffix, 4, '0', STR_PAD_LEFT),
        'account_type' => 'supplier',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $purchaseId = DB::table('purchases')->insertGetId([
        'purchase_no' => 'PO-NT-'.str_pad((string) $suffix, 4, '0', STR_PAD_LEFT),
        'supplier_id' => $supplierId,
        'currency_id' => $currencyId,
        'status' => 'arrived',
        'exchange_rate' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $batchId = DB::table('purchase_items')->insertGetId([
        'purchase_id' => $purchaseId,
        'product_id' => $materialId,
        'purchase_currency_id' => $currencyId,
        'qty' => 100,
        'qty_available' => 100,
        'qty_sold' => 0,
        'qty_used' => 0,
        'qty_wasted' => 0,
        'unit' => 'kg',
        'cost_per_unit' => 1,
        'usd_total' => 100,
        'batch_no' => 'NT-BATCH-'.$suffix,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $adjustment = StockAdjustment::create([
        'adjustment_no' => 'SA-NT-'.str_pad((string) $suffix, 4, '0', STR_PAD_LEFT),
        'stock_reconciliation_id' => null,
        'adjustment_date' => today()->toDateString(),
        'type' => 'reconciliation',
        'status' => 'posted',
        'notes' => 'Notification assignment fixture.',
        'created_by' => $creator->id,
        'posted_by' => $creator->id,
        'posted_at' => $now,
    ]);

    $line = StockAdjustmentItem::create([
        'stock_adjustment_id' => $adjustment->id,
        'product_id' => $materialId,
        'purchase_item_id' => $batchId,
        'inventory_unit' => 'kg',
        'before_quantity' => 100,
        'adjustment_quantity' => -5,
        'after_quantity' => 95,
        'cost_per_unit_usd' => 1,
        'adjustment_value_usd' => -5,
        'reason_code' => 'unknown',
        'notes' => 'Notification assignment fixture.',
    ]);

    $case = StockVarianceInvestigation::create([
        'stock_adjustment_item_id' => $line->id,
        'status' => StockVarianceInvestigation::STATUS_INVESTIGATING,
        'assigned_to' => $manager->id,
        'due_date' => now()->addDays(3)->toDateString(),
        'investigation_notes' => 'Assigned for notification testing.',
        'opened_by' => $creator->id,
        'opened_at' => $now,
        'started_at' => $now,
    ]);

    StockVarianceInvestigationEvent::create([
        'stock_variance_investigation_id' => $case->id,
        'event_type' => 'updated',
        'user_id' => $creator->id,
        'from_status' => StockVarianceInvestigation::STATUS_OPEN,
        'to_status' => StockVarianceInvestigation::STATUS_INVESTIGATING,
        'notes' => 'Investigation assigned.',
        'metadata' => [
            'before' => ['assigned_to' => null],
            'after' => ['assigned_to' => $manager->id],
        ],
        'created_at' => $now,
    ]);

    return $case;
}

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
        ->and($preference->minimum_escalation_level)->toBe(2)
        ->and($preference->assignment_alerts_enabled)->toBeTrue();

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
        ->and($email->recipient)->toBe('m******@example.test')
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
        'recipient' => null,
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
        ->and($sent->recipient)->toBe('m******@example.test')
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

    $escalation->update([
        'last_detected_at' => now()->subDay(),
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
