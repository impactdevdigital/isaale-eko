<?php

use Illuminate\Support\Number;

if (!function_exists('lroute')) {
    /**
     * Generate localized route: /products/x or /fr/products/x based on current active locale
     */
    function lroute(string $name, mixed $params = []): string
    {
        $prefix = app()->getLocale() === 'fr' ? 'fr.' : '';
        return route($prefix . $name, $params);
    }
}

if (!function_exists('switch_locale_url')) {
    /**
     * URL of the current page in the alternate language for header switcher
     */
    function switch_locale_url(string $to): string
    {
        $route = request()->route();
        $base = preg_replace('/^fr\./', '', (string) $route?->getName());
        return $base ? route(($to === 'fr' ? 'fr.' : '') . $base, $route->parameters()) : url('/');
    }
}

if (!function_exists('money')) {
    /**
     * Format cents to currency: money(1250) -> "$12.50" or "12,50 $"
     */
    function money(?int $cents): string
    {
        if ($cents === null) {
            return '';
        }

        $locale = app()->getLocale() === 'fr' ? 'fr_CA' : 'en_CA';
        $currency = config('shop.currency', 'CAD');

        return Number::currency($cents / 100, in: $currency, locale: $locale);
    }
}
