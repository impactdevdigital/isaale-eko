<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keep products, prices and stock in step with Square.
Schedule::command('square:sync')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->when(fn () => filled(config('services.square.token')));

// Expire unanswered substitution requests after 15 minutes
Schedule::command('substitutions:expire')->everyMinute();

// Alert on open issues exceeding 24-hour response target
Schedule::command('issues:remind-overdue')->hourly();

// Process uncollected orders and overdue pickup reminders
Schedule::command('orders:handle-uncollected')->hourly();


