@php
    $images = $product->images();
    $category = $product->category;
    $canBuy = in_array($state, ['in_stock', 'low_stock'], true);
    $stock = $variation?->stock_quantity;
@endphp

<div class="page-container pt-6 pb-16 md:pt-10">
    <nav aria-label="Breadcrumb" class="text-[11px] leading-[14px] font-semibold text-body-muted">
        <ol class="flex flex-wrap items-center gap-1.5">
            <li><a href="{{ route('home') }}" class="hover:text-ink">Home</a></li>
            <li aria-hidden="true">/</li>
            <li><a href="{{ route('shop') }}" wire:navigate class="hover:text-ink">Shop</a></li>
            @if ($category)
                <li aria-hidden="true">/</li>
                <li><a href="{{ route('shop', ['category' => $category->slug]) }}" wire:navigate class="hover:text-ink">{{ $category->name }}</a></li>
            @endif
            <li aria-hidden="true">/</li>
            <li class="font-bold text-ink" aria-current="page">{{ $product->localized_name }}</li>
        </ol>
    </nav>

    <div class="mt-6 grid gap-8 lg:mt-9 lg:grid-cols-[610fr_661fr] lg:gap-6">
        {{-- Gallery: stays under the header while the details scroll, then both scroll away together --}}
        <div x-data="{ active: 0 }" class="flex flex-col-reverse gap-2.5 sm:flex-row lg:sticky lg:top-[calc(var(--header-h,0px)+1.5rem)] lg:self-start">
            @if (count($images) > 1)
                <div class="flex gap-2.5 sm:flex-col">
                    @foreach ($images as $i => $image)
                        <button type="button" @click="active = {{ $i }}" aria-label="Show image {{ $i + 1 }}"
                                class="size-16 overflow-hidden rounded-sm border sm:size-20"
                                :class="active === {{ $i }} ? 'border-ink' : 'border-grayscale-40'">
                            <img src="{{ asset($image) }}" alt="" class="size-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif

            <div class="relative aspect-[520/480] flex-1 overflow-hidden rounded-sm bg-grayscale-10">
                @foreach ($images as $i => $image)
                    <img src="{{ asset($image) }}" alt="{{ $i === 0 ? $product->localized_name : '' }}" x-show="active === {{ $i }}" @if ($i > 0) x-cloak @endif
                         @class(['absolute inset-0 size-full object-cover', 'opacity-50 grayscale' => ! $canBuy])>
                @endforeach
            </div>
        </div>

        {{-- Details --}}
        <div class="flex flex-col gap-4">
            <div>
                <h1 class="text-hero-mobile">{{ $product->localized_name }}</h1>
                @if ($variation?->name)
                    <p class="mt-1 text-body text-body-muted">{{ $variation->name }}</p>
                @endif
                @if ($product->localized_description)
                    <div class="mt-2 text-sm text-[#555555] leading-relaxed whitespace-pre-line">
                        {{ $product->localized_description }}
                    </div>
                @endif
            </div>

            @if ($variation)
                <div class="flex flex-wrap items-center gap-3">
                    <strong class="text-[28px] leading-[30px] font-extrabold tracking-[-1px] text-price-40">{{ money($variation->price_cents) }}</strong>
                    @if ($variation->previous_price_cents)
                        <s class="text-body text-body-muted">{{ money($variation->previous_price_cents) }}</s>
                    @endif
                    @if ($product->discount_estimate)
                        <x-store.discount-tag :percent="$product->discount_estimate" />
                    @endif
                </div>

                @if ($promotion)
                    <p class="rounded-sm bg-stock-10 px-3 py-2 text-body-sm text-stock-50">
                        <strong>{{ $promotion->offerLabel() }}</strong> with {{ $promotion->name }}{{ $promotion->audience !== 'everyone' ? ' ('.str(\App\Models\Promotion::AUDIENCES[$promotion->audience])->lower()->before(' (').')' : '' }}, until {{ $promotion->ends_at->timezone(config('shop.timezone'))->format('j M') }}. Taken off in your basket.
                    </p>
                @endif
            @endif

            @if ($product->bestseller_rank)
                <div class="flex items-center gap-3 bg-best-10 p-1.5">
                    <span class="flex h-[23px] items-center gap-1 bg-best-40 px-2 text-badge uppercase text-white">
                        <x-store.icon name="star" /> #{{ $product->bestseller_rank }} Bestseller
                    </span>
                    @if ($category)
                        <span class="text-body-sm text-best-40">In {{ $category->name }}</span>
                    @endif
                </div>
            @endif

            {{-- Stock state --}}
            @switch($state)
                @case('in_stock')
                    <div class="flex items-center gap-2.5 bg-stock-0 px-3.5 py-3 text-[12px] leading-4 font-bold text-stock-50">
                        <x-store.icon name="box" /> In stock · Ready to deliver
                    </div>
                    @break
                @case('low_stock')
                    <div class="flex gap-2.5 bg-brand-yellow-10 px-3.5 py-3 text-brand-yellow-70">
                        <x-store.icon name="box" class="mt-0.5 size-4 shrink-0" />
                        <div>
                            <p class="text-[12px] leading-4 font-bold">Low stock · Only {{ $stock }} left</p>
                            <p class="text-[10px] leading-[13px] font-medium">Ready to deliver · Order while stock lasts</p>
                        </div>
                    </div>
                    <p class="border border-sale-50 px-2 py-1 text-[10px] leading-[13px] font-medium uppercase text-sale-50">{{ $stock }} remaining in stock</p>
                    @break
                @case('out_of_stock')
                    <div class="flex gap-2.5 bg-grayscale-20 px-3.5 py-3 text-body-muted">
                        <x-store.icon name="box" class="mt-0.5 size-4 shrink-0 text-stock-50" />
                        <div>
                            <p class="text-[12px] leading-4 font-bold text-grayscale-90">Out of stock · Temporarily sold out</p>
                            <p class="text-[10px] leading-[13px]">We’re restocking this product. Get notified when it’s back.</p>
                        </div>
                    </div>
                    @break
                @default
                    <div class="flex gap-2.5 bg-grayscale-20 px-3.5 py-3 text-body-muted">
                        <x-store.icon name="box" class="mt-0.5 size-4 shrink-0 text-stock-50" />
                        <div>
                            <p class="text-[12px] leading-4 font-bold text-grayscale-90">Product unavailable</p>
                            <p class="text-[10px] leading-[13px]">This product is no longer available. Explore similar products below.</p>
                        </div>
                    </div>
            @endswitch

            <div class="border-t border-grayscale-40 pt-4">
                <p class="text-eyebrow uppercase">Good to know</p>
                <p class="mt-2 text-body text-body-muted">{{ $product->description ?: 'An authentic pantry find selected for Ìsàlèè Èkó’s African and Caribbean shelves. Add it to a family favourite or discover a new way to cook with it below.' }}</p>
            </div>

            <dl class="border-t border-grayscale-40 text-body-sm">
                @foreach (array_filter(['Origin' => $product->country?->name, 'Pack size' => $variation?->name, 'Category' => $category?->name]) as $term => $detail)
                    <div class="flex justify-between gap-4 border-b border-grayscale-40 py-3.5 last:border-b-0">
                        <dt class="font-normal text-grayscale-80">{{ $term }}</dt>
                        <dd class="font-bold">{{ $detail }}</dd>
                    </div>
                @endforeach
            </dl>

            {{-- Buy --}}
            <div class="flex items-center gap-3 sm:gap-4">
                <div @class(['flex h-11 shrink-0 items-center rounded-full border border-grayscale-40 bg-surface', 'opacity-50' => ! $canBuy])>
                    <button type="button" wire:click="decrement" @disabled(! $canBuy || $quantity <= 1) class="-m-px flex size-11 items-center justify-center rounded-full border border-grayscale-40 bg-surface text-[16px] leading-[21px] font-extrabold transition-colors enabled:hover:border-ink" aria-label="Decrease quantity">−</button>
                    <span class="w-[50px] text-center text-[16px] leading-[21px] font-extrabold" aria-live="polite">{{ $quantity }}</span>
                    <button type="button" wire:click="increment" @disabled(! $canBuy) class="-m-px flex size-11 items-center justify-center rounded-full border border-grayscale-40 bg-surface text-[16px] leading-[21px] font-extrabold transition-colors enabled:hover:border-ink" aria-label="Increase quantity">+</button>
                </div>

                @if ($canBuy)
                    <button type="button" wire:click="addToBasket" class="flex h-11 flex-1 items-center justify-center gap-2 rounded-full bg-brand-yellow text-[14px] font-extrabold transition-colors hover:bg-brand-yellow-60 sm:text-[16px]">
                        Add to basket · {{ money($variation->price_cents * $quantity) }}
                        <x-store.icon name="shopping-bag" class="size-4" />
                    </button>
                @else
                    <span aria-disabled="true" class="flex h-11 flex-1 cursor-not-allowed items-center justify-center rounded-full bg-grayscale-20 text-[14px] font-extrabold text-grayscale-60 sm:text-[16px]">
                        {{ $state === 'unavailable' ? 'Unavailable' : 'Add to basket · '.money($variation?->price_cents * $quantity) }}
                    </span>
                @endif

                @php($saved = auth()->user()?->wishlist()->whereKey($product->id)->exists())
                <button type="button" wire:click="toggleWishlist" aria-pressed="{{ $saved ? 'true' : 'false' }}"
                        @class(['flex size-11 shrink-0 items-center justify-center rounded-full border transition-colors', 'border-price-40 bg-sale-0 text-price-40' => $saved, 'border-grayscale-40 hover:border-ink' => ! $saved])
                        aria-label="{{ $saved ? 'Remove '.$product->name.' from wishlist' : 'Save '.$product->name.' to wishlist' }}">
                    <x-store.icon name="heart" class="size-4" />
                </button>
            </div>

            @unless ($canBuy)
                <div class="flex flex-col gap-3 pt-2">
                    <h2 class="text-[20px] leading-6 font-bold">Get Notified When It’s Available</h2>
                    @if ($subscribed)
                        <p class="rounded-sm bg-stock-0 px-4 py-3 text-body-sm text-stock-50">Thanks! We’ll email {{ $email }} as soon as it’s back.</p>
                    @else
                        <form wire:submit="notify" class="flex h-16 items-center rounded-full border border-grayscale-40 p-2 pl-6 focus-within:border-ink">
                            <label for="notify-email" class="sr-only">Email address</label>
                            <input id="notify-email" type="email" wire:model="email" required placeholder="Your email address" class="min-w-0 flex-1 bg-transparent text-[16px] placeholder:text-body-muted focus:outline-none">
                            <button type="submit" class="h-full rounded-full bg-ink px-5 text-[14px] font-extrabold text-white">Notify me</button>
                        </form>
                        @error('email') <p class="text-body-sm text-price-40">{{ $message }}</p> @enderror
                    @endif
                </div>
            @endunless

            <div class="flex items-center gap-3 bg-grayscale-0 px-3.5 py-3">
                <x-store.icon name="bus" class="size-[18px]" />
                <div>
                    <p class="text-[12px] leading-4 font-bold">Free delivery over $40</p>
                    <p class="text-[10px] leading-[13px] text-body-muted">Local delivery and pickup details at checkout</p>
                </div>
            </div>

            <div class="flex flex-col gap-5 rounded-sm border border-grayscale-40 p-6">
                <p class="flex items-center gap-3 text-[15px]"><x-store.icon name="location" class="size-[22px] text-stock-50" /><span><strong>Shipping to</strong> <span class="text-body-muted">Saint John NB, Canada</span></span></p>
                <p class="flex items-center gap-3 text-[15px]"><x-store.icon name="returns" class="size-[22px] text-stock-50" />30-Day Returns</p>
                <div>
                    <p class="flex items-center gap-3 text-[15px]"><x-store.icon name="shield" class="size-[22px] text-stock-50" />Shopping Security</p>
                    <p class="mt-3 flex flex-wrap gap-4 pl-[34px] text-body-sm text-stock-50"><span>✓ Safe Payments</span><span>✓ Privacy Protection</span></p>
                </div>
            </div>
        </div>
    </div>

    @if ($recipes->isNotEmpty())
        <section class="mt-16 flex flex-col gap-4">
            <x-store.section-heading :title="'Cook With '.$product->name" subtitle="Recipes that put this ingredient to work" link="All recipes" />
            <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($recipes as $recipe)
                    <x-store.recipe-card :recipe="$recipe" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($related->isNotEmpty())
        <section class="mt-16 flex flex-col gap-4">
            <x-store.section-heading title="You Might Also Like" subtitle="A few good companions from our shelves" link="All products" :href="route('shop')" />
            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4 lg:grid-cols-7">
                @foreach ($related as $item)
                    <x-store.product-card :product="$item" wire:key="related-{{ $item->id }}" />
                @endforeach
            </div>
        </section>
    @endif
</div>
