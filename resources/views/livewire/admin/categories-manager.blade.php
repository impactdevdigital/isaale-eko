<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-extrabold text-[#111111] tracking-tight">Categories & Sub-Categories</h2>
            <p class="mt-1 text-sm text-[#686868]">
                Catalog hierarchy synchronized with Square POS. Manage parent departments, nested sub-categories, Square IDs, and storefront featured visibility.
            </p>
        </div>

        <div class="flex items-center gap-3">
            @if ($lastSync)
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#DAF4E7] text-xs font-bold text-[#007D49]">
                    <span class="w-2 h-2 rounded-full bg-[#007D49]"></span>
                    Square sync: {{ \Illuminate\Support\Carbon::parse($lastSync)->diffForHumans() }}
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#FFF9EA] text-xs font-bold text-[#D58700]">
                    <span class="w-2 h-2 rounded-full bg-[#D58700]"></span>
                    Square: Sandbox Mode Active
                </span>
            @endif

            <button type="button"
                    wire:click="openCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#DF2600] text-white text-sm font-bold shadow-sm hover:bg-[#b82000] transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                New Category
            </button>
        </div>
    </div>

    <!-- Flash Notifications -->
    @if (session('success'))
        <div class="p-4 rounded-xl bg-[#DAF4E7] border border-[#007D49]/20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-[#007D49]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-sm font-bold text-[#007D49]">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 rounded-xl bg-[#FFF0EC] border border-[#DF2600]/20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-[#DF2600]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span class="text-sm font-bold text-[#DF2600]">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <div class="p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-[#686868]">Total Categories</span>
            <div class="mt-2 text-2xl font-extrabold text-[#111111]">{{ number_format($counts['total']) }}</div>
            <span class="text-[11px] text-[#686868]">All levels combined</span>
        </div>

        <div class="p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-[#686868]">Root Departments</span>
            <div class="mt-2 text-2xl font-extrabold text-[#DF2600]">{{ number_format($counts['roots']) }}</div>
            <span class="text-[11px] text-[#686868]">Parent departments</span>
        </div>

        <div class="p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-[#686868]">Sub-Categories</span>
            <div class="mt-2 text-2xl font-extrabold text-[#007D49]">{{ number_format($counts['subcategories']) }}</div>
            <span class="text-[11px] text-[#686868]">Nested under parents</span>
        </div>

        <div class="p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-[#686868]">Featured Online</span>
            <div class="mt-2 text-2xl font-extrabold text-[#D58700]">{{ number_format($counts['featured']) }}</div>
            <span class="text-[11px] text-[#686868]">Homepage spotlights</span>
        </div>

        <div class="p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs col-span-2 md:col-span-1">
            <span class="text-xs font-bold uppercase tracking-wider text-[#686868]">Synced with Square</span>
            <div class="mt-2 text-2xl font-extrabold text-[#111111]">{{ number_format($counts['synced']) }}</div>
            <span class="text-[11px] text-[#686868]">Has POS Square ID</span>
        </div>
    </div>

    <!-- Filter & View Switcher Bar -->
    <div class="bg-white rounded-2xl border border-[#EEEEEE] p-5 shadow-xs space-y-4">
        <!-- View Mode Navigation Tabs -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[#EEEEEE]">
            <div class="flex items-center gap-1.5 p-1 bg-[#FAFAFA] rounded-xl border border-[#EEEEEE] self-start">
                <button type="button"
                        wire:click="$set('viewMode', 'hierarchy')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-colors {{ $viewMode === 'hierarchy' ? 'bg-white text-[#DF2600] shadow-xs' : 'text-[#686868] hover:text-[#111111]' }}">
                    Hierarchical View (Square POS)
                </button>
                <button type="button"
                        wire:click="$set('viewMode', 'all_sub')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-colors {{ $viewMode === 'all_sub' ? 'bg-white text-[#DF2600] shadow-xs' : 'text-[#686868] hover:text-[#111111]' }}">
                    All Sub-Categories
                </button>
                <button type="button"
                        wire:click="$set('viewMode', 'parents')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-colors {{ $viewMode === 'parents' ? 'bg-white text-[#DF2600] shadow-xs' : 'text-[#686868] hover:text-[#111111]' }}">
                    Parent Departments
                </button>
            </div>

            @if ($viewMode === 'hierarchy')
                <div class="flex items-center gap-2">
                    <button type="button"
                            wire:click="expandAll"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold text-[#686868] hover:text-[#111111] hover:bg-[#F6F6F6] transition-colors">
                        Expand All
                    </button>
                    <span class="text-[#EEEEEE]">|</span>
                    <button type="button"
                            wire:click="collapseAll"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold text-[#686868] hover:text-[#111111] hover:bg-[#F6F6F6] transition-colors">
                        Collapse All
                    </button>
                </div>
            @endif
        </div>

        <!-- Filter Controls -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Search -->
            <div class="relative">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search name, slug, or Square ID..."
                       class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                <svg class="w-4 h-4 text-[#686868] absolute left-3 top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                @if ($search)
                    <button type="button"
                            wire:click="$set('search', '')"
                            class="absolute right-3 top-3 text-[#686868] hover:text-[#111111]">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>

            <!-- Parent Department Filter -->
            <div>
                <select wire:model.live="parentFilter"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                    <option value="">All Departments</option>
                    @foreach ($rootDepartments as $root)
                        <option value="{{ $root->id }}">{{ $root->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Featured Filter -->
            <div>
                <select wire:model.live="featuredFilter"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                    <option value="">All Visibility</option>
                    <option value="featured">Featured Online Only</option>
                    <option value="standard">Standard (Not Featured)</option>
                </select>
            </div>

            <!-- Square Sync Filter -->
            <div>
                <select wire:model.live="syncFilter"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                    <option value="">All Sync States</option>
                    <option value="synced">Synced with Square</option>
                    <option value="local">Local Only / Unsynced</option>
                </select>
            </div>
        </div>
    </div>

    <!-- TAB 1: Hierarchical View (Square POS Structure) -->
    @if ($viewMode === 'hierarchy')
        <div class="space-y-4">
            @forelse ($categories as $parent)
                @php
                    $isExpanded = in_array($parent->id, $expandedParents, true);
                    $subCount = $parent->children->count();
                    $directProducts = $parent->products_count;
                    $totalProducts = $parent->allProductsCount();
                @endphp

                <div class="bg-white rounded-2xl border border-[#EEEEEE] overflow-hidden shadow-xs transition-shadow hover:shadow-sm">
                    <!-- Parent Department Header Row -->
                    <div class="p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 {{ $isExpanded ? 'bg-[#FAFAFA] border-b border-[#EEEEEE]' : '' }}">
                        <div class="flex items-center gap-3.5">
                            <button type="button"
                                    wire:click="toggleExpand({{ $parent->id }})"
                                    class="w-8 h-8 rounded-lg bg-white border border-[#EEEEEE] flex items-center justify-center text-[#686868] hover:text-[#111111] hover:border-[#111111] transition-colors shrink-0">
                                <svg class="w-4 h-4 transform transition-transform {{ $isExpanded ? 'rotate-90' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>

                            <div class="flex items-center gap-2.5">
                                <span class="text-2xl">{{ $parent->emoji ?: '📁' }}</span>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-base font-extrabold text-[#111111]">{{ $parent->name }}</h3>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-extrabold uppercase tracking-wider bg-[#FFF0EC] text-[#DF2600]">
                                            Department
                                        </span>
                                    </div>
                                    <span class="text-xs text-[#686868] font-mono">/shop?category={{ $parent->slug }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 flex-wrap md:justify-end">
                            <!-- Square POS ID Badge -->
                            @if ($parent->square_id)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#FAFAFA] border border-[#EEEEEE] text-[11px] font-mono font-semibold text-[#111111]"
                                      title="Square POS Category Object ID">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#007D49]"></span>
                                    {{ $parent->square_id }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-[#F6F6F6] text-[#686868]">
                                    Local Only
                                </span>
                            @endif

                            <!-- Subcategories Count Badge -->
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-[#F6F6F6] text-xs font-bold text-[#111111]">
                                {{ $subCount }} sub-{{ Str::plural('category', $subCount) }}
                            </span>

                            <!-- Total Products Count Badge & Link -->
                            <a href="{{ route('admin.products', ['categoryFilter' => $parent->id]) }}"
                               class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#DAF4E7] text-xs font-bold text-[#007D49] hover:bg-[#c2eddb] transition-colors"
                               title="Click to view products in this department and its sub-categories">
                                <span>{{ $totalProducts }} products</span>
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>

                            <!-- Featured Toggle -->
                            <button type="button"
                                    wire:click="toggleFeatured({{ $parent->id }})"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold transition-colors {{ $parent->is_featured ? 'bg-[#FFF0EC] text-[#DF2600]' : 'bg-[#FAFAFA] text-[#686868] hover:bg-[#EEEEEE]' }}">
                                <span class="w-2 h-2 rounded-full {{ $parent->is_featured ? 'bg-[#DF2600]' : 'bg-[#686868]' }}"></span>
                                {{ $parent->is_featured ? 'Featured' : 'Standard' }}
                            </button>

                            <!-- Actions -->
                            <div class="flex items-center gap-1.5 border-l border-[#EEEEEE] pl-2">
                                <button type="button"
                                        wire:click="openCreateModal({{ $parent->id }})"
                                        class="p-1.5 rounded-lg text-[#686868] hover:text-[#007D49] hover:bg-[#DAF4E7] transition-colors"
                                        title="Add Sub-category under {{ $parent->name }}">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                                <button type="button"
                                        wire:click="openEditModal({{ $parent->id }})"
                                        class="p-1.5 rounded-lg text-[#686868] hover:text-[#111111] hover:bg-[#EEEEEE] transition-colors"
                                        title="Edit Department">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button type="button"
                                        wire:click="confirmDelete({{ $parent->id }})"
                                        class="p-1.5 rounded-lg text-[#686868] hover:text-[#DF2600] hover:bg-[#FFF0EC] transition-colors"
                                        title="Delete Department">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Nested Sub-Categories Table (Square POS Children) -->
                    @if ($isExpanded)
                        <div class="divide-y divide-[#EEEEEE]">
                            @if ($parent->children->isEmpty())
                                <div class="p-6 text-center text-xs text-[#686868] bg-[#FAFAFA]">
                                    No sub-categories defined under <span class="font-bold text-[#111111]">{{ $parent->name }}</span>.
                                    <button type="button" wire:click="openCreateModal({{ $parent->id }})" class="ml-2 text-[#DF2600] font-bold hover:underline">
                                        + Add the first sub-category
                                    </button>
                                </div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left text-sm">
                                        <thead class="bg-[#FAFAFA] text-[11px] font-extrabold uppercase tracking-wider text-[#686868] border-b border-[#EEEEEE]">
                                            <tr>
                                                <th class="py-2.5 px-6 pl-12">Sub-Category</th>
                                                <th class="py-2.5 px-4">Square POS ID</th>
                                                <th class="py-2.5 px-4 text-center">Assigned Products</th>
                                                <th class="py-2.5 px-4 text-center">Storefront Spotlight</th>
                                                <th class="py-2.5 px-4 text-center">Order</th>
                                                <th class="py-2.5 px-6 text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-[#EEEEEE]">
                                            @foreach ($parent->children as $child)
                                                <tr class="hover:bg-[#FAFAFA]/70 transition-colors">
                                                    <!-- Sub-category Name -->
                                                    <td class="py-3 px-6 pl-12">
                                                        <div class="flex items-center gap-2.5">
                                                            <span class="text-base">{{ $child->emoji ?: '🏷️' }}</span>
                                                            <div>
                                                                <span class="font-extrabold text-[#111111] block">{{ $child->name }}</span>
                                                                <span class="text-[11px] text-[#686868] font-mono">{{ $child->slug }}</span>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <!-- Square ID -->
                                                    <td class="py-3 px-4 font-mono text-xs">
                                                        @if ($child->square_id)
                                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-[#FAFAFA] border border-[#EEEEEE] font-semibold text-[#111111]"
                                                                  title="Square Catalog Object ID">
                                                                <span class="w-1.5 h-1.5 rounded-full bg-[#007D49]"></span>
                                                                {{ $child->square_id }}
                                                            </span>
                                                        @else
                                                            <span class="text-[11px] text-[#686868] italic">Unlinked</span>
                                                        @endif
                                                    </td>

                                                    <!-- Product Count -->
                                                    <td class="py-3 px-4 text-center">
                                                        <a href="{{ route('admin.products', ['categoryFilter' => $child->id]) }}"
                                                           class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold {{ $child->products_count > 0 ? 'bg-[#DAF4E7] text-[#007D49] hover:bg-[#c2eddb]' : 'bg-[#F6F6F6] text-[#686868]' }} transition-colors"
                                                           title="View products in this sub-category">
                                                            {{ $child->products_count }} {{ Str::plural('item', $child->products_count) }}
                                                        </a>
                                                    </td>

                                                    <!-- Featured Toggle -->
                                                    <td class="py-3 px-4 text-center">
                                                        <button type="button"
                                                                wire:click="toggleFeatured({{ $child->id }})"
                                                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold transition-colors {{ $child->is_featured ? 'bg-[#FFF0EC] text-[#DF2600]' : 'bg-[#FAFAFA] text-[#686868] hover:bg-[#EEEEEE]' }}">
                                                            <span class="w-1.5 h-1.5 rounded-full {{ $child->is_featured ? 'bg-[#DF2600]' : 'bg-[#686868]' }}"></span>
                                                            {{ $child->is_featured ? 'Featured' : 'Standard' }}
                                                        </button>
                                                    </td>

                                                    <!-- Sort Order -->
                                                    <td class="py-3 px-4 text-center text-xs font-mono text-[#686868]">
                                                        {{ $child->sort_order }}
                                                    </td>

                                                    <!-- Actions -->
                                                    <td class="py-3 px-6 text-right">
                                                        <div class="flex items-center justify-end gap-1.5">
                                                            <button type="button"
                                                                    wire:click="openEditModal({{ $child->id }})"
                                                                    class="p-1 rounded-lg text-[#686868] hover:text-[#111111] hover:bg-[#EEEEEE] transition-colors"
                                                                    title="Edit Sub-category">
                                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                </svg>
                                                            </button>
                                                            <button type="button"
                                                                    wire:click="confirmDelete({{ $child->id }})"
                                                                    class="p-1 rounded-lg text-[#686868] hover:text-[#DF2600] hover:bg-[#FFF0EC] transition-colors"
                                                                    title="Delete Sub-category">
                                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-12 text-center bg-white rounded-2xl border border-[#EEEEEE]">
                    <div class="w-12 h-12 rounded-full bg-[#FFF0EC] text-[#DF2600] flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-extrabold text-[#111111]">No matching categories found</h3>
                    <p class="text-xs text-[#686868] mt-1">Try adjusting your search terms or filters.</p>
                    <button type="button"
                            wire:click="$set('search', ''); $set('parentFilter', ''); $set('featuredFilter', ''); $set('syncFilter', '');"
                            class="mt-4 px-4 py-2 rounded-xl bg-[#FAFAFA] text-xs font-bold text-[#111111] hover:bg-[#EEEEEE] transition-colors">
                        Reset All Filters
                    </button>
                </div>
            @endforelse
        </div>
    @endif

    <!-- TAB 2: All Sub-Categories Flat Table -->
    @if ($viewMode === 'all_sub')
        <div class="bg-white rounded-2xl border border-[#EEEEEE] overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#FAFAFA] text-[11px] font-extrabold uppercase tracking-wider text-[#686868] border-b border-[#EEEEEE]">
                        <tr>
                            <th class="py-3 px-6">Sub-Category</th>
                            <th class="py-3 px-4">Parent Department</th>
                            <th class="py-3 px-4">Square POS ID</th>
                            <th class="py-3 px-4 text-center">Assigned Products</th>
                            <th class="py-3 px-4 text-center">Spotlight</th>
                            <th class="py-3 px-4 text-center">Sort Order</th>
                            <th class="py-3 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EEEEEE]">
                        @forelse ($subCategories as $sub)
                            <tr class="hover:bg-[#FAFAFA]/70 transition-colors">
                                <td class="py-3.5 px-6">
                                    <div class="flex items-center gap-3">
                                        <span class="text-lg">{{ $sub->emoji ?: '🏷️' }}</span>
                                        <div>
                                            <span class="font-extrabold text-[#111111] block">{{ $sub->name }}</span>
                                            <span class="text-[11px] text-[#686868] font-mono">{{ $sub->slug }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    @if ($sub->parent)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#FAFAFA] border border-[#EEEEEE] text-xs font-semibold text-[#111111]">
                                            <span>{{ $sub->parent->emoji ?: '📁' }}</span>
                                            {{ $sub->parent->name }}
                                        </span>
                                    @else
                                        <span class="text-xs text-[#686868] italic">None (Root)</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 font-mono text-xs">
                                    @if ($sub->square_id)
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-[#FAFAFA] border border-[#EEEEEE] font-semibold text-[#111111]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#007D49]"></span>
                                            {{ $sub->square_id }}
                                        </span>
                                    @else
                                        <span class="text-xs text-[#686868] italic">Unlinked</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <a href="{{ route('admin.products', ['categoryFilter' => $sub->id]) }}"
                                       class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold {{ $sub->products_count > 0 ? 'bg-[#DAF4E7] text-[#007D49] hover:bg-[#c2eddb]' : 'bg-[#F6F6F6] text-[#686868]' }} transition-colors">
                                        {{ $sub->products_count }} {{ Str::plural('item', $sub->products_count) }}
                                    </a>
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <button type="button"
                                            wire:click="toggleFeatured({{ $sub->id }})"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold transition-colors {{ $sub->is_featured ? 'bg-[#FFF0EC] text-[#DF2600]' : 'bg-[#FAFAFA] text-[#686868] hover:bg-[#EEEEEE]' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $sub->is_featured ? 'bg-[#DF2600]' : 'bg-[#686868]' }}"></span>
                                        {{ $sub->is_featured ? 'Featured' : 'Standard' }}
                                    </button>
                                </td>

                                <td class="py-3.5 px-4 text-center text-xs font-mono text-[#686868]">
                                    {{ $sub->sort_order }}
                                </td>

                                <td class="py-3.5 px-6 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button"
                                                wire:click="openEditModal({{ $sub->id }})"
                                                class="p-1.5 rounded-lg text-[#686868] hover:text-[#111111] hover:bg-[#EEEEEE] transition-colors"
                                                title="Edit Sub-category">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button type="button"
                                                wire:click="confirmDelete({{ $sub->id }})"
                                                class="p-1.5 rounded-lg text-[#686868] hover:text-[#DF2600] hover:bg-[#FFF0EC] transition-colors"
                                                title="Delete Sub-category">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-xs text-[#686868]">
                                    No sub-categories matched your filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($subCategories->hasPages())
                <div class="p-4 border-t border-[#EEEEEE]">
                    {{ $subCategories->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- TAB 3: Parent Departments Table -->
    @if ($viewMode === 'parents')
        <div class="bg-white rounded-2xl border border-[#EEEEEE] overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#FAFAFA] text-[11px] font-extrabold uppercase tracking-wider text-[#686868] border-b border-[#EEEEEE]">
                        <tr>
                            <th class="py-3 px-6">Department</th>
                            <th class="py-3 px-4">Square POS ID</th>
                            <th class="py-3 px-4 text-center">Sub-Categories</th>
                            <th class="py-3 px-4 text-center">Direct Products</th>
                            <th class="py-3 px-4 text-center">Total Products (Roll-up)</th>
                            <th class="py-3 px-4 text-center">Spotlight</th>
                            <th class="py-3 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EEEEEE]">
                        @forelse ($parentCategories as $parent)
                            <tr class="hover:bg-[#FAFAFA]/70 transition-colors">
                                <td class="py-3.5 px-6">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xl">{{ $parent->emoji ?: '📁' }}</span>
                                        <div>
                                            <span class="font-extrabold text-[#111111] block">{{ $parent->name }}</span>
                                            <span class="text-[11px] text-[#686868] font-mono">{{ $parent->slug }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 font-mono text-xs">
                                    @if ($parent->square_id)
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-[#FAFAFA] border border-[#EEEEEE] font-semibold text-[#111111]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#007D49]"></span>
                                            {{ $parent->square_id }}
                                        </span>
                                    @else
                                        <span class="text-xs text-[#686868] italic">Unlinked</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-[#F6F6F6] text-xs font-bold text-[#111111]">
                                        {{ $parent->children_count }} sub-categories
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-center text-xs font-semibold text-[#686868]">
                                    {{ $parent->products_count }}
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <a href="{{ route('admin.products', ['categoryFilter' => $parent->id]) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-[#DAF4E7] text-[#007D49] hover:bg-[#c2eddb] transition-colors">
                                        {{ $parent->allProductsCount() }} total
                                    </a>
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <button type="button"
                                            wire:click="toggleFeatured({{ $parent->id }})"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold transition-colors {{ $parent->is_featured ? 'bg-[#FFF0EC] text-[#DF2600]' : 'bg-[#FAFAFA] text-[#686868] hover:bg-[#EEEEEE]' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $parent->is_featured ? 'bg-[#DF2600]' : 'bg-[#686868]' }}"></span>
                                        {{ $parent->is_featured ? 'Featured' : 'Standard' }}
                                    </button>
                                </td>

                                <td class="py-3.5 px-6 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button"
                                                wire:click="openCreateModal({{ $parent->id }})"
                                                class="p-1.5 rounded-lg text-[#686868] hover:text-[#007D49] hover:bg-[#DAF4E7] transition-colors"
                                                title="Add Sub-category">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                        <button type="button"
                                                wire:click="openEditModal({{ $parent->id }})"
                                                class="p-1.5 rounded-lg text-[#686868] hover:text-[#111111] hover:bg-[#EEEEEE] transition-colors"
                                                title="Edit Department">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button type="button"
                                                wire:click="confirmDelete({{ $parent->id }})"
                                                class="p-1.5 rounded-lg text-[#686868] hover:text-[#DF2600] hover:bg-[#FFF0EC] transition-colors"
                                                title="Delete Department">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-xs text-[#686868]">
                                    No parent departments matched your filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($parentCategories->hasPages())
                <div class="p-4 border-t border-[#EEEEEE]">
                    {{ $parentCategories->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- Create / Edit Category Modal -->
    @if ($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="category-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 transition-opacity" wire:click="$set('showModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full" style="background-color: #ffffff;">
                    <!-- Modal Header -->
                    <div class="p-6 border-b border-[#EEEEEE] flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-extrabold text-[#111111]" id="category-modal-title">
                                {{ $editingCategoryId ? 'Edit Category: '.$name : 'Create New Category' }}
                            </h3>
                            <p class="mt-0.5 text-xs text-[#686868]">
                                Define category hierarchy, Square POS linking, and storefront display parameters.
                            </p>
                        </div>
                        <button type="button" wire:click="$set('showModal', false)" class="text-[#686868] hover:text-[#111111] p-1.5 rounded-lg hover:bg-[#FAFAFA]">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Form -->
                    <form wire:submit="save">
                        <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                            <!-- Category Name -->
                            <div>
                                <label class="block text-xs font-bold text-[#111111] mb-1">Category Name *</label>
                                <input type="text"
                                       wire:model.live.debounce.300ms="name"
                                       placeholder="e.g. Flour & Fufu, Grains & Rice, Fresh Veggies"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                @error('name') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Slug & Parent Department -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-[#111111] mb-1">URL Slug *</label>
                                    <input type="text"
                                           wire:model="slug"
                                           placeholder="e.g. flour-fufu"
                                           class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                    @error('slug') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-[#111111] mb-1">Parent Category (Department)</label>
                                    <select wire:model="parent_id"
                                            class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                        <option value="">-- None (Top-Level Department) --</option>
                                        @foreach ($rootDepartments as $root)
                                            @if ($editingCategoryId !== $root->id)
                                                <option value="{{ $root->id }}">{{ $root->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('parent_id') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Square POS ID & Emoji Icon -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-[#111111] mb-1">Square POS Category ID</label>
                                    <input type="text"
                                           wire:model="square_id"
                                           placeholder="e.g. 2L4O4NMJTYW6427357T2L..."
                                           class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] font-mono text-xs text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                    <p class="mt-1 text-[11px] text-[#686868]">Corresponds to Square's catalog CATEGORY object ID.</p>
                                    @error('square_id') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-[#111111] mb-1">Emoji / Icon Badge</label>
                                    <input type="text"
                                           wire:model="emoji"
                                           placeholder="e.g. 🌾, 🌶️, 🍗, 🥤, 🧼"
                                           class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                    <p class="mt-1 text-[11px] text-[#686868]">Displayed beside category title in navigation menus.</p>
                                    @error('emoji') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Image Path & Sort Order -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-[#111111] mb-1">Image Asset Path / URL</label>
                                    <input type="text"
                                           wire:model="image"
                                           placeholder="e.g. images/categories/grains.jpg"
                                           class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                    @error('image') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-[#111111] mb-1">Sort Order</label>
                                    <input type="number"
                                           min="0"
                                           wire:model="sort_order"
                                           class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                    @error('sort_order') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Featured on Storefront Toggle -->
                            <div class="pt-2">
                                <label class="inline-flex items-center gap-3 p-3.5 rounded-xl bg-[#FAFAFA] border border-[#EEEEEE] cursor-pointer hover:bg-white transition-colors w-full">
                                    <input type="checkbox"
                                           wire:model="is_featured"
                                           class="w-4 h-4 rounded text-[#DF2600] focus:ring-[#DF2600]">
                                    <div>
                                        <span class="block text-xs font-bold text-[#111111]">Feature on Storefront Homepage</span>
                                        <span class="block text-[11px] text-[#686868]">Highlight this category in top navigation badges and homepage spotlight cards.</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Modal Actions -->
                        <div class="p-6 border-t border-[#EEEEEE] bg-[#FAFAFA] flex items-center justify-end gap-3">
                            <button type="button"
                                    wire:click="$set('showModal', false)"
                                    class="px-4 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-xs font-bold text-[#111111] hover:bg-[#F6F6F6] transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#DF2600] text-white text-xs font-bold hover:bg-[#b82000] transition-colors shadow-xs">
                                <span wire:loading.remove>{{ $editingCategoryId ? 'Save Changes' : 'Create Category' }}</span>
                                <span wire:loading class="inline-flex items-center gap-1.5">
                                    <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Saving...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="delete-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 transition-opacity" wire:click="$set('showDeleteModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full" style="background-color: #ffffff;">
                    <div class="p-6">
                        <div class="w-12 h-12 rounded-full bg-[#FFF0EC] text-[#DF2600] flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>

                        <h3 class="text-base font-extrabold text-[#111111] text-center" id="delete-modal-title">
                            Delete Category: {{ $deletingCategoryName }}?
                        </h3>

                        @if ($deletingCategoryHasProducts > 0 || $deletingCategoryHasChildren > 0)
                            <div class="mt-3 p-3 rounded-xl bg-[#FFF0EC] border border-[#DF2600]/20 text-xs text-[#DF2600] space-y-1">
                                @if ($deletingCategoryHasProducts > 0)
                                    <p><strong>Warning:</strong> {{ $deletingCategoryHasProducts }} product(s) are currently assigned to this category. You must reassign them before deleting.</p>
                                @endif
                                @if ($deletingCategoryHasChildren > 0)
                                    <p><strong>Warning:</strong> {{ $deletingCategoryHasChildren }} sub-category(ies) belong to this parent department. You must reassign or remove them first.</p>
                                @endif
                            </div>
                        @else
                            <p class="mt-2 text-xs text-[#686868] text-center">
                                Are you sure you want to delete this category? This action cannot be undone.
                            </p>
                        @endif
                    </div>

                    <div class="p-4 border-t border-[#EEEEEE] bg-[#FAFAFA] flex items-center justify-end gap-3">
                        <button type="button"
                                wire:click="$set('showDeleteModal', false)"
                                class="px-4 py-2 rounded-xl border border-[#EEEEEE] bg-white text-xs font-bold text-[#111111] hover:bg-[#F6F6F6] transition-colors">
                            Cancel
                        </button>
                        <button type="button"
                                wire:click="deleteCategory"
                                class="px-4 py-2 rounded-xl bg-[#DF2600] text-white text-xs font-bold hover:bg-[#b82000] transition-colors {{ ($deletingCategoryHasProducts > 0 || $deletingCategoryHasChildren > 0) ? 'opacity-50 cursor-not-allowed' : '' }}"
                                {{ ($deletingCategoryHasProducts > 0 || $deletingCategoryHasChildren > 0) ? 'disabled' : '' }}>
                            Confirm Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
