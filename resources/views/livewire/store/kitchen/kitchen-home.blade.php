<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-8 sm:space-y-12">
    <!-- Hero Banner -->
    <div class="relative rounded-3xl overflow-hidden bg-[#111111] text-white p-6 sm:p-10 lg:p-12 shadow-xl border border-white/10">
        <div class="relative z-10 max-w-2xl space-y-4">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider bg-[#DF2600] text-white">
                Ìsalèè Èkó Kitchen
            </span>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-white leading-tight">
                Authentic Heritage Recipes &amp; Ingredient Kits
            </h1>
            <p class="text-sm sm:text-base text-white/80 leading-relaxed">
                Step-by-step culinary guides for rich West African and Caribbean dishes. Scale servings dynamically and order perfectly portioned shelf ingredients with a single click.
            </p>

            <div class="pt-2 flex flex-wrap items-center gap-3">
                <a href="{{ route('kitchen.pantry') }}"
                   style="background-color: #DF2600;"
                   class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl text-white font-extrabold text-sm hover:opacity-95 transition-opacity shadow-md">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Cook With What I Have (Pantry Matcher)
                </a>
            </div>
        </div>

        <div class="absolute right-0 bottom-0 top-0 w-1/3 opacity-20 lg:opacity-30 pointer-events-none bg-linear-to-l from-[#DF2600] to-transparent"></div>
    </div>

    <!-- Cuisines & Search Bar -->
    <div class="flex flex-col md:flex-row items-center justify-between gap-4">
        <!-- Cuisine Filter Pills -->
        <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-2 md:pb-0 [scrollbar-width:none]">
            <button type="button"
                    wire:click="setCuisine('')"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-extrabold transition-all shrink-0 {{ $cuisine === '' ? 'bg-[#111111] text-white shadow-xs' : 'bg-white border border-[#EEEEEE] text-[#686868] hover:text-[#111111]' }}">
                All Cuisines
            </button>
            @foreach ($cuisines as $cKey => $cLabel)
                <button type="button"
                        wire:click="setCuisine('{{ $cKey }}')"
                        class="px-4 py-2 rounded-xl text-xs sm:text-sm font-extrabold transition-all shrink-0 {{ $cuisine === $cKey ? 'bg-[#DF2600] text-white shadow-xs' : 'bg-white border border-[#EEEEEE] text-[#686868] hover:text-[#111111]' }}">
                    {{ $cLabel }}
                </button>
            @endforeach
        </div>

        <!-- Search Input -->
        <div class="w-full md:w-72 relative">
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Search dishes or flavours..."
                   class="w-full pl-9 pr-3 py-2 rounded-xl border border-[#EEEEEE] bg-white text-xs text-[#111111] placeholder-[#686868] focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
            <svg class="w-4 h-4 text-[#686868] absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            @if ($search)
                <button type="button" wire:click="$set('search', '')" class="absolute right-3 top-2.5 text-xs text-[#686868] hover:text-[#111111]">Clear</button>
            @endif
        </div>
    </div>

    <!-- Recipes Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($recipes as $recipe)
            @php
                $kitCost = $costPreviews[$recipe->id] ?? 0;
            @endphp
            <div class="bg-white rounded-3xl border border-[#EEEEEE] shadow-xs overflow-hidden flex flex-col justify-between hover:shadow-md hover:border-[#DF2600]/30 transition-all group">
                <div>
                    <!-- Photo Header -->
                    <a href="{{ route('kitchen.recipe', $recipe) }}" class="relative block h-52 bg-[#242424] overflow-hidden">
                        @if ($recipe->image)
                            <img src="{{ asset($recipe->image) }}" alt="{{ $recipe->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        @elseif ($recipe->thumbnail)
                            <img src="{{ asset($recipe->thumbnail) }}" alt="{{ $recipe->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-linear-to-br from-[#DF2600] to-[#111111] text-white font-extrabold text-2xl">
                                {{ $recipe->title }}
                            </div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>

                        <!-- Top Badges -->
                        <div class="absolute top-3 inset-x-3 flex items-center justify-between">
                            <span class="px-2.5 py-1 rounded-lg bg-black/60 backdrop-blur-xs text-white text-[11px] font-bold">
                                {{ $cuisines[$recipe->cuisine] ?? ucfirst($recipe->cuisine) }}
                            </span>

                            @if ($recipe->durationLabel())
                                <span class="px-2.5 py-1 rounded-lg bg-white/20 backdrop-blur-xs text-white text-[11px] font-semibold">
                                    ⏱ {{ $recipe->durationLabel() }}
                                </span>
                            @endif
                        </div>

                        <!-- Bottom Title -->
                        <div class="absolute bottom-3 inset-x-3 text-white">
                            <h3 class="text-lg font-extrabold tracking-tight truncate group-hover:text-white transition-colors">
                                {{ $recipe->title }}
                            </h3>
                            @if ($recipe->country)
                                <span class="text-xs text-white/80 font-medium block">
                                    {{ $recipe->country->name }} Tradition
                                </span>
                            @endif
                        </div>
                    </a>

                    <!-- Card Body -->
                    <div class="p-5 space-y-3">
                        @if ($recipe->intro)
                            <p class="text-xs sm:text-sm text-[#686868] line-clamp-2 leading-relaxed">
                                {{ $recipe->intro }}
                            </p>
                        @endif

                        <div class="flex items-center justify-between pt-2 border-t border-[#EEEEEE] text-xs">
                            <span class="text-[#686868]">
                                Serves <strong class="text-[#111111]">{{ $recipe->base_servings }} people</strong>
                            </span>
                            <span class="text-[#686868]">
                                <strong class="text-[#111111]">{{ $recipe->ingredients->count() }}</strong> ingredients
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Footer & CTA -->
                <div class="p-4 sm:p-5 bg-[#FAFAFA] border-t border-[#EEEEEE] flex items-center justify-between gap-3">
                    <div>
                        <span class="block text-[10px] uppercase font-bold text-[#686868]">Kit (4 People)</span>
                        <span class="block text-sm sm:text-base font-extrabold text-[#007D49]">
                            {{ $kitCost > 0 ? money($kitCost) : 'Check Kit' }}
                        </span>
                    </div>

                    <a href="{{ route('kitchen.recipe', $recipe) }}"
                       style="background-color: #DF2600;"
                       class="px-4 py-2 rounded-xl text-white text-xs font-extrabold hover:opacity-90 transition-opacity shadow-xs inline-flex items-center gap-1">
                        View Recipe
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 px-6 text-center bg-white rounded-3xl border border-[#EEEEEE]">
                <div class="max-w-sm mx-auto space-y-3">
                    <div class="w-12 h-12 mx-auto rounded-full bg-[#FAFAFA] border border-[#EEEEEE] flex items-center justify-center text-[#686868]">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h3 class="text-base font-extrabold text-[#111111]">No recipes found</h3>
                    <p class="text-xs text-[#686868]">Try selecting another cuisine or clearing your search query.</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Curated Pairings (Meal Combos) -->
    @if ($combos->isNotEmpty())
        <div class="pt-8 sm:pt-12 border-t border-[#EEEEEE] space-y-6">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-[#DF2600]">Curated Pairings</span>
                <h2 class="text-xl sm:text-2xl font-extrabold text-[#111111] tracking-tight">Authentic Meal Combinations</h2>
                <p class="text-xs sm:text-sm text-[#686868]">Popular complementary dishes often served together for Sunday gatherings and feasts.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach ($combos as $combo)
                    <div class="p-5 sm:p-6 rounded-3xl bg-white border border-[#EEEEEE] shadow-xs space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base sm:text-lg font-extrabold text-[#111111]">{{ $combo->name }}</h3>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-[#DAF4E7] text-[#007D49]">Combo</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            @foreach ($combo->recipes as $r)
                                <a href="{{ route('kitchen.recipe', $r) }}" class="group block p-3 rounded-2xl bg-[#FAFAFA] border border-[#EEEEEE] hover:border-[#DF2600]/30 transition-all">
                                    <div class="h-24 rounded-xl overflow-hidden bg-[#242424] mb-2">
                                        @if ($r->image)
                                            <img src="{{ asset($r->image) }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                        @endif
                                    </div>
                                    <span class="text-xs font-bold text-[#111111] line-clamp-1 group-hover:text-[#DF2600]">{{ $r->title }}</span>
                                    <span class="text-[10px] text-[#686868] block">{{ $r->durationLabel() }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
