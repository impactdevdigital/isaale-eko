<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next, ?string $forced = null)
    {
        $locale = $forced
            ?? session('locale')
            ?? $request->user()?->language
            ?? config('locales.default', 'en');

        if (!array_key_exists($locale, config('locales.supported', ['en' => 'English', 'fr' => 'Français']))) {
            $locale = config('locales.default', 'en');
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);
        session(['locale' => $locale]);

        // When customer switches language, persist to their account
        if ($forced && $request->user() && $request->user()->language !== $locale) {
            $request->user()->update(['language' => $locale]);
        }

        return $next($request);
    }
}
