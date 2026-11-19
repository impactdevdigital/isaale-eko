<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\ProductVariation;
use Livewire\Livewire;

/**
 * Customer-journey events. Each one is saved in analytics_events and, when
 * GA_MEASUREMENT_ID is set, also sent to Google Analytics 4 from the
 * shopper's browser (the store layout loads gtag and fires them).
 */
class Analytics
{
    /** Events waiting for the next store page the shopper opens. */
    public const QUEUE = 'analytics.queue';

    /**
     * Track a customer action: Analytics::track('add_to_cart', ['item_id' => 'SKU-12']);
     * Never store PII (names, emails, addresses, phone numbers) in $properties.
     *
     * $nextPage: fire it on the page the shopper lands on next, for actions
     * that end in a redirect (e.g. purchase → order confirmation).
     */
    public static function track(string $name, array $properties = [], bool $nextPage = false): void
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

        self::toGoogle($name, $properties, $nextPage);
    }

    /**
     * A product size in GA4's ecommerce "items" shape.
     *
     * @return array{item_id: string, item_name: ?string, item_variant: ?string, item_category: ?string, item_origin: ?string, price: float, quantity: int}
     */
    public static function item(ProductVariation $variation, int $quantity = 1): array
    {
        $product = $variation->product;

        return [
            'item_id' => (string) ($variation->sku ?: $variation->id),
            'item_name' => $product?->name,
            'item_variant' => $variation->name,
            'item_category' => $product?->category?->name,
            'item_origin' => $product?->country?->name,
            'price' => round($variation->price_cents / 100, 2),
            'quantity' => $quantity,
        ];
    }

    public static function googleEnabled(): bool
    {
        return filled(config('services.google_analytics.measurement_id'));
    }

    /**
     * Events queued for this page view; the store layout prints them as gtag calls.
     *
     * @return array<int, array{name: string, params: array}>
     */
    public static function drain(): array
    {
        return (array) session()->pull(self::QUEUE, []);
    }

    /**
     * A Livewire update sends the event straight to the browser; a full page
     * load (or a redirect) queues it for the store layout to print.
     */
    private static function toGoogle(string $name, array $params, bool $nextPage): void
    {
        if (! self::googleEnabled() || (app()->runningInConsole() && ! app()->runningUnitTests())) {
            return; // e.g. scheduled commands: no shopper browser to send to
        }

        try {
            if (! $nextPage && Livewire::isLivewireRequest() && ($component = Livewire::current())) {
                $component->dispatch('analytics', name: $name, params: (object) $params);

                return;
            }

            session()->push(self::QUEUE, ['name' => $name, 'params' => $params]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
