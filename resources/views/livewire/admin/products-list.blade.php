<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-extrabold text-[#111111] tracking-tight">Products & Inventory Overview</h2>
            <p class="mt-1 text-sm text-[#686868]">
                Catalog synchronized live from Square POS. Configure product classes for returns policy, feature items, and monitor stock levels.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#DAF4E7] text-xs font-bold text-[#007D49]">
                <span class="w-2 h-2 rounded-full bg-[#007D49]"></span>
                Square Sync: Live
            </span>
        </div>
    </div>

    <!-- Quick Stat KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-[#686868]">Total Products</span>
            <div class="mt-2 text-2xl font-extrabold text-[#111111]">{{ number_format($counts['total']) }}</div>
            <span class="text-[11px] text-[#686868]">Synced from Square</span>
        </div>

        <div class="p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-[#686868]">Active Online</span>
            <div class="mt-2 text-2xl font-extrabold text-[#007D49]">{{ number_format($counts['active']) }}</div>
            <span class="text-[11px] text-[#686868]">Visible on storefront</span>
        </div>

        <div class="p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-[#686868]">Featured Items</span>
            <div class="mt-2 text-2xl font-extrabold text-[#DF2600]">{{ number_format($counts['featured']) }}</div>
            <span class="text-[11px] text-[#686868]">Homepage spotlights</span>
        </div>

        <div class="p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-[#D58700]">Untracked Stock</span>
            <div class="mt-2 text-2xl font-extrabold text-[#D58700]">{{ number_format($counts['untracked']) }}</div>
            <span class="text-[11px] text-[#686868]">Treated as Out of Stock</span>
        </div>
    </div>

    <!-- Filter Bar Container -->
    <div class="bg-white rounded-2xl border border-[#EEEEEE] p-5 shadow-xs space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div class="md:col-span-1 relative">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search products or SKU..."
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
                    <option value="non_perishable">Non-perishable Groceries (24h claim)</option>
                    <option value="non_food">Non-Food Merchandise (7d return)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-2xl border border-[#EEEEEE] shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-[#FAFAFA] border-b border-[#EEEEEE] text-[11px] font-extrabold uppercase tracking-wider text-[#686868]">
                    <tr>
                        <th class="px-5 py-3.5">Product</th>
                        <th class="px-4 py-3.5">Category</th>
                        <th class="px-4 py-3.5">Variations / Pricing</th>
                        <th class="px-4 py-3.5">Availability</th>
                        <th class="px-4 py-3.5">Policy Class</th>
                        <th class="px-4 py-3.5 text-center">Featured</th>
                        <th class="px-4 py-3.5 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EEEEEE]">
                    @forelse ($products as $p)
                        <tr class="hover:bg-[#FAFAFA]/40 transition-colors">
                            <!-- Product Column -->
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    @if ($p->image_url)
                                        <img src="{{ $p->image_url }}" alt="{{ $p->name }}" class="w-11 h-11 rounded-lg object-cover bg-[#F6F6F6] shrink-0 border border-[#EEEEEE]">
                                    @else
                                        <div class="w-11 h-11 rounded-lg bg-[#F6F6F6] border border-[#EEEEEE] flex items-center justify-center text-xs font-bold text-[#9E9E9E] shrink-0">
                                            No img
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <span class="block font-bold text-[#111111] truncate max-w-xs">{{ $p->name }}</span>
                                        <span class="block text-xs text-[#686868] font-mono truncate">ID: {{ $p->square_item_id }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Category Column -->
                            <td class="px-4 py-4">
                                @if ($p->category)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-[#F6F6F6] text-[#111111]">
                                        {{ $p->category->name }}
                                    </span>
                                    @if ($p->subCategory)
                                        <span class="block text-[11px] text-[#686868] mt-0.5">{{ $p->subCategory->name }}</span>
                                    @endif
                                @else
                                    <span class="text-xs text-[#9E9E9E] italic">Uncategorized</span>
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
                                    $hasUntracked = $p->variations->contains(fn ($v) => !$v->tracks_inventory);
                                    $isOrderable = $p->variations->contains(fn ($v) => $v->isOrderable());
                                    $hasLow = $p->variations->contains(fn ($v) => $v->availabilityState() === 'low');
                                @endphp

                                @if ($isOrderable)
                                    @if ($hasLow)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-[#FFF9EA] text-[#D58700]">
                                            Low Stock
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-[#DAF4E7] text-[#007D49]">
                                            In Stock
                                        </span>
                                    @endif
                                @else
                                    @if ($hasUntracked)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#F6F6F6] text-[#686868]" title="Stock tracking off in Square = treated as Out of Stock">
                                            Untracked (Out)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-[#F6F6F6] text-[#686868]">
                                            Out of Stock
                                        </span>
                                    @endif
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
                            <td class="px-4 py-4 text-right">
                                <button type="button"
                                        wire:click="toggleStatus({{ $p->id }})"
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold transition-colors {{ $p->status === 'active' ? 'bg-[#DAF4E7] text-[#007D49]' : 'bg-[#F6F6F6] text-[#686868]' }}">
                                    {{ ucfirst($p->status) }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-[#686868]">
                                No products found matching your search and filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-5 border-t border-[#EEEEEE] bg-[#FAFAFA]">
            {{ $products->links() }}
        </div>
    </div>
</div>
