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
