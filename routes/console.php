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

// Settle card orders whose payment outcome Square never told us (timeouts).
// Render has no scheduler on the free plan, so the orders queue also runs this (see OrdersQueue).
Schedule::command('payments:reconcile')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->when(fn () => filled(config('services.square.token')));

// Expire unanswered substitution requests after 15 minutes
Schedule::command('substitutions:expire')->everyMinute();

// Alert on open issues exceeding 24-hour response target
Schedule::command('issues:remind-overdue')->hourly();

// Process uncollected orders and overdue pickup reminders
Schedule::command('orders:handle-uncollected')->hourly();

// Back-in-stock emails (also sent straight after each Square stock update)
Schedule::command('stock:notify')->everyTenMinutes()->withoutOverlapping();

// Brevo: newsletter sign-ups that haven't reached the list yet (older ones, or failed)
Schedule::command('brevo:sync')->hourly()->withoutOverlapping()
    ->when(fn () => filled(config('services.brevo.key')) && filled(config('services.brevo.list_id')));

// Square Loyalty: retry points that didn't reach Square, give back points held on unpaid orders
Schedule::command('loyalty:sync')->everyTenMinutes()->withoutOverlapping();
