@php
    use App\Livewire\Store\ProductListing;

    $title = match (true) {
        $q !== '' => "Search Results For “{$q}”",
        $countryPage !== null => $countryPage->name,
        $selectedCategory !== null => $selectedCategory->name,
        default => 'All Products',
    };

    $description = match (true) {
        $countryPage !== null => "Explore flavours and favourites from {$countryPage->name}.",
        default => 'From everyday staples to special finds, discover food that brings us closer.',
    };

    $crumb = match (true) {
        $q !== '' => "Search results for “{$q}”",
        $countryPage !== null => $countryPage->name,
        $selectedCategory !== null => $selectedCategory->name,
        default => null,
    };
@endphp

<div class="page-container pt-6 pb-16 md:pt-10">
    @if ($noSearchResults)
        {{-- Search with nothing to show: full-width empty state --}}
        <section class="mx-auto flex max-w-[520px] flex-col items-center py-10 text-center md:py-16">
            <img src="{{ asset('images/store/illustrations/no-results.webp') }}" alt="" class="w-[300px]">
            <h1 class="mt-8 text-h2-mobile md:text-h2">There Were No Search Results For {{ $q }}</h1>
            <p class="mt-3 text-lead-mobile font-normal text-body-muted md:text-lead">Check your spelling for typing errors and keyword</p>
            <div class="mt-6 flex w-full flex-col gap-2.5 sm:flex-row">
                <a href="#" class="flex h-[42px] flex-1 items-center justify-center rounded-full bg-brand-yellow text-[14px] font-extrabold transition-colors hover:bg-brand-yellow-60">Ask Asha</a>
                <a href="{{ route('shop') }}" wire:navigate class="flex h-[42px] flex-1 items-center justify-center gap-2 rounded-full border border-grayscale-40 text-[14px] font-extrabold transition-colors hover:border-ink">
                    Show all products <x-store.icon name="arrow-right" />
                </a>
            </div>
        </section>
    @else
        <nav aria-label="Breadcrumb" class="text-[11px] leading-[14px] font-semibold text-body-muted">
            <ol class="flex flex-wrap items-center gap-1.5">
                <li><a href="{{ route('home') }}" class="hover:text-ink">Home</a></li>
                <li aria-hidden="true">/</li>
                <li>
                    @if ($crumb)
                        <a href="{{ route('shop') }}" wire:navigate class="hover:text-ink">Shop</a>
                    @else
                        <span class="font-bold text-ink" aria-current="page">Shop</span>
                    @endif
                </li>
                @if ($crumb)
                    <li aria-hidden="true">/</li>
                    <li class="font-bold text-ink" aria-current="page">{{ $crumb }}</li>
                @endif
            </ol>
        </nav>

        <div x-data="{ filtersOpen: false }" class="mt-6 flex flex-col gap-8 lg:mt-9 lg:flex-row lg:gap-12">
            {{-- Filters --}}
            <aside class="lg:w-60 lg:shrink-0">
                <button type="button" @click="filtersOpen = ! filtersOpen" :aria-expanded="filtersOpen"
                        class="flex w-full items-center justify-between rounded-sm border border-grayscale-40 px-4 py-3 text-[14px] font-bold lg:hidden">
                    Filter products
                    <x-store.icon name="arrow-down" class="size-3 transition-transform" x-bind:class="filtersOpen && 'rotate-180'" />
                </button>

                <div x-show="filtersOpen" x-cloak class="mt-4 lg:mt-0 lg:!block">
                    <p class="hidden pb-8 text-[14px] leading-[18px] font-bold lg:block">Filter products</p>

                    @if ($categoryTree->isNotEmpty())
                        <x-store.filter-group title="Category">
                            @foreach ($categoryTree as $group)
                                <x-store.filter-option name="category" :value="$group->slug" :label="$group->name" />
                                @if ($group->children->isNotEmpty())
                                    <div class="flex flex-col gap-1.5 pl-5">
                                        @foreach ($group->children as $child)
                                            <x-store.filter-option name="category" :value="$child->slug" :label="$child->name" />
                                        @endforeach
                                    </div>
                                @endif
                            @endforeach
                        </x-store.filter-group>
                    @endif

                    @if ($countries->isNotEmpty())
                        <x-store.filter-group title="Country">
                            @foreach ($countries as $option)
                                <x-store.filter-option name="country" :value="$option->slug" :label="$option->name" :flag="$option->flagUrl()" />
                            @endforeach
                        </x-store.filter-group>
                    @endif

                    <x-store.filter-group title="Price range">
                        @foreach (ProductListing::PRICES as $value => [$label])
                            <x-store.filter-option name="price" :value="$value" :label="$label" />
                        @endforeach
                    </x-store.filter-group>

                    <x-store.filter-group title="Availability">
                        <x-store.filter-option name="availability" value="in-stock" label="In stock" />
                        <x-store.filter-option name="availability" value="out-of-stock" label="Out of stock" />
                    </x-store.filter-group>

                    <button type="button" wire:click="clearFilters" class="mt-7 rounded-full border-2 border-ink px-2 py-1 text-[16px] leading-6 font-extrabold transition-colors hover:bg-ink hover:text-white">Clear all filters</button>
                </div>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col gap-8">
                @if ($countryPage)
                    <x-store.country-banner :country="$countryPage" />

                    @if ($popular->isNotEmpty())
                        <x-store.product-row :title="'Popular '.($countryPage->demonym ?? $countryPage->name).' Favourites'" :products="$popular" />
                    @endif
                @endif

                <section class="flex flex-col gap-6" wire:loading.class="opacity-60" aria-live="polite">
                    <div class="flex flex-col gap-4">
                        <div class="flex items-end justify-between gap-4">
                            <div class="flex flex-col gap-1">
                                <h1 class="text-h2-mobile md:text-h2">{{ $title }}</h1>
                                <p class="text-lead-mobile font-normal text-body-muted md:text-lead">{{ $description }}</p>
                            </div>
                            <x-store.pager :paginator="$products" class="hidden sm:flex" />
                        </div>

                        <x-store.sort-menu :sorts="ProductListing::SORTS" :current="$sort" />
                    </div>

                    @if ($products->isEmpty())
                        <div class="flex flex-col items-start gap-3 rounded-sm border border-dashed border-grayscale-40 p-8">
                            <p class="text-h4">No products match these filters.</p>
                            <p class="text-body text-body-muted">Try another category or price range, or clear the filters to see everything.</p>
                            <button type="button" wire:click="clearFilters" class="rounded-full border border-ink px-4 py-2 text-button-sm">Clear all filters</button>
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 md:grid-cols-4 lg:gap-4 xl:grid-cols-5">
                            @foreach ($products as $product)
                                <x-store.product-card :product="$product" wire:key="product-{{ $product->id }}" />
                            @endforeach
                        </div>

                        <x-store.pager :paginator="$products" class="self-end" />
                    @endif
                </section>
            </div>
        </div>
    @endif
</div>
