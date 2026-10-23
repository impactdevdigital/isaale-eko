@props(['country'])

@php
    $shortcuts = \App\Models\Category::featured()
        ->whereHas('products', fn ($query) => $query->active()->where('country_id', $country->id))
        ->take(5)
        ->get();
@endphp

<section class="relative isolate overflow-hidden rounded-sm bg-[#EFF4EB] p-6 md:p-[30px]">
    @if ($country->banner_image)
        <img src="{{ asset($country->banner_image) }}" alt="" class="absolute inset-y-0 right-0 -z-10 h-full w-auto max-w-none opacity-30 sm:opacity-100">
        {{-- Keeps the copy readable where the photo reaches under it --}}
        <div class="absolute inset-y-0 left-0 -z-10 w-3/5 bg-gradient-to-r from-[#EFF4EB] from-60% to-transparent"></div>
    @endif

    <div class="flex max-w-[500px] flex-col gap-2">
        <p class="text-eyebrow uppercase text-body-muted">Authentic {{ $country->demonym ?? $country->name }} products</p>
        <h2 class="text-hero-mobile md:text-hero">Shop {{ $country->name }}</h2>
        @if ($country->banner_description)
            <p class="max-w-[465px] text-lead-mobile text-body-muted md:text-lead">{{ $country->banner_description }}</p>
        @endif
    </div>

    @if ($shortcuts->isNotEmpty())
        <div class="mt-6 flex flex-wrap gap-2 md:mt-[27px]">
            @foreach ($shortcuts as $category)
                <button type="button" wire:click="$set('category', '{{ $category->slug }}')"
                        class="flex h-11 items-center gap-2 rounded-full bg-surface px-4 text-button-sm transition-shadow hover:shadow-card">
                    @if ($category->image && file_exists(public_path($category->image)))
                        <img src="{{ asset($category->image) }}" alt="" class="w-6 h-6 rounded-full object-cover">
                    @elseif ($category->emoji)
                        <span class="text-[22px] leading-none" aria-hidden="true">{{ $category->emoji }}</span>
                    @endif
                    {{ $category->name }}
                </button>
            @endforeach
        </div>
    @endif

    @if ($country->banner_image && $country->flagUrl())
        <div class="absolute right-[2%] top-[34px] hidden w-[118px] flex-col items-center gap-3 text-center xl:flex" aria-hidden="true">
            <img src="{{ $country->flagUrl() }}" alt="" class="h-[30px] w-11 rounded-full object-cover">
            <p class="font-['Caveat',cursive] text-[24px] leading-[23px] font-bold">Good {{ $country->demonym }} food brings us closer.</p>
        </div>
    @endif
</section>
