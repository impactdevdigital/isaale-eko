<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10 space-y-8 sm:space-y-10">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-[#686868]">
        <a href="{{ route('home') }}" class="hover:text-[#111111]">Home</a>
        <span>/</span>
        <a href="{{ route('kitchen.index') }}" class="hover:text-[#111111]">Kitchen &amp; Recipes</a>
        <span>/</span>
        <span class="text-[#111111] font-bold truncate">{{ $recipe->title }}</span>
    </nav>

    <!-- Flash notification -->
    @if (session()->has('cart_success'))
        <div class="p-4 bg-[#DAF4E7] border border-[#007D49]/20 rounded-2xl flex items-center justify-between text-[#007D49] text-xs sm:text-sm font-semibold">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('cart_success') }}</span>
            </div>
            <a href="{{ route('cart') }}" class="underline font-extrabold hover:opacity-80">View Basket &rarr;</a>
        </div>
    @endif
    @if (session()->has('cart_warning'))
        <div class="p-4 bg-[#FFF8E6] border border-[#B76E00]/20 rounded-2xl flex items-center justify-between text-[#B76E00] text-xs sm:text-sm font-semibold">
            <span>{{ session('cart_warning') }}</span>
        </div>
    @endif

    <!-- Recipe Hero Showcase Card -->
    <div class="bg-white rounded-3xl border border-[#EEEEEE] shadow-xs overflow-hidden grid grid-cols-1 lg:grid-cols-12">
        <!-- Photo -->
        <div class="lg:col-span-5 relative h-64 sm:h-80 lg:h-full min-h-[300px] bg-[#242424]">
            @if ($recipe->image)
                <img src="{{ asset($recipe->image) }}" alt="{{ $recipe->title }}" class="w-full h-full object-cover">
            @elseif ($recipe->thumbnail)
                <img src="{{ asset($recipe->thumbnail) }}" alt="{{ $recipe->title }}" class="w-full h-full object-cover">
            @else
                <div class="w-full h-full flex items-center justify-center bg-linear-to-br from-[#DF2600] to-[#111111] text-white font-extrabold text-3xl">
                    {{ $recipe->title }}
                </div>
            @endif
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent lg:hidden"></div>
        </div>

        <!-- Recipe Profile -->
        <div class="lg:col-span-7 p-6 sm:p-8 lg:p-10 flex flex-col justify-between space-y-6">
            <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider bg-[#DF2600] text-white">
                        {{ $cuisines[$recipe->cuisine] ?? ucfirst($recipe->cuisine) }}
                    </span>
                    @if ($recipe->country)
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-[#FAFAFA] border border-[#EEEEEE] text-[#111111]">
                            {{ $recipe->country->name }} Tradition
                        </span>
                    @endif
                    @if ($recipe->durationLabel())
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-[#FAFAFA] border border-[#EEEEEE] text-[#686868]">
                            ⏱ {{ $recipe->durationLabel() }}
                        </span>
                    @endif
                </div>

                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight text-[#111111]">
                    {{ $recipe->title }}
                </h1>

                @if ($recipe->intro)
                    <p class="text-sm sm:text-base text-[#686868] leading-relaxed">
                        {{ $recipe->intro }}
                    </p>
                @endif

                @if ($recipe->description)
                    <div class="text-xs sm:text-sm text-[#444444] leading-relaxed pt-2 border-t border-[#EEEEEE] whitespace-pre-line">
                        {{ $recipe->description }}
                    </div>
                @endif
            </div>

            <!-- Servings Selector & Kit Estimate Card -->
            <div class="p-4 sm:p-5 rounded-2xl bg-[#FAFAFA] border border-[#EEEEEE] flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <span class="block text-xs font-bold uppercase tracking-wider text-[#686868]">Portion &amp; Servings</span>
                    <span class="text-xs text-[#686868]">Dynamically scales ingredient pack quantities.</span>
                </div>

                <!-- Stepper -->
                <div class="flex items-center gap-2">
                    <button type="button"
                            wire:click="decrementPeople"
                            class="w-10 h-10 rounded-xl bg-white border border-[#EEEEEE] font-extrabold text-lg flex items-center justify-center hover:bg-[#F6F6F6] transition-colors shadow-2xs">
                        -
                    </button>
                    <div class="px-4 py-2 rounded-xl bg-white border border-[#EEEEEE] text-center min-w-[110px] shadow-2xs">
                        <span class="text-sm font-extrabold text-[#111111] block">{{ $people }} Servings</span>
                        <span class="text-[10px] text-[#686868] block">({{ $people }} people)</span>
                    </div>
                    <button type="button"
                            wire:click="incrementPeople"
                            class="w-10 h-10 rounded-xl bg-white border border-[#EEEEEE] font-extrabold text-lg flex items-center justify-center hover:bg-[#F6F6F6] transition-colors shadow-2xs">
                        +
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Two-Column Layout: Ingredients Checklist vs Cooking Steps -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Left Column: Interactive Ingredients Kit (7 cols) -->
        <div class="lg:col-span-7 space-y-6">
            <div class="bg-white rounded-3xl border border-[#EEEEEE] shadow-xs p-6 sm:p-8 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-4 border-b border-[#EEEEEE]">
                    <div>
                        <h2 class="text-lg sm:text-xl font-extrabold text-[#111111]">
                            Recipe Ingredients for {{ $people }} People
                        </h2>
                        <p class="text-xs text-[#686868]">
                            Uncheck items you already have in your kitchen cupboards.
                        </p>
                    </div>

                    <div class="text-right">
                        @php
                            $selectedSubtotalCents = $resolved->lines
                                ->filter(fn ($l) => in_array($l->ingredient->id, $selectedIngredientIds, true) && $l->available())
                                ->sum(fn ($l) => $l->totalCents());
                        @endphp
                        <span class="block text-[10px] uppercase font-bold text-[#686868]">Selected Kit Total</span>
                        <span class="block text-xl font-extrabold text-[#007D49]">{{ money($selectedSubtotalCents) }}</span>
                    </div>
                </div>

                <!-- Lines Checklist -->
                <div class="space-y-3">
                    @forelse ($resolved->lines as $line)
                        @php
                            $isSelected = in_array($line->ingredient->id, $selectedIngredientIds, true);
                            $isAvailable = $line->available();
                        @endphp
                        <div class="p-3.5 sm:p-4 rounded-2xl border transition-all flex items-center justify-between gap-3 {{ $isSelected ? 'border-[#DF2600]/30 bg-[#FFF0EC]/20' : 'border-[#EEEEEE] bg-[#FAFAFA]' }}">
                            <div class="flex items-center gap-3 min-w-0">
                                <input type="checkbox"
                                       wire:click="toggleIngredient({{ $line->ingredient->id }})"
                                       {{ $isSelected ? 'checked' : '' }}
                                       class="w-4 h-4 rounded text-[#DF2600] focus:ring-[#DF2600] cursor-pointer shrink-0">

                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="text-xs sm:text-sm font-extrabold text-[#111111] truncate">
                                            {{ $line->ingredient->name }}
                                        </h4>
                                        @if ($line->role === 'optional')
                                            <span class="text-[9px] px-2 py-0.5 rounded-full bg-[#EEEEEE] text-[#686868] font-bold">
                                                Optional
                                            </span>
                                        @endif
                                    </div>

                                    @if ($isAvailable)
                                        <span class="text-xs text-[#686868] block mt-0.5">
                                            {{ $line->variation->product?->name }} ({{ $line->variation->name ?: 'Standard' }})
                                            &bull;
                                            <strong>{{ $line->quantity }} pack{{ $line->quantity > 1 ? 's' : '' }}</strong>
                                        </span>
                                    @else
                                        <span class="text-xs text-[#DF2600] font-bold block mt-0.5">
                                            Currently out of stock on shelf
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                @if ($isAvailable)
                                    <span class="text-xs sm:text-sm font-extrabold text-[#111111] block">
                                        {{ money($line->totalCents()) }}
                                    </span>
                                    <span class="text-[10px] text-[#686868] block">
                                        {{ money($line->variation->price_cents) }} each
                                    </span>
                                @else
                                    <span class="text-xs text-[#686868] italic">—</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-[#686868] italic">No buyable ingredients required for this dish.</p>
                    @endforelse
                </div>

                <!-- Pantry Staples Note -->
                @php
                    $staples = $recipe->ingredients->filter(fn ($ri) => $ri->ingredient && $ri->ingredient->is_pantry_staple);
                @endphp
                @if ($staples->isNotEmpty())
                    <div class="p-4 rounded-2xl bg-[#FAFAFA] border border-[#EEEEEE] space-y-1.5">
                        <span class="text-xs font-bold text-[#111111] flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-[#007D49]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            Pantry Staples (Assumed at home, never billed)
                        </span>
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            @foreach ($staples as $st)
                                <span class="px-2.5 py-1 rounded-lg bg-white border border-[#EEEEEE] text-[11px] font-medium text-[#686868]">
                                    {{ $st->ingredient->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- One-Click Add to Cart CTA -->
                <div class="pt-4 border-t border-[#EEEEEE] space-y-3">
                    <button type="button"
                            wire:click="addIngredientsToCart"
                            style="background-color: #DF2600;"
                            class="w-full py-3.5 px-6 rounded-2xl text-white font-extrabold text-sm sm:text-base hover:opacity-95 transition-opacity shadow-md flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        Add Selected Ingredients to Basket ({{ money($selectedSubtotalCents) }})
                    </button>

                    <p class="text-[11px] text-[#686868] text-center leading-relaxed">
                        Pack counts and estimated totals are calculated based on our live shelf stock. Prices reflect the portioned product packs needed for {{ $people }} people. Free pantry staples (water, standard salt) are never billed.
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Column: Step-by-Step Cooking Guide (5 cols) -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white rounded-3xl border border-[#EEEEEE] shadow-xs p-6 sm:p-8 space-y-6">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-[#DF2600]">Method</span>
                    <h2 class="text-lg sm:text-xl font-extrabold text-[#111111]">Cooking Instructions</h2>
                    <p class="text-xs text-[#686868]">Follow these authentic steps for the best flavour.</p>
                </div>

                <div class="space-y-4">
                    @forelse ($stepLines as $idx => $step)
                        <div class="flex items-start gap-3 p-3.5 rounded-2xl bg-[#FAFAFA] border border-[#EEEEEE]">
                            <span class="w-6 h-6 rounded-full bg-[#111111] text-white text-xs font-extrabold flex items-center justify-center shrink-0 mt-0.5">
                                {{ $idx + 1 }}
                            </span>
                            <p class="text-xs sm:text-sm text-[#111111] leading-relaxed">
                                {{ ltrim($step, '0123456789. ') }}
                            </p>
                        </div>
                    @empty
                        <p class="text-xs text-[#686868] italic">Detailed cooking steps are being curated by our kitchen staff.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
