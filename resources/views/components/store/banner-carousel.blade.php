{{-- Auto-advancing promo banner with arrows, dots and a pause control (Alpine via Livewire).
     Heights follow Figma: top banner 214 / 330 / 372, middle banner 210 / 315 / 360 (phone / tablet / desktop). --}}
@props(['slides', 'tall' => false, 'interval' => 6000])

<section
    x-data="{
        active: 0,
        count: {{ count($slides) }},
        playing: true,
        timer: null,
        go(i) { this.active = (i + this.count) % this.count },
        start() { this.stop(); if (this.playing && this.count > 1) this.timer = setInterval(() => this.go(this.active + 1), {{ $interval }}) },
        stop() { clearInterval(this.timer) },
        toggle() { this.playing = ! this.playing; this.start() },
    }"
    x-init="start()"
    @mouseenter="stop()"
    @mouseleave="start()"
    aria-roledescription="carousel"
    {{ $attributes->class([
        'relative isolate overflow-hidden rounded-hero-mobile md:rounded-hero',
        'h-[214px] md:h-[330px] xl:h-[372px]' => $tall,
        'h-[210px] md:h-[315px] xl:h-[360px]' => ! $tall,
    ]) }}
>
    @foreach ($slides as $i => $slide)
        <div
            x-show="active === {{ $i }}"
            x-transition.opacity.duration.500ms
            @if ($i > 0) x-cloak @endif
            class="absolute inset-0 {{ $slide['tint'] }}"
            aria-roledescription="slide"
            aria-label="{{ $i + 1 }} of {{ count($slides) }}"
        >
            <img src="{{ $slide['image'] }}" alt="" class="absolute inset-0 size-full object-cover object-right" @if ($i > 0) loading="lazy" @endif>
            {{-- On phones the photo sits under the copy: a soft shade keeps it readable --}}
            <div @class([
                'absolute inset-0 bg-gradient-to-r to-transparent md:hidden',
                'from-white/80 via-white/50' => ($slide['text'] ?? 'text-white') === 'text-ink',
                'from-black/60 via-black/35' => ($slide['text'] ?? 'text-white') !== 'text-ink',
            ])></div>

            <div class="relative flex h-full max-w-[460px] flex-col justify-center gap-1.5 px-5 md:box-content md:gap-2 md:px-[72px] {{ $slide['text'] ?? 'text-white' }}">
                @if ($slide['eyebrow'])
                    <p class="text-eyebrow uppercase opacity-90">{{ $slide['eyebrow'] }}</p>
                @endif
                <h2 class="max-w-[260px] text-h2-mobile md:max-w-none md:text-hero-mobile xl:text-hero">{{ $slide['title'] }}</h2>
                @if ($slide['body'])
                    <p class="hidden pb-2 text-lead-mobile opacity-90 sm:block md:pt-1.5 md:pb-4 xl:text-lead">{{ $slide['body'] }}</p>
                @endif
                @if ($slide['cta'] && ($slide['url'] ?? null))
                    <div>
                        <a href="{{ $slide['url'] }}" class="inline-flex items-center rounded-full py-3 pl-5 pr-[22px] text-[14px] leading-[18px] font-extrabold transition-colors {{ $slide['button'] ?? 'bg-surface text-ink hover:bg-brand-yellow-50' }}">{{ $slide['cta'] }}</a>
                    </div>
                @endif
            </div>
        </div>
    @endforeach

    @if (count($slides) > 1)
        <button type="button" @click="go(active - 1)" class="absolute left-3 top-1/2 hidden size-[50px] -translate-y-1/2 items-center justify-center rounded-full bg-surface/80 text-ink transition-colors hover:bg-surface md:flex" aria-label="Previous slide">
            <x-store.icon name="arrow-left" />
        </button>
        <button type="button" @click="go(active + 1)" class="absolute right-3 top-1/2 hidden size-[50px] -translate-y-1/2 items-center justify-center rounded-full bg-surface/80 text-ink transition-colors hover:bg-surface md:flex" aria-label="Next slide">
            <x-store.icon name="chevron-right" />
        </button>

        <div class="absolute bottom-6 left-1/2 hidden -translate-x-1/2 items-center gap-3 rounded-full bg-surface/90 px-4 py-2.5 md:flex">
            <button type="button" @click="toggle()" class="text-ink" :aria-label="playing ? 'Pause slideshow' : 'Play slideshow'">
                <x-store.icon name="pause" x-show="playing" />
                <x-store.icon name="play" x-show="! playing" x-cloak />
            </button>
            <span class="h-5 w-px bg-grayscale-60"></span>
            <div class="flex items-center gap-3">
                @foreach ($slides as $i => $slide)
                    <button type="button" @click="go({{ $i }})"
                            class="h-3 rounded-full transition-all"
                            :class="active === {{ $i }} ? 'w-[30px] bg-ink' : 'w-3 bg-muted'"
                            aria-label="Go to slide {{ $i + 1 }}"></button>
                @endforeach
            </div>
        </div>
    @endif
</section>
