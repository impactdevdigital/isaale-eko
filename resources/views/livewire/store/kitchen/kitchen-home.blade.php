<div class="page-container py-8 lg:py-8">
    <!-- Breadcrumb (Figma: height 14px, gap 6px, font-weight 800, 11px / 14px) -->
    <nav aria-label="Breadcrumb" class="mb-8">
        <ol class="flex items-center gap-1.5 text-[11px] leading-[14px] font-extrabold text-[#686868]">
            <li><a href="{{ route('home') }}" class="transition-colors hover:text-[#111111]">Home</a></li>
            <li aria-hidden="true" class="text-[10px] leading-[13px] font-bold text-[#686868]">/</li>
            <li><span class="cursor-default">Discover</span></li>
            <li aria-hidden="true" class="text-[10px] leading-[13px] font-bold text-[#686868]">/</li>
            <li class="font-extrabold text-[#111111]" aria-current="page">Recipes</li>
        </ol>
    </nav>

    <!-- Hero Banner (Figma 07.01: width 1296px, height 250px, border-radius 4px, background #FAFAFA / artwork) -->
    <div class="relative overflow-hidden rounded-[4px] bg-[#FAFAFA] min-h-[220px] md:h-[250px] flex flex-col justify-between">
        <div class="relative z-10 max-w-[588px] py-8 px-[30px] flex flex-col justify-center gap-2">
            <!-- Eyebrow: uppercase, 11px/14px, tracking 1.35px, #574B2F -->
            <span class="block text-[11px] font-extrabold uppercase tracking-[1.35px] text-[#574B2F]">
                Discover
            </span>
            <!-- Heading: 42px/40px, font-bold, tracking -2.4px, #111111, capitalize -->
            <h1 class="text-[28px] sm:text-[34px] lg:text-[42px] font-bold tracking-[-2.4px] text-[#111111] leading-[1.05] lg:leading-[40px] capitalize">
                Recipes, stories &amp; the people behind the food.
            </h1>
            <!-- Paragraph: 14px/22px, font-medium, #686868 -->
            <p class="text-[14px] leading-[22px] font-medium text-[#686868]">
                Cook the classics step by step, then shop every ingredient in one tap. Read the stories behind what’s on our shelves.
            </p>
        </div>

        <!-- Artwork: Seamlessly blended on right side in desktop, neat alignment on mobile -->
        <div class="hidden md:block absolute right-0 top-0 bottom-0 h-full pointer-events-none select-none">
            <img src="{{ asset('images/store/banners/recipes-hero-art.png') }}" alt="" class="h-full w-auto object-contain object-right">
        </div>
        <div class="block md:hidden w-full h-[180px] relative overflow-hidden pointer-events-none select-none">
            <img src="{{ asset('images/store/banners/recipes-hero-art.png') }}" alt="" class="size-full object-contain object-center">
        </div>
    </div>

    <!-- Country introduction / Filter Pills & Search Bar (Figma: height 46px, gap 8px, 48px below banner) -->
    <div class="mt-12 mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <!-- Meal Category Filters (Figma: height 34px, padding 9px 12px, border-radius 12px, 12px/16px font-extrabold) -->
        <div class="-mx-4 flex overflow-x-auto px-4 [scrollbar-width:none] md:mx-0 md:px-0 gap-2 shrink-0">
            <button type="button" wire:click="setMeal('')"
                    @class([
                        'h-[34px] px-3 py-[9px] rounded-[12px] text-[12px] leading-[16px] font-extrabold transition-all shrink-0',
                        'bg-[#111111] text-white border border-[#111111]' => $meal === '',
                        'bg-white border border-[#DEDEDE] text-[#111111] hover:border-[#111111]' => $meal !== ''
                    ])>
                All Recipes
            </button>
            <button type="button" wire:click="setMeal('breakfast')"
                    @class([
                        'h-[34px] px-3 py-[9px] rounded-[12px] text-[12px] leading-[16px] font-extrabold transition-all shrink-0',
                        'bg-[#111111] text-white border border-[#111111]' => $meal === 'breakfast',
                        'bg-white border border-[#DEDEDE] text-[#111111] hover:border-[#111111]' => $meal !== 'breakfast'
                    ])>
                Breakfast Recipe
            </button>
            <button type="button" wire:click="setMeal('lunch')"
                    @class([
                        'h-[34px] px-3 py-[9px] rounded-[12px] text-[12px] leading-[16px] font-extrabold transition-all shrink-0',
                        'bg-[#111111] text-white border border-[#111111]' => $meal === 'lunch',
                        'bg-white border border-[#DEDEDE] text-[#111111] hover:border-[#111111]' => $meal !== 'lunch'
                    ])>
                Lunch Recipe
            </button>
            <button type="button" wire:click="setMeal('dinner')"
                    @class([
                        'h-[34px] px-3 py-[9px] rounded-[12px] text-[12px] leading-[16px] font-extrabold transition-all shrink-0',
                        'bg-[#111111] text-white border border-[#111111]' => $meal === 'dinner',
                        'bg-white border border-[#DEDEDE] text-[#111111] hover:border-[#111111]' => $meal !== 'dinner'
                    ])>
                Dinner Recipe
            </button>
        </div>

        <!-- Search Bar (Figma: width 500px, height 46px, border-radius 12px, border #DEDEDE) -->
        <form wire:submit.prevent="$refresh" class="relative w-full md:w-[500px]">
            <span class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none text-[#111111]">
                <x-store.icon name="search" class="size-[18px]" />
            </span>
            <input type="search" wire:model.live.debounce.300ms="search"
                   placeholder="Search products, ingredients or recipes..."
                   class="w-full h-[46px] pl-10 pr-20 rounded-[12px] border border-[#DEDEDE] bg-white text-[14px] leading-[22px] font-medium text-[#111111] placeholder:text-[#686868] focus:outline-none focus:border-[#111111] transition-colors">
            <button type="submit"
                    class="absolute right-2 top-2 bottom-2 px-3 rounded-full bg-[#111111] text-white text-[12px] leading-[16px] font-extrabold hover:bg-grayscale-90 transition-colors">
                Search
            </button>
        </form>
    </div>

    <!-- Recipes Grid (Figma Frame 1000003084: 3 columns, gap 12px, gap-y 16px, cards 424px x 230px, border-radius 4px, pb-80px) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 gap-y-4 mb-20">
        @forelse ($recipes as $recipe)
            <article class="flex flex-col justify-between rounded-[4px] overflow-hidden border border-[#DEDEDE] bg-white shadow-2xs hover:shadow-md transition-all group">
                <!-- Upper Card: Image area (Figma: height 190px, padding 23px, bottom-aligned content) -->
                <a href="{{ route('kitchen.recipe', $recipe) }}" class="relative block h-[190px] bg-grayscale-90 overflow-hidden flex flex-col justify-end p-[23px]">
                    @if ($recipe->image || $recipe->image_url)
                        <img src="{{ asset($recipe->image ?: $recipe->image_url) }}" alt="{{ $recipe->title }}"
                             class="absolute inset-0 size-full object-cover group-hover:scale-105 transition-transform duration-500">
                    @else
                        <div class="absolute inset-0 size-full flex items-center justify-center bg-grayscale-80 text-white font-bold text-xl">
                            {{ $recipe->title }}
                        </div>
                    @endif

                    <!-- Dual overlay: 20% tint (Figma: rgba(0,0,0,0.2)) + bottom vignette for text legibility -->
                    <div class="absolute inset-0 bg-black/20 pointer-events-none"></div>
                    <div class="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-black/85 via-black/40 to-transparent pointer-events-none"></div>

                    <!-- Content container inside image (Figma: gap 4px between meta and title) -->
                    <div class="relative z-10 w-full text-white">
                        <!-- Meta Badges: 10px font-black, gap 8px, 12px icons -->
                        <div class="flex items-center gap-2 text-[10px] leading-[13px] font-black text-white mb-1">
                            @if ($recipe->durationLabel())
                                <span class="flex items-center gap-1">
                                    <x-store.icon name="clock" class="size-3" />
                                    {{ $recipe->durationLabel() }}
                                </span>
                            @endif
                            <span class="flex items-center gap-1">
                                <x-store.icon name="user" class="size-3" />
                                {{ $recipe->base_servings }} &ndash; {{ $recipe->base_servings + 2 }}
                            </span>
                            @if ($recipe->country)
                                <span class="flex items-center gap-1">
                                    <x-store.icon name="location" class="size-3" />
                                    {{ $recipe->country->name }}
                                </span>
                            @endif
                        </div>

                        <!-- Title: 28px/28px, font-bold, letter-spacing -1px, capitalize -->
                        <h2 class="text-[28px] leading-[28px] font-bold tracking-[-1px] text-white capitalize drop-shadow-xs">
                            {{ $recipe->title }}
                        </h2>

                        <!-- Subtitle: 12px/18px, font-medium -->
                        @if ($recipe->intro || $recipe->tagline)
                            <p class="mt-0.5 text-[12px] leading-[18px] font-medium text-white/95 line-clamp-1">
                                {{ $recipe->intro ?: $recipe->tagline }}
                            </p>
                        @endif

                        <!-- Cook this Pill (Figma: height 30px, 12px/16px font-extrabold #FFFFFF, border 1px #FFFFFF, rounded-full) -->
                        <div class="mt-2.5 flex items-center justify-between">
                            <span class="inline-flex items-center justify-center h-[30px] px-2.5 rounded-full border border-white text-[12px] leading-[16px] font-extrabold text-white bg-white/5 backdrop-blur-xs transition-colors group-hover:bg-white group-hover:text-[#111111]">
                                Cook this
                            </span>
                        </div>
                    </div>
                </a>

                <!-- Bottom Bar: View Ingredient Recipe (Figma: height 40px, bg #F6F6F6, 11px/14px font-extrabold #111111) -->
                <a href="{{ route('kitchen.recipe', $recipe) }}"
                   class="flex h-[40px] items-center justify-center bg-[#F6F6F6] border-t border-[#DEDEDE] text-[11px] leading-[14px] font-extrabold text-[#111111] hover:bg-[#EEEEEE] hover:text-[#806500] transition-colors">
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

    <!-- Discover More / Food Stories Section (Figma 07.01: Frame 1000003072, no border divider) -->
    <section class="mb-20">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
            <div class="max-w-[600px]">
                <span class="block text-[11px] leading-[14px] font-extrabold uppercase tracking-[1.35px] text-[#574B2F]">
                    Discover More
                </span>
                <h2 class="mt-1 text-[32px] sm:text-[42px] leading-[40px] font-bold tracking-[-2.4px] text-[#111111] capitalize">
                    Food Stories
                </h2>
                <p class="mt-1 text-[14px] leading-[22px] font-medium text-[#686868]">
                    The history and meaning behind the ingredients on our shelves. Or try our <a href="{{ route('kitchen.pantry') }}" class="underline font-bold text-[#111111] hover:text-[#806500] transition-colors">Cook With What I Have</a> pantry matcher.
                </p>
            </div>
            <a href="#" class="inline-flex items-center gap-1.5 text-[12px] leading-[16px] font-extrabold text-[#111111] hover:text-brand-yellow-80 transition-colors shrink-0">
                <span>View more food stories</span>
                <x-store.icon name="arrow-right" class="size-[15px]" />
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Story Card 1 (Figma: width 640px, height 210px, border-radius 4px) -->
            <article class="flex flex-col sm:flex-row rounded-[4px] overflow-hidden border border-[#DEDEDE] bg-white h-auto sm:h-[210px]">
                <div class="sm:w-1/2 h-48 sm:h-[210px] relative overflow-hidden bg-grayscale-90 shrink-0">
                    <img src="{{ asset('images/store/stories/story-jollof.png') }}" alt="Why Jollof Rice Is Never Just Rice"
                         class="size-full object-cover">
                </div>
                <div class="sm:w-1/2 p-5 sm:p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="text-[20px] sm:text-[28px] leading-[24px] sm:leading-[28px] font-bold tracking-[-1px] text-[#111111] capitalize">
                            Why Jollof Rice Is Never Just Rice
                        </h3>
                        <p class="mt-2 text-[14px] leading-[22px] font-normal text-[#575042] line-clamp-2">
                            Behind every pot is a family, a debate and a standard that can never quite be met.
                        </p>
                    </div>
                    <div class="pt-3">
                        <a href="#" class="inline-flex items-center justify-center rounded-full bg-[#FFD21F] px-6 py-2.5 text-[14px] leading-[18px] font-extrabold text-[#111111] hover:bg-brand-yellow-40 transition-colors">
                            Read story
                        </a>
                        <div class="mt-3 flex items-center gap-3 text-[14px] text-[#111111] border-t border-grayscale-20 pt-2.5">
                            <span class="flex items-center gap-1.5 font-extrabold">
                                <x-store.icon name="clock" class="size-4" />
                                50 min
                            </span>
                            <span class="text-[#111111]">&vert;</span>
                            <span class="font-medium">By Amina Yusuf</span>
                        </div>
                    </div>
                </div>
            </article>

            <!-- Story Card 2 (Figma: width 640px, height 210px, border-radius 4px) -->
            <article class="flex flex-col sm:flex-row rounded-[4px] overflow-hidden border border-[#DEDEDE] bg-white h-auto sm:h-[210px]">
                <div class="sm:w-1/2 h-48 sm:h-[210px] relative overflow-hidden bg-grayscale-90 shrink-0">
                    <img src="{{ asset('images/store/stories/story-scotch-bonnet.png') }}" alt="The Scotch Bonnet: Heat With A Heart"
                         class="size-full object-cover">
                </div>
                <div class="sm:w-1/2 p-5 sm:p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="text-[20px] sm:text-[28px] leading-[24px] sm:leading-[28px] font-bold tracking-[-1px] text-[#111111] capitalize">
                            The Scotch Bonnet: Heat With A Heart
                        </h3>
                        <p class="mt-2 text-[14px] leading-[22px] font-normal text-[#575042] line-clamp-2">
                            It’s fruity, aromatic and the single ingredient that makes West African and Caribbean cooking taste like itself.
                        </p>
                    </div>
                    <div class="pt-3">
                        <a href="#" class="inline-flex items-center justify-center rounded-full bg-[#FFD21F] px-6 py-2.5 text-[14px] leading-[18px] font-extrabold text-[#111111] hover:bg-brand-yellow-40 transition-colors">
                            Read story
                        </a>
                        <div class="mt-3 flex items-center gap-3 text-[14px] text-[#111111] border-t border-grayscale-20 pt-2.5">
                            <span class="flex items-center gap-1.5 font-extrabold">
                                <x-store.icon name="clock" class="size-4" />
                                50 min
                            </span>
                            <span class="text-[#111111]">&vert;</span>
                            <span class="font-medium">By Amina Yusuf</span>
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </section>
</div>
