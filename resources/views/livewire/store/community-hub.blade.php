{{-- Community Hub — Coming Soon (Figma 07.07, node 1180:71996) --}}
@php
    // Four columns of four photos, as in the Figma grid.
    $columns = [
        ['community/community-01', 'community/community-02', 'community/community-03', 'community/community-04'],
        ['community/community-05', 'community/community-06', 'stories/zobo', 'community/community-08'],
        ['community/community-09', 'community/community-10', 'community/community-11', 'community/community-12'],
        ['community/community-13', 'community/community-14', 'community/community-15', 'stories/madam-ellen'],
    ];
@endphp

<div class="page-container pt-6 pb-10 md:pt-8">
    <nav aria-label="Breadcrumb">
        <ol class="flex items-center gap-1.5 text-[11px] leading-[14px] font-extrabold text-body-muted">
            <li><a href="{{ route('home') }}" class="hover:text-ink">Home</a></li>
            <li aria-hidden="true" class="text-[10px] font-bold">/</li>
            <li class="text-ink" aria-current="page">Community</li>
        </ol>
    </nav>

    <section class="mt-6 grid items-center gap-10 py-4 lg:mt-10 lg:grid-cols-[520px_minmax(0,1fr)] lg:gap-16 lg:py-0" aria-labelledby="community-title">
        <div class="flex flex-col gap-6">
            <div class="flex flex-col gap-5">
                <p class="w-fit rounded-[4px] bg-brand-yellow-20 px-2 py-1 text-[12px] leading-4 font-extrabold uppercase text-brand-yellow-80">Launching soon</p>
                <h1 id="community-title" class="text-[44px] leading-[44px] font-bold tracking-[-2.2px] text-ink md:text-[64px] md:leading-[61px] md:tracking-[-3.2px]">
                    Community Hub<br>is coming soon
                </h1>
                <p class="max-w-[496px] text-[16px] leading-[26px] font-medium text-body-muted">
                    We’re building a space for recipes, stories, food finds and conversations. Join our WhatsApp community for now and be the first to hear when the full hub launches.
                </p>
            </div>

            <div class="flex flex-col gap-2">
                @if ($subscribed)
                    <p class="flex items-center gap-2 rounded-lg bg-[#DAF4E7] px-4 py-2.5 text-[14px] font-bold text-[#007D49]" role="status">
                        <x-store.icon name="check" class="size-4" /> You’re on the list. We’ll email you when the Community Hub launches.
                    </p>
                @else
                    <form wire:submit="subscribe" class="flex flex-col gap-3 sm:flex-row" novalidate>
                        <div class="flex-1">
                            <label for="community-email" class="sr-only">Email address</label>
                            <input id="community-email" type="email" wire:model="email" autocomplete="email" placeholder="Your email address"
                                   class="h-[42px] w-full rounded-lg border border-[#DEDEDE] bg-surface px-4 text-[16px] leading-[26px] font-medium text-ink placeholder:text-body-muted focus:border-ink focus:outline-none">
                            @error('email') <p class="mt-1 text-body-xs text-price-40">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" wire:loading.attr="disabled" wire:target="subscribe"
                                class="h-[42px] shrink-0 rounded-full bg-ink px-5 text-[14px] leading-[18px] font-extrabold text-white transition-colors hover:bg-grayscale-90 disabled:opacity-60">
                            Get launch updates
                        </button>
                    </form>
                @endif

                <div class="flex items-center gap-2.5 text-[16px] leading-[26px] font-medium text-body-muted" aria-hidden="true">
                    <span class="h-px flex-1 bg-[#DEDEDE]"></span> or <span class="h-px flex-1 bg-[#DEDEDE]"></span>
                </div>

                @if ($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener"
                       class="flex h-[42px] items-center justify-center gap-1.5 rounded-full bg-brand-yellow px-5 text-[14px] leading-[18px] font-extrabold text-ink transition-colors hover:bg-brand-yellow-40">
                        <x-store.icon name="whatsapp" class="size-[17px]" /> Join our WhatsApp community
                    </a>
                @else
                    {{-- No group link set yet (SHOP_WHATSAPP_COMMUNITY_URL): keep the design, explain on click. --}}
                    <div x-data="{ told: false }">
                        <button type="button" @click="told = true"
                                class="flex h-[42px] w-full items-center justify-center gap-1.5 rounded-full bg-brand-yellow px-5 text-[14px] leading-[18px] font-extrabold text-ink transition-colors hover:bg-brand-yellow-40">
                            <x-store.icon name="whatsapp" class="size-[17px]" /> Join our WhatsApp community
                        </button>
                        <p x-show="told" x-cloak class="mt-2 text-center text-body-sm text-body-muted" role="status">Our WhatsApp community opens very soon. Leave your email above and we’ll send you the invite.</p>
                    </div>
                @endif
            </div>

            <p class="flex gap-3 text-[16px] leading-[26px] font-medium text-body-muted">
                <span class="w-[3px] shrink-0 rounded-full bg-brand-yellow" aria-hidden="true"></span>
                Chat with fellow food lovers, get updates, and stay connected while we build the full experience.
            </p>
        </div>

        {{-- Photo grid: clipped like the Figma frame; decorative. --}}
        <div class="relative h-[360px] overflow-hidden sm:h-[460px] lg:h-[576px]" aria-hidden="true">
            <div class="absolute inset-x-0 -top-[85px] grid grid-cols-4 gap-2 lg:gap-[14px]">
                @foreach ($columns as $c => $photos)
                    <div class="flex flex-col gap-2">
                        @foreach ($photos as $file)
                            <img src="{{ asset('images/store/'.$file.'.webp') }}" alt="" loading="{{ $c < 2 ? 'eager' : 'lazy' }}"
                                 class="aspect-[167/171] w-full rounded-[10px] object-cover">
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</div>
