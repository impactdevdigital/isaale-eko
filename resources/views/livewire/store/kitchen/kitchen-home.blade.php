<div class="page-container py-6 md:py-10">
    <!-- Breadcrumb -->
    <nav aria-label="Breadcrumb" class="mb-6">
        <ol class="flex items-center gap-2 text-body-xs text-body-muted">
            <li><a href="{{ route('home') }}" class="transition-colors hover:text-ink">Home</a></li>
            <li aria-hidden="true">/</li>
            <li><span class="cursor-default">Discover</span></li>
            <li aria-hidden="true">/</li>
            <li class="font-bold text-ink" aria-current="page">Recipes</li>
        </ol>
    </nav>

    <!-- Hero Banner (Figma 07.01) -->
    <div class="relative overflow-hidden rounded-2xl border border-grayscale-30 bg-surface">
        <div class="flex flex-col-reverse lg:flex-row items-center justify-between min-h-[220px] lg:min-h-[260px] p-6 sm:p-8 lg:p-10 gap-6">
            <div class="max-w-xl z-10 space-y-3">
                <span class="inline-block text-[11px] font-bold uppercase tracking-widest text-grayscale-80">
                    Discover
                </span>
                <h1 class="text-[28px] sm:text-[36px] lg:text-[44px] font-bold tracking-tight text-ink leading-tight">
                    Recipes, Stories &amp; The People Behind The Food.
                </h1>
                <p class="text-body-sm sm:text-body text-body-muted leading-relaxed">
                    Cook the classics step by step, then shop every ingredient in one tap. Read the stories behind what's on our shelves.
                </p>
                <div class="pt-2">
                    <a href="{{ route('kitchen.pantry') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-ink text-white text-button-sm font-bold hover:bg-grayscale-90 transition-colors shadow-2xs">
                        <span>Cook With What I Have</span>
                        <x-store.icon name="arrow-right" class="size-3.5 text-brand-yellow" />
                    </a>
                </div>
            </div>
            <div class="relative shrink-0 w-full sm:w-[420px] lg:w-[480px] h-[180px] sm:h-[220px] flex items-center justify-center lg:justify-end">
                <img src="{{ asset('images/store/banners/recipes-hero-art.png') }}" alt="" class="h-full w-auto object-contain">
            </div>
        </div>
    </div>

    <!-- Category Filter Pills & Search Bar -->
    <div class="mt-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <!-- Meal Category Filters -->
        <div class="-mx-4 flex overflow-x-auto px-4 [scrollbar-width:none] md:mx-0 md:px-0 gap-2 shrink-0">
            <button type="button" wire:click="setMeal('')"
                    @class([
                        'px-4 py-2 rounded-full text-button-sm transition-all shrink-0',
                        'bg-ink text-white font-bold shadow-xs' => $meal === '',
                        'bg-white border border-grayscale-30 text-ink hover:border-ink font-medium' => $meal !== ''
                    ])>
                All Recipes
            </button>
            <button type="button" wire:click="setMeal('breakfast')"
                    @class([
                        'px-4 py-2 rounded-full text-button-sm transition-all shrink-0',
                        'bg-ink text-white font-bold shadow-xs' => $meal === 'breakfast',
                        'bg-white border border-grayscale-30 text-ink hover:border-ink font-medium' => $meal !== 'breakfast'
                    ])>
                Breakfast Recipe
            </button>
            <button type="button" wire:click="setMeal('lunch')"
                    @class([
                        'px-4 py-2 rounded-full text-button-sm transition-all shrink-0',
                        'bg-ink text-white font-bold shadow-xs' => $meal === 'lunch',
                        'bg-white border border-grayscale-30 text-ink hover:border-ink font-medium' => $meal !== 'lunch'
                    ])>
                Lunch Recipe
            </button>
            <button type="button" wire:click="setMeal('dinner')"
                    @class([
                        'px-4 py-2 rounded-full text-button-sm transition-all shrink-0',
                        'bg-ink text-white font-bold shadow-xs' => $meal === 'dinner',
                        'bg-white border border-grayscale-30 text-ink hover:border-ink font-medium' => $meal !== 'dinner'
                    ])>
                Dinner Recipe
            </button>
        </div>

        <!-- Search Bar -->
        <form wire:submit.prevent="$refresh" class="relative w-full md:w-[380px]">
            <span class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none text-body-muted">
                <x-store.icon name="search" class="size-4" />
            </span>
            <input type="search" wire:model.live.debounce.300ms="search"
                   placeholder="Search products, ingredients or recipes..."
                   class="w-full h-11 pl-10 pr-24 rounded-full border border-grayscale-30 bg-white text-body-sm text-ink placeholder:text-body-muted focus:outline-none focus:border-ink transition-colors">
            <button type="submit"
                    class="absolute right-1.5 top-1.5 bottom-1.5 px-4 rounded-full bg-ink text-white text-xs font-bold hover:bg-grayscale-90 transition-colors">
                Search
            </button>
        </form>
    </div>

    <!-- Recipes Grid (Figma 07.01) -->
    <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($recipes as $recipe)
            <article class="flex flex-col rounded-xl overflow-hidden border border-grayscale-30 bg-surface shadow-2xs hover:shadow-md transition-all group">
                <!-- Recipe Photo Top with Dark Gradient and Meta -->
                <a href="{{ route('kitchen.recipe', $recipe) }}" class="relative block h-[240px] sm:h-[260px] bg-grayscale-90 overflow-hidden">
                    @if ($recipe->image || $recipe->image_url)
                        <img src="{{ asset($recipe->image ?: $recipe->image_url) }}" alt="{{ $recipe->title }}"
                             class="size-full object-cover group-hover:scale-105 transition-transform duration-500">
                    @else
                        <div class="size-full flex items-center justify-center bg-grayscale-80 text-white font-bold text-xl">
                            {{ $recipe->title }}
                        </div>
                    @endif
                    <!-- Gradient overlay for text legibility -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/35 to-black/30"></div>

                    <!-- Top Meta Badges -->
                    <div class="absolute top-3.5 inset-x-3.5 flex items-center gap-2 text-[11px] font-semibold text-white">
                        @if ($recipe->durationLabel())
                            <span class="flex items-center gap-1 px-2.5 py-1 rounded-full bg-black/40 backdrop-blur-xs">
                                <x-store.icon name="clock" class="size-3" />
                                {{ $recipe->durationLabel() }}
                            </span>
                        @endif
                        <span class="flex items-center gap-1 px-2.5 py-1 rounded-full bg-black/40 backdrop-blur-xs">
                            <x-store.icon name="user" class="size-3" />
                            {{ $recipe->base_servings }} &ndash; {{ $recipe->base_servings + 2 }}
                        </span>
                        @if ($recipe->country)
                            <span class="flex items-center gap-1 px-2.5 py-1 rounded-full bg-black/40 backdrop-blur-xs">
                                <x-store.icon name="location" class="size-3" />
                                {{ $recipe->country->name }}
                            </span>
                        @endif
                    </div>

                    <!-- Bottom Title & Tagline -->
                    <div class="absolute bottom-4 inset-x-4 text-white">
                        <h2 class="text-[22px] font-bold tracking-tight leading-snug">
                            {{ $recipe->title }}
                        </h2>
                        @if ($recipe->intro || $recipe->tagline)
                            <p class="mt-1 text-xs text-white/90 line-clamp-1">
                                {{ $recipe->intro ?: $recipe->tagline }}
                            </p>
                        @endif

                        <div class="mt-3 flex items-center justify-between">
                            <span class="inline-flex items-center rounded-full border border-white/80 px-3 py-1 text-[11px] font-semibold text-white transition-colors group-hover:bg-white group-hover:text-ink">
                                Cook this
                            </span>
                        </div>
                    </div>
                </a>

                <!-- Bottom Bar: View Ingredient Recipe -->
                <a href="{{ route('kitchen.recipe', $recipe) }}"
                   class="flex h-12 items-center justify-center bg-grayscale-0 border-t border-grayscale-30 text-[14px] font-bold text-ink hover:bg-grayscale-20 transition-colors">
                    View Ingredient Recipe
                </a>
            </article>
        @empty
            <div class="col-span-full py-16 text-center">
                <x-store.icon name="search" class="mx-auto size-10 text-body-muted" />
                <p class="mt-3 text-body font-bold text-ink">No recipes match your criteria.</p>
                <p class="text-body-sm text-body-muted">Try clearing your filters or searching for something else.</p>
                <button type="button" wire:click="$set('search', ''); $set('meal', ''); $set('cuisine', '');"
                        class="mt-4 px-4 py-2 rounded-full bg-ink text-white text-xs font-bold">
                    View All Recipes
                </button>
            </div>
        @endforelse
    </div>

    <!-- Discover More / Food Stories Section (Figma 07.01) -->
    <section class="mt-16 md:mt-24 border-t border-grayscale-20 pt-12 md:pt-16">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
            <div>
                <span class="block text-body-xs font-bold uppercase tracking-wider text-grayscale-80">
                    Discover More
                </span>
                <h2 class="mt-1 text-h2-mobile md:text-h2 font-bold text-ink">
                    Food Stories
                </h2>
                <p class="mt-1 text-body-sm text-body-muted">
                    The history and meaning behind the ingredients on our shelves.
                </p>
            </div>
            <a href="#" class="inline-flex items-center gap-1.5 text-body-sm font-bold text-ink hover:text-brand-yellow-80 transition-colors shrink-0">
                <span>View more food stories</span>
                <x-store.icon name="arrow-right" class="size-4" />
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Story Card 1 -->
            <article class="flex flex-col sm:flex-row rounded-2xl overflow-hidden border border-grayscale-30 bg-surface">
                <div class="sm:w-1/2 h-56 sm:h-auto relative overflow-hidden bg-grayscale-90">
                    <img src="{{ asset('images/store/stories/story-jollof.png') }}" alt="Why Jollof Rice Is Never Just Rice"
                         class="size-full object-cover">
                </div>
                <div class="sm:w-1/2 p-6 flex flex-col justify-between space-y-4">
                    <div>
                        <h3 class="text-xl font-bold tracking-tight text-ink leading-snug">
                            Why Jollof Rice Is Never Just Rice
                        </h3>
                        <p class="mt-2 text-body-xs text-body-muted leading-relaxed line-clamp-3">
                            Behind every pot is a family, a debate and a standard that can never quite be met.
                        </p>
                    </div>
                    <div>
                        <a href="#" class="inline-flex items-center justify-center rounded-full bg-brand-yellow px-6 py-2.5 text-button-sm font-bold text-ink hover:bg-brand-yellow-40 transition-colors">
                            Read story
                        </a>
                        <div class="mt-4 flex items-center gap-3 text-body-xs text-body-muted border-t border-grayscale-20 pt-3">
                            <span class="flex items-center gap-1">
                                <x-store.icon name="clock" class="size-3.5" />
                                50 min
                            </span>
                            <span>&vert;</span>
                            <span>By Amina Yusuf</span>
                        </div>
                    </div>
                </div>
            </article>

            <!-- Story Card 2 -->
            <article class="flex flex-col sm:flex-row rounded-2xl overflow-hidden border border-grayscale-30 bg-surface">
                <div class="sm:w-1/2 h-56 sm:h-auto relative overflow-hidden bg-grayscale-90">
                    <img src="{{ asset('images/store/stories/story-scotch-bonnet.png') }}" alt="The Scotch Bonnet: Heat With A Heart"
                         class="size-full object-cover">
                </div>
                <div class="sm:w-1/2 p-6 flex flex-col justify-between space-y-4">
                    <div>
                        <h3 class="text-xl font-bold tracking-tight text-ink leading-snug">
                            The Scotch Bonnet: Heat With A Heart
                        </h3>
                        <p class="mt-2 text-body-xs text-body-muted leading-relaxed line-clamp-3">
                            It&rsquo;s fruity, aromatic and the single ingredient that makes West African and Caribbean kitchens sing.
                        </p>
                    </div>
                    <div>
                        <a href="#" class="inline-flex items-center justify-center rounded-full bg-brand-yellow px-6 py-2.5 text-button-sm font-bold text-ink hover:bg-brand-yellow-40 transition-colors">
                            Read story
                        </a>
                        <div class="mt-4 flex items-center gap-3 text-body-xs text-body-muted border-t border-grayscale-20 pt-3">
                            <span class="flex items-center gap-1">
                                <x-store.icon name="clock" class="size-3.5" />
                                50 min
                            </span>
                            <span>&vert;</span>
                            <span>By Amina Yusuf</span>
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </section>
</div>
