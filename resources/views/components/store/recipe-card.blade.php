@props(['recipe'])

<article {{ $attributes->merge(['class' => 'flex flex-col overflow-hidden rounded-sm border border-grayscale-40 bg-surface']) }}>
    <div class="relative flex h-[190px] flex-col justify-end p-[23px] text-white">
        <img src="{{ asset($recipe->image) }}" alt="{{ $recipe->name }}" loading="lazy" class="absolute inset-0 -z-0 size-full object-cover">
        <div class="absolute inset-0 bg-black/20"></div>

        <div class="relative">
            <p class="text-body-xs font-semibold uppercase">Everything for</p>
            <h3 class="py-[3px] text-h3">{{ $recipe->name }}</h3>
            <p class="text-body-sm">{{ $recipe->tagline }}</p>
            <a href="#" class="mt-3 inline-flex h-[30px] items-center rounded-full border border-white pl-2 pr-2.5 text-button-sm transition-colors hover:bg-white hover:text-ink">Cook this</a>
        </div>
    </div>

    <div class="flex snap-x overflow-x-auto border-b border-grayscale-40 [scrollbar-width:none]">
        @foreach ($recipe->products as $ingredient)
            <x-store.product-card :product="$ingredient" compact class="w-[110px] shrink-0 snap-start" />
        @endforeach
    </div>

    <button type="button" x-data @click="$dispatch('cart-add-many', { variations: @js($recipe->products->filter->inStock()->pluck('defaultVariation.id')->values()) })" class="flex h-10 items-center justify-center gap-[7px] bg-grayscale-20 text-[11px] leading-[14px] font-extrabold text-ink transition-colors hover:bg-grayscale-30">
        Add all {{ $recipe->products->count() }} ingredients
        <x-store.icon name="plus" />
    </button>
</article>
