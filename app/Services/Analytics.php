<?php

namespace App\Services;

use App\Models\AnalyticsEvent;

class Analytics
{
    /**
     * Track customer actions: Analytics::track('add_to_cart', ['variation_id' => 12]);
     * Never store PII (names, emails, addresses) in $properties.
     */
    public static function track(string $name, array $properties = []): void
    {
        try {
            AnalyticsEvent::create([
                'name' => $name,
                'user_id' => auth()->id(),
                'session_id' => session()->getId(),
                'locale' => app()->getLocale(),
                'properties' => $properties ?: null,
            ]);
        } catch (\Throwable $e) {
            report($e); // Analytics must never disrupt the shopping experience
        }
    }
}
