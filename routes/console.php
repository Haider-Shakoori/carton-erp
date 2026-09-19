<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

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
