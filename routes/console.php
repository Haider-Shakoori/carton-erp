<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\StockControlManagementService;
use App\Services\ManagementNotificationOrchestrator;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('queue:run-once', function () {
    $this->call('queue:work', [
        '--once' => true,
        '--tries' => 3,
        '--timeout' => 60,
    ]);
})->describe('Runs the queue once (for cron on shared hosting)');


Artisan::command('stock-control:sync', function () {
    $result = app(StockControlManagementService::class)->syncEscalations();

    $this->info(sprintf(
        'Stock control signals synchronized: %d created, %d updated, %d reopened.',
        $result['created'],
        $result['updated'],
        $result['reopened']
    ));
})->purpose('Synchronize prevention intelligence into management escalations');

Artisan::command('stock-control:weekly-review', function () {
    $review = app(StockControlManagementService::class)->ensureWeeklyReview();

    $this->info(sprintf(
        'Weekly stock control review ready for %s to %s.',
        $review->week_start->toDateString(),
        $review->week_end->toDateString()
    ));
})->purpose('Generate or refresh the current weekly stock management review');

Schedule::command('stock-control:sync')
    ->dailyAt(config(
        'stock_reconciliation.management_control.daily_sync_time',
        '08:15'
    ))
    ->withoutOverlapping();

Schedule::command('stock-control:weekly-review')
    ->weeklyOn(
        (int) config(
            'stock_reconciliation.management_control.weekly_review_schedule_day',
            1
        ),
        config(
            'stock_reconciliation.management_control.weekly_review_schedule_time',
            '08:30'
        )
    )
    ->withoutOverlapping();


Artisan::command('management-notifications:sync', function () {
    $result = app(ManagementNotificationOrchestrator::class)->sync();

    $this->info(sprintf(
        'Management notifications synchronized: %d logical events, %d in-app, %d external queued, %d duplicates skipped.',
        $result['logical_events'],
        $result['in_app'],
        $result['queued_external'],
        $result['skipped_duplicates']
    ));
})->purpose('Synchronize stock-management notifications and reminders');

Schedule::command('management-notifications:sync')
    ->everyFifteenMinutes()
    ->withoutOverlapping();
