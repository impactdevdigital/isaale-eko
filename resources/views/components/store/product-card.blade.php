{{-- Merchandise card. `compact` is the narrow variant used inside recipe cards. --}}
@props(['product', 'compact' => false])

@php
    $variation = $product->defaultVariation;
    $inStock = $product->inStock();
@endphp

<article {{ $attributes->class([
    'relative flex flex-col overflow-hidden rounded-sm bg-surface',
    'border border-grayscale-40' => ! $compact,
    'border-[0.5px] border-grayscale-40' => $compact,
]) }}>
    <div @class([
        'relative overflow-hidden border-b border-[#EDEDED] bg-surface',
        'aspect-[109/106]' => $compact,
        'aspect-[5/4] md:aspect-[7/8]' => ! $compact,
    ])>
        <a href="{{ route('product', $product) }}" wire:navigate tabindex="-1" aria-hidden="true" class="block size-full">
            <img src="{{ asset($product->image) }}" alt="" loading="lazy"
                 @class(['size-full object-cover', 'opacity-40' => ! $inStock])>
        </a>

        @if ($inStock)
            <x-store.product-badges :product="$product" :compact="$compact" />

            @unless ($compact)
                <a href="{{ route('product', $product) }}" wire:navigate class="absolute bottom-2 left-2 flex size-12 items-center justify-center rounded-full bg-surface md:hidden" aria-label="Quick view {{ $product->name }}">
                    <x-store.icon name="eye" />
                </a>
            @endunless
        @else
            <div class="pointer-events-none absolute inset-0 bg-[#CCCCCC]/70"></div>
            <span class="absolute bottom-[9px] left-1/2 -translate-x-1/2 whitespace-nowrap rounded-[2px] bg-black px-4 py-1 text-badge-sm leading-[10px] uppercase text-white">Out of stock</span>
        @endif
    </div>

    <div @class([
        'flex flex-1 flex-col gap-0.5',
        'px-[7px] pb-8 pt-2.5' => $compact,
        'px-[11px] pb-3.5 pt-2.5' => ! $compact,
        'opacity-50' => ! $inStock,
    ])>
        <h3 @class([
            'capitalize',
            'min-h-[34px] pb-2 text-link' => $compact,
            'pb-[23px] text-button-sm' => ! $compact,
        ])><a href="{{ route('product', $product) }}" wire:navigate class="hover:underline">{{ $product->localized_name }}</a></h3>

        @if (! $compact && $variation?->name)
            <p class="text-body-xs text-body-muted">{{ $variation->name }}</p>
        @endif

        @if ($variation)
            <p @class(['mt-auto flex items-baseline gap-[7px]', 'pt-1' => ! $compact])>
                <strong @class(['text-price-40', 'text-price-sm' => $compact, 'text-price-mobile md:text-price' => ! $compact])>{{ money($variation->price_cents) }}</strong>
                @if ($variation->previous_price_cents)
                    <s class="text-body-xs font-normal text-muted">{{ money($variation->previous_price_cents) }}</s>
                @endif
            </p>
        @endif

        @if (! $compact && $inStock)
            <p class="flex items-center gap-[5px] pt-1 text-[9px] leading-3 font-bold text-stock-50">
                <span class="size-1.5 rounded-full bg-stock-50"></span>
                In Stock
            </p>
        @endif
    </div>

    @if ($inStock)
        <button type="button" x-data @click="$dispatch('cart-add', { variation: {{ $variation->id }} })" aria-label="Add {{ $product->name }} to cart" @class([
            'absolute flex items-center justify-center rounded-sm border border-grayscale-40 bg-surface transition-colors hover:border-ink',
            'bottom-2.5 right-2.5 size-[25px]' => $compact,
            'bottom-[9px] right-[9px] size-[34px]' => ! $compact,
        ])>
            <x-store.icon name="bag" :class="$compact ? 'size-3' : null" />
        </button>
    @endif
</article>
