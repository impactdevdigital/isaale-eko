{{--
    /shop banner (Figma 1082:94760). The title follows the filter: a chosen
    country reads "Shop Nigeria" (Figma 734:59269), a category "Shop Sauces".
--}}
@props(['country' => null, 'category' => null])

@php
    $eyebrow = $country ? 'Authentic '.($country->demonym ?? $country->name).' products' : 'Authentic products';
    $title = match (true) {
        $country !== null => 'Shop '.$country->localized_name,
        $category !== null => 'Shop '.$category->localized_name,
        default => 'Grocery store with different treasures',
    };
    $text = $country?->banner_description ?: 'We have curated all your traditional african grocery product just for you.';
@endphp

<section class="relative isolate overflow-hidden rounded-[4px] bg-surface-quiet p-6 md:p-[30px]">
    <img src="{{ asset('images/store/countries/banners/nigeria.webp') }}" alt=""
         class="absolute inset-y-0 right-0 -z-10 h-full w-auto max-w-none opacity-30 sm:opacity-100">
    {{-- Keeps the copy readable where the photo reaches under it --}}
    <div class="absolute inset-y-0 left-0 -z-10 w-3/5 bg-gradient-to-r from-surface-quiet from-60% to-transparent"></div>

    <div class="flex max-w-[500px] flex-col gap-2">
        <p class="text-eyebrow uppercase text-body-muted">{{ __($eyebrow) }}</p>
        <h2 class="text-hero-mobile capitalize text-ink">{{ __($title) }}</h2>
        <p class="max-w-[465px] text-lead-mobile text-body-muted md:text-lead">{{ __($text) }}</p>
    </div>
</section>
