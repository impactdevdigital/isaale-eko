<div class="page-container py-6 md:py-10">
    <!-- Breadcrumb -->
    <nav aria-label="Breadcrumb" class="mb-6">
        <ol class="flex items-center gap-2 text-body-xs text-body-muted">
            <li><a href="{{ route('home') }}" class="transition-colors hover:text-ink">Home</a></li>
            <li aria-hidden="true">/</li>
            <li><a href="{{ route('kitchen.index') }}" class="transition-colors hover:text-ink">Discover</a></li>
            <li aria-hidden="true">/</li>
            <li><a href="{{ route('kitchen.index') }}" class="transition-colors hover:text-ink">Recipes</a></li>
            <li aria-hidden="true">/</li>
            <li class="font-bold text-ink truncate max-w-[200px] sm:max-w-none" aria-current="page">{{ $recipe->title }}</li>
        </ol>
    </nav>

    <!-- Flash notifications -->
    @if (session()->has('cart_success'))
        <div class="mb-6 p-4 bg-brand-yellow-10 border border-brand-yellow-40 rounded-xl flex items-center justify-between text-ink text-body-sm font-medium">
            <div class="flex items-center gap-2">
                <x-store.icon name="check" class="size-5 text-price-50 shrink-0" />
                <span>{{ session('cart_success') }}</span>
            </div>
            <a href="{{ route('cart') }}" class="underline font-bold hover:text-brand-yellow-80 shrink-0 ml-4">View Basket &rarr;</a>
        </div>
    @endif
    @if (session()->has('cart_warning'))
        <div class="mb-6 p-4 bg-sale-10 border border-price-20 rounded-xl flex items-center justify-between text-price-50 text-body-sm font-medium">
            <span>{{ session('cart_warning') }}</span>
        </div>
    @endif

    <!-- Top Split Layout: Hero Media & Overview (Figma 07.02) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
        <!-- Left: Hero Media Player Box -->
        <div class="lg:col-span-5 relative aspect-square sm:aspect-4/3 lg:aspect-square rounded-2xl overflow-hidden bg-grayscale-90 shadow-sm border border-grayscale-30 group">
            @if ($recipe->image || $recipe->image_url)
                <img src="{{ asset($recipe->image ?: $recipe->image_url) }}" alt="{{ $recipe->title }}"
                     class="size-full object-cover">
            @else
                <div class="size-full flex items-center justify-center bg-grayscale-80 text-white font-bold text-3xl">
                    {{ $recipe->title }}
                </div>
            @endif
            <!-- Subtle gradient vignette -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-black/40 pointer-events-none"></div>

            <!-- Top Left Brand Badge -->
            <div class="absolute top-4 left-4 z-10 flex items-center gap-2.5">
                <span class="flex size-9 items-center justify-center rounded-full bg-surface border border-white/40 shadow-xs overflow-hidden p-1">
                    <img src="{{ asset('images/logo-small.svg') }}" alt="" class="size-full object-contain">
                </span>
                <div class="text-white drop-shadow-xs">
                    <p class="text-xs font-bold leading-tight">Ìsàlèè Èkó</p>
                    <p class="text-[10px] text-white/80 leading-tight">Food From Home</p>
                </div>
            </div>

            <!-- Center Play Button (Visual representation of video guide) -->
            <div class="absolute inset-0 flex items-center justify-center z-10">
                <button type="button" aria-label="Play recipe video"
                        class="size-14 sm:size-16 rounded-full bg-black/60 backdrop-blur-xs text-white flex items-center justify-center hover:scale-110 transition-transform shadow-xl cursor-pointer">
                    <svg class="size-6 ml-1 fill-white" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z" />
                    </svg>
                </button>
            </div>

            <!-- Bottom Video Timeline Bar -->
            <div class="absolute bottom-4 inset-x-4 z-10 text-white space-y-2">
                <div class="flex items-center gap-2">
                    <div class="relative flex-1 h-1.5 rounded-full bg-white/30 overflow-hidden">
                        <div class="absolute left-0 top-0 bottom-0 w-1/3 bg-[#DF2600] rounded-full"></div>
                    </div>
                </div>
                <div class="flex items-center justify-between text-[11px] font-semibold text-white/90">
                    <span>5:07 &sol; {{ $recipe->durationLabel() ?: '15:28' }}</span>
                    <div class="flex items-center gap-3">
                        <svg class="size-4 opacity-80 hover:opacity-100 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                        </svg>
                        <svg class="size-4 opacity-80 hover:opacity-100 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Recipe Details & Method Steps -->
        <div class="lg:col-span-7 flex flex-col justify-between">
            <div>
                <!-- Category Eyebrow -->
                <span class="block text-body-xs font-bold uppercase tracking-widest text-grayscale-80">
                    {{ $recipe->country ? strtoupper($recipe->country->name) . ' RECIPE' : 'AUTHENTIC RECIPE' }}
                </span>

                <!-- Recipe Title + Save meal -->
                <div class="mt-1 flex items-start justify-between gap-4">
                    <h1 class="text-h2-mobile md:text-[34px] lg:text-[40px] font-bold text-ink tracking-tight leading-tight">
                        {{ $recipe->title }}
                    </h1>
                    <button type="button" wire:click="toggleSaved" aria-pressed="{{ $isSaved ? 'true' : 'false' }}"
                            @class(['mt-1 inline-flex shrink-0 items-center gap-1.5 rounded-full border px-3.5 py-2 text-[13px] font-bold transition-colors', 'border-price-40 bg-sale-10 text-price-40' => $isSaved, 'border-grayscale-40 hover:border-ink' => ! $isSaved])>
                        <x-store.icon name="heart" class="size-4" /> {{ $isSaved ? 'Saved' : 'Save meal' }}
                    </button>
                </div>

                <!-- Description -->
                <p class="mt-3 text-body-sm sm:text-body text-ink/80 leading-relaxed">
                    {{ $recipe->description ?: ($recipe->intro ?: $recipe->tagline) }}
                </p>

                <!-- Metadata Chips -->
                <div class="mt-4 flex flex-wrap items-center gap-2 text-body-xs font-semibold text-ink">
                    @if ($recipe->durationLabel())
                        <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-grayscale-10 border border-grayscale-30">
                            <x-store.icon name="clock" class="size-3.5 text-body-muted" />
                            {{ $recipe->durationLabel() }}
                        </span>
                    @endif
                    <!-- Servings Selector -->
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-grayscale-10 border border-grayscale-30">
                        <x-store.icon name="user" class="size-3.5 text-body-muted" />
                        <span>{{ $people }} Servings</span>
                        <div class="inline-flex items-center gap-1 ml-1 pl-1.5 border-l border-grayscale-30">
                            <button type="button" wire:click="decrementPeople" aria-label="Decrease servings" class="size-4 flex items-center justify-center rounded hover:bg-grayscale-30 text-grayscale-80 font-bold transition-colors">&minus;</button>
                            <button type="button" wire:click="incrementPeople" aria-label="Increase servings" class="size-4 flex items-center justify-center rounded hover:bg-grayscale-30 text-grayscale-80 font-bold transition-colors">&plus;</button>
                        </div>
                    </div>
                    @if ($recipe->country)
                        <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-grayscale-10 border border-grayscale-30">
                            <x-store.icon name="location" class="size-3.5 text-body-muted" />
                            {{ $recipe->country->name }}
                        </span>
                    @endif
                    <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-grayscale-10 border border-grayscale-30">
                        <span>🍲</span>
                        Lunch &amp; Dinner
                    </span>
                </div>
            </div>

            <!-- Method Steps Section -->
            <div class="mt-8 pt-6 border-t border-grayscale-20">
                <h2 class="text-xl sm:text-[22px] font-bold text-ink">Method</h2>
                <p class="mt-1 text-body-xs text-body-muted">
                    Follow these simple steps to make a delicious {{ $recipe->title }}.
                </p>

                <div class="mt-4 space-y-3">
                    @forelse ($recipe->parsedSteps() as $step)
                        <div class="rounded-xl border border-grayscale-30 p-4 bg-white flex items-start gap-4 shadow-2xs">
                            <span class="size-7 rounded-full bg-brand-yellow font-bold text-ink flex items-center justify-center shrink-0 text-xs shadow-2xs">
                                {{ $step['number'] }}
                            </span>
                            <div class="text-body-sm">
                                <h3 class="font-bold text-ink">{{ $step['title'] }}</h3>
                                <p class="mt-1 text-body-muted leading-relaxed">{{ $step['body'] }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-body-sm text-body-muted">No method steps have been published for this recipe yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Ingredients Section: Shop the Recipe (Figma 07.02) -->
    <section class="mt-16 md:mt-24 border-t border-grayscale-20 pt-12 md:pt-16">
        <div>
            <h2 class="text-h2-mobile md:text-h2 font-bold text-ink">Ingredients</h2>
            <p class="mt-1 text-body-sm text-body-muted">
                Get everything you need to make {{ $recipe->title }}. Add individual items or add all to your basket.
            </p>
        </div>

        @php
            $ingredientLines = $resolved->lines->filter(fn ($l) => $l->variation && ! $l->ingredient->is_pantry_staple)->values();
        @endphp

        <!-- Ingredients Cards: Mobile horizontal swipe carousel, Desktop multi-column grid -->
        <div class="mt-8 flex lg:grid lg:grid-cols-6 gap-4 overflow-x-auto lg:overflow-visible pb-4 -mx-4 px-4 lg:mx-0 lg:px-0 snap-x [scrollbar-width:none]">
            @foreach ($ingredientLines as $index => $line)
                @php
                    $variation = $line->variation;
                    $product = $variation?->product;
                @endphp
                <article class="flex flex-col justify-between rounded-xl border border-grayscale-30 bg-surface p-3.5 shrink-0 w-[170px] sm:w-[190px] lg:w-auto snap-start relative group shadow-2xs hover:shadow-xs transition-shadow">
                    <!-- Top Badge / Spacer -->
                    <div class="flex items-center justify-between mb-2 min-h-[20px]">
                        @if ($index === 0)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-brand-yellow-20 text-ink">
                                Trending
                            </span>
                        @elseif ($index === 2)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-sale-10 text-price-50">
                                #4 Bestseller
                            </span>
                        @else
                            <span></span>
                        @endif

                        <button type="button" aria-label="Save to wishlist" class="text-grayscale-60 hover:text-price-50 transition-colors">
                            <x-store.icon name="heart" class="size-4" />
                        </button>
                    </div>

                    <!-- Product Image -->
                    <a href="{{ $product ? route('product', $product) : '#' }}" class="block aspect-square w-full rounded-lg overflow-hidden bg-grayscale-0 mb-3">
                        @if ($variation->image || $product?->image)
                            <img src="{{ asset($variation->image ?: $product->image) }}" alt="{{ $product?->name ?? $line->ingredient->name }}"
                                 class="size-full object-contain p-2 group-hover:scale-105 transition-transform duration-300">
                        @else
                            <div class="size-full flex items-center justify-center text-body-muted text-xs">No image</div>
                        @endif
                    </a>

                    <!-- Title & Details -->
                    <div class="space-y-1">
                        <a href="{{ $product ? route('product', $product) : '#' }}" class="block text-xs font-bold text-ink truncate hover:underline" title="{{ $product?->name ?? $line->ingredient->name }}">
                            {{ $product?->name ?? $line->ingredient->name }}
                        </a>
                        <p class="text-[11px] text-body-muted truncate">{{ $variation->name ?: '1 pack' }}</p>

                        <!-- Price & Add Button Row (Figma 07.02) -->
                        <div class="mt-3 pt-1 flex items-end justify-between gap-2 border-t border-grayscale-20">
                            <div>
                                <div class="flex items-baseline gap-1.5">
                                    <span class="text-sm font-bold text-ink">{{ money($line->unitPriceCents) }}</span>
                                    @if ($variation->compare_at_price_cents && $variation->compare_at_price_cents > $line->unitPriceCents)
                                        <span class="text-[11px] text-grayscale-60 line-through">{{ money($variation->compare_at_price_cents) }}</span>
                                    @endif
                                </div>
                                <p class="text-[10px] font-bold text-stock-50 uppercase flex items-center gap-1 pt-0.5">
                                    <span class="size-1.5 rounded-full bg-stock-50"></span>
                                    In Stock
                                </p>
                            </div>

                            <button type="button" wire:click="addSingleVariation({{ $variation->id }})"
                                    aria-label="Add {{ $product?->name ?? $line->ingredient->name }} to cart"
                                    class="size-8 rounded-lg border border-grayscale-30 bg-white flex items-center justify-center text-ink hover:bg-ink hover:text-white transition-colors shrink-0 shadow-2xs">
                                <x-store.icon name="cart" class="size-4" />
                            </button>
                        </div>
                    </div>
                </article>
            @endforeach

            <!-- Add all ingredients card (Figma 07.02) -->
            @if ($ingredientLines->isNotEmpty())
                <div wire:click="addIngredientsToCart"
                     class="flex flex-col items-center justify-center rounded-xl border border-grayscale-30 bg-grayscale-10 p-5 shrink-0 w-[170px] sm:w-[190px] lg:w-auto snap-start text-center cursor-pointer hover:bg-grayscale-20 hover:border-ink/40 transition-all group">
                    <p class="text-xs sm:text-sm font-bold text-ink leading-snug">
                        Add all {{ $ingredientLines->count() }} ingredients
                    </p>
                    <span class="text-2xl font-bold text-ink mt-2 group-hover:scale-110 transition-transform leading-none">
                        &plus;
                    </span>
                    <p class="mt-2 text-[11px] font-semibold text-price-50">
                        {{ money($resolved->totalCents(true)) }}
                    </p>
                </div>
            @endif
        </div>
    </section>

    <!-- Cook Next Section (Figma 07.02) -->
    @if ($cookNext->isNotEmpty())
        <section class="mt-16 md:mt-24 border-t border-grayscale-20 pt-12 md:pt-16">
            <div>
                <h2 class="text-h2-mobile md:text-h2 font-bold text-ink">Cook Next</h2>
                <p class="mt-1 text-body-sm text-body-muted">
                    More delicious recipes to try from our kitchen.
                </p>
            </div>

            <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($cookNext as $nextRecipe)
                    <article class="flex flex-col rounded-2xl overflow-hidden border border-grayscale-30 bg-surface shadow-2xs hover:shadow-md transition-all group">
                        <a href="{{ route('kitchen.recipe', $nextRecipe) }}" class="relative block h-[250px] sm:h-[270px] bg-grayscale-90 overflow-hidden">
                            @if ($nextRecipe->image || $nextRecipe->image_url)
                                <img src="{{ asset($nextRecipe->image ?: $nextRecipe->image_url) }}" alt="{{ $nextRecipe->title }}"
                                     class="size-full object-cover group-hover:scale-105 transition-transform duration-500">
                            @else
                                <div class="size-full flex items-center justify-center bg-grayscale-80 text-white font-bold text-xl">
                                    {{ $nextRecipe->title }}
                                </div>
                            @endif
                            <!-- Subtle bottom-only gradient -->
                            <div class="absolute inset-x-0 bottom-0 h-44 bg-gradient-to-t from-black/90 via-black/45 to-transparent pointer-events-none"></div>

                            <!-- Top Meta Badges -->
                            <div class="absolute top-3.5 left-3.5 flex items-center gap-1.5 text-[11px] font-semibold text-white">
                                @if ($nextRecipe->durationLabel())
                                    <span class="flex items-center gap-1 px-2.5 py-1 rounded-full bg-black/40 backdrop-blur-xs">
                                        <x-store.icon name="clock" class="size-3" />
                                        {{ $nextRecipe->durationLabel() }}
                                    </span>
                                @endif
                                <span class="flex items-center gap-1 px-2.5 py-1 rounded-full bg-black/40 backdrop-blur-xs">
                                    <x-store.icon name="user" class="size-3" />
                                    {{ $nextRecipe->base_servings }} &ndash; {{ $nextRecipe->base_servings + 2 }}
                                </span>
                                @if ($nextRecipe->country)
                                    <span class="flex items-center gap-1 px-2.5 py-1 rounded-full bg-black/40 backdrop-blur-xs">
                                        <x-store.icon name="location" class="size-3" />
                                        {{ $nextRecipe->country->name }}
                                    </span>
                                @endif
                            </div>

                            <!-- Bottom Title & Tagline -->
                            <div class="absolute bottom-3.5 inset-x-4 text-white z-10">
                                <h3 class="text-[22px] font-bold tracking-tight leading-tight">
                                    {{ $nextRecipe->title }}
                                </h3>
                                @if ($nextRecipe->intro || $nextRecipe->tagline)
                                    <p class="mt-1 text-xs text-white/90 line-clamp-1">
                                        {{ $nextRecipe->intro ?: $nextRecipe->tagline }}
                                    </p>
                                @endif
                                <div class="mt-2.5">
                                    <span class="inline-flex items-center rounded-full border border-white/80 px-3 py-0.5 text-[11px] font-semibold text-white group-hover:bg-white group-hover:text-ink transition-colors">
                                        Cook this
                                    </span>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('kitchen.recipe', $nextRecipe) }}"
                           class="flex h-12 items-center justify-center bg-grayscale-10 border-t border-grayscale-30 text-[13px] font-bold text-ink hover:bg-grayscale-20 hover:text-brand-yellow-80 transition-colors">
                            View Ingredient Recipe
                        </a>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</div>
