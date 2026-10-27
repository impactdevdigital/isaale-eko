<x-layouts::store>
    <div class="page-container flex flex-col pt-6 md:pt-6">
        <x-store.banner-carousel :slides="$heroSlides" tall />

        {{-- Fresh this week --}}
        <section class="flex flex-col gap-4 pt-10 pb-2">
            <x-store.section-heading title="Fresh This Week" subtitle="Popular picks. Great prices. Real flavour." link="View all" :href="route('shop')" />
            <div class="-mx-4 flex snap-x gap-2.5 overflow-x-auto px-4 [scrollbar-width:none] after:w-4 after:shrink-0 md:mx-0 md:grid md:grid-cols-4 md:overflow-visible md:px-0 md:after:hidden lg:grid-cols-6 xl:grid-cols-7">
                @foreach ($freshThisWeek as $product)
                    <x-store.product-card :product="$product" class="w-40 shrink-0 snap-start md:w-auto" />
                @endforeach
            </div>
        </section>

        {{-- Shop ingredients by recipe --}}
        <section class="flex flex-col gap-4 pt-10 pb-2 md:pt-[55px]">
            <x-store.section-heading eyebrow="Cook by craving" title="Shop Ingredients By Recipe" subtitle="Real recipes. Real ingredients. Real home cooking." link="View more recipes" />
            <div class="-mx-4 flex snap-x gap-3 overflow-x-auto px-4 [scrollbar-width:none] after:w-4 after:shrink-0 lg:mx-0 lg:grid lg:grid-cols-3 lg:overflow-visible lg:px-0 lg:after:hidden">
                @foreach ($recipes as $recipe)
                    <x-store.recipe-card :recipe="$recipe" class="w-[340px] shrink-0 snap-start lg:w-auto" />
                @endforeach
            </div>
        </section>

        <x-store.banner-carousel :slides="$promoSlides" class="mt-10" />

        {{-- More from the shelves --}}
        <section class="flex flex-col gap-4 pt-10 pb-2">
            <x-store.section-heading title="More From The Shelves" subtitle="Everyday essentials and new things worth trying." link="Explore all products" :href="route('shop')" />
            <div class="-mx-4 flex snap-x gap-2.5 overflow-x-auto px-4 [scrollbar-width:none] after:w-4 after:shrink-0 md:mx-0 md:grid md:grid-cols-4 md:overflow-visible md:px-0 md:after:hidden lg:grid-cols-6 xl:grid-cols-7">
                @foreach ($moreFromTheShelves as $product)
                    <x-store.product-card :product="$product" class="w-40 shrink-0 snap-start md:w-auto" />
                @endforeach
            </div>
        </section>

        {{-- Shop by category --}}
        <section class="flex flex-col gap-4 pt-10 pb-2">
            <x-store.section-heading title="Shop By Category" subtitle="Everything you need, from pantry staples to everyday essentials." link="View all" :href="route('shop')" />
            <div class="grid grid-cols-3 gap-2.5 md:grid-cols-4 lg:grid-cols-8 lg:gap-[9px]">
                @foreach ($categories as $category)
                    <a href="{{ route('shop', ['category' => $category->slug]) }}" @class(['group flex flex-col overflow-hidden rounded-sm border border-grayscale-40 bg-surface pb-3 transition-colors hover:border-ink', 'max-md:hidden' => $loop->index >= 6])>
                        <img src="{{ asset($category->image) }}" alt="" loading="lazy" class="aspect-[152/125] w-full object-cover">
                        <span class="flex items-center justify-between gap-2 px-2.5 pt-2.5">
                            <span class="text-[11px] leading-[14px] font-bold">{{ $category->name }}</span>
                            <x-store.icon name="arrow-right" class="size-[15px] shrink-0 transition-transform group-hover:translate-x-0.5" />
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Shop by country --}}
        <section id="shop-by-country" class="flex scroll-mt-4 flex-col gap-4 pt-10 pb-12">
            <x-store.section-heading title="Shop By Country" subtitle="Explore authentic flavours from across Africa and the Caribbean." />
            <div class="-mx-4 flex snap-x gap-2 overflow-x-auto px-4 [scrollbar-width:none] after:w-4 after:shrink-0 md:-mr-[30px] md:ml-0 md:pl-0 md:after:hidden xl:-mr-[72px]">
                @foreach ($countries as $country)
                    <a href="{{ route('shop.country', $country) }}" @class([
                        'relative isolate flex size-[170px] shrink-0 snap-start flex-col gap-2 overflow-hidden rounded-sm p-4 md:size-[260px] md:gap-4 md:p-8',
                        'bg-[#288129] text-white' => $country->theme === 'green',
                        'bg-[#F7C200] text-[#242424]' => $country->theme === 'yellow',
                    ])>
                        <img src="{{ asset($country->image) }}" alt="" loading="lazy" class="absolute left-1 top-[85px] -z-10 w-[162px] md:left-1.5 md:top-[130px] md:w-[248px]">
                        <h3 class="text-[18px]/6 font-bold tracking-[-0.5px] md:text-h3 md:tracking-[-1px]">{{ $country->name }}</h3>
                        <p @class(['text-[13px] leading-5 md:text-[14px]', 'text-[#F9FAFB]' => $country->theme === 'green'])>{{ $country->tagline }}</p>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Why customers return (Testimonials) --}}
        <x-store.testimonials />

        {{-- Ask Asha + community --}}
        <div class="mb-12 overflow-hidden rounded-sm">
            <section class="grid gap-6 bg-surface-hero-cream p-3.5 lg:grid-cols-[664fr_604fr] lg:gap-0">
                <img src="{{ asset('images/store/community/asha.webp?v=2') }}" alt="Asha, your grocery concierge" loading="lazy" class="aspect-[3/2] w-full rounded-xl object-cover lg:h-[440px] lg:aspect-auto">
                <div class="flex flex-col gap-2 px-4 pb-10 lg:justify-center lg:px-[52px] lg:py-[72px]">
                    <p class="text-eyebrow uppercase text-[#574B2F]">Your grocery concierge</p>
                    <h2 class="text-h2-mobile md:text-hero-mobile">Meet Asha.</h2>
                    <p class="text-[15px] leading-6 text-[#575042] md:text-[16px] md:leading-[25px]">Tell Asha what you’re cooking, who’s coming, or what you can’t find. She’ll help you build a practical shopping list from our shelves.</p>
                    <div class="flex flex-wrap gap-[7px] pt-2.5 pb-[7px]">
                        @foreach (['What can I make with plantain?', 'Do you have egusi?', 'Best rice for jollof?'] as $prompt)
                            <button type="button" class="rounded-full border border-[#CFC7B4] bg-surface px-[11px] py-2 text-[11px] leading-[14px] transition-colors hover:border-ink">{{ $prompt }}</button>
                        @endforeach
                    </div>
                    <div>
                        <a href="#" class="inline-flex rounded-full bg-brand-yellow py-3 pl-5 pr-[22px] text-[14px] leading-[18px] font-extrabold transition-colors hover:bg-brand-yellow-60">Ask Asha</a>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 bg-ink p-3.5 lg:grid-cols-[664fr_604fr] lg:gap-0">
                <div class="flex flex-col gap-2 px-4 pt-6 lg:px-8 lg:py-[52px]">
                    <p class="text-eyebrow uppercase text-brand-yellow">More than a store</p>
                    <h2 class="text-h2-mobile text-white md:text-hero-mobile">A Modern Market With Community At Its Heart.</h2>
                    <p class="max-w-[330px] pt-[3px] pb-4 text-[13px] leading-5 text-[#C9C9C9]">Meet local makers, taste new flavours and celebrate the cultures that make every table richer.</p>
                    <div>
                        <a href="#" class="inline-flex rounded-full bg-brand-yellow py-3 pl-5 pr-[22px] text-[14px] leading-[18px] font-extrabold transition-colors hover:bg-brand-yellow-60">See what’s on</a>
                    </div>
                </div>
                <img src="{{ asset('images/store/community/market.webp') }}" alt="A shopper choosing plantain at the market" loading="lazy" class="aspect-[3/2] w-full rounded-xl object-cover lg:h-[440px] lg:aspect-auto">
            </section>
        </div>
    </div>
</x-layouts::store>
