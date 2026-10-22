{{-- Horizontally scrolling product row with a "1–5 of 15" pager. --}}
@props(['title', 'products', 'perPage' => 5])

<section
    x-data="{
        page: 0,
        pages: 1,
        measure() { const el = this.$refs.track; this.pages = Math.max(1, Math.ceil(el.scrollWidth / el.clientWidth - 0.05)); this.page = Math.round(el.scrollLeft / el.clientWidth) },
        go(step) { const el = this.$refs.track; el.scrollBy({ left: step * el.clientWidth, behavior: 'smooth' }) },
    }"
    x-init="measure()"
    @resize.window.debounce="measure()"
    class="flex flex-col gap-4"
>
    <div class="flex items-center justify-between gap-4">
        <h2 class="text-h2-mobile md:text-h2">{{ $title }}</h2>
        <div class="flex items-center gap-2">
            <span class="hidden pr-2 text-lead text-body-muted sm:inline"
                  x-text="`${page * {{ $perPage }} + 1}–${Math.min((page + 1) * {{ $perPage }}, {{ $products->count() }})} of {{ $products->count() }}`"></span>
            <button type="button" @click="go(-1)" :disabled="page === 0" aria-label="Previous products"
                    class="flex size-9 items-center justify-center rounded-sm border border-grayscale-40 disabled:text-grayscale-50">
                <x-store.icon name="arrow-right" class="size-4 rotate-180" />
            </button>
            <button type="button" @click="go(1)" :disabled="page >= pages - 1" aria-label="More products"
                    class="flex size-9 items-center justify-center rounded-sm border border-grayscale-40 disabled:text-grayscale-50">
                <x-store.icon name="arrow-right" />
            </button>
        </div>
    </div>

    <div x-ref="track" @scroll.debounce.100ms="measure()"
         class="grid snap-x snap-mandatory grid-flow-col gap-2.5 overflow-x-auto [scrollbar-width:none] auto-cols-[45%] sm:auto-cols-[calc((100%-2*0.625rem)/3)] xl:auto-cols-[calc((100%-4*1rem)/5)] xl:gap-4">
        @foreach ($products as $product)
            <x-store.product-card :product="$product" class="snap-start" />
        @endforeach
    </div>
</section>
