@php
    $eyebrow = 'text-[10px] leading-[13px] font-black uppercase tracking-[1px] text-ink';
    $heading = 'text-[24px] font-bold leading-[1.15] tracking-tight text-ink md:text-[30px]';
    $prose = 'mt-4 flex flex-col gap-4 text-body text-body-muted';
    $photo = fn (string $file) => asset('images/store/about/'.$file.'.webp');
@endphp

<x-layouts::store :title="__('About Ìsàlèè Èkó')">
    <div class="page-container py-8 md:py-12">
        <nav aria-label="Breadcrumb" class="mb-6">
            <ol class="flex items-center gap-2 text-body-xs text-body-muted">
                <li><a href="{{ route('home') }}" class="transition-colors hover:text-ink">{{ __('Home') }}</a></li>
                <li aria-hidden="true">/</li>
                <li class="font-bold text-ink" aria-current="page">{{ __('About') }}</li>
            </ol>
        </nav>

        {{-- Intro + shopfront --}}
        <section class="grid items-center gap-8 lg:grid-cols-2 lg:gap-12">
            <div class="max-w-[600px]">
                <p class="{{ $eyebrow }}">About Ìsàlèè Èkó</p>
                <h1 class="mt-2 text-[32px] font-bold leading-[1.1] tracking-tight text-ink md:text-[44px]">More than a grocery store. A little closer to home.</h1>
                <div class="{{ $prose }}">
                    <p>There’s something about finding the food you grew up with when you’re far from home. That familiar ingredient on the shelf. The smell of something you haven’t cooked in months. The excitement of knowing you can finally make the meal you’ve been thinking about.</p>
                    <p>For many Africans and Caribbeans living in Atlantic Canada, finding those ingredients hasn’t always been easy.</p>
                </div>
            </div>
            <img src="{{ $photo('shopfront') }}" alt="The Ìsàlèè Èkó shopfront with its yellow sign" width="1600" height="900" class="aspect-[16/9] w-full rounded-lg object-cover">
        </section>

        <section class="mt-10 max-w-[760px] md:mt-14">
            <div class="flex flex-col gap-4 text-body text-body-muted">
                <p>When we looked at the grocery options in Saint John, we noticed a gap. There were a few international grocery stores, but the selection of Nigerian food products was limited. Finding particular ingredients could mean travelling to Halifax or Moncton. And even when you found a store that stocked what you needed, you sometimes spent more time explaining the ingredient than actually shopping for it.</p>
                <p class="font-bold text-ink">We knew there had to be a better way.</p>
            </div>
        </section>

        {{-- Where it all began --}}
        <section class="mt-14 grid items-center gap-8 md:mt-20 lg:grid-cols-2 lg:gap-12" aria-labelledby="about-began">
            <img src="{{ $photo('aisle') }}" alt="Shelves of groceries and beauty products inside the store" loading="lazy" class="aspect-[4/3] w-full rounded-lg object-cover lg:order-first">
            <div class="max-w-[600px]">
                <h2 id="about-began" class="{{ $heading }}">Where It All Began</h2>
                <div class="{{ $prose }}">
                    <p>The idea behind Ìsàlèè Èkó started with a simple observation, that there were many Nigerians living and studying in the area, but very few places where they could easily find the food products and ingredients they knew from home.</p>
                    <p>We wanted to change that.</p>
                    <p>Ìsàlèè Èkó officially opened in March 2024. Olabisi took the lead in establishing and running the store, supported by her brother, Adetomiwa, in developing the business.</p>
                    <p>What began as an effort to make Nigerian groceries more accessible has grown into something with a wider purpose: making African and Caribbean food easier to find for the communities who know and love it, and for those discovering it for the first time.</p>
                </div>
            </div>
        </section>

        {{-- The name --}}
        <section class="mt-14 grid items-center gap-8 md:mt-20 lg:grid-cols-2 lg:gap-12" aria-labelledby="about-name">
            <div class="max-w-[600px]">
                <h2 id="about-name" class="{{ $heading }}">A Name That Brings Lagos Closer</h2>
                <div class="{{ $prose }}">
                    <p class="font-bold text-ink">Why Ìsàlèè Èkó?</p>
                    <p>The name draws inspiration from Isale Eko, the historic heart of Lagos Island, an area deeply connected to the city’s history, markets and everyday life.</p>
                    <p>For many Lagosians and Nigerians, the name carries a certain familiarity. It brings to mind the energy of Lagos, the bustle of market life and the experience of finding all sorts of things in one place.</p>
                    <p>We wanted to bring a little of that feeling to Canada. Not just a place to pick up groceries, but somewhere that reminds you of home. Somewhere you can find the ingredients you know, discover something you haven’t tried before and feel understood when you ask for that particular thing you’ve been looking for.</p>
                </div>
            </div>
            <img src="{{ $photo('shopfront-sign') }}" alt="The Ìsàlèè Èkó sign above the door" loading="lazy" class="aspect-[4/3] w-full rounded-lg object-cover">
        </section>

        {{-- Why we exist --}}
        <section class="mt-14 rounded-lg bg-[#F6F6F6] px-6 py-10 md:mt-20 md:px-12 md:py-14" aria-labelledby="about-why">
            <div class="max-w-[760px]">
                <h2 id="about-why" class="{{ $heading }}">Why We Exist</h2>
                <div class="{{ $prose }}">
                    <p class="text-[18px] font-bold leading-[1.4] text-ink md:text-[20px]">We believe living away from home shouldn’t mean losing access to the food that makes home feel like home.</p>
                    <p>Our purpose is to make African and Caribbean food more accessible to the people who need and love it, so more people can enjoy the foods they grew up with, share their cultures and explore new flavours.</p>
                    <p>But accessibility goes beyond having products on a shelf. It also means knowing what customers are looking for, understanding the ingredients we stock and making people feel welcome when they walk through our doors.</p>
                </div>
            </div>
        </section>

        {{-- Every home + gallery --}}
        <section class="mt-14 md:mt-20" aria-labelledby="about-every-home">
            <div class="max-w-[760px]">
                <h2 id="about-every-home" class="{{ $heading }}">A Little Something for Every Home</h2>
                <div class="{{ $prose }}">
                    <p>Today, Ìsàlèè Èkó welcomes a growing mix of African and Caribbean communities, including Nigerians, Ghanaians, Francophone Africans and customers from across the Caribbean.</p>
                    <p>Our shelves bring together familiar staples, pantry essentials, snacks, drinks and speciality products from different parts of Africa and the Caribbean.</p>
                    <p>Whether you’re making a meal from home, restocking your kitchen, exploring a new cuisine or introducing someone to the flavours you grew up with, we want you to find something that feels right.</p>
                </div>
            </div>
            <ul class="mt-8 grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
                @foreach ($gallery as $item)
                    <li>
                        <img src="{{ $photo($item['file']) }}" alt="{{ $item['alt'] }}" loading="lazy" class="aspect-[4/3] w-full rounded-lg object-cover">
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Where we're going + welcome --}}
        <section class="mt-14 grid gap-10 md:mt-20 lg:grid-cols-2 lg:gap-12" aria-labelledby="about-going">
            <div class="max-w-[600px]">
                <h2 id="about-going" class="{{ $heading }}">Where We’re Going</h2>
                <div class="{{ $prose }}">
                    <p>Our vision is to become a trusted destination for African and Caribbean groceries in Atlantic Canada and beyond. As we grow, we hope to support the wider African and Caribbean food community through accessible products and, in time, wholesale opportunities.</p>
                    <p>We see Ìsàlèè Èkó growing into more than a place to shop: a welcoming space for the African diaspora where food, culture, discovery and community come together. A place where people can share what they love, discover what others enjoy and feel a little more connected, even when home is thousands of miles away.</p>
                    <p>We’re working towards that one customer, one product and one shopping experience at a time.</p>
                </div>
            </div>
            <div class="flex flex-col justify-center rounded-lg bg-ink px-6 py-10 text-white md:px-12">
                <p class="text-body text-grayscale-10">And it starts with something simple: you shouldn’t have to travel to another city or spend ten minutes explaining an ingredient just to find the food you need.</p>
                <p class="mt-3 text-body text-grayscale-10">That’s the experience we’re here to change.</p>
                <p class="mt-8 text-[28px] font-bold leading-[1.1] tracking-tight md:text-[36px]">Welcome to Ìsàlèè Èkó.</p>
                <p class="mt-3 text-body text-grayscale-10">Whether you’re looking for something familiar or curious to try something new, we would love to have you shop with us.</p>
                <div class="mt-6">
                    <a href="{{ route('shop') }}" class="inline-flex h-[42px] items-center rounded-full bg-surface px-5 text-[14px] font-extrabold text-ink transition-colors hover:bg-brand-yellow-50">Explore Our Store</a>
                </div>
            </div>
        </section>

        {{-- Visit the store --}}
        <section class="mt-14 grid gap-6 md:mt-20 lg:grid-cols-[minmax(0,1fr)_380px]" aria-labelledby="about-find">
            <img src="{{ $photo('building') }}" alt="The red-brick building where Ìsàlèè Èkó is located" loading="lazy" class="aspect-[16/9] w-full rounded-lg object-cover">

            <div class="flex flex-col gap-4">
                <div class="rounded-lg bg-[#F6F6F6] p-6">
                    <p class="{{ $eyebrow }}">{{ __('Visit the store') }}</p>
                    <h2 id="about-find" class="mt-1 text-[18px] font-bold text-ink">{{ __('Find us') }}</h2>
                    <p class="mt-2 text-body-sm text-body-muted">{{ $location }}</p>
                    <ul class="mt-3 flex flex-col gap-1.5 text-body-sm">
                        @if ($supportPhone)
                            <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $supportPhone) }}" class="font-bold underline">{{ $supportPhone }}</a></li>
                        @endif
                        @if ($supportEmail)
                            <li><a href="mailto:{{ $supportEmail }}" class="font-bold underline">{{ $supportEmail }}</a></li>
                        @endif
                    </ul>
                    <a href="{{ $mapUrl }}" target="_blank" rel="noopener" class="mt-4 inline-flex items-center gap-1.5 text-[14px] font-bold text-ink underline">
                        <x-store.icon name="location" class="size-4" /> {{ __('Open in Google Maps') }}
                    </a>
                </div>

                <div class="rounded-lg border border-grayscale-30 p-6">
                    <h2 class="text-[18px] font-bold text-ink">{{ __('Opening hours') }}</h2>
                    @if ($hours->isEmpty())
                        <p class="mt-2 text-body-sm text-body-muted">{{ __('Opening hours coming soon.') }}</p>
                    @else
                        <dl class="mt-3 flex flex-col gap-1.5 text-body-sm">
                            @foreach ($hours as $row)
                                <div class="flex justify-between gap-4 {{ $row['today'] ? 'font-bold text-ink' : 'text-body-muted' }}">
                                    <dt>{{ $row['day'] }}@if ($row['today']) <span class="sr-only">({{ __('today') }})</span>@endif</dt>
                                    <dd>{{ $row['time'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                    @if ($closures->isNotEmpty())
                        <div class="mt-4 border-t border-grayscale-30 pt-3 text-body-sm">
                            <p class="font-bold text-ink">{{ __('Upcoming closures') }}</p>
                            <ul class="mt-1 text-body-muted">
                                @foreach ($closures as $closure)
                                    <li>{{ $closure->date->translatedFormat('l j F') }}@if ($closure->reason) · {{ $closure->reason }}@endif</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>
</x-layouts::store>
