@php
    $navLinks = \App\Support\StoreNavigation::links();
    $menus = \App\Support\StoreNavigation::menus();
    $user = auth()->user();
@endphp

<header x-data="{ menu: null }" @keydown.escape.window="menu = null" @click.outside="menu = null" class="relative z-30">
    {{-- Utility bar --}}
    <div class="bg-ink text-caption">
        <div class="flex h-8 items-center justify-between px-4 md:px-7">
            <p class="hidden items-center gap-1.5 text-white lg:flex">
                <x-store.icon name="bus" />
                Fresh African &amp; Caribbean groceries, delivered
            </p>
            <p class="flex items-center gap-1.5 text-brand-yellow">
                <x-store.icon name="magic-star" />
                Ingredients for every table
            </p>
            <p class="text-white">Good food brings us closer.</p>
        </div>
    </div>

    <div class="border-b border-grayscale-30 bg-surface">
        <div class="px-4 md:px-[30px]">
            {{-- Logo, search, location, account, cart --}}
            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-4 py-[18px] md:flex-nowrap md:py-1.5 md:h-[76px]">
                <a href="{{ url('/') }}" class="shrink-0" aria-label="Ìsàlèè Èkó home">
                    <img src="{{ asset('images/logo-small.svg') }}" alt="Ìsàlèè Èkó" class="h-[39px] w-[61px]">
                </a>

                <form action="{{ route('shop') }}" method="GET" role="search" class="order-last flex h-[46px] w-full items-center overflow-hidden rounded-xl border border-grayscale-20 bg-grayscale-0 md:order-none md:ml-2.5 md:max-w-[930px] md:flex-1">
                    <label for="site-search" class="flex flex-1 items-center gap-2.5 pl-5 md:pl-10">
                        <x-store.icon name="search" class="size-[18px] shrink-0 text-ink" />
                        <span class="sr-only">Search</span>
                        <input id="site-search" type="search" name="q" value="{{ request()->routeIs('shop') ? request('q') : '' }}"
                               placeholder="Search products, ingredients or recipes..."
                               class="w-full bg-transparent text-lead-mobile text-ink placeholder:text-body-muted focus:outline-none max-md:placeholder:text-[13px]">
                    </label>
                    <button type="submit" class="flex size-[46px] shrink-0 items-center justify-center rounded-r-sm bg-brand-yellow text-ink transition-colors hover:bg-brand-yellow-60" aria-label="Search">
                        <x-store.icon name="arrow-right" />
                    </button>
                </form>

                <a href="#" class="hidden shrink-0 items-center gap-2 lg:flex">
                    <x-store.icon name="location" />
                    <span class="flex flex-col">
                        <span class="text-body-xs font-normal text-body-muted">Store Location</span>
                        <strong class="text-body-xs font-extrabold capitalize">Saint John, Canada</strong>
                    </span>
                </a>

                <div class="flex shrink-0 items-center gap-4 lg:pl-20">
                    <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
                        <button type="button" @click="open = ! open" :aria-expanded="open" aria-haspopup="menu" aria-controls="account-menu"
                                class="flex flex-col items-center gap-[3px] px-1.5 py-px text-body-xs font-normal">
                            <x-store.icon name="profile" />
                            {{ $user ? str($user->name)->before(' ') : 'Account' }}
                        </button>
                        <x-store.account-menu :user="$user" />
                    </div>
                    <button type="button"
                            x-data="{ count: {{ app(\App\Support\Cart::class)->count() }} }"
                            @cart-updated.window="count = $event.detail.count"
                            @click="$dispatch('cart-open')"
                            :aria-label="`Open cart, ${count} items`"
                            class="relative flex flex-col items-center gap-[3px] px-3 py-px text-body-xs font-normal">
                        <x-store.icon name="shopping-bag" />
                        Cart
                        <span x-show="count > 0" x-cloak x-text="count" class="absolute -top-1 right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-price-40 px-1 text-[9px] leading-none font-bold text-white"></span>
                    </button>
                </div>
            </div>

            {{-- Main navigation --}}
            <div
                x-data="{ canLeft: false, canRight: false, update() { const el = this.$refs.track; this.canLeft = el.scrollLeft > 0; this.canRight = el.scrollLeft + el.clientWidth < el.scrollWidth - 1 } }"
                x-init="update()"
                @resize.window.debounce="update()"
                class="flex h-12 border-t border-grayscale-30"
            >
                <button type="button" @click="$refs.track.scrollBy({ left: -200, behavior: 'smooth' })" :disabled="! canLeft"
                        class="flex w-[33px] shrink-0 items-center justify-center border-r border-grayscale-30 disabled:text-grayscale-50" aria-label="Scroll navigation left">
                    <x-store.icon name="arrow-left" />
                </button>

                <nav x-ref="track" @scroll.debounce.50ms="update()" aria-label="Main navigation" class="flex flex-1 overflow-x-auto px-[5px] [scrollbar-width:none]">
                    @foreach ($navLinks as $link)
                        @isset($link['menu'])
                            <button type="button"
                                    @click="menu = menu === '{{ $link['menu'] }}' ? null : '{{ $link['menu'] }}'"
                                    :aria-expanded="menu === '{{ $link['menu'] }}'"
                                    aria-controls="menu-{{ $link['menu'] }}"
                                    :class="menu === '{{ $link['menu'] }}' ? 'border-brand-yellow' : 'border-transparent'"
                                    class="flex shrink-0 items-center gap-[5px] border-b-[3px] px-[15px] text-nav capitalize transition-colors hover:border-brand-yellow">
                                {{ $link['label'] }}
                                <x-store.icon name="arrow-down" class="size-2.5 transition-transform" x-bind:class="menu === '{{ $link['menu'] }}' && 'rotate-180'" />
                            </button>
                        @else
                            <a href="{{ $link['url'] }}" class="flex shrink-0 items-center border-b-[3px] border-transparent px-[15px] text-nav capitalize transition-colors hover:border-brand-yellow">
                                {{ $link['label'] }}
                            </a>
                        @endisset
                    @endforeach
                </nav>

                <button type="button" @click="$refs.track.scrollBy({ left: 200, behavior: 'smooth' })" :disabled="! canRight"
                        class="flex w-[33px] shrink-0 items-center justify-center border-l border-grayscale-30 disabled:text-grayscale-50" aria-label="Scroll navigation right">
                    <x-store.icon name="chevron-right" />
                </button>
            </div>
        </div>
    </div>

    @foreach ($menus as $key => $menu)
        <x-store.mega-menu :name="$key" :menu="$menu" />
    @endforeach
</header>
