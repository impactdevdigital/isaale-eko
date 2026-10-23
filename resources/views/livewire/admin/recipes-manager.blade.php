<div class="space-y-8">
    <!-- Header with CTA -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-extrabold text-[#111111] tracking-tight">Kitchen Recipes & Ingredient Bundles</h2>
            <p class="mt-1 text-sm text-[#686868]">
                Curate authentic African &amp; Caribbean dishes for the storefront. Attach shelf products as one-click buyable ingredient kits.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <button type="button"
                    wire:click="createRecipe"
                    style="background-color: #DF2600;"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-white text-sm font-extrabold hover:opacity-90 transition-opacity shadow-xs">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                Add New Recipe
            </button>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="p-4 bg-[#DAF4E7] border border-[#007D49]/20 rounded-2xl flex items-center justify-between text-[#007D49] text-sm font-semibold">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-xs font-bold opacity-70 hover:opacity-100">Dismiss</button>
        </div>
    @endif

    <!-- Quick Stat KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-[#686868]">Total Recipes</span>
            <div class="mt-2 text-2xl font-extrabold text-[#111111]">{{ number_format($counts['total']) }}</div>
            <span class="text-[11px] text-[#686868]">In recipe library</span>
        </div>

        <div class="p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-[#DF2600]">Homepage Featured</span>
            <div class="mt-2 text-2xl font-extrabold text-[#DF2600]">{{ number_format($counts['featured']) }}</div>
            <span class="text-[11px] text-[#686868]">Active in "Cook by craving"</span>
        </div>

        <div class="p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-[#007D49]">Linked Ingredients</span>
            <div class="mt-2 text-2xl font-extrabold text-[#007D49]">{{ number_format($counts['total_ingredients']) }}</div>
            <span class="text-[11px] text-[#686868]">Shelf product attachments</span>
        </div>

        <div class="p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-[#0055D4]">Origin Heritages</span>
            <div class="mt-2 text-2xl font-extrabold text-[#0055D4]">{{ number_format($counts['countries']) }}</div>
            <span class="text-[11px] text-[#686868]">Countries represented</span>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs flex flex-col md:flex-row items-center gap-3">
        <div class="flex-1 w-full relative">
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Search recipes by name or flavour profile..."
                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] placeholder-[#686868] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
            <svg class="w-4 h-4 text-[#686868] absolute left-3.5 top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            @if ($search)
                <button type="button" wire:click="$set('search', '')" class="absolute right-3.5 top-3.5 text-xs text-[#686868] hover:text-[#111111]">Clear</button>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
            <select wire:model.live="countryFilter" class="px-3 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-xs font-semibold text-[#111111] focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20">
                <option value="">All Countries</option>
                @foreach ($countries as $country)
                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="featuredFilter" class="px-3 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-xs font-semibold text-[#111111] focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20">
                <option value="">All Displays</option>
                <option value="1">Featured on Homepage</option>
                <option value="0">Standard Library</option>
            </select>
        </div>
    </div>

    <!-- Recipes Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($recipes as $recipe)
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
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>

                        <!-- Top Badges -->
                        <div class="absolute top-3 inset-x-3 flex items-center justify-between">
                            @if ($recipe->country)
                                <span class="px-2.5 py-1 rounded-lg bg-black/60 backdrop-blur-xs text-white text-[11px] font-bold">
                                    {{ $recipe->country->name }}
                                </span>
                            @else
                                <span></span>
                            @endif

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
                                <h3 class="text-lg font-extrabold tracking-tight truncate">{{ $recipe->name }}</h3>
                                @if ($recipe->durationLabel())
                                    <span class="shrink-0 text-xs font-semibold px-2 py-0.5 rounded-md bg-white/20 backdrop-blur-xs">
                                        {{ $recipe->durationLabel() }}
                                    </span>
                                @endif
                            </div>
                            @if ($recipe->tagline)
                                <p class="text-xs text-white/80 line-clamp-1 mt-0.5">{{ $recipe->tagline }}</p>
                            @endif
                        </div>
                    </div>

                    <!-- Linked Ingredients Section -->
                    <div class="p-5 space-y-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-[#686868] uppercase tracking-wider text-[10px]">
                                Attached Ingredients ({{ $recipe->products->count() }})
                            </span>
                            @php
                                $totalKitCents = $recipe->products->sum(fn ($p) => $p->defaultVariation?->price_cents ?? 0);
                            @endphp
                            @if ($totalKitCents > 0)
                                <span class="font-extrabold text-[#111111]">
                                    Kit: {{ money($totalKitCents) }}
                                </span>
                            @endif
                        </div>

                        @if ($recipe->products->isNotEmpty())
                            <div class="flex items-center gap-1.5 overflow-x-auto py-1 [scrollbar-width:none]">
                                @foreach ($recipe->products->take(6) as $ing)
                                    <div class="relative shrink-0 group" title="{{ $ing->name }}">
                                        @if ($ing->image)
                                            <img src="{{ asset($ing->image) }}" alt="" class="w-10 h-10 object-cover rounded-lg border border-[#EEEEEE]">
                                        @else
                                            <div class="w-10 h-10 rounded-lg bg-[#FAFAFA] border border-[#EEEEEE] flex items-center justify-center text-[10px] font-bold text-[#686868]">
                                                {{ substr($ing->name, 0, 2) }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                                @if ($recipe->products->count() > 6)
                                    <span class="w-10 h-10 rounded-lg bg-[#FAFAFA] border border-[#EEEEEE] flex items-center justify-center text-xs font-bold text-[#686868] shrink-0">
                                        +{{ $recipe->products->count() - 6 }}
                                    </span>
                                @endif
                            </div>
                        @else
                            <div class="p-3 rounded-xl bg-[#FAFAFA] border border-dashed border-[#EEEEEE] text-center text-xs text-[#686868]">
                                No ingredients linked yet.
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Card Actions Footer -->
                <div class="p-4 bg-[#FAFAFA] border-t border-[#EEEEEE] flex items-center justify-between gap-2">
                    <span class="text-xs text-[#686868]">
                        @if ($recipe->is_featured)
                            <span class="inline-flex items-center gap-1 text-[#DF2600] font-bold text-[11px]">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#DF2600]"></span>
                                Homepage Live
                            </span>
                        @else
                            <span class="text-[11px] text-[#686868]">Standard Recipe</span>
                        @endif
                    </span>

                    <div class="flex items-center gap-2">
                        <button type="button"
                                wire:click="editRecipe({{ $recipe->id }})"
                                class="px-3 py-1.5 rounded-xl bg-white border border-[#EEEEEE] text-xs font-bold text-[#111111] hover:bg-[#F6F6F6] transition-colors shadow-2xs">
                            Edit &amp; Ingredients
                        </button>
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
            </div>
        @empty
            <div class="col-span-full py-16 px-6 text-center bg-white rounded-2xl border border-[#EEEEEE]">
                <div class="max-w-sm mx-auto space-y-3">
                    <div class="w-12 h-12 mx-auto rounded-full bg-[#FAFAFA] border border-[#EEEEEE] flex items-center justify-center text-[#686868]">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h3 class="text-base font-extrabold text-[#111111]">No recipes match your search</h3>
                    <p class="text-xs text-[#686868]">
                        Try searching for another dish or clear your country/display filters.
                    </p>
                </div>
            </div>
        @endforelse
    </div>

    @if ($recipes->hasPages())
        <div class="p-4 bg-white rounded-2xl border border-[#EEEEEE]">
            {{ $recipes->links() }}
        </div>
    @endif

    <!-- Create / Edit Recipe Modal -->
    @if ($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/40 backdrop-blur-xs transition-opacity" wire:click="$set('showEditModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <!-- Modal Header -->
                    <div class="p-6 border-b border-[#EEEEEE] flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-extrabold text-[#111111]" id="modal-title">
                                {{ $editingRecipeId ? 'Edit Recipe: '.$name : 'Create New Kitchen Recipe' }}
                            </h3>
                            <p class="mt-0.5 text-xs text-[#686868]">
                                Configure recipe profile and bundle shelf products as buyable ingredients.
                            </p>
                        </div>
                        <button type="button" wire:click="$set('showEditModal', false)" class="text-[#686868] hover:text-[#111111] p-1.5 rounded-lg hover:bg-[#FAFAFA]">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body (Two-Column Layout) -->
                    <form wire:submit="save">
                        <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 max-h-[75vh] overflow-y-auto">
                            <!-- Left Column: Recipe Information (7 cols) -->
                            <div class="lg:col-span-7 space-y-4">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-[#686868] pb-1 border-b border-[#EEEEEE]">
                                    Dish Profile
                                </h4>

                                <div>
                                    <label class="block text-xs font-bold text-[#111111] mb-1">Recipe Name *</label>
                                    <input type="text"
                                           wire:model.live.debounce.300ms="name"
                                           placeholder="e.g. Nigerian Jollof Rice"
                                           class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                    @error('name') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-[#111111] mb-1">URL Slug *</label>
                                        <input type="text"
                                               wire:model="slug"
                                               placeholder="jollof-rice"
                                               class="w-full px-3.5 py-2 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs font-mono text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                        @error('slug') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-[#111111] mb-1">Heritage Origin</label>
                                        <select wire:model="country_id" class="w-full px-3.5 py-2 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs font-semibold text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20">
                                            <option value="">None / Regional</option>
                                            @foreach ($countries as $c)
                                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('country_id') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-[#111111] mb-1">Tagline / Flavour Profile</label>
                                    <input type="text"
                                           wire:model="tagline"
                                           placeholder="e.g. Smoky, rich and deeply savoury"
                                           class="w-full px-3.5 py-2 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                    @error('tagline') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-[#111111] mb-1">Cook Time (Minutes)</label>
                                        <input type="number"
                                               wire:model="duration_minutes"
                                               min="5"
                                               max="480"
                                               placeholder="50"
                                               class="w-full px-3.5 py-2 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                        @error('duration_minutes') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-[#111111] mb-1">Sort Order</label>
                                        <input type="number"
                                               wire:model="sort_order"
                                               min="0"
                                               class="w-full px-3.5 py-2 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                        @error('sort_order') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-[#111111] mb-1">Hero Image Path</label>
                                    <input type="text"
                                           wire:model="image"
                                           placeholder="images/store/recipes/jollof-rice.webp"
                                           class="w-full px-3.5 py-2 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs font-mono text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                    <div class="mt-1 flex items-center gap-2 text-[11px] text-[#686868]">
                                        <span>Presets:</span>
                                        <button type="button" wire:click="$set('image', 'images/store/recipes/jollof-rice.webp')" class="underline hover:text-[#DF2600]">Jollof</button>
                                        <button type="button" wire:click="$set('image', 'images/store/recipes/egusi-soup.webp')" class="underline hover:text-[#DF2600]">Egusi</button>
                                        <button type="button" wire:click="$set('image', 'images/store/recipes/banga-soup.webp')" class="underline hover:text-[#DF2600]">Banga</button>
                                        <button type="button" wire:click="$set('image', 'images/store/recipes/zobo-hibiscus-drink.webp')" class="underline hover:text-[#DF2600]">Zobo</button>
                                    </div>
                                    @error('image') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div class="pt-2">
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
                            </div>

                            <!-- Right Column: Ingredient Bundler (5 cols) -->
                            <div class="lg:col-span-5 space-y-4">
                                <div class="flex items-center justify-between pb-1 border-b border-[#EEEEEE]">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-[#686868]">
                                        Ingredient Kit ({{ count($attachedProductIds) }})
                                    </h4>
                                    @php
                                        $kitTotalCents = $attachedProducts->sum(fn ($p) => $p->defaultVariation?->price_cents ?? 0);
                                    @endphp
                                    <span class="text-xs font-extrabold text-[#007D49]">
                                        Total: {{ money($kitTotalCents) }}
                                    </span>
                                </div>

                                <!-- Live Product Search for Adding Ingredients -->
                                <div class="relative">
                                    <input type="text"
                                           wire:model.live.debounce.250ms="ingredientSearch"
                                           placeholder="Type product name or SKU to add..."
                                           class="w-full pl-9 pr-3 py-2 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                    <svg class="w-3.5 h-3.5 text-[#686868] absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>

                                    <!-- Search Results Dropdown -->
                                    @if ($searchResults->isNotEmpty())
                                        <div class="absolute left-0 right-0 top-full mt-1 bg-white border border-[#EEEEEE] rounded-xl shadow-lg z-30 max-h-56 overflow-y-auto divide-y divide-[#EEEEEE]">
                                            @foreach ($searchResults as $result)
                                                <div class="p-2.5 hover:bg-[#FAFAFA] flex items-center justify-between gap-2">
                                                    <div class="flex items-center gap-2 truncate">
                                                        @if ($result->image)
                                                            <img src="{{ asset($result->image) }}" class="w-7 h-7 rounded object-cover shrink-0">
                                                        @else
                                                            <div class="w-7 h-7 rounded bg-[#FAFAFA] flex items-center justify-center text-[9px] font-bold shrink-0">
                                                                {{ substr($result->name, 0, 2) }}
                                                            </div>
                                                        @endif
                                                        <div class="truncate">
                                                            <span class="block text-xs font-bold text-[#111111] truncate">{{ $result->name }}</span>
                                                            <span class="block text-[10px] text-[#686868]">{{ money($result->defaultVariation?->price_cents ?? 0) }}</span>
                                                        </div>
                                                    </div>
                                                    <button type="button"
                                                            wire:click="attachProduct({{ $result->id }})"
                                                            style="background-color: #007D49;"
                                                            class="px-2 py-1 rounded text-white text-[10px] font-extrabold hover:opacity-90 shrink-0">
                                                        + Add
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif (strlen(trim($ingredientSearch)) >= 2)
                                        <div class="absolute left-0 right-0 top-full mt-1 bg-white border border-[#EEEEEE] rounded-xl shadow-lg z-30 p-3 text-xs text-[#686868] text-center">
                                            No matching products found.
                                        </div>
                                    @endif
                                </div>

                                <!-- Attached Ingredients List -->
                                <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                                    @forelse ($attachedProducts as $product)
                                        <div class="p-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] flex items-center justify-between gap-3">
                                            <div class="flex items-center gap-2.5 truncate">
                                                @if ($product->image)
                                                    <img src="{{ asset($product->image) }}" class="w-9 h-9 rounded-lg object-cover shrink-0 border border-[#EEEEEE]">
                                                @else
                                                    <div class="w-9 h-9 rounded-lg bg-white border border-[#EEEEEE] flex items-center justify-center text-[10px] font-bold text-[#686868] shrink-0">
                                                        {{ substr($product->name, 0, 2) }}
                                                    </div>
                                                @endif
                                                <div class="truncate">
                                                    <h6 class="text-xs font-bold text-[#111111] truncate">{{ $product->name }}</h6>
                                                    <span class="text-[11px] text-[#686868]">{{ money($product->defaultVariation?->price_cents ?? 0) }}</span>
                                                </div>
                                            </div>

                                            <button type="button"
                                                    wire:click="removeProduct({{ $product->id }})"
                                                    class="p-1 text-[#686868] hover:text-[#DF2600] rounded hover:bg-white"
                                                    title="Remove ingredient">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    @empty
                                        <div class="p-6 text-center rounded-xl border border-dashed border-[#EEEEEE] text-xs text-[#686868]">
                                            Search shelf products above to build the buyable ingredient kit.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="p-4 bg-[#FAFAFA] border-t border-[#EEEEEE] flex items-center justify-end gap-3">
                            <button type="button"
                                    wire:click="$set('showEditModal', false)"
                                    class="px-4 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-xs font-bold text-[#111111] hover:bg-[#FAFAFA]">
                                Cancel
                            </button>
                            <button type="submit"
                                    style="background-color: #DF2600;"
                                    class="px-5 py-2.5 rounded-xl text-white text-xs font-extrabold hover:opacity-90 shadow-xs">
                                {{ $editingRecipeId ? 'Save Changes' : 'Create Recipe' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-60 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/40 backdrop-blur-xs transition-opacity" wire:click="$set('showDeleteModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6 space-y-4">
                    <div class="flex items-center gap-3 text-[#DF2600]">
                        <div class="w-10 h-10 rounded-full bg-[#FFF0EC] flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-extrabold text-[#111111]" id="modal-title">
                            Delete Kitchen Recipe?
                        </h3>
                    </div>

                    <p class="text-sm text-[#686868]">
                        Are you sure you want to delete this recipe? It will be removed from the recipe library and will no longer appear on the storefront.
                    </p>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#EEEEEE]">
                        <button type="button"
                                wire:click="$set('showDeleteModal', false)"
                                class="px-4 py-2.5 rounded-xl border border-[#EEEEEE] text-sm font-bold text-[#111111] hover:bg-[#FAFAFA]">
                            Keep Recipe
                        </button>
                        <button type="button"
                                wire:click="confirmDelete"
                                style="background-color: #DF2600;"
                                class="px-4 py-2.5 rounded-xl text-white text-sm font-bold hover:opacity-90">
                            Yes, Delete Recipe
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
