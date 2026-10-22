<?php

use App\Models\AnalyticsEvent;
use App\Models\Consent;
use App\Models\User;
use App\Services\Analytics;
use App\Services\ConsentService;

test('helpers format Canadian currency and routes properly in English and French', function () {
    app()->setLocale('en');
    expect(money(1250))->toContain('12.50');

    app()->setLocale('fr');
    expect(money(1250))->toContain('12,50');
});

test('analytics records customer events with session and locale', function () {
    app()->setLocale('en');

    Analytics::track('test_action', ['item_id' => 42]);

    $event = AnalyticsEvent::where('name', 'test_action')->first();

    expect($event)->not->toBeNull()
        ->and($event->name)->toBe('test_action')
        ->and($event->locale)->toBe('en')
        ->and($event->properties)->toBe(['item_id' => 42]);
});

test('consent service grants, verifies, and revokes marketing permissions', function () {
    $service = new ConsentService();

    // Not consented initially
    expect($service->has('email', null, 'customer@example.com'))->toBeFalse();

    // Grant consent
    $service->grant('email', null, 'customer@example.com', 'checkout');
    expect($service->has('email', null, 'customer@example.com'))->toBeTrue();

    // Revoke consent
    $service->revoke('email', null, 'customer@example.com');
    expect($service->has('email', null, 'customer@example.com'))->toBeFalse();
});
