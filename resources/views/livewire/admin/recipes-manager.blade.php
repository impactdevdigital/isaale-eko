<div class="space-y-6 sm:space-y-8">
    <!-- Header with Tabs and CTAs -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-[#111111] tracking-tight">Kitchen Recipes &amp; Ingredients Engine</h2>
            <p class="mt-1 text-xs sm:text-sm text-[#686868]">
                Curate authentic African &amp; Caribbean recipes, link real store product variations to culinary ingredients, define substitutions, and enforce the culinary approval stage gate.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 sm:gap-3 shrink-0">
            @if ($tab === 'recipes')
                <button type="button"
                        wire:click="createRecipe"
                        style="background-color: #DF2600;"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-white text-xs sm:text-sm font-extrabold hover:opacity-90 transition-opacity shadow-xs">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    New Recipe
                </button>
            @elseif ($tab === 'ingredients')
                <button type="button"
                        wire:click="createIngredient"
                        style="background-color: #DF2600;"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-white text-xs sm:text-sm font-extrabold hover:opacity-90 transition-opacity shadow-xs">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    New Ingredient
                </button>
            @elseif ($tab === 'substitutions')
                <button type="button"
                        wire:click="createSubstitution"
                        style="background-color: #DF2600;"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-white text-xs sm:text-sm font-extrabold hover:opacity-90 transition-opacity shadow-xs">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    New Substitution
                </button>
            @endif
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="p-4 bg-[#DAF4E7] border border-[#007D49]/20 rounded-2xl flex items-center justify-between text-[#007D49] text-xs sm:text-sm font-semibold">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-xs font-bold opacity-70 hover:opacity-100">Dismiss</button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-4 bg-[#FFF0EC] border border-[#DF2600]/20 rounded-2xl flex items-center justify-between text-[#DF2600] text-xs sm:text-sm font-semibold">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-xs font-bold opacity-70 hover:opacity-100">Dismiss</button>
        </div>
    @endif

    <!-- Quick Stat KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="p-4 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-[#686868]">Total Recipes</span>
            <div class="mt-1 sm:mt-2 text-xl sm:text-2xl font-extrabold text-[#111111]">{{ number_format($counts['total_recipes']) }}</div>
            <div class="mt-0.5 flex items-center gap-2 text-[10px] sm:text-[11px]">
                <span class="text-[#007D49] font-bold">{{ $counts['approved_recipes'] }} Approved</span>
                <span class="text-[#686868]">•</span>
                <span class="text-[#DF2600] font-bold">{{ $counts['draft_recipes'] }} Draft</span>
            </div>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-[#007D49]">Culinary Ingredients</span>
            <div class="mt-1 sm:mt-2 text-xl sm:text-2xl font-extrabold text-[#007D49]">{{ number_format($counts['total_ingredients']) }}</div>
            <span class="text-[10px] sm:text-[11px] text-[#686868]">Concepts in database</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-[#0055D4]">Approved Swaps</span>
            <div class="mt-1 sm:mt-2 text-xl sm:text-2xl font-extrabold text-[#0055D4]">{{ number_format($counts['total_substitutions']) }}</div>
            <span class="text-[10px] sm:text-[11px] text-[#686868]">Substitutions linked</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-[#111111]">Active Cuisines</span>
            <div class="mt-1 sm:mt-2 text-xl sm:text-2xl font-extrabold text-[#111111]">{{ number_format($counts['cuisines']) }}</div>
            <span class="text-[10px] sm:text-[11px] text-[#686868]">Regions supported</span>
        </div>
    </div>

    <!-- Main Tab Navigation Bar -->
    <div class="flex items-center gap-2 border-b border-[#EEEEEE] pb-2 overflow-x-auto [scrollbar-width:none]">
        <button type="button"
                wire:click="setTab('recipes')"
                class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-extrabold transition-all flex items-center gap-2 shrink-0 {{ $tab === 'recipes' ? 'bg-[#111111] text-white shadow-xs' : 'text-[#686868] hover:text-[#111111] hover:bg-[#FAFAFA]' }}">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            Recipes &amp; Stage Gate ({{ $counts['total_recipes'] }})
        </button>

        <button type="button"
                wire:click="setTab('ingredients')"
                class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-extrabold transition-all flex items-center gap-2 shrink-0 {{ $tab === 'ingredients' ? 'bg-[#111111] text-white shadow-xs' : 'text-[#686868] hover:text-[#111111] hover:bg-[#FAFAFA]' }}">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            Ingredients &amp; Shelf Variations ({{ $counts['total_ingredients'] }})
        </button>

        <button type="button"
                wire:click="setTab('substitutions')"
                class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-extrabold transition-all flex items-center gap-2 shrink-0 {{ $tab === 'substitutions' ? 'bg-[#111111] text-white shadow-xs' : 'text-[#686868] hover:text-[#111111] hover:bg-[#FAFAFA]' }}">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
            Curated Substitutions ({{ $counts['total_substitutions'] }})
        </button>
    </div>

    <!-- TAB 1: RECIPES & STAGE GATE -->
    @if ($tab === 'recipes')
        <!-- Filters & Search Bar -->
        <div class="p-4 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs flex flex-col md:flex-row items-center gap-3">
            <div class="flex-1 w-full relative">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search recipes by title, ingredients, or flavour profile..."
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] placeholder-[#686868] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                <svg class="w-4 h-4 text-[#686868] absolute left-3.5 top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                @if ($search)
                    <button type="button" wire:click="$set('search', '')" class="absolute right-3.5 top-3.5 text-xs text-[#686868] hover:text-[#111111]">Clear</button>
                @endif
            </div>

            <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full md:w-auto">
                <select wire:model.live="cuisineFilter" class="w-full sm:w-auto px-3 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-xs font-semibold text-[#111111] focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20">
                    <option value="">All Cuisines</option>
                    @foreach ($cuisines as $cKey => $cLabel)
                        <option value="{{ $cKey }}">{{ $cLabel }}</option>
                    @endforeach
                </select>

                <select wire:model.live="statusFilter" class="w-full sm:w-auto px-3 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-xs font-semibold text-[#111111] focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20">
                    <option value="">All Statuses</option>
                    <option value="approved">Approved Live</option>
                    <option value="draft">Draft / Pending</option>
                </select>

                <select wire:model.live="countryFilter" class="w-full sm:w-auto px-3 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-xs font-semibold text-[#111111] focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20">
                    <option value="">All Countries</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country->id }}">{{ $country->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="featuredFilter" class="w-full sm:w-auto px-3 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-xs font-semibold text-[#111111] focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20">
                    <option value="">All Displays</option>
                    <option value="1">Featured Homepage</option>
                    <option value="0">Standard Library</option>
                </select>
            </div>
        </div>

        <!-- Recipes Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            @forelse ($recipes as $recipe)
                @php
                    $problems = $recipeProblems[$recipe->id] ?? [];
                    $costPreview = $recipeCostPreviews[$recipe->id] ?? 0;
                    $isApproved = $recipe->isApproved();
                @endphp
                <div class="bg-white rounded-2xl border border-[#EEEEEE] shadow-xs overflow-hidden flex flex-col justify-between hover:border-[#DF2600]/30 transition-colors">
                    <div>
                        <!-- Recipe Banner Artwork -->
                        <div class="relative h-44 bg-[#242424] overflow-hidden">
                            @if ($recipe->image)
                                <img src="{{ asset($recipe->image) }}" alt="{{ $recipe->name }}" class="w-full h-full object-cover">
                            @elseif ($recipe->thumbnail)
                                <img src="{{ asset($recipe->thumbnail) }}" alt="{{ $recipe->name }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-linear-to-br from-[#DF2600]/80 to-[#242424] text-white font-extrabold text-2xl">
                                    {{ $recipe->name }}
                                </div>
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent"></div>

                            <!-- Top Badges -->
                            <div class="absolute top-3 inset-x-3 flex items-center justify-between">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @if ($recipe->cuisine)
                                        <span class="px-2.5 py-1 rounded-lg bg-black/60 backdrop-blur-xs text-white text-[11px] font-bold">
                                            {{ $cuisines[$recipe->cuisine] ?? ucfirst(str_replace('_', ' ', $recipe->cuisine)) }}
                                        </span>
                                    @endif
                                    @if ($recipe->country)
                                        <span class="px-2 py-0.5 rounded-md bg-white/20 backdrop-blur-xs text-white text-[10px] font-semibold">
                                            {{ $recipe->country->name }}
                                        </span>
                                    @endif
                                </div>

                                <button type="button"
                                        wire:click="toggleFeatured({{ $recipe->id }})"
                                        title="{{ $recipe->is_featured ? 'Click to unfeature from homepage' : 'Click to feature on homepage' }}"
                                        class="p-1.5 rounded-xl backdrop-blur-xs transition-colors {{ $recipe->is_featured ? 'bg-[#DF2600] text-white shadow-xs' : 'bg-black/40 text-white/70 hover:text-white' }}">
                                    <svg class="w-4 h-4 {{ $recipe->is_featured ? 'fill-current' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Banner Title & Duration -->
                            <div class="absolute bottom-3 inset-x-3 text-white">
                                <div class="flex items-center justify-between gap-2">
                                    <h3 class="text-base sm:text-lg font-extrabold tracking-tight truncate">{{ $recipe->title }}</h3>
                                    @if ($recipe->durationLabel())
                                        <span class="shrink-0 text-[11px] font-semibold px-2 py-0.5 rounded-md bg-white/20 backdrop-blur-xs">
                                            {{ $recipe->durationLabel() }}
                                        </span>
                                    @endif
                                </div>
                                @if ($recipe->intro)
                                    <p class="text-xs text-white/80 line-clamp-1 mt-0.5">{{ $recipe->intro }}</p>
                                @endif
                            </div>
                        </div>

                        <!-- Stage Gate Status & Ingredients Summary -->
                        <div class="p-4 sm:p-5 space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                @if ($isApproved)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#DAF4E7] text-[#007D49] text-xs font-extrabold">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Approved Live
                                    </span>
                                @elseif (empty($problems))
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#FFF8E6] text-[#B76E00] text-xs font-extrabold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#B76E00]"></span>
                                        Ready for Approval
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#FFF0EC] text-[#DF2600] text-xs font-extrabold">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        {{ count($problems) }} Issue{{ count($problems) > 1 ? 's' : '' }}
                                    </span>
                                @endif

                                @if ($costPreview > 0)
                                    <span class="text-xs font-extrabold text-[#111111]">
                                        Est. Kit (4p): {{ money($costPreview) }}
                                    </span>
                                @endif
                            </div>

                            @if (! empty($problems) && ! $isApproved)
                                <div class="p-2.5 rounded-xl bg-[#FFF0EC] text-[11px] text-[#DF2600] space-y-1">
                                    @foreach (array_slice($problems, 0, 2) as $prob)
                                        <div class="flex items-start gap-1">
                                            <span>•</span>
                                            <span>{{ $prob }}</span>
                                        </div>
                                    @endforeach
                                    @if (count($problems) > 2)
                                        <div class="text-[10px] opacity-80 pl-2">+{{ count($problems) - 2 }} more issue(s)</div>
                                    @endif
                                </div>
                            @endif

                            <!-- Ingredients Chips -->
                            <div>
                                <span class="font-bold text-[#686868] uppercase tracking-wider text-[10px] block mb-1.5">
                                    Ingredients ({{ $recipe->ingredients->count() }})
                                </span>
                                @if ($recipe->ingredients->isNotEmpty())
                                    <div class="flex flex-wrap gap-1 max-h-16 overflow-hidden">
                                        @foreach ($recipe->ingredients->take(6) as $ri)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium bg-[#FAFAFA] border border-[#EEEEEE] text-[#111111]">
                                                {{ $ri->ingredient?->name ?? 'Ingredient' }}
                                                @if ($ri->role === 'optional')
                                                    <span class="text-[9px] text-[#686868] ml-1">(opt)</span>
                                                @endif
                                            </span>
                                        @endforeach
                                        @if ($recipe->ingredients->count() > 6)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-[#EEEEEE] text-[#686868]">
                                                +{{ $recipe->ingredients->count() - 6 }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-xs text-[#686868] italic">No ingredients linked yet.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Card Actions Footer -->
                    <div class="p-3 sm:p-4 bg-[#FAFAFA] border-t border-[#EEEEEE] flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5">
                            @if (! $isApproved && empty($problems))
                                <button type="button"
                                        wire:click="approveRecipeAction({{ $recipe->id }})"
                                        class="px-2.5 py-1.5 rounded-xl bg-[#007D49] text-white text-xs font-extrabold hover:opacity-90 shadow-2xs">
                                    Approve Live
                                </button>
                            @endif
                            <button type="button"
                                    wire:click="editRecipe({{ $recipe->id }})"
                                    class="px-3 py-1.5 rounded-xl bg-white border border-[#EEEEEE] text-xs font-bold text-[#111111] hover:bg-[#F6F6F6] transition-colors shadow-2xs">
                                Edit Recipe
                            </button>
                        </div>

                        <button type="button"
                                wire:click="openDeleteModal({{ $recipe->id }})"
                                class="p-1.5 rounded-xl text-[#686868] hover:text-[#DF2600] hover:bg-white transition-colors"
                                title="Delete recipe">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-16 px-6 text-center bg-white rounded-2xl border border-[#EEEEEE]">
                    <div class="max-w-sm mx-auto space-y-3">
                        <div class="w-12 h-12 mx-auto rounded-full bg-[#FAFAFA] border border-[#EEEEEE] flex items-center justify-center text-[#686868]">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <h3 class="text-base font-extrabold text-[#111111]">No recipes match your criteria</h3>
                        <p class="text-xs text-[#686868]">Try searching another dish or clear your filters.</p>
                    </div>
                </div>
            @endforelse
        </div>

        @if ($recipes->hasPages())
            <div class="p-4 bg-white rounded-2xl border border-[#EEEEEE]">
                {{ $recipes->links() }}
            </div>
        @endif
    @endif

    <!-- TAB 2: INGREDIENTS & STORE OPTIONS -->
    @if ($tab === 'ingredients')
        <div class="p-4 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="w-full sm:max-w-md relative">
                <input type="text"
                       wire:model.live.debounce.300ms="ingredientSearch"
                       placeholder="Search culinary ingredients or alternative names..."
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] placeholder-[#686868] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                <svg class="w-4 h-4 text-[#686868] absolute left-3.5 top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <button type="button"
                    wire:click="createIngredient"
                    style="background-color: #DF2600;"
                    class="w-full sm:w-auto px-4 py-2 rounded-xl text-white text-xs sm:text-sm font-extrabold hover:opacity-90 shadow-xs">
                + Add Ingredient
            </button>
        </div>

        <div class="bg-white rounded-2xl border border-[#EEEEEE] shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs sm:text-sm">
                    <thead class="bg-[#FAFAFA] border-b border-[#EEEEEE] text-[11px] font-bold uppercase tracking-wider text-[#686868]">
                        <tr>
                            <th class="py-3.5 px-4 sm:px-6">Ingredient</th>
                            <th class="py-3.5 px-4">Classification</th>
                            <th class="py-3.5 px-4">Linked Store Products</th>
                            <th class="py-3.5 px-4">Preferred Variation (Rank 1)</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EEEEEE]">
                        @forelse ($ingredients as $ing)
                            @php
                                $pref = $ing->options->first();
                            @endphp
                            <tr class="hover:bg-[#FAFAFA]/60 transition-colors">
                                <td class="py-3 px-4 sm:px-6 font-extrabold text-[#111111]">
                                    <div class="flex items-center gap-2">
                                        <span>{{ $ing->name }}</span>
                                    </div>
                                    @if ($ing->alt_names)
                                        <span class="block text-[11px] text-[#686868] font-normal">
                                            Synonyms: {{ $ing->alt_names }}
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3 px-4">
                                    @if ($ing->is_pantry_staple)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#EEEEEE] text-[#686868]">
                                            Pantry Staple (No cart addition)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#DAF4E7] text-[#007D49]">
                                            Store Buyable
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3 px-4">
                                    @if ($ing->is_pantry_staple)
                                        <span class="text-xs text-[#686868] italic">Customer provides</span>
                                    @elseif ($ing->options->isNotEmpty())
                                        <span class="inline-flex items-center gap-1 font-bold text-xs text-[#111111]">
                                            <span class="w-2 h-2 rounded-full bg-[#007D49]"></span>
                                            {{ $ing->options->count() }} option{{ $ing->options->count() > 1 ? 's' : '' }} ranked
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 font-bold text-xs text-[#DF2600]">
                                            <span class="w-2 h-2 rounded-full bg-[#DF2600]"></span>
                                            Missing Store Variation
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3 px-4">
                                    @if ($pref && $pref->variation)
                                        <div class="text-xs">
                                            <span class="font-bold text-[#111111]">{{ $pref->variation->product?->name }}</span>
                                            <span class="text-[#686868]">({{ $pref->variation->name ?: 'Standard' }})</span>
                                            <span class="text-[#007D49] font-extrabold ml-1">{{ money($pref->variation->price_cents) }}</span>
                                            @if ((float) $pref->pack_factor !== 1.0)
                                                <span class="text-[10px] text-[#686868] ml-1">[{{ $pref->pack_factor }}x pack]</span>
                                            @endif
                                        </div>
                                    @elseif ($ing->is_pantry_staple)
                                        <span class="text-xs text-[#686868]">—</span>
                                    @else
                                        <span class="text-xs text-[#DF2600] italic">Not configured</span>
                                    @endif
                                </td>

                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if (! $ing->is_pantry_staple)
                                            <button type="button"
                                                    wire:click="openManageOptions({{ $ing->id }})"
                                                    class="px-2.5 py-1 rounded-lg bg-white border border-[#EEEEEE] text-xs font-bold text-[#111111] hover:bg-[#F6F6F6] shadow-2xs">
                                                Link Variations ({{ $ing->options->count() }})
                                            </button>
                                        @endif
                                        <button type="button"
                                                wire:click="editIngredient({{ $ing->id }})"
                                                class="px-2 py-1 rounded-lg text-xs font-bold text-[#686868] hover:text-[#111111] hover:bg-[#FAFAFA]">
                                            Edit
                                        </button>
                                        <button type="button"
                                                wire:click="openDeleteIngredient({{ $ing->id }})"
                                                class="p-1 rounded-lg text-[#686868] hover:text-[#DF2600]">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-[#686868] text-xs">
                                    No ingredients configured yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($ingredients->hasPages())
                <div class="p-4 border-t border-[#EEEEEE]">
                    {{ $ingredients->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- TAB 3: CURATED SUBSTITUTIONS -->
    @if ($tab === 'substitutions')
        <div class="p-4 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <h3 class="text-sm sm:text-base font-extrabold text-[#111111]">Chef-Approved Ingredient Swaps</h3>
                <p class="text-xs text-[#686868]">
                    When a primary recipe ingredient runs out of stock, Asha and the recipe resolver intelligently offer these authentic substitutes.
                </p>
            </div>

            <button type="button"
                    wire:click="createSubstitution"
                    style="background-color: #DF2600;"
                    class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-white text-xs sm:text-sm font-extrabold hover:opacity-90 shadow-xs shrink-0">
                + Add Substitution
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse ($substitutions as $baseIng)
                @foreach ($baseIng->substitutes as $sub)
                    <div class="p-4 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs flex items-center justify-between gap-4">
                        <div class="space-y-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap text-sm font-extrabold text-[#111111]">
                                <span class="px-2.5 py-1 rounded-lg bg-[#FAFAFA] border border-[#EEEEEE] text-[#DF2600]">
                                    {{ $baseIng->name }}
                                </span>
                                <svg class="w-4 h-4 text-[#686868]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                                <span class="px-2.5 py-1 rounded-lg bg-[#DAF4E7] border border-[#007D49]/20 text-[#007D49]">
                                    {{ $sub->name }}
                                </span>
                            </div>

                            @if ($sub->pivot->note)
                                <p class="text-xs text-[#686868] mt-1 italic">
                                    "{{ $sub->pivot->note }}"
                                </p>
                            @else
                                <p class="text-xs text-[#686868] mt-1">Direct 1:1 culinary swap</p>
                            @endif
                        </div>

                        <button type="button"
                                wire:click="removeSubstitution({{ $baseIng->id }}, {{ $sub->id }})"
                                class="p-2 rounded-xl text-[#686868] hover:text-[#DF2600] hover:bg-[#FFF0EC] transition-colors shrink-0"
                                title="Remove substitution">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                @endforeach
            @empty
                <div class="col-span-full py-16 px-6 text-center bg-white rounded-2xl border border-[#EEEEEE]">
                    <div class="max-w-sm mx-auto space-y-3">
                        <div class="w-12 h-12 mx-auto rounded-full bg-[#FAFAFA] border border-[#EEEEEE] flex items-center justify-center text-[#686868]">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                        </div>
                        <h3 class="text-base font-extrabold text-[#111111]">No substitutions configured</h3>
                        <p class="text-xs text-[#686868]">Click "+ Add Substitution" to pair culinary swaps.</p>
                    </div>
                </div>
            @endforelse
        </div>
    @endif

    <!-- MODAL 1: RECIPE CREATE / EDIT -->
    @if ($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-3 sm:px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 transition-opacity" wire:click="$set('showEditModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all my-4 sm:my-8 align-middle max-w-5xl w-full mx-auto" style="background-color: #ffffff;">
                    <!-- Modal Header -->
                    <div class="p-4 sm:p-6 border-b border-[#EEEEEE] flex items-center justify-between">
                        <div>
                            <h3 class="text-base sm:text-lg font-extrabold text-[#111111]" id="modal-title">
                                {{ $editingRecipeId ? 'Edit Recipe: '.$name : 'Create New Kitchen Recipe' }}
                            </h3>
                            <p class="mt-0.5 text-xs text-[#686868]">
                                Configure recipe culinary instructions and link ingredients to enforce stage gate approval.
                            </p>
                        </div>
                        <button type="button" wire:click="$set('showEditModal', false)" class="text-[#686868] hover:text-[#111111] p-1.5 rounded-lg hover:bg-[#FAFAFA]">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body (Two-Column Layout) -->
                    <form wire:submit="saveRecipe">
                        <div class="p-4 sm:p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 max-h-[75vh] overflow-y-auto">
                            <!-- Left Column: Recipe Information (7 cols) -->
                            <div class="lg:col-span-7 space-y-4">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-[#686868] pb-1 border-b border-[#EEEEEE]">
                                    Recipe Profile &amp; Instructions
                                </h4>

                                <!-- Recipe Title & Cuisine -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-[#111111] mb-1">Recipe Title *</label>
                                        <input type="text"
                                               wire:model.live.debounce.300ms="name"
                                               placeholder="e.g. Nigerian Jollof Rice"
                                               class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                        @error('name') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-[#111111] mb-1">Cuisine / Heritage *</label>
                                        <select wire:model="cuisine" class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs font-semibold text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20">
                                            @foreach ($cuisines as $cKey => $cLabel)
                                                <option value="{{ $cKey }}">{{ $cLabel }}</option>
                                            @endforeach
                                        </select>
                                        @error('cuisine') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <!-- Servings & Prep Time -->
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-[#111111] mb-1">Base Servings *</label>
                                        <input type="number"
                                               wire:model="base_servings"
                                               min="1"
                                               max="20"
                                               class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                        @error('base_servings') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-[#111111] mb-1">Prep Time (Min)</label>
                                        <input type="number"
                                               wire:model="prep_minutes"
                                               min="5"
                                               max="480"
                                               class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                        @error('prep_minutes') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-span-2 sm:col-span-1">
                                        <label class="block text-xs font-bold text-[#111111] mb-1">Origin Country</label>
                                        <select wire:model="country_id" class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs font-semibold text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20">
                                            <option value="">Regional</option>
                                            @foreach ($countries as $c)
                                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Intro / Flavour Tagline -->
                                <div>
                                    <label class="block text-xs font-bold text-[#111111] mb-1">Intro / Flavour Summary</label>
                                    <textarea wire:model="intro"
                                              rows="2"
                                              placeholder="e.g. Smoky, party-style Nigerian Jollof Rice simmered in an aromatic tomato-pepper base..."
                                              class="w-full px-3.5 py-2 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]"></textarea>
                                </div>

                                <!-- Step-by-Step Cooking Steps -->
                                <div>
                                    <label class="block text-xs font-bold text-[#111111] mb-1">Step-by-Step Cooking Method *</label>
                                    <textarea wire:model="steps"
                                              rows="6"
                                              placeholder="1. Blend fresh tomatoes and scotch bonnets...&#10;2. Fry tomato paste in vegetable oil...&#10;3. Add parboiled rice and stock..."
                                              class="w-full px-3.5 py-2 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs font-mono text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]"></textarea>
                                    <p class="text-[10px] text-[#686868] mt-1">Number each step clearly. Required for recipe approval.</p>
                                    @error('steps') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <!-- Dish Photo Selector -->
                                <div class="p-4 rounded-2xl bg-[#FAFAFA] border border-[#EEEEEE] space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="block text-xs font-bold text-[#111111]">Recipe Photo</span>
                                            <span class="block text-[11px] text-[#686868]">Choose from meal presets or upload custom artwork.</span>
                                        </div>

                                        @if ($imageUpload || $selectedPreset || $image)
                                            <button type="button"
                                                    wire:click="removeImage"
                                                    class="text-[11px] font-bold text-[#DF2600] hover:underline flex items-center gap-1">
                                                Remove Photo
                                            </button>
                                        @endif
                                    </div>

                                    <!-- Current Photo Preview -->
                                    @if ($imageUpload)
                                        <div class="flex items-center gap-3 p-2.5 rounded-xl bg-white border border-[#007D49]/30">
                                            <span class="text-xs font-bold text-[#007D49]">New upload selected</span>
                                        </div>
                                    @elseif ($selectedPreset || $image)
                                        @php
                                            $currentDisplayImg = $selectedPreset ?: $image;
                                        @endphp
                                        <div class="flex items-center gap-3 p-2 rounded-xl bg-white border border-[#EEEEEE]">
                                            <img src="{{ asset($currentDisplayImg) }}" alt="Recipe" class="w-12 h-12 rounded-lg object-cover border border-[#EEEEEE]">
                                            <div class="flex-1 min-w-0">
                                                <span class="text-xs font-bold text-[#111111] block">Selected Photo</span>
                                                <span class="text-[10px] text-[#686868] truncate block">{{ basename($currentDisplayImg) }}</span>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Preset Gallery -->
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-36 overflow-y-auto p-1 bg-white rounded-xl border border-[#EEEEEE]">
                                        @foreach ($presets as $preset)
                                            @php
                                                $isSelected = ($selectedPreset === $preset['path']) || ($image === $preset['path'] && ! $selectedPreset);
                                            @endphp
                                            <button type="button"
                                                    wire:click="selectPreset('{{ $preset['path'] }}')"
                                                    class="p-1.5 rounded-xl border text-left flex items-center gap-2 {{ $isSelected ? 'border-[#DF2600] bg-[#FFF0EC]/50 ring-2 ring-[#DF2600]/30' : 'border-[#EEEEEE] hover:bg-[#FAFAFA]' }}">
                                                <img src="{{ asset($preset['path']) }}" alt="{{ $preset['name'] }}" class="w-8 h-8 rounded-lg object-cover shrink-0">
                                                <div class="min-w-0 flex-1">
                                                    <span class="text-[11px] font-bold text-[#111111] truncate block">{{ $preset['name'] }}</span>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Homepage Spotlight Feature -->
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] cursor-pointer hover:bg-white transition-colors">
                                    <input type="checkbox"
                                           wire:model="is_featured"
                                           class="w-4 h-4 rounded text-[#DF2600] focus:ring-[#DF2600]">
                                    <div>
                                        <span class="block text-xs font-bold text-[#111111]">Feature on Storefront Homepage</span>
                                        <span class="block text-[11px] text-[#686868]">Display in the "Cook by craving" recipe showcase</span>
                                    </div>
                                </label>
                            </div>

                            <!-- Right Column: Recipe Ingredients Builder (5 cols) -->
                            <div class="lg:col-span-5 space-y-4">
                                <div class="flex items-center justify-between pb-1 border-b border-[#EEEEEE]">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-[#686868]">
                                        Recipe Ingredients ({{ count($recipeIngredients) }})
                                    </h4>
                                    <span class="text-[11px] text-[#686868]">Required &amp; Optional</span>
                                </div>

                                <!-- Search & Add Ingredient Dropdown -->
                                <div class="relative">
                                    <input type="text"
                                           wire:model.live.debounce.250ms="ingredientSearchQuery"
                                           placeholder="Type ingredient name to add..."
                                           class="w-full pl-9 pr-3 py-2 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                    <svg class="w-3.5 h-3.5 text-[#686868] absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>

                                    @if ($ingredientSearchResults->isNotEmpty())
                                        <div class="absolute left-0 right-0 top-full mt-1 bg-white border border-[#EEEEEE] rounded-xl shadow-lg z-30 max-h-56 overflow-y-auto divide-y divide-[#EEEEEE]">
                                            @foreach ($ingredientSearchResults as $res)
                                                <div class="p-2.5 hover:bg-[#FAFAFA] flex items-center justify-between gap-2">
                                                    <div>
                                                        <span class="block text-xs font-bold text-[#111111]">{{ $res->name }}</span>
                                                        <span class="block text-[10px] text-[#686868]">{{ $res->is_pantry_staple ? 'Pantry Staple' : 'Store Buyable' }}</span>
                                                    </div>
                                                    <button type="button"
                                                            wire:click="addIngredientToRecipe({{ $res->id }})"
                                                            style="background-color: #007D49;"
                                                            class="px-2 py-1 rounded text-white text-[10px] font-extrabold hover:opacity-90 shrink-0">
                                                        + Add
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <!-- Attached Recipe Ingredients List -->
                                <div class="space-y-2.5 max-h-80 overflow-y-auto pr-1">
                                    @forelse ($recipeIngredients as $idx => $ri)
                                        <div class="p-3 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] space-y-2">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-1.5 min-w-0">
                                                    <span class="w-5 h-5 rounded-full bg-[#EEEEEE] text-[#686868] text-[10px] font-bold flex items-center justify-center shrink-0">
                                                        {{ $idx + 1 }}
                                                    </span>
                                                    <h6 class="text-xs font-extrabold text-[#111111] truncate">{{ $ri['name'] }}</h6>
                                                    @if ($ri['is_pantry_staple'])
                                                        <span class="text-[9px] px-1.5 py-0.5 rounded bg-[#EEEEEE] text-[#686868]">Pantry</span>
                                                    @elseif (! $ri['has_options'])
                                                        <span class="text-[9px] px-1.5 py-0.5 rounded bg-[#FFF0EC] text-[#DF2600] font-bold">No shelf link</span>
                                                    @endif
                                                </div>

                                                <button type="button"
                                                        wire:click="removeIngredientFromRecipe({{ $idx }})"
                                                        class="p-1 text-[#686868] hover:text-[#DF2600] rounded hover:bg-white"
                                                        title="Remove ingredient">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <div class="grid grid-cols-2 gap-2 text-[11px]">
                                                <div>
                                                    <label class="block text-[10px] font-bold text-[#686868] mb-0.5">Role</label>
                                                    <select wire:model="recipeIngredients.{{ $idx }}.role"
                                                            class="w-full px-2 py-1.5 rounded-lg border border-[#EEEEEE] bg-white text-xs font-semibold text-[#111111]">
                                                        <option value="required">Required</option>
                                                        <option value="optional">Optional</option>
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="block text-[10px] font-bold text-[#686868] mb-0.5">People / Pack</label>
                                                    <input type="number"
                                                           wire:model="recipeIngredients.{{ $idx }}.people_per_pack"
                                                           min="1"
                                                           max="50"
                                                           class="w-full px-2 py-1.5 rounded-lg border border-[#EEEEEE] bg-white text-xs text-[#111111]">
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="p-6 text-center rounded-xl border border-dashed border-[#EEEEEE] text-xs text-[#686868]">
                                            Search ingredients above to add to this recipe.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="p-4 bg-[#FAFAFA] border-t border-[#EEEEEE] flex items-center justify-between gap-3">
                            <button type="button"
                                    wire:click="$set('showEditModal', false)"
                                    class="px-4 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-xs font-bold text-[#111111] hover:bg-[#FAFAFA]">
                                Cancel
                            </button>

                            <button type="submit"
                                    style="background-color: #DF2600;"
                                    class="px-5 py-2.5 rounded-xl text-white text-xs font-extrabold hover:opacity-90 shadow-xs">
                                {{ $editingRecipeId ? 'Save Recipe' : 'Create Recipe' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL 2: INGREDIENT CREATE / EDIT -->
    @if ($showIngredientModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-3 sm:px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 transition-opacity" wire:click="$set('showIngredientModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all my-4 sm:my-8 align-middle max-w-lg w-full mx-auto p-5 sm:p-6 space-y-4" style="background-color: #ffffff;">
                    <div class="flex items-center justify-between border-b border-[#EEEEEE] pb-3">
                        <h3 class="text-base font-extrabold text-[#111111]">
                            {{ $editingIngredientId ? 'Edit Culinary Ingredient' : 'Create Culinary Ingredient' }}
                        </h3>
                        <button type="button" wire:click="$set('showIngredientModal', false)" class="text-[#686868] hover:text-[#111111]">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="saveIngredient" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-[#111111] mb-1">Ingredient Name *</label>
                            <input type="text"
                                   wire:model="ingredientName"
                                   placeholder="e.g. Red Palm Oil, Parboiled Rice, Egusi"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                            @error('ingredientName') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-[#111111] mb-1">Alternative Names &amp; Synonyms</label>
                            <input type="text"
                                   wire:model="ingredientAltNames"
                                   placeholder="e.g. melon seed, egusi powder, agushi"
                                   class="w-full px-3.5 py-2 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                            <p class="text-[10px] text-[#686868] mt-1">Comma-separated terms used for customer search and "Cook With What I Have".</p>
                        </div>

                        <label class="flex items-center gap-3 p-3 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] cursor-pointer hover:bg-white">
                            <input type="checkbox"
                                   wire:model="ingredientIsPantryStaple"
                                   class="w-4 h-4 rounded text-[#DF2600] focus:ring-[#DF2600]">
                            <div>
                                <span class="block text-xs font-bold text-[#111111]">Pantry Staple</span>
                                <span class="block text-[11px] text-[#686868]">Water, salt, basic oils: assumed already at home and never added to shopping cart.</span>
                            </div>
                        </label>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#EEEEEE]">
                            <button type="button"
                                    wire:click="$set('showIngredientModal', false)"
                                    class="px-4 py-2 rounded-xl border border-[#EEEEEE] text-xs font-bold text-[#111111]">
                                Cancel
                            </button>
                            <button type="submit"
                                    style="background-color: #DF2600;"
                                    class="px-5 py-2 rounded-xl text-white text-xs font-extrabold hover:opacity-90">
                                Save Ingredient
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL 3: MANAGE PRODUCT VARIATIONS FOR INGREDIENT -->
    @if ($showOptionsModal && $managingIngredient)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-3 sm:px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 transition-opacity" wire:click="$set('showOptionsModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all my-4 sm:my-8 align-middle max-w-2xl w-full mx-auto p-5 sm:p-6 space-y-4" style="background-color: #ffffff;">
                    <div class="flex items-center justify-between border-b border-[#EEEEEE] pb-3">
                        <div>
                            <h3 class="text-base font-extrabold text-[#111111]">
                                Link Shelf Variations: {{ $managingIngredient->name }}
                            </h3>
                            <p class="text-xs text-[#686868]">
                                Rank products in order of recommendation (Rank 1 = Preferred).
                            </p>
                        </div>
                        <button type="button" wire:click="$set('showOptionsModal', false)" class="text-[#686868] hover:text-[#111111]">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    @if (session()->has('options_success'))
                        <div class="p-3 bg-[#DAF4E7] text-[#007D49] rounded-xl text-xs font-bold">
                            {{ session('options_success') }}
                        </div>
                    @endif

                    <!-- Add Option Form -->
                    <div class="p-4 rounded-xl bg-[#FAFAFA] border border-[#EEEEEE] space-y-3">
                        <span class="block text-xs font-bold text-[#111111]">Attach Store Variation</span>
                        <div class="relative">
                            <input type="text"
                                   wire:model.live.debounce.250ms="variationSearchQuery"
                                   placeholder="Search product variation by name..."
                                   class="w-full px-3 py-2 rounded-xl border border-[#EEEEEE] bg-white text-xs text-[#111111]">

                            @if ($variationSearchResults->isNotEmpty())
                                <div class="absolute left-0 right-0 top-full mt-1 bg-white border border-[#EEEEEE] rounded-xl shadow-lg z-30 max-h-48 overflow-y-auto divide-y divide-[#EEEEEE]">
                                    @foreach ($variationSearchResults as $v)
                                        <button type="button"
                                                wire:click="$set('selectedVariationId', {{ $v->id }}); $set('variationSearchQuery', '{{ addslashes($v->product?->name . ' - ' . ($v->name ?: 'Standard')) }}')"
                                                class="w-full p-2 text-left hover:bg-[#FAFAFA] flex items-center justify-between text-xs">
                                            <div>
                                                <span class="font-bold text-[#111111]">{{ $v->product?->name }}</span>
                                                <span class="text-[#686868]">({{ $v->name ?: 'Standard' }})</span>
                                            </div>
                                            <span class="font-extrabold text-[#007D49]">{{ money($v->price_cents) }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div>
                                <label class="block text-[10px] font-bold text-[#686868] mb-0.5">Rank (1 = Top Pick)</label>
                                <input type="number"
                                       wire:model="optionRank"
                                       min="1"
                                       max="20"
                                       class="w-full px-3 py-1.5 rounded-lg border border-[#EEEEEE] bg-white text-xs">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-[#686868] mb-0.5">Pack Factor (e.g. 1.0 or 2.0)</label>
                                <input type="number"
                                       step="0.1"
                                       wire:model="optionPackFactor"
                                       min="0.1"
                                       max="50"
                                       class="w-full px-3 py-1.5 rounded-lg border border-[#EEEEEE] bg-white text-xs">
                            </div>
                        </div>

                        <button type="button"
                                wire:click="addProductOption"
                                style="background-color: #007D49;"
                                class="w-full py-2 rounded-xl text-white text-xs font-extrabold hover:opacity-90">
                            + Link Variation to Ingredient
                        </button>
                    </div>

                    <!-- Existing Options Table -->
                    <div class="space-y-2">
                        <span class="text-xs font-bold text-[#686868] uppercase tracking-wider block">Configured Options ({{ $managingIngredient->options->count() }})</span>
                        @forelse ($managingIngredient->options as $opt)
                            <div class="p-3 rounded-xl border border-[#EEEEEE] bg-white flex items-center justify-between gap-3 text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-[#111111] text-white font-extrabold text-[11px] flex items-center justify-center shrink-0">
                                        #{{ $opt->rank }}
                                    </span>
                                    <div>
                                        <h6 class="font-bold text-[#111111]">{{ $opt->variation?->product?->name }} ({{ $opt->variation?->name ?: 'Standard' }})</h6>
                                        <span class="text-[11px] text-[#007D49] font-extrabold">{{ money($opt->variation?->price_cents ?? 0) }}</span>
                                        <span class="text-[10px] text-[#686868] ml-1">Pack Factor: {{ $opt->pack_factor }}x</span>
                                    </div>
                                </div>

                                <button type="button"
                                        wire:click="removeProductOption({{ $opt->id }})"
                                        class="p-1 text-[#686868] hover:text-[#DF2600]"
                                        title="Remove option">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        @empty
                            <p class="text-xs text-[#686868] italic p-4 text-center">No variations linked to this ingredient yet.</p>
                        @endforelse
                    </div>

                    <div class="flex items-center justify-end pt-3 border-t border-[#EEEEEE]">
                        <button type="button"
                                wire:click="$set('showOptionsModal', false)"
                                class="px-4 py-2 rounded-xl bg-[#111111] text-white text-xs font-bold">
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL 4: ADD SUBSTITUTION -->
    @if ($showSubstitutionModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-3 sm:px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 transition-opacity" wire:click="$set('showSubstitutionModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all my-4 sm:my-8 align-middle max-w-md w-full mx-auto p-5 sm:p-6 space-y-4" style="background-color: #ffffff;">
                    <div class="flex items-center justify-between border-b border-[#EEEEEE] pb-3">
                        <h3 class="text-base font-extrabold text-[#111111]">
                            Define Ingredient Substitution
                        </h3>
                        <button type="button" wire:click="$set('showSubstitutionModal', false)" class="text-[#686868] hover:text-[#111111]">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="saveSubstitution" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-[#111111] mb-1">Base Ingredient *</label>
                            <select wire:model="subBaseIngredientId" class="w-full px-3 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs font-semibold text-[#111111]">
                                <option value="">Select Primary Ingredient...</option>
                                @foreach ($allIngredients as $ing)
                                    <option value="{{ $ing->id }}">{{ $ing->name }}</option>
                                @endforeach
                            </select>
                            @error('subBaseIngredientId') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-[#111111] mb-1">Approved Alternative *</label>
                            <select wire:model="subSubstituteId" class="w-full px-3 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs font-semibold text-[#111111]">
                                <option value="">Select Substitute Ingredient...</option>
                                @foreach ($allIngredients as $ing)
                                    <option value="{{ $ing->id }}">{{ $ing->name }}</option>
                                @endforeach
                            </select>
                            @error('subSubstituteId') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-[#111111] mb-1">Culinary Advice / Exchange Note</label>
                            <input type="text"
                                   wire:model="subNote"
                                   placeholder="e.g. Use equal quantity; imparts a milder, sweeter flavor"
                                   class="w-full px-3 py-2 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs text-[#111111]">
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#EEEEEE]">
                            <button type="button"
                                    wire:click="$set('showSubstitutionModal', false)"
                                    class="px-4 py-2 rounded-xl border border-[#EEEEEE] text-xs font-bold text-[#111111]">
                                Cancel
                            </button>
                            <button type="submit"
                                    style="background-color: #DF2600;"
                                    class="px-5 py-2 rounded-xl text-white text-xs font-extrabold hover:opacity-90">
                                Save Substitution
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal (Recipe) -->
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-60 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-3 sm:px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 transition-opacity" wire:click="$set('showDeleteModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all my-4 sm:my-8 align-middle max-w-lg w-full mx-auto p-5 sm:p-6 space-y-4" style="background-color: #ffffff;">
                    <div class="flex items-center gap-3 text-[#DF2600]">
                        <div class="w-10 h-10 rounded-full bg-[#FFF0EC] flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <h3 class="text-base sm:text-lg font-extrabold text-[#111111]">
                            Delete Kitchen Recipe?
                        </h3>
                    </div>

                    <p class="text-xs sm:text-sm text-[#686868]">
                        Are you sure you want to delete this recipe? It will be permanently removed from the recipe library and will no longer appear on the storefront.
                    </p>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#EEEEEE]">
                        <button type="button"
                                wire:click="$set('showDeleteModal', false)"
                                class="px-4 py-2.5 rounded-xl border border-[#EEEEEE] text-xs sm:text-sm font-bold text-[#111111] hover:bg-[#FAFAFA]">
                            Keep Recipe
                        </button>
                        <button type="button"
                                wire:click="confirmDelete"
                                style="background-color: #DF2600;"
                                class="px-4 py-2.5 rounded-xl text-white text-xs sm:text-sm font-bold hover:opacity-90">
                            Yes, Delete Recipe
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal (Ingredient) -->
    @if ($showDeleteIngredientModal)
        <div class="fixed inset-0 z-60 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-3 sm:px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 transition-opacity" wire:click="$set('showDeleteIngredientModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all my-4 sm:my-8 align-middle max-w-lg w-full mx-auto p-5 sm:p-6 space-y-4" style="background-color: #ffffff;">
                    <div class="flex items-center gap-3 text-[#DF2600]">
                        <div class="w-10 h-10 rounded-full bg-[#FFF0EC] flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <h3 class="text-base sm:text-lg font-extrabold text-[#111111]">
                            Delete Culinary Ingredient?
                        </h3>
                    </div>

                    <p class="text-xs sm:text-sm text-[#686868]">
                        Deleting this ingredient will unlink it from all recipes and product options.
                    </p>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#EEEEEE]">
                        <button type="button"
                                wire:click="$set('showDeleteIngredientModal', false)"
                                class="px-4 py-2.5 rounded-xl border border-[#EEEEEE] text-xs sm:text-sm font-bold text-[#111111] hover:bg-[#FAFAFA]">
                            Cancel
                        </button>
                        <button type="button"
                                wire:click="confirmDeleteIngredient"
                                style="background-color: #DF2600;"
                                class="px-4 py-2.5 rounded-xl text-white text-xs sm:text-sm font-bold hover:opacity-90">
                            Yes, Delete Ingredient
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
