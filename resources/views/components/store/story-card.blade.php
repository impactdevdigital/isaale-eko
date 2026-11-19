{{-- Compact story teaser for product and recipe pages. --}}
@props(['story'])

<article {{ $attributes->merge(['class' => 'relative flex min-h-[260px] flex-col justify-end overflow-hidden rounded-xl bg-grayscale-90 p-5']) }}>
    @if ($story->coverUrl())
        <img src="{{ $story->coverUrl() }}" alt="" loading="lazy" class="absolute inset-0 size-full object-cover">
    @endif
    <span class="absolute inset-0 bg-linear-to-t from-black/80 via-black/30 to-transparent" aria-hidden="true"></span>

    <div class="relative flex flex-col gap-1 text-white">
        <p class="text-[10px] leading-[13px] font-black uppercase tracking-[1px]">{{ $story->isFoodStory() ? __('Food Story') : __('Brand Update') }}</p>
        <h3 class="text-[22px] leading-[26px] font-bold tracking-[-1px]">
            <a href="{{ $story->url() }}" class="after:absolute after:inset-0">{{ $story->title }}</a>
        </h3>
        @if ($story->excerpt)
            <p class="text-[13px] leading-[18px] font-bold line-clamp-2">{{ $story->excerpt }}</p>
        @endif
        <p class="mt-2 flex items-center gap-1.5 text-[13px] font-extrabold"><x-store.icon name="clock" class="size-4" /> {{ $story->readMinutes() }} min read</p>
    </div>
</article>
