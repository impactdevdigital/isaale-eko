<div class="space-y-6 sm:space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-[#111111] tracking-tight">Products & Inventory Overview</h2>
            <p class="mt-1 text-xs sm:text-sm text-[#686868]">
                Catalog synchronized live from Square POS. Click any product to review full details, manage French translations, and configure return policy classes.
            </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            @if ($lastSync)
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#DAF4E7] text-xs font-bold text-[#007D49]">
                    <span class="w-2 h-2 rounded-full bg-[#007D49]"></span>
                    Square sync: {{ \Illuminate\Support\Carbon::parse($lastSync)->diffForHumans() }}
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#FFF9EA] text-xs font-bold text-[#D58700]">
                    <span class="w-2 h-2 rounded-full bg-[#D58700]"></span>
                    Not synced with Square yet
                </span>
            @endif
        </div>
    </div>

    <!-- Quick Stat KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="p-4 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-[#686868]">Total Products</span>
            <div class="mt-1 sm:mt-2 text-xl sm:text-2xl font-extrabold text-[#111111]">{{ number_format($counts['total']) }}</div>
            <span class="text-[10px] sm:text-[11px] text-[#686868]">Synced from Square</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-[#686868]">Active Online</span>
            <div class="mt-1 sm:mt-2 text-xl sm:text-2xl font-extrabold text-[#007D49]">{{ number_format($counts['active']) }}</div>
            <span class="text-[10px] sm:text-[11px] text-[#686868]">Visible on storefront</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-[#686868]">Featured Items</span>
            <div class="mt-1 sm:mt-2 text-xl sm:text-2xl font-extrabold text-[#DF2600]">{{ number_format($counts['featured']) }}</div>
            <span class="text-[10px] sm:text-[11px] text-[#686868]">Homepage spotlights</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-[#D58700]">Untracked Stock</span>
            <div class="mt-1 sm:mt-2 text-xl sm:text-2xl font-extrabold text-[#D58700]">{{ number_format($counts['untracked']) }}</div>
            <span class="text-[10px] sm:text-[11px] text-[#686868]">Always available</span>
        </div>
    </div>

    <!-- Filter Bar Container -->
    <div class="bg-white rounded-2xl border border-[#EEEEEE] p-4 sm:p-5 shadow-xs space-y-3 sm:space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- Search -->
            <div class="sm:col-span-2 lg:col-span-1 relative">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search products, French name, SKU..."
                       class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-[#DEDEDE] text-sm text-[#111111] placeholder-[#9E9E9E] focus:outline-none focus:border-[#DF2600] focus:ring-1 focus:ring-[#DF2600] transition-colors">
                <svg class="w-4 h-4 text-[#9E9E9E] absolute left-3 top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <!-- Category Filter -->
            <div>
                <select wire:model.live="categoryFilter"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDEDE] text-sm text-[#111111] focus:outline-none focus:border-[#DF2600] focus:ring-1 focus:ring-[#DF2600] transition-colors">
                    <option value="">All Categories ({{ count($categories) }})</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @foreach ($cat->children as $child)
                            <option value="{{ $child->id }}">&nbsp;&nbsp;– {{ $child->name }}</option>
                        @endforeach
                    @endforeach
                </select>
            </div>

            <!-- Stock State Filter -->
            <div>
                <select wire:model.live="stockFilter"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDEDE] text-sm text-[#111111] focus:outline-none focus:border-[#DF2600] focus:ring-1 focus:ring-[#DF2600] transition-colors">
                    <option value="">All Stock States</option>
                    <option value="in_stock">In Stock Only</option>
                    <option value="low_stock">Low Stock Only</option>
                    <option value="out_of_stock">Out of Stock</option>
                    <option value="untracked">Untracked in Square</option>
                </select>
            </div>

            <!-- Item Class Filter -->
            <div>
                <select wire:model.live="classFilter"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDEDE] text-sm text-[#111111] focus:outline-none focus:border-[#DF2600] focus:ring-1 focus:ring-[#DF2600] transition-colors">
                    <option value="">All Product Classes</option>
                    <option value="perishable">Perishables (24h claim)</option>
                    <option value="non_perishable">Non-perishable Groceries (24h)</option>
                    <option value="non_food">Non-Food (7d return)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Products Container: Card Stack for Mobile, Table for Desktop -->
    <div class="bg-white rounded-2xl border border-[#EEEEEE] shadow-xs overflow-hidden">
        <!-- 1. Mobile Card Stack (Visible on < md screens) -->
        <div class="block md:hidden divide-y divide-[#EEEEEE]">
            @forelse ($products as $p)
                @php
                    $minPrice = $p->variations->min('price_cents');
                    $maxPrice = $p->variations->max('price_cents');
                    $varCount = $p->variations->count();
                    $stock = $p->variations->pluck('stock_quantity');
                    $untracked = $stock->contains(null);
                    $available = $untracked || $stock->contains(fn ($q) => $q > 0);
                    $isLow = ! $untracked && $stock->contains(fn ($q) => $q > 0 && $q <= $low);
                @endphp

                <div class="p-4 space-y-3 hover:bg-[#FAFAFA] transition-colors">
                    <!-- Top Row: Image, Name, Status -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3 min-w-0 cursor-pointer" wire:click="viewProduct({{ $p->id }})">
                            @if ($p->image)
                                <img src="{{ asset($p->image) }}" alt="{{ $p->name }}" class="w-12 h-12 rounded-xl object-cover bg-[#F6F6F6] shrink-0 border border-[#EEEEEE]">
                            @else
                                <div class="w-12 h-12 rounded-xl bg-[#F6F6F6] border border-[#EEEEEE] flex items-center justify-center text-xs font-bold text-[#9E9E9E] shrink-0">
                                    No img
                                </div>
                            @endif

                            <div class="min-w-0">
                                <h4 class="font-extrabold text-[#111111] text-sm leading-snug hover:text-[#DF2600] transition-colors line-clamp-2">{{ $p->name }}</h4>
                                @if ($p->name_fr)
                                    <span class="inline-block text-[11px] text-[#0055D4] font-medium truncate max-w-[200px]">FR: {{ $p->name_fr }}</span>
                                @endif
                                <div class="flex items-center gap-1.5 flex-wrap mt-1">
                                    @if ($p->category)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#F6F6F6] text-[#111111]">
                                            {{ $p->category->parent?->name ?? $p->category->name }}
                                        </span>
                                    @else
                                        <span class="text-[11px] text-[#9E9E9E] italic">Uncategorized</span>
                                    @endif
                                    @if ($p->country)
                                        <span class="text-[10px] text-[#686868]">{{ $p->country->name }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Status Button -->
                        <div class="shrink-0">
                            @if (! $p->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#F6F6F6] text-[#9E9E9E]">Off Square</span>
                            @else
                                <button type="button"
                                        wire:click="toggleHidden({{ $p->id }})"
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold transition-colors {{ $p->is_hidden ? 'bg-[#F6F6F6] text-[#686868]' : 'bg-[#DAF4E7] text-[#007D49]' }}">
                                    {{ $p->is_hidden ? 'Hidden' : 'Live' }}
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Middle Row: Price & Availability -->
                    <div class="flex items-center justify-between text-xs bg-[#FAFAFA] p-2.5 rounded-xl border border-[#EEEEEE]">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[#686868] block">Price</span>
                            <span class="font-extrabold text-[#DF2600]">
                                @if ($minPrice !== null)
                                    @if ($minPrice === $maxPrice)
                                        {{ money($minPrice) }}
                                    @else
                                        {{ money($minPrice) }} – {{ money($maxPrice) }}
                                    @endif
                                @else
                                    Till Only
                                @endif
                            </span>
                            <span class="text-[10px] text-[#686868] ml-1">({{ $varCount }} var.)</span>
                        </div>

                        <div class="text-right">
                            <span class="text-[10px] uppercase font-bold text-[#686868] block">Stock</span>
                            @if (! $available)
                                <span class="font-bold text-[#686868]">Out of Stock</span>
                            @elseif ($untracked)
                                <span class="font-bold text-[#686868]">Not tracked</span>
                            @elseif ($isLow)
                                <span class="font-bold text-[#D58700]">Low · {{ $stock->sum() }}</span>
                            @else
                                <span class="font-bold text-[#007D49]">In Stock · {{ $stock->sum() }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Bottom Row: Class, Feature & Details Button -->
                    <div class="flex items-center justify-between gap-2 pt-1">
                        <select wire:change="updateItemClass({{ $p->id }}, $event.target.value)"
                                class="px-2 py-1.5 rounded-lg border border-[#DEDEDE] text-xs font-semibold text-[#111111] bg-white">
                            <option value="perishable" {{ $p->item_class === 'perishable' ? 'selected' : '' }}>Perishable (24h)</option>
                            <option value="non_perishable" {{ $p->item_class === 'non_perishable' ? 'selected' : '' }}>Grocery (24h)</option>
                            <option value="non_food" {{ $p->item_class === 'non_food' ? 'selected' : '' }}>Non-Food (7d return)</option>
                        </select>

                        <div class="flex items-center gap-2">
                            <button type="button"
                                    wire:click="toggleFeatured({{ $p->id }})"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-[#EEEEEE] text-xs font-bold transition-colors {{ $p->is_featured ? 'bg-[#FFF9EA] border-[#FFE58F] text-[#D58700]' : 'bg-white text-[#686868] hover:bg-[#FAFAFA]' }}">
                                <svg class="w-4 h-4 {{ $p->is_featured ? 'text-[#FFD21F] fill-[#FFD21F]' : 'text-[#9E9E9E]' }}" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <span>{{ $p->is_featured ? 'Featured' : 'Feature' }}</span>
                            </button>

                            <button type="button"
                                    wire:click="viewProduct({{ $p->id }})"
                                    class="px-2.5 py-1.5 rounded-lg bg-[#111111] text-white text-xs font-bold hover:bg-[#222222] transition-colors">
                                Details
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-4 py-12 text-center text-sm text-[#686868]">
                    No products found matching your search and filter criteria.
                </div>
            @endforelse
        </div>

        <!-- 2. Desktop Table View (Visible on md+) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-sm min-w-[800px]">
                <thead class="bg-[#FAFAFA] border-b border-[#EEEEEE] text-[11px] font-extrabold uppercase tracking-wider text-[#686868]">
                    <tr>
                        <th class="px-5 py-3.5">Product Details</th>
                        <th class="px-4 py-3.5">Category & Origin</th>
                        <th class="px-4 py-3.5">Variations / Pricing</th>
                        <th class="px-4 py-3.5">Availability</th>
                        <th class="px-4 py-3.5">Policy Class</th>
                        <th class="px-4 py-3.5 text-center">Featured</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EEEEEE]">
                    @forelse ($products as $p)
                        <tr class="hover:bg-[#FAFAFA]/60 transition-colors {{ $selectedProductId === $p->id ? 'bg-[#FFF0EC]/30' : '' }}">
                            <!-- Product Column -->
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="cursor-pointer shrink-0" wire:click="viewProduct({{ $p->id }})">
                                        @if ($p->image)
                                            <img src="{{ asset($p->image) }}" alt="{{ $p->name }}" class="w-11 h-11 rounded-lg object-cover bg-[#F6F6F6] border border-[#EEEEEE] hover:opacity-90 transition-opacity">
                                        @else
                                            <div class="w-11 h-11 rounded-lg bg-[#F6F6F6] border border-[#EEEEEE] flex items-center justify-center text-xs font-bold text-[#9E9E9E]">
                                                No img
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <button type="button"
                                                wire:click="viewProduct({{ $p->id }})"
                                                class="text-left font-bold text-[#111111] hover:text-[#DF2600] transition-colors truncate max-w-xs block">
                                            {{ $p->name }}
                                        </button>
                                        @if ($p->name_fr)
                                            <span class="block text-xs text-[#0055D4] font-medium truncate max-w-xs">
                                                FR: {{ $p->name_fr }}
                                            </span>
                                        @endif
                                        <span class="block text-[11px] text-[#686868] font-mono truncate">
                                            {{ $p->square_id ? 'Square: '.$p->square_id : 'Not linked to Square (demo)' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Category & Origin Column -->
                            <td class="px-4 py-4">
                                @if ($p->category)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-[#F6F6F6] text-[#111111]">
                                        {{ $p->category->parent?->name ?? $p->category->name }}
                                    </span>
                                    @if ($p->category->parent)
                                        <span class="block text-[11px] text-[#686868] mt-0.5">{{ $p->category->name }}</span>
                                    @endif
                                @else
                                    <span class="text-xs text-[#9E9E9E] italic">Uncategorized</span>
                                @endif
                                @if ($p->country)
                                    <span class="block text-[11px] text-[#686868] mt-0.5 font-medium">🌍 {{ $p->country->name }}</span>
                                @endif
                            </td>

                            <!-- Variations / Pricing -->
                            <td class="px-4 py-4">
                                @php
                                    $minPrice = $p->variations->min('price_cents');
                                    $maxPrice = $p->variations->max('price_cents');
                                    $varCount = $p->variations->count();
                                @endphp
                                <div class="font-bold text-[#DF2600]">
                                    @if ($minPrice !== null)
                                        @if ($minPrice === $maxPrice)
                                            {{ money($minPrice) }}
                                        @else
                                            {{ money($minPrice) }} – {{ money($maxPrice) }}
                                        @endif
                                    @else
                                        <span class="text-xs text-[#9E9E9E]">Price set at till</span>
                                    @endif
                                </div>
                                <span class="text-xs text-[#686868]">{{ $varCount }} variation{{ $varCount > 1 ? 's' : '' }}</span>
                            </td>

                            <!-- Availability Badge -->
                            <td class="px-4 py-4">
                                @php
                                    $stock = $p->variations->pluck('stock_quantity');
                                    $untracked = $stock->contains(null);
                                    $available = $untracked || $stock->contains(fn ($q) => $q > 0);
                                    $isLow = ! $untracked && $stock->contains(fn ($q) => $q > 0 && $q <= $low);
                                @endphp

                                @if (! $available)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-[#F6F6F6] text-[#686868]">Out of Stock</span>
                                @elseif ($untracked)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#F6F6F6] text-[#686868]" title="Stock tracking is off in Square, so it always shows as available">Not tracked</span>
                                @elseif ($isLow)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-[#FFF9EA] text-[#D58700]">Low Stock · {{ $stock->sum() }}</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-[#DAF4E7] text-[#007D49]">In Stock · {{ $stock->sum() }}</span>
                                @endif
                            </td>

                            <!-- Item Class (Returns Policy) -->
                            <td class="px-4 py-4">
                                <select wire:change="updateItemClass({{ $p->id }}, $event.target.value)"
                                        class="px-2 py-1 rounded-lg border border-[#DEDEDE] text-xs font-semibold text-[#111111] bg-white focus:outline-none focus:border-[#DF2600]">
                                    <option value="perishable" {{ $p->item_class === 'perishable' ? 'selected' : '' }}>Perishable (24h)</option>
                                    <option value="non_perishable" {{ $p->item_class === 'non_perishable' ? 'selected' : '' }}>Grocery (24h)</option>
                                    <option value="non_food" {{ $p->item_class === 'non_food' ? 'selected' : '' }}>Non-Food (7d)</option>
                                </select>
                            </td>

                            <!-- Featured Toggle -->
                            <td class="px-4 py-4 text-center">
                                <button type="button"
                                        wire:click="toggleFeatured({{ $p->id }})"
                                        title="{{ $p->is_featured ? 'Remove from Featured' : 'Mark as Featured' }}"
                                        class="p-1 rounded-lg hover:bg-[#F6F6F6] transition-colors {{ $p->is_featured ? 'text-[#FFD21F]' : 'text-[#DEDEDE] hover:text-[#9E9E9E]' }}">
                                    <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                </button>
                            </td>

                            <!-- Status Column -->
                            <td class="px-4 py-4 text-center">
                                @if (! $p->is_active)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-[#F6F6F6] text-[#9E9E9E]" title="Removed, archived or unpriced in Square">Off in Square</span>
                                @else
                                    <button type="button"
                                            wire:click="toggleHidden({{ $p->id }})"
                                            title="{{ $p->is_hidden ? 'Show on the website' : 'Hide from the website' }}"
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold transition-colors {{ $p->is_hidden ? 'bg-[#F6F6F6] text-[#686868]' : 'bg-[#DAF4E7] text-[#007D49]' }}">
                                        {{ $p->is_hidden ? 'Hidden' : 'Live' }}
                                    </button>
                                @endif
                            </td>

                            <!-- Review / Details Action -->
                            <td class="px-4 py-4 text-right">
                                <button type="button"
                                        wire:click="viewProduct({{ $p->id }})"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl border border-[#DEDEDE] text-xs font-bold text-[#111111] hover:bg-[#111111] hover:text-white hover:border-[#111111] transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <span>Details</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-sm text-[#686868]">
                                No products found matching your search and filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-4 sm:p-5 border-t border-[#EEEEEE] bg-[#FAFAFA]">
            {{ $products->links() }}
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- SLIDE-OVER PRODUCT DETAILS & FRENCH TRANSLATIONS DRAWER        -->
    <!-- ============================================================== -->
    @if ($selectedProduct)
        <div class="fixed inset-0 z-50 overflow-hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
            <!-- Background Backdrop -->
            <div class="fixed inset-0 bg-[#111111]/40 backdrop-blur-xs transition-opacity"
                 wire:click="closeDrawer"></div>

            <div class="fixed inset-y-0 right-0 max-w-full flex pl-6 sm:pl-10">
                <div class="w-screen max-w-2xl bg-white shadow-2xl flex flex-col h-full border-l border-[#EEEEEE]">
                    <!-- Drawer Header -->
                    <div class="p-5 sm:p-6 border-b border-[#EEEEEE] bg-[#FAFAFA] flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-3 min-w-0">
                            @if ($selectedProduct->image)
                                <img src="{{ asset($selectedProduct->image) }}" alt="{{ $selectedProduct->name }}" class="w-12 h-12 rounded-xl object-cover bg-white border border-[#EEEEEE] shrink-0">
                            @else
                                <div class="w-12 h-12 rounded-xl bg-white border border-[#EEEEEE] flex items-center justify-center text-xs font-bold text-[#9E9E9E] shrink-0">
                                    No img
                                </div>
                            @endif
                            <div class="min-w-0">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-[#DF2600]">Product Inspection & Merchandising</span>
                                <h3 class="text-base sm:text-lg font-black text-[#111111] truncate" id="slide-over-title">{{ $selectedProduct->name }}</h3>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-xs font-mono text-[#686868]">Slug: {{ $selectedProduct->slug }}</span>
                                    @if ($selectedProduct->square_id)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-[#007D49] bg-[#DAF4E7] px-2 py-0.5 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#007D49]"></span> Square Linked
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <button type="button"
                                wire:click="closeDrawer"
                                class="p-2 rounded-xl text-[#686868] hover:text-[#111111] hover:bg-[#EEEEEE] transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Flash Notification inside Drawer -->
                    @if (session()->has('success'))
                        <div class="mx-5 sm:mx-6 mt-4 p-3.5 rounded-xl bg-[#DAF4E7] border border-[#B3E8CE] text-[#007D49] text-xs sm:text-sm font-bold flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    <!-- Drawer Scrollable Content -->
                    <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-6">
                        <!-- SECTION 1: EDITABLE METADATA & FRENCH FIELDS -->
                        <div class="bg-white rounded-2xl border border-[#EEEEEE] p-5 shadow-xs space-y-4">
                            <div class="flex items-center justify-between border-b border-[#EEEEEE] pb-3">
                                <div>
                                    <h4 class="font-extrabold text-sm text-[#111111]">Product Content & Localization</h4>
                                    <p class="text-xs text-[#686868] mt-0.5">Manage bilingual catalog descriptions and store categorization.</p>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#EBF3FF] text-[#0055D4]">EN / FR Bilingual</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- English Name (Read-only from Square) -->
                                <div>
                                    <label class="block text-xs font-bold text-[#686868] mb-1">
                                        Product Name (English · Square Source)
                                    </label>
                                    <input type="text"
                                           value="{{ $selectedProduct->name }}"
                                           disabled
                                           class="w-full px-3 py-2 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs font-bold text-[#111111] cursor-not-allowed">
                                </div>

                                <!-- French Name (Editable) -->
                                <div>
                                    <label class="block text-xs font-bold text-[#111111] mb-1">
                                        French Name (Nom en français) <span class="text-[#0055D4]">*</span>
                                    </label>
                                    <input type="text"
                                           wire:model="editNameFr"
                                           placeholder="e.g. Igname Blanche du Nigéria"
                                           class="w-full px-3 py-2 rounded-xl border border-[#DEDEDE] text-xs font-semibold text-[#111111] focus:outline-none focus:border-[#DF2600] focus:ring-1 focus:ring-[#DF2600]">
                                    @error('editNameFr') <span class="text-[11px] text-[#DF2600] font-semibold mt-0.5 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Category & Country -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-[#111111] mb-1">Storefront Category</label>
                                    <select wire:model="editCategoryId"
                                            class="w-full px-3 py-2 rounded-xl border border-[#DEDEDE] text-xs font-semibold text-[#111111] focus:outline-none focus:border-[#DF2600]">
                                        <option value="">No Category</option>
                                        @foreach ($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                            @foreach ($cat->children as $child)
                                                <option value="{{ $child->id }}">&nbsp;&nbsp;– {{ $child->name }}</option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                    @error('editCategoryId') <span class="text-[11px] text-[#DF2600] font-semibold mt-0.5 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-[#111111] mb-1">Country of Heritage</label>
                                    <select wire:model="editCountryId"
                                            class="w-full px-3 py-2 rounded-xl border border-[#DEDEDE] text-xs font-semibold text-[#111111] focus:outline-none focus:border-[#DF2600]">
                                        <option value="">No Country Tag</option>
                                        @foreach ($countries as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('editCountryId') <span class="text-[11px] text-[#DF2600] font-semibold mt-0.5 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Policy Class & Visibility Flags -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1">
                                <div>
                                    <label class="block text-xs font-bold text-[#111111] mb-1">Returns Policy Class</label>
                                    <select wire:model="editItemClass"
                                            class="w-full px-3 py-2 rounded-xl border border-[#DEDEDE] text-xs font-semibold text-[#111111] focus:outline-none focus:border-[#DF2600]">
                                        <option value="perishable">Perishable (24h claim)</option>
                                        <option value="non_perishable">Grocery (24h claim)</option>
                                        <option value="non_food">Non-Food (7d return)</option>
                                    </select>
                                </div>

                                <div class="flex items-center gap-2 pt-5">
                                    <input type="checkbox"
                                           id="editIsFeatured"
                                           wire:model="editIsFeatured"
                                           class="w-4 h-4 rounded text-[#DF2600] focus:ring-[#DF2600] border-gray-300">
                                    <label for="editIsFeatured" class="text-xs font-bold text-[#111111] cursor-pointer">
                                        Feature on Homepage
                                    </label>
                                </div>

                                <div class="flex items-center gap-2 pt-5">
                                    <input type="checkbox"
                                           id="editIsHidden"
                                           wire:model="editIsHidden"
                                           class="w-4 h-4 rounded text-[#DF2600] focus:ring-[#DF2600] border-gray-300">
                                    <label for="editIsHidden" class="text-xs font-bold text-[#111111] cursor-pointer">
                                        Hide from Storefront
                                    </label>
                                </div>
                            </div>

                            <!-- English Description -->
                            <div>
                                <label class="block text-xs font-bold text-[#111111] mb-1">English Product Description</label>
                                <textarea wire:model="editDescription"
                                          rows="3"
                                          placeholder="Provide detailed product origin, preparation advice, or ingredients list..."
                                          class="w-full px-3 py-2 rounded-xl border border-[#DEDEDE] text-xs text-[#111111] placeholder-[#9E9E9E] focus:outline-none focus:border-[#DF2600] focus:ring-1 focus:ring-[#DF2600]"></textarea>
                                @error('editDescription') <span class="text-[11px] text-[#DF2600] font-semibold mt-0.5 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- French Description -->
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-bold text-[#111111]">
                                        French Product Description (Description en français)
                                    </label>
                                    <span class="text-[10px] text-[#686868]">Used when customer switches language to Français</span>
                                </div>
                                <textarea wire:model="editDescriptionFr"
                                          rows="3"
                                          placeholder="Fournissez la description du produit, les conseils de préparation ou la provenance en français..."
                                          class="w-full px-3 py-2 rounded-xl border border-[#DEDEDE] text-xs text-[#111111] placeholder-[#9E9E9E] focus:outline-none focus:border-[#DF2600] focus:ring-1 focus:ring-[#DF2600]"></textarea>
                                @error('editDescriptionFr') <span class="text-[11px] text-[#DF2600] font-semibold mt-0.5 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Save Button -->
                            <div class="flex justify-end pt-2">
                                <button type="button"
                                        wire:click="saveProduct"
                                        wire:loading.attr="disabled"
                                        class="px-5 py-2.5 rounded-xl bg-[#DF2600] text-white text-xs font-extrabold hover:bg-[#B82000] transition-colors shadow-xs flex items-center gap-2">
                                    <span wire:loading.remove wire:target="saveProduct">Save Product Changes</span>
                                    <span wire:loading wire:target="saveProduct">Saving Updates...</span>
                                </button>
                            </div>
                        </div>

                        <!-- SECTION 2: VARIATIONS & INVENTORY TABLE -->
                        <div class="bg-white rounded-2xl border border-[#EEEEEE] p-5 shadow-xs space-y-4">
                            <div class="flex items-center justify-between border-b border-[#EEEEEE] pb-3">
                                <div>
                                    <h4 class="font-extrabold text-sm text-[#111111]">Pack Variations & Stock ({{ $selectedProduct->variations->count() }})</h4>
                                    <p class="text-xs text-[#686868] mt-0.5">Real-time prices and stock levels managed in Square POS.</p>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-[#FAFAFA] border-b border-[#EEEEEE] text-[10px] font-extrabold uppercase tracking-wider text-[#686868]">
                                        <tr>
                                            <th class="px-3 py-2.5">Variation Name</th>
                                            <th class="px-3 py-2.5">SKU</th>
                                            <th class="px-3 py-2.5">Price</th>
                                            <th class="px-3 py-2.5">Inventory</th>
                                            <th class="px-3 py-2.5 text-right">Square ID</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#EEEEEE]">
                                        @forelse ($selectedProduct->variations as $var)
                                            <tr>
                                                <td class="px-3 py-3 font-bold text-[#111111]">
                                                    {{ $var->name ?: 'Standard Pack' }}
                                                </td>
                                                <td class="px-3 py-3 font-mono text-[#686868]">
                                                    {{ $var->sku ?: 'No SKU' }}
                                                </td>
                                                <td class="px-3 py-3 font-bold text-[#DF2600]">
                                                    {{ money($var->price_cents) }}
                                                    @if ($var->previous_price_cents && $var->previous_price_cents > $var->price_cents)
                                                        <span class="text-[10px] text-[#9E9E9E] line-through ml-1">{{ money($var->previous_price_cents) }}</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-3">
                                                    @if ($var->stock_quantity === null)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#F6F6F6] text-[#686868]">Untracked</span>
                                                    @elseif ($var->stock_quantity <= 0)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#FFF0EC] text-[#DF2600]">Out of Stock</span>
                                                    @elseif ($var->stock_quantity <= $low)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#FFF9EA] text-[#D58700]">Low Stock ({{ $var->stock_quantity }})</span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#DAF4E7] text-[#007D49]">{{ $var->stock_quantity }} in stock</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-3 text-right font-mono text-[10px] text-[#9E9E9E]">
                                                    {{ $var->square_id ?: '—' }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-3 py-6 text-center text-[#9E9E9E] italic">
                                                    No variations found for this product.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- SECTION 3: CONNECTED KITCHEN RECIPES -->
                        @if ($selectedProduct->recipes->isNotEmpty())
                            <div class="bg-white rounded-2xl border border-[#EEEEEE] p-5 shadow-xs space-y-3">
                                <h4 class="font-extrabold text-sm text-[#111111]">Used in Kitchen Recipes ({{ $selectedProduct->recipes->count() }})</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    @foreach ($selectedProduct->recipes as $rc)
                                        <div class="flex items-center gap-2.5 p-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA]">
                                            @if ($rc->thumbnail || $rc->image)
                                                <img src="{{ asset($rc->thumbnail ?: $rc->image) }}" alt="{{ $rc->name }}" class="w-8 h-8 rounded-lg object-cover">
                                            @endif
                                            <div class="min-w-0">
                                                <span class="block text-xs font-bold text-[#111111] truncate">{{ $rc->name }}</span>
                                                <span class="block text-[10px] text-[#686868]">{{ ucfirst($rc->cuisine ?? 'West African') }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- SECTION 4: SQUARE INTEGRATION & AUDIT METADATA -->
                        <div class="p-4 rounded-xl bg-[#FAFAFA] border border-[#EEEEEE] space-y-2 text-xs text-[#686868]">
                            <div class="flex items-center justify-between font-mono text-[11px]">
                                <span>Square Item ID:</span>
                                <span class="font-bold text-[#111111]">{{ $selectedProduct->square_id ?: 'Not linked' }}</span>
                            </div>
                            <div class="flex items-center justify-between text-[11px]">
                                <span>First Seen in Catalog:</span>
                                <span>{{ $selectedProduct->first_seen_at ? $selectedProduct->first_seen_at->format('M j, Y g:i A') : 'Unknown' }}</span>
                            </div>
                            <div class="flex items-center justify-between text-[11px]">
                                <span>Last Database Update:</span>
                                <span>{{ $selectedProduct->updated_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
