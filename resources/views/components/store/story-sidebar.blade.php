{{-- Stories sidebar (Figma 07.03/07.04): Trending Stories and the "Get food stories in your inbox" box.
     Must sit inside a Livewire component that uses SubscribesToStories. --}}
@props(['trending', 'subscribed' => false])

<aside {{ $attributes->class('flex flex-col gap-6 lg:pt-6') }} aria-label="More food stories">
    @if ($trending->isNotEmpty())
        <section aria-labelledby="trending-title">
            <h2 id="trending-title" class="text-[18px] leading-6 font-extrabold text-ink">Trending Stories</h2>
            <ul class="mt-6 flex flex-col gap-4">
                @foreach ($trending as $item)
                    <li @class(['pb-4 border-b border-[#DEDEDE]' => ! $loop->last]) wire:key="trending-{{ $item->id }}">
                        <a href="{{ $item->url() }}" class="group grid grid-cols-[139px_1fr] gap-4">
                            <span class="block h-[110px] overflow-hidden rounded-[4px] bg-grayscale-20">
                                @if ($item->coverUrl())
                                    <img src="{{ $item->coverUrl() }}" alt="" loading="lazy" class="size-full object-cover transition-transform duration-300 group-hover:scale-105">
                                @endif
                            </span>
                            <span class="flex min-w-0 flex-col gap-2">
                                <span class="text-[14px] leading-[18px] font-extrabold text-ink group-hover:underline line-clamp-3">{{ $item->title }}</span>
                                <span class="text-[14px] leading-5 font-normal text-[#575042] line-clamp-2">{{ $item->excerpt }}</span>
                                <span class="flex items-center gap-1.5 text-[12px] leading-4 text-ink">
                                    <x-store.icon name="clock" class="size-[14px]" />
                                    <span class="font-extrabold whitespace-nowrap">{{ $item->readMinutes() }} min read</span>
                                    <span class="h-4 w-px bg-ink" aria-hidden="true"></span>
                                    <span class="font-medium whitespace-nowrap">{{ $item->dateLabel() }}</span>
                                </span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="rounded-lg border border-[#DEDEDE] bg-surface px-4 py-6" aria-labelledby="stories-inbox-title">
        <h2 id="stories-inbox-title" class="text-[18px] leading-6 font-extrabold text-ink">Get food stories in your inbox</h2>
        <p class="mt-2 text-[14px] leading-[22px] font-medium text-body-muted">Real stories, fresh ideas and more.</p>

        @if ($subscribed)
            <p class="mt-6 flex items-center gap-2 rounded-[4px] bg-[#DAF4E7] px-4 py-3 text-[14px] font-bold text-[#007D49]" role="status">
                <x-store.icon name="check" class="size-4" /> You’re on the list. Look out for our next story.
            </p>
        @else
            <form wire:submit="subscribe" class="mt-6 flex flex-col gap-3" novalidate>
                <div>
                    <label for="stories-inbox-name" class="sr-only">Full name</label>
                    <input id="stories-inbox-name" type="text" wire:model="subscribeName" autocomplete="name" placeholder="Full Name"
                           class="h-[42px] w-full rounded-full border border-[#DEDEDE] bg-surface px-4 text-[14px] leading-5 text-ink placeholder:text-ink focus:border-ink focus:outline-none">
                    @error('subscribeName') <p class="mt-1 px-4 text-body-xs text-price-40">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="stories-inbox-email" class="sr-only">Email address</label>
                    <input id="stories-inbox-email" type="email" wire:model="subscribeEmail" autocomplete="email" placeholder="Your email address"
                           class="h-[42px] w-full rounded-full border border-[#DEDEDE] bg-surface px-4 text-[14px] leading-5 text-ink placeholder:text-ink focus:border-ink focus:outline-none">
                    @error('subscribeEmail') <p class="mt-1 px-4 text-body-xs text-price-40">{{ $message }}</p> @enderror
                </div>
                <button type="submit" wire:loading.attr="disabled" wire:target="subscribe"
                        class="mt-3 h-[42px] rounded-full bg-ink text-[14px] leading-[18px] font-extrabold text-white transition-colors hover:bg-grayscale-90 disabled:opacity-60">
                    Join
                </button>
            </form>
        @endif
    </section>
</aside>
