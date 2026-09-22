<?php

use App\Http\Controllers\Admin\StockNotificationController;
use App\Jobs\DeliverStockControlNotification;
use App\Models\StockControlEscalation;
use App\Models\StockControlEscalationEvent;
use App\Models\StockNotificationDelivery;
use App\Models\StockNotificationPreference;
use App\Models\User;
use App\Services\StockControlNotificationService;
use App\Services\StockReconciliationService;
use App\Services\StockVarianceInvestigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function stockNotificationHardeningVarianceFixture(): array
{
    $creator = User::factory()->create([
        'is_active' => true,
        'account_type' => 'employee',
    ]);
    Auth::login($creator);
    $now = now();

    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Notification Hardening Raw Materials',
        'slug' => 'notification-hardening-raw-materials',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $materialId = DB::table('products')->insertGetId([
        'name' => 'Notification Hardening Material',
        'slug' => 'notification-hardening-material',
        'unit' => 'kg',
        'category_id' => $categoryId,
        'type' => 'raw_material',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $currencyId = DB::table('currencies')->insertGetId([
        'name' => 'Notification Hardening USD',
        'code' => 'NHUSD',
        'symbol' => '$',
        'exchange_rate' => 1,
        'is_default' => 0,
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $supplierId = DB::table('accounts')->insertGetId([
        'name' => 'Notification Hardening Supplier',
        'code' => 'NH-SUP',
        'account_type' => 'supplier',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $purchaseId = DB::table('purchases')->insertGetId([
        'purchase_no' => 'PO-NH-001',
        'supplier_id' => $supplierId,
        'currency_id' => $currencyId,
        'status' => 'arrived',
        'exchange_rate' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('purchase_items')->insert([
        'purchase_id' => $purchaseId,
        'product_id' => $materialId,
        'purchase_currency_id' => $currencyId,
        'qty' => 50,
        'qty_available' => 50,
        'qty_sold' => 0,
        'qty_used' => 0,
        'qty_wasted' => 0,
        'unit' => 'kg',
        'cost_per_unit' => 1,
        'usd_total' => 50,
        'batch_no' => 'NH-BATCH-001',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $service = app(StockReconciliationService::class);
    $reconciliation = $service->createSnapshot(
        today()->toDateString(),
        'Notification hardening variance fixture',
        [$materialId]
    );
    $item = $reconciliation->items->first();

    $service->updateCount(
        $reconciliation,
        $item,
        40,
        'unknown',
        '10 kg physical shortage'
    );
    $service->submit($reconciliation);
    $service->approve($reconciliation->fresh());
    $adjustment = $service->post($reconciliation->fresh());

    return compact('creator', 'adjustment');
}

it('adds assignment preference and keeps escalation assignment alerts distinct', function () {
    expect(Schema::hasColumn(
        'stock_notification_preferences',
        'assignment_alerts_enabled'
    ))->toBeTrue();

    $manager = User::factory()->create([
        'is_active' => true,
        'account_type' => 'employee',
    ]);

    $escalation = StockControlEscalation::create([
        'source_key' => 'test:notification-hardening:assignment',
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
        'from_status' => $escalation->status,
        'to_status' => $escalation->status,
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

it('honors assignment opt-out independently from minimum escalation level', function () {
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
        'source_key' => 'test:notification-hardening:opt-out',
        'source_type' => 'recurring_root_cause',
        'severity' => 'high',
        'level' => 2,
        'status' => StockControlEscalation::STATUS_OPEN,
        'title' => 'Assignment opt-out test',
        'message' => 'This assignment should not notify the manager.',
        'occurrences' => 2,
        'absolute_value_usd' => 10,
        'escalated_to' => $manager->id,
        'review_due_date' => now()->addDays(2)->toDateString(),
        'opened_at' => now(),
        'last_detected_at' => now(),
    ]);

    StockControlEscalationEvent::create([
        'stock_control_escalation_id' => $escalation->id,
        'event_type' => 'assignment_updated',
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

it('notifies a user only when a variance investigation is actually assigned', function () {
    $fx = stockNotificationHardeningVarianceFixture();
    $manager = User::factory()->create([
        'is_active' => true,
        'account_type' => 'employee',
    ]);

    $case = $fx['adjustment']->items->first()->investigation;

    app(StockVarianceInvestigationService::class)->update($case, [
        'assigned_to' => $manager->id,
        'event_note' => 'Assigned for management investigation.',
    ]);

    app(StockControlNotificationService::class)->sync();

    expect($manager->fresh()->notifications()->count())->toBe(1)
        ->and(data_get(
            $manager->fresh()->notifications()->first()->data,
            'event_type'
        ))->toBe('investigation_assignment');
});

it('blocks external notification redirects and retries only owned failed deliveries', function () {
    Queue::fake();
    config(['app.url' => 'https://erp.example.test']);

    $user = User::factory()->create([
        'is_active' => true,
        'account_type' => 'admin',
        'email' => 'manager@example.test',
    ]);
    Auth::login($user);

    app(StockControlNotificationService::class)->dispatchEvent(
        eventKey: 'test:notification-hardening:unsafe-url',
        eventType: 'management_escalation',
        title: 'Unsafe URL test',
        message: 'This notification contains an external URL.',
        url: 'https://evil.example.test/steal',
        severity: 'high',
        recipients: collect([$user]),
        minimumLevel: 2,
        category: 'escalation'
    );

    /** @var DatabaseNotification $notification */
    $notification = $user->fresh()->notifications()->firstOrFail();
    $response = app(StockNotificationController::class)->open($notification);

    expect($response->getTargetUrl())
        ->toBe(route('admin.stock-notifications.index'));

    $delivery = StockNotificationDelivery::create([
        'user_id' => $user->id,
        'event_key' => 'test:notification-hardening:retry',
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

it('stores masked delivery destinations and resolves the live profile at send time', function () {
    Queue::fake();

    $user = User::factory()->create([
        'is_active' => true,
        'account_type' => 'admin',
        'email' => 'old@example.test',
    ]);

    StockNotificationPreference::create([
        'user_id' => $user->id,
        'in_app_enabled' => false,
        'email_enabled' => true,
        'whatsapp_enabled' => false,
        'minimum_escalation_level' => 1,
        'assignment_alerts_enabled' => true,
        'overdue_reminders_enabled' => true,
        'recurrence_alerts_enabled' => true,
        'weekly_review_alerts_enabled' => true,
    ]);

    app(StockControlNotificationService::class)->dispatchEvent(
        eventKey: 'test:notification-hardening:masked',
        eventType: 'weekly_review_created',
        title: 'Masked recipient test',
        message: 'Delivery audit must not store the live address.',
        url: '/admin/stock-notifications',
        severity: 'normal',
        recipients: collect([$user]),
        minimumLevel: 1,
        category: 'weekly_review'
    );

    $delivery = StockNotificationDelivery::where('channel', 'email')->firstOrFail();

    expect($delivery->recipient)
        ->toBe(StockNotificationDelivery::maskRecipient('old@example.test', 'email'))
        ->and($delivery->recipient)->not->toBe('old@example.test');

    Mail::fake();
    $user->update(['email' => 'new@example.test']);

    (new DeliverStockControlNotification($delivery->id))->handle();

    expect($delivery->fresh()->status)->toBe(StockNotificationDelivery::STATUS_SENT)
        ->and($delivery->fresh()->recipient)
        ->toBe(StockNotificationDelivery::maskRecipient('new@example.test', 'email'));
});

it('exposes privacy, retry, and assignment controls in the notification UI', function () {
    $view = file_get_contents(resource_path('views/admin/stock-notifications/index.blade.php'));
    $routes = file_get_contents(base_path('routes/admin.php'));

    expect($view)
        ->toContain('Assignment alerts')
        ->toContain('Retry')
        ->toContain('maskedNotificationEmail')
        ->toContain('maskedNotificationPhone')
        ->and($routes)
        ->toContain("name('deliveries.retry')");
});
