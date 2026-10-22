{{-- Commerce signal tags stacked on a product image: trending, bestseller, hot, estimated discount. --}}
@props(['product', 'compact' => false])

@php
    $tag = $compact ? 'h-[19px] text-badge-sm' : 'h-[23px] text-badge';
@endphp

<div {{ $attributes->merge(['class' => 'pointer-events-none absolute left-0 top-2 flex flex-col items-start gap-1 uppercase']) }}>
    @if ($label = $product->tagLabel())
        <span @class([$tag, 'flex items-center gap-1 bg-trend-50 pl-[5px] pr-2.5 italic text-white [clip-path:polygon(0_0,100%_0,calc(100%-8px)_100%,0_100%)]'])>
            <x-store.icon name="stars" />
            {{ $label }}
        </span>
    @endif

    @if ($product->bestseller_rank)
        <span @class([$tag, 'flex items-center gap-1 bg-best-40 px-2 text-white'])>
            <x-store.icon name="star" />
            #{{ $product->bestseller_rank }} Bestseller
        </span>
    @endif

    @if ($product->is_hot)
        <span @class([$tag, 'flex items-center bg-sale-50 px-2 text-white'])>Hot</span>
    @endif

    @if ($product->discount_estimate)
        <x-store.discount-tag :percent="$product->discount_estimate" :compact="$compact" />
    @endif
</div>
