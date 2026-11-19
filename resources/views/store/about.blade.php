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
            <div class="max-w-[560px]">
                <p class="text-[10px] leading-[13px] font-black uppercase tracking-[1px] text-ink">{{ __('Visit the store') }}</p>
                <h1 class="mt-2 text-[32px] font-bold leading-[1.1] tracking-tight text-ink md:text-[44px]">{{ __('A taste of home in Saint John') }}</h1>
                <p class="mt-4 text-body text-body-muted">{{ __('Ìsàlèè Èkó is an African and Caribbean grocery store in Saint John, New Brunswick. We stock the flours, grains, spices, oils, snacks and household favourites you grew up with, plus our own Ìsàlèè Èkó range.') }}</p>
                <p class="mt-3 text-body text-body-muted">{{ __('Shop online and pick up in store, or come in and browse the shelves. Look for the yellow Ìsàlèè Èkó sign.') }}</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('shop') }}" class="inline-flex h-[42px] items-center rounded-full bg-ink px-5 text-[14px] font-extrabold text-white transition-colors hover:bg-grayscale-90">{{ __('Shop online') }}</a>
                    <a href="{{ $mapUrl }}" target="_blank" rel="noopener" class="inline-flex h-[42px] items-center gap-2 rounded-full border border-ink px-5 text-[14px] font-extrabold text-ink transition-colors hover:bg-ink hover:text-white">
                        <x-store.icon name="location" class="size-4" /> {{ __('Get directions') }}
                    </a>
                </div>
            </div>
            <img src="{{ asset('images/store/about/shopfront.webp') }}" alt="{{ __('The Ìsàlèè Èkó shopfront with its yellow sign') }}" width="1600" height="900" class="aspect-[16/9] w-full rounded-lg object-cover">
        </section>

        {{-- Inside the store --}}
        <section class="mt-14 md:mt-20" aria-labelledby="about-inside">
            <h2 id="about-inside" class="text-[24px] font-bold tracking-tight text-ink md:text-[30px]">{{ __('Inside the store') }}</h2>
            <p class="mt-1 text-body-sm text-body-muted">{{ __('Full shelves, everyday staples and our own Ìsàlèè Èkó products.') }}</p>
            <ul class="mt-6 grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-4">
                @foreach ($gallery as $photo)
                    <li>
                        <img src="{{ asset('images/store/about/'.$photo['file'].'.webp') }}" alt="{{ __($photo['alt']) }}" loading="lazy" class="aspect-[4/3] w-full rounded-lg object-cover">
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Find us --}}
        <section class="mt-14 grid gap-6 md:mt-20 lg:grid-cols-[minmax(0,1fr)_380px]" aria-labelledby="about-find">
            <img src="{{ asset('images/store/about/building.webp') }}" alt="{{ __('The red-brick building where Ìsàlèè Èkó is located') }}" loading="lazy" class="aspect-[16/9] w-full rounded-lg object-cover">

            <div class="flex flex-col gap-4">
                <div class="rounded-lg bg-[#F6F6F6] p-6">
                    <h2 id="about-find" class="text-[18px] font-bold text-ink">{{ __('Find us') }}</h2>
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
