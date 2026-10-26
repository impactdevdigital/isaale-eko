<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-8 sm:space-y-12">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-[#686868]">
        <a href="{{ route('home') }}" class="hover:text-[#111111]">Home</a>
        <span>/</span>
        <a href="{{ route('kitchen.index') }}" class="hover:text-[#111111]">Kitchen &amp; Recipes</a>
        <span>/</span>
        <span class="text-[#111111] font-bold">Cook With What I Have</span>
    </nav>

    <!-- Flash message -->
    @if (session()->has('match_success'))
        <div class="p-4 bg-[#DAF4E7] border border-[#007D49]/20 rounded-2xl flex items-center justify-between text-[#007D49] text-xs sm:text-sm font-semibold">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('match_success') }}</span>
            </div>
            <a href="{{ route('cart') }}" class="underline font-extrabold hover:opacity-80">View Basket &rarr;</a>
        </div>
    @endif
    @if (session()->has('match_info'))
        <div class="p-4 bg-[#FFF8E6] border border-[#B76E00]/20 rounded-2xl text-[#B76E00] text-xs sm:text-sm font-semibold">
            {{ session('match_info') }}
        </div>
    @endif

    <!-- Header Section -->
    <div class="max-w-3xl space-y-3">
        <span class="px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider bg-[#DF2600] text-white inline-block">
            Pantry Matcher
        </span>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-[#111111]">
            Cook With What You Have at Home
        </h1>
        <p class="text-sm sm:text-base text-[#686868] leading-relaxed">
            Select the ingredients currently in your kitchen cupboards or freezer. We'll show you authentic dishes you can prepare right now and calculate only the exact missing items you need to order.
        </p>
    </div>

    <!-- Interactive Pantry Builder Card -->
    <div class="p-6 sm:p-8 rounded-3xl bg-white border border-[#EEEEEE] shadow-xs space-y-6">
        <!-- Quick Common Staples Toggles -->
        <div class="space-y-2">
            <span class="text-xs font-bold uppercase tracking-wider text-[#686868] block">
                Quick Select Common Kitchen Ingredients
            </span>
            <div class="flex flex-wrap gap-2">
                @foreach ($quickPantry as $slug => $label)
                    @php
                        $isSelected = $selectedIngredients->contains('slug', $slug);
                    @endphp
                    <button type="button"
                            wire:click="toggleQuick('{{ $slug }}')"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 {{ $isSelected ? 'bg-[#111111] text-white shadow-xs' : 'bg-[#FAFAFA] border border-[#EEEEEE] text-[#111111] hover:border-[#111111]' }}">
                        @if ($isSelected)
                            <svg class="w-3.5 h-3.5 text-[#007D49]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                        @else
                            <span class="text-[#686868]">+</span>
                        @endif
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Search Bar for Additional Ingredients -->
        <div class="relative">
            <label class="block text-xs font-bold text-[#111111] mb-1.5">
                Search or Type Any Other Ingredient
            </label>
            <div class="relative">
                <input type="text"
                       wire:model.live.debounce.250ms="search"
                       placeholder="e.g. Stockfish, Zobo Leaves, Ginger, Hibiscus..."
                       class="w-full pl-10 pr-4 py-3 rounded-2xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] placeholder-[#686868] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                <svg class="w-4 h-4 text-[#686868] absolute left-3.5 top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            @if ($searchResults->isNotEmpty())
                <div class="absolute left-0 right-0 top-full mt-1 bg-white border border-[#EEEEEE] rounded-2xl shadow-xl z-30 max-h-56 overflow-y-auto divide-y divide-[#EEEEEE]">
                    @foreach ($searchResults as $res)
                        <div class="p-3 hover:bg-[#FAFAFA] flex items-center justify-between gap-2">
                            <div>
                                <span class="text-xs font-bold text-[#111111] block">{{ $res->name }}</span>
                                @if ($res->alt_names)
                                    <span class="text-[10px] text-[#686868] block">{{ $res->alt_names }}</span>
                                @endif
                            </div>
                            <button type="button"
                                    wire:click="addIngredient({{ $res->id }})"
                                    style="background-color: #007D49;"
                                    class="px-3 py-1 rounded-xl text-white text-xs font-extrabold hover:opacity-90 shrink-0">
                                + Add
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Selected Ingredients Tray -->
        @if ($selectedIngredients->isNotEmpty())
            <div class="pt-4 border-t border-[#EEEEEE] space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-[#111111]">
                        Your Selected Pantry ({{ $selectedIngredients->count() }})
                    </span>
                    <button type="button" wire:click="clearAll" class="text-xs text-[#DF2600] font-bold hover:underline">
                        Clear all
                    </button>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach ($selectedIngredients as $ing)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#FFF0EC] text-[#DF2600] text-xs font-bold border border-[#DF2600]/20">
                            {{ $ing->name }}
                            <button type="button"
                                    wire:click="removeIngredient({{ $ing->id }})"
                                    class="hover:text-[#111111] ml-0.5">
                                &times;
                            </button>
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Matched Recipes Section -->
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-xl sm:text-2xl font-extrabold text-[#111111]">
                    Matching Authentic Dishes ({{ $matches->count() }})
                </h2>
                <p class="text-xs text-[#686868]">Ranked by how many ingredients you already have.</p>
            </div>

            <!-- Servings Scaler -->
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-[#686868]">Scale for:</span>
                <select wire:model.live="people" class="px-3 py-1.5 rounded-xl border border-[#EEEEEE] bg-white text-xs font-bold text-[#111111]">
                    @for ($p = 2; $p <= 12; $p += 2)
                        <option value="{{ $p }}">{{ $p }} People</option>
                    @endfor
                </select>
            </div>
        </div>

        @if ($matches->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach ($matches as $match)
                    @php
                        $r = $match['recipe'];
                        $pct = $match['coverage_percent'];
                        $needCount = count($match['need']);
                        $haveCount = count($match['have']);
                    @endphp
                    <div class="p-6 rounded-3xl bg-white border border-[#EEEEEE] shadow-xs flex flex-col justify-between space-y-5 hover:border-[#DF2600]/30 transition-colors">
                        <div class="space-y-4">
                            <!-- Recipe Card Header -->
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-16 h-16 rounded-2xl overflow-hidden bg-[#242424] shrink-0">
                                        @if ($r->image)
                                            <img src="{{ asset($r->image) }}" alt="{{ $r->title }}" class="w-full h-full object-cover">
                                        @endif
                                    </div>
                                    <div>
                                        <h3 class="text-base sm:text-lg font-extrabold text-[#111111] line-clamp-1">
                                            {{ $r->title }}
                                        </h3>
                                        <div class="flex items-center gap-2 text-xs text-[#686868] mt-0.5">
                                            <span>{{ $cuisines[$r->cuisine] ?? ucfirst($r->cuisine) }}</span>
                                            <span>&bull;</span>
                                            <span>⏱ {{ $r->durationLabel() }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Match Score Badge -->
                                <span class="px-3 py-1 rounded-full text-xs font-extrabold shrink-0 {{ $pct >= 80 ? 'bg-[#DAF4E7] text-[#007D49]' : ($pct >= 50 ? 'bg-[#FFF8E6] text-[#B76E00]' : 'bg-[#EEEEEE] text-[#686868]') }}">
                                    {{ $pct }}% Match
                                </span>
                            </div>

                            <!-- "You Have" vs "You Need" Breakdown -->
                            <div class="space-y-2.5 text-xs">
                                <!-- You Have -->
                                <div class="p-3 rounded-2xl bg-[#DAF4E7]/30 border border-[#007D49]/20 space-y-1">
                                    <div class="flex items-center justify-between font-bold text-[#007D49]">
                                        <span class="flex items-center gap-1.5">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                            In Your Pantry ({{ $haveCount }})
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap gap-1 text-[11px] text-[#007D49]">
                                        @foreach ($match['have'] as $h)
                                            <span class="px-2 py-0.5 rounded-md bg-white/70 border border-[#007D49]/10">
                                                {{ $h->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- You Still Need -->
                                @if ($needCount > 0)
                                    <div class="p-3 rounded-2xl bg-[#FFF0EC]/40 border border-[#DF2600]/20 space-y-1">
                                        <div class="flex items-center justify-between font-bold text-[#DF2600]">
                                            <span>Still Need to Order ({{ $needCount }})</span>
                                            <span class="font-extrabold text-[#111111]">
                                                {{ money($match['cost_to_complete_cents']) }}
                                            </span>
                                        </div>
                                        <div class="flex flex-wrap gap-1 text-[11px] text-[#DF2600]">
                                            @foreach ($match['need'] as $n)
                                                <span class="px-2 py-0.5 rounded-md bg-white border border-[#DF2600]/10">
                                                    {{ $n->ingredient->name }} ({{ money($n->totalCents()) }})
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div class="p-3 rounded-2xl bg-[#DAF4E7] text-[#007D49] font-extrabold text-center">
                                        You have 100% of the required ingredients ready to cook!
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Card Action Buttons -->
                        <div class="pt-3 border-t border-[#EEEEEE] flex items-center justify-between gap-3">
                            <a href="{{ route('kitchen.recipe', $r) }}"
                               class="text-xs font-extrabold text-[#111111] hover:underline">
                                View Recipe &rarr;
                            </a>

                            @if ($needCount > 0)
                                <button type="button"
                                        wire:click="addMissingToCart({{ $r->id }})"
                                        style="background-color: #DF2600;"
                                        class="px-4 py-2 rounded-xl text-white text-xs font-extrabold hover:opacity-90 shadow-xs flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                    Add Missing Items ({{ money($match['cost_to_complete_cents']) }})
                                </button>
                            @else
                                <a href="{{ route('kitchen.recipe', $r) }}"
                                   style="background-color: #007D49;"
                                   class="px-4 py-2 rounded-xl text-white text-xs font-extrabold hover:opacity-90 shadow-xs">
                                    Start Cooking!
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif (! empty($pantryIngredientIds))
            <div class="p-12 text-center bg-white rounded-3xl border border-[#EEEEEE] space-y-2">
                <h3 class="text-base font-extrabold text-[#111111]">No matching recipes found</h3>
                <p class="text-xs text-[#686868]">Try selecting common ingredients like Rice, Palm Oil, or Beef above to find recipes.</p>
            </div>
        @else
            <div class="p-12 text-center bg-white rounded-3xl border border-dashed border-[#EEEEEE] space-y-2">
                <h3 class="text-base font-extrabold text-[#111111]">Select ingredients from your kitchen above</h3>
                <p class="text-xs text-[#686868]">Click the ingredient chips or search to discover authentic dishes you can prepare right now.</p>
            </div>
        @endif
    </div>
</div>
