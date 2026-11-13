@php
    $count = $available->sum('quantity') + $unavailable->sum('quantity');
@endphp

<div class="page-container pt-6 pb-16 md:pt-10">
    <nav aria-label="Breadcrumb" class="text-[11px] leading-[14px] font-semibold text-body-muted">
        <ol class="flex items-center gap-1.5">
            <li><a href="{{ route('home') }}" class="hover:text-ink">Home</a></li>
            <li aria-hidden="true">/</li>
            <li class="font-bold text-ink" aria-current="page">Your Cart</li>
        </ol>
    </nav>

    <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_400px] lg:gap-8">
        <section aria-labelledby="basket-title">
            <div class="flex items-center gap-3 border-b border-grayscale-40 pb-5">
                <x-store.icon name="shopping-bag" class="size-6" />
                <h1 id="basket-title" class="text-h2-mobile md:text-h2">Your Basket</h1>
                <span class="rounded-full bg-grayscale-30 px-2.5 py-1 text-[13px] font-bold text-body-muted">{{ $count }} {{ str('item')->plural($count) }}</span>
            </div>

            @if ($available->isEmpty() && $unavailable->isEmpty())
                <div class="flex flex-col items-center py-16 text-center">
                    <img src="{{ asset('images/store/empty-basket.svg') }}" alt="" class="w-[300px]">
                    <h2 class="mt-10 text-h2-mobile md:text-h2">Your Basket Is Hungry</h2>
                    <p class="mt-3 max-w-[450px] text-lead-mobile font-normal text-body-muted md:text-lead">Add an ingredient or a complete recipe to get started. Your next great meal is just a few clicks away.</p>
                    <a href="{{ route('shop') }}" wire:navigate class="mt-6 rounded-full bg-ink px-6 py-3 text-[14px] font-extrabold text-white">Start shopping</a>
                </div>
            @else
                @if ($available->isNotEmpty())
                    <div class="flex items-center justify-between py-5">
                        <button type="button" wire:click="toggleAll" role="checkbox" aria-checked="{{ $allSelected ? 'true' : 'false' }}" class="flex items-center gap-3 text-[14px] font-bold">
                            <x-store.checkbox :checked="$allSelected" />
                            Select all items
                        </button>
                        <button type="button" wire:click="removeSelected" class="text-[12px] text-body-muted underline">Remove selected</button>
                    </div>

                    <ul class="border-b border-grayscale-40">
                        @foreach ($available as $id => $line)
                            @php($product = $line->variation->product)
                            <li wire:key="line-{{ $id }}" class="flex gap-4 border-t border-grayscale-40 py-5 first:border-t-0 sm:gap-5">
                                <button type="button" wire:click="toggle({{ $id }})" role="checkbox" aria-checked="{{ $line->selected ? 'true' : 'false' }}" aria-label="Include {{ $product->name }} in checkout" class="self-center">
                                    <x-store.checkbox :checked="$line->selected" />
                                </button>

                                <a href="{{ route('product', $product) }}" wire:navigate class="shrink-0">
                                    <img src="{{ asset($product->image) }}" alt="" class="size-20 rounded-sm border border-grayscale-40 object-cover">
                                </a>

                                <div class="flex min-w-0 flex-1 flex-col gap-3 sm:flex-row sm:justify-between">
                                    <div class="flex flex-col gap-1">
                                        <a href="{{ route('product', $product) }}" wire:navigate class="text-[15px] font-bold hover:underline">{{ $product->name }}</a>
                                        @if ($line->variation->name)
                                            <p class="text-body-sm font-normal text-body-muted">{{ $line->variation->name }}</p>
                                        @endif
                                        <div class="mt-2 flex items-center gap-2">
                                            <span class="text-[12px] font-bold">Qty:</span>
                                            <div class="flex h-8 items-center rounded-full border border-grayscale-40">
                                                <button type="button" wire:click="decrement({{ $id }})" @disabled($line->quantity <= 1) class="flex h-full w-8 items-center justify-center border-r border-grayscale-40 disabled:text-grayscale-50" aria-label="Decrease quantity"><x-store.icon name="minus" /></button>
                                                <span class="w-8 text-center text-[13px] font-bold" aria-live="polite">{{ $line->quantity }}</span>
                                                <button type="button" wire:click="increment({{ $id }})" @disabled($line->quantity >= min(\App\Support\Cart::MAX_QUANTITY, $line->variation->stock_quantity ?? PHP_INT_MAX)) class="flex h-full w-8 items-center justify-center border-l border-grayscale-40 disabled:text-grayscale-50" aria-label="Increase quantity"><x-store.icon name="plus" class="size-3" /></button>
                                            </div>
                                        </div>
                                        @if ($line->notice)
                                            <p class="mt-1 text-body-xs text-sale-60" role="status">{{ $line->notice }}</p>
                                        @endif
                                    </div>

                                    <div class="flex flex-row items-end justify-between gap-2 sm:flex-col">
                                        <p class="text-right">
                                            <span class="block text-[18px] font-extrabold text-price-40">{{ money($line->variation->price_cents * $line->quantity) }}</span>
                                            @if ($line->variation->previous_price_cents)
                                                <s class="text-body-xs text-muted">{{ money($line->variation->previous_price_cents * $line->quantity) }}</s>
                                            @endif
                                        </p>
                                        <div class="flex items-center gap-4 text-[12px] font-bold">
                                            <button type="button" wire:click="remove({{ $id }})" class="flex items-center gap-1 underline">Remove <x-store.icon name="card-remove" /></button>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($unavailable->isNotEmpty())
                    <section class="border-b border-grayscale-40 py-5" aria-labelledby="unavailable-title">
                        <div class="flex items-center justify-between">
                            <h2 id="unavailable-title" class="text-[14px] font-bold">Unavailable items ({{ $unavailable->count() }})</h2>
                            <button type="button" wire:click="removeUnavailable" class="text-[12px] font-bold underline">Remove all</button>
                        </div>
                        <ul class="mt-4 flex flex-col gap-4">
                            @foreach ($unavailable as $id => $line)
                                @php($product = $line->variation->product)
                                <li wire:key="unavailable-{{ $id }}" class="flex gap-4">
                                    <div class="relative size-20 shrink-0 overflow-hidden rounded-sm border border-grayscale-40">
                                        <img src="{{ asset($product->image) }}" alt="" class="size-full object-cover opacity-50 grayscale">
                                        <span class="absolute inset-x-1 bottom-1 rounded-[2px] bg-black py-0.5 text-center text-[7px] font-black uppercase text-white">Out of stock</span>
                                    </div>
                                    <div class="flex flex-1 justify-between gap-3">
                                        <div class="text-grayscale-60">
                                            <p class="text-[15px] font-bold">{{ $product->name }}</p>
                                            <p class="text-body-sm">{{ $line->variation->name }}</p>
                                            <p class="mt-2 flex items-center gap-1.5 text-body-xs"><span class="size-1.5 rounded-full bg-grayscale-50"></span> Item unavailable</p>
                                        </div>
                                        <div class="flex flex-col items-end justify-between">
                                            <span class="text-[18px] font-extrabold text-price-40/60">{{ money($line->variation->price_cents) }}</span>
                                            <button type="button" wire:click="remove({{ $id }})" class="flex items-center gap-1 text-[12px] font-bold underline">Remove <x-store.icon name="card-remove" /></button>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <a href="{{ route('shop') }}" wire:navigate class="mt-6 inline-flex items-center gap-2 text-[14px] font-bold underline">
                    <x-store.icon name="arrow-left" class="size-4" /> Continue shopping
                </a>
            @endif
        </section>

        <x-store.order-summary :lines="$checkoutLines" :totals="$totals" :coupon-result="$couponResult"
                               :show-free-delivery-hint="\App\Support\Delivery::enabled()"
                               :fee-label="\App\Support\Delivery::enabled() ? 'Estimated delivery' : 'Pickup fee'"
                               :fee-value="\App\Support\Delivery::enabled() ? 'Calculated at checkout' : 'Free'"
                               class="lg:self-start">
            @if ($checkoutLines->isNotEmpty())
                <a href="{{ route('checkout') }}" class="flex h-[50px] items-center justify-center gap-2 rounded-full bg-ink text-[15px] font-extrabold text-white transition-colors hover:bg-grayscale-90">
                    Proceed to checkout <x-store.icon name="arrow-right" />
                </a>
            @else
                <span aria-disabled="true" class="flex h-[50px] cursor-not-allowed items-center justify-center gap-2 rounded-full bg-grayscale-30 text-[15px] font-extrabold text-white">
                    Proceed to checkout <x-store.icon name="arrow-right" />
                </span>
            @endif
        </x-store.order-summary>
    </div>

    @if ($popular->isNotEmpty())
        <section class="mt-16 flex flex-col gap-4">
            <x-store.section-heading eyebrow="While you’re here" title="Popular Picks" subtitle="Bestsellers and everyday essentials to get you started" link="View all products" :href="route('shop')" />
            <div class="flex snap-x gap-2.5 overflow-x-auto [scrollbar-width:none] md:grid md:grid-cols-4 md:overflow-visible lg:grid-cols-7">
                @foreach ($popular as $product)
                    <x-store.product-card :product="$product" class="w-40 shrink-0 snap-start md:w-auto" wire:key="popular-{{ $product->id }}" />
                @endforeach
            </div>
        </section>
    @endif
</div>
