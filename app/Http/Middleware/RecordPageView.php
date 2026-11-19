<?php

namespace App\Http\Middleware;

use App\Services\Analytics;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Counts storefront page views for the admin Analytics screen. Only full
 * page loads (including wire:navigate) of the public store: not the admin,
 * Livewire updates, API calls, redirects or errors. Google Analytics counts
 * its own page views in the browser, so nothing is sent to Google here.
 */
class RecordPageView
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! $request->isMethod('GET')
            || $response->getStatusCode() !== 200
            || $request->is('admin', 'admin/*', 'api/*', 'livewire*', 'up', 'language/*')
            || $request->hasHeader('X-Livewire')
            || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return;
        }

        Analytics::record('page_view', [
            'path' => '/'.ltrim($request->path(), '/'),
            'route' => $request->route()?->getName(),
        ]);
    }
}
