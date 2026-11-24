{{-- Search and sharing tags (App\Support\Seo). Included in the store layout's <head>. --}}
@php
    $seo = \App\Support\Seo::get();
    $site = \App\Support\Seo::SITE_NAME;
    $fullTitle = $pageTitle ? __($pageTitle).' | '.$site : $site.' | African & Caribbean Groceries in Saint John, NB';
    $description = $seo->metaDescription();
    $canonical = $seo->canonicalUrl();
    $image = $seo->metaImage();
    $locale = app()->getLocale() === 'fr' ? 'fr_CA' : 'en_CA';
@endphp
<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $description }}">
<meta name="robots" content="{{ $seo->robots() }}">
<link rel="canonical" href="{{ $canonical }}">
<meta name="author" content="{{ $site }}">
<meta name="theme-color" content="#FFD21F">
<meta name="format-detection" content="telephone=no">
<meta name="geo.region" content="CA-NB">
<meta name="geo.placename" content="Saint John">
<meta name="geo.position" content="45.2753734;-66.0789801">
<meta name="ICBM" content="45.2753734, -66.0789801">
@if ($verification = config('services.google.site_verification'))
    <meta name="google-site-verification" content="{{ $verification }}">
@endif
@if ($bing = config('services.bing.site_verification'))
    <meta name="msvalidate.01" content="{{ $bing }}">
@endif

{{-- Icons --}}
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" href="{{ asset('images/logo-small.svg') }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">

{{-- Open Graph (Facebook, WhatsApp, LinkedIn) --}}
<meta property="og:site_name" content="{{ $site }}">
<meta property="og:type" content="{{ $seo->ogType() }}">
<meta property="og:title" content="{{ $pageTitle ? __($pageTitle) : $fullTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $image }}">
<meta property="og:image:alt" content="{{ $pageTitle ? __($pageTitle) : $site }}">
<meta property="og:locale" content="{{ $locale }}">
<meta property="og:locale:alternate" content="{{ $locale === 'fr_CA' ? 'en_CA' : 'fr_CA' }}">

{{-- Twitter / X --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $pageTitle ? __($pageTitle) : $fullTitle }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $image }}">

{{-- Structured data for Google --}}
@foreach ($seo->schemas() as $schema)
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endforeach
