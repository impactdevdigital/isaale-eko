@props(['title', 'subtitle' => null, 'eyebrow' => null, 'link' => null, 'href' => '#'])

<div {{ $attributes->merge(['class' => 'flex items-end justify-between gap-4']) }}>
    <div class="flex min-w-0 flex-col gap-[3px]">
        @if ($eyebrow)
            <p class="text-eyebrow uppercase text-[#574B2F]">{{ $eyebrow }}</p>
        @endif
        <h2 class="text-h2-mobile text-ink md:text-h2">{{ $title }}</h2>
        @if ($subtitle)
            <p class="max-w-[245px] text-lead-mobile text-body-muted md:max-w-none">{{ $subtitle }}</p>
        @endif
    </div>

    @if ($link)
        <a href="{{ $href }}" class="flex shrink-0 items-center gap-[7px] text-button-sm text-ink hover:underline">
            {{ $link }}
            <x-store.icon name="arrow-right" />
        </a>
    @endif
</div>
