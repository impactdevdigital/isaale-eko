@props(['name', 'menu'])

<div
    x-show="menu === '{{ $name }}'"
    x-cloak
    x-transition.opacity.duration.150ms
    @mouseenter="open('{{ $name }}')"
    @mouseleave="close(180)"
    id="menu-{{ $name }}"
    class="absolute inset-x-0 top-full z-30 flex max-h-[min(640px,calc(100vh-160px))] border-b border-grayscale-40 bg-surface shadow-card"
>
    <ul class="hidden w-60 shrink-0 border-r border-grayscale-40 bg-grayscale-0 py-6 lg:block">
        @foreach ($menu['sidebar'] as $item => $url)
            <li>
                <a href="{{ $url }}" @class([
                    'flex h-12 items-center justify-between px-5',
                    'border-l-4 border-brand-yellow bg-surface text-[14px] leading-[18px] font-extrabold' => $loop->first,
                    'pl-4 text-body text-body-muted hover:bg-surface hover:text-ink' => ! $loop->first,
                ])>
                    {{ $item }}
                    <x-store.icon name="arrow-circle-right" :class="$loop->first ? 'text-ink' : 'text-grayscale-80'" />
                </a>
            </li>
        @endforeach
    </ul>

    <div class="flex-1 overflow-y-auto px-4 pt-6 pb-8 md:px-8 md:pt-8">
        <div class="flex items-end justify-between gap-4">
            <div class="flex flex-col gap-2">
                <p class="text-eyebrow uppercase text-body-muted">{{ $menu['eyebrow'] }}</p>
                <p class="text-h2-mobile md:text-h2">{{ $menu['title'] }}</p>
            </div>
            <a href="{{ $menu['url'] }}" class="flex shrink-0 items-center gap-1 text-[14px] leading-[18px] font-extrabold hover:underline">
                {{ $menu['link'] }}
                <x-store.icon name="chevron-right" />
            </a>
        </div>

        {{-- Sidebar links on small screens, where the sidebar is hidden --}}
        <div class="mt-4 flex flex-wrap gap-2 lg:hidden">
            @foreach (array_slice($menu['sidebar'], 1, preserve_keys: true) as $item => $url)
                <a href="{{ $url }}" class="rounded-full border border-grayscale-40 px-3 py-1.5 text-body-sm">{{ $item }}</a>
            @endforeach
        </div>

        @isset($menu['groups'])
            <div class="mt-6 flex flex-col gap-5">
                @foreach ($menu['groups'] as $group => $items)
                    <section class="flex flex-col gap-4">
                        <h3 class="text-[16px] leading-5 font-extrabold uppercase">{{ $group }}</h3>
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-[repeat(auto-fill,148px)]">
                            @foreach ($items as $item)
                                <a href="{{ $item['url'] }}" class="flex h-[140px] flex-col overflow-hidden rounded-sm border border-grayscale-40 transition-colors hover:border-ink">
                                    @if ($item['image'])
                                        <img src="{{ asset($item['image']) }}" alt="" loading="lazy" class="h-20 w-full object-cover">
                                    @else
                                        <span class="h-20 w-full bg-grayscale-30"></span>
                                    @endif
                                    <span class="px-4 pt-3 text-[14px] leading-[18px] font-bold">{{ $item['name'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @endisset

        @isset($menu['recipes'])
            <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach ($menu['recipes'] as $recipe)
                    <a href="#" class="flex flex-col overflow-hidden rounded-sm border border-grayscale-40 transition-colors hover:border-ink">
                        <img src="{{ asset($recipe['image']) }}" alt="{{ $recipe['name'] }}" loading="lazy" class="aspect-[270/180] w-full object-cover">
                        <span class="flex flex-col gap-1 px-4 pt-4 pb-5">
                            <span class="text-[14px] leading-[18px] font-bold">{{ $recipe['name'] }}</span>
                            <span class="text-body text-body-muted">{{ $recipe['meta'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endisset

        @isset($menu['body'])
            <p class="mt-6 max-w-3xl text-body text-body-muted">{{ $menu['body'] }}</p>
        @endisset
    </div>
</div>
