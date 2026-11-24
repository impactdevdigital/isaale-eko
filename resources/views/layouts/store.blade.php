<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php($pageTitle = $title ?? (isset($attributes) ? $attributes->get('title') : null))
    @include('partials.seo')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@700&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    @if ($gaId = config('services.google_analytics.measurement_id'))
        {{-- Google Analytics 4. Events from the server arrive as Livewire "analytics" events or are printed at the end of <body>. --}}
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($gaId) }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag() { dataLayer.push(arguments); }
            gtag('js', new Date());
            gtag('config', @js($gaId), { currency: @js(config('shop.currency', 'CAD')) });

            if (! window.__isaleeAnalytics) {
                window.__isaleeAnalytics = true;
                document.addEventListener('livewire:init', () => {
                    Livewire.on('analytics', (event) => {
                        const data = Array.isArray(event) ? event[0] : event;
                        gtag('event', data.name, data.params || {});
                    });
                });
                // Pages opened with wire:navigate don't reload, so count them as page views here.
                let firstLoad = true;
                document.addEventListener('livewire:navigated', () => {
                    if (firstLoad) { firstLoad = false; return; }
                    gtag('event', 'page_view', { page_location: location.href, page_title: document.title });
                });
            }
        </script>
    @endif
</head>
<body class="bg-surface font-sans text-ink antialiased">
    <x-store.header />

    <main>
        {{ $slot }}
    </main>

    <livewire:store.newsletter-signup />
    <x-store.footer />

    <livewire:store.cart-drawer />
    @guest
        <livewire:store.sign-in />
    @endguest
    {{-- Asha isn't live yet: floating button hidden on all pages (client request). Restore with <x-store.asha-launcher /> --}}

    @livewireScripts

    @if (\App\Services\Analytics::googleEnabled() && ($analyticsEvents = \App\Services\Analytics::drain()))
        <script>
            @foreach ($analyticsEvents as $event)
                gtag('event', @js($event['name']), @js((object) $event['params']));
            @endforeach
        </script>
    @endif
</body>
</html>
