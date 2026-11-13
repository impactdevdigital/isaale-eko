<div class="page-container pt-6 pb-20 md:pt-10">
    <nav aria-label="Breadcrumb" class="text-[11px] leading-[14px] font-semibold text-body-muted">
        <ol class="flex flex-wrap items-center gap-1.5">
            <li><a href="{{ route('home') }}" class="hover:text-ink">Home</a></li>
            <li aria-hidden="true">/</li>
            <li><a href="{{ route('kitchen.index') }}" class="hover:text-ink">Kitchen &amp; Recipes</a></li>
            <li aria-hidden="true">/</li>
            <li class="font-bold text-ink" aria-current="page">Cook on a budget</li>
        </ol>
    </nav>

    <div class="mt-6 max-w-[720px]">
        <h1 class="text-h2-mobile md:text-h2">What can I cook with {{ money($budgetCents) }}?</h1>
        <p class="mt-2 text-lead-mobile font-normal text-body-muted md:text-lead">Tell us your budget and who you’re feeding. We’ll price real meals from our shelves, scaled to your table.</p>
    </div>

    {{-- Inputs --}}
    <div class="mt-8 grid gap-4 rounded-sm border border-grayscale-40 p-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="flex flex-col gap-2">
            <label for="budget" class="text-[14px] leading-5">Budget</label>
            <div class="relative">
                <span class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-[14px] font-bold">$</span>
                <input id="budget" type="text" inputmode="decimal" wire:model.live.debounce.500ms="budget"
                       class="h-12 w-full rounded-sm border border-grayscale-40 bg-surface pr-4 pl-8 text-[14px] font-bold focus:border-ink focus:outline-none">
            </div>
        </div>

        <div class="flex flex-col gap-2">
            <span class="text-[14px] leading-5" id="people-label">People</span>
            <div class="flex h-12 items-center justify-between rounded-sm border border-grayscale-40 px-2" role="group" aria-labelledby="people-label">
                <button type="button" wire:click="decrementPeople" class="flex size-9 items-center justify-center" aria-label="Fewer people"><x-store.icon name="minus" class="size-3" /></button>
                <span class="text-[14px] font-bold">{{ $people }} {{ str('person')->plural($people) }}</span>
                <button type="button" wire:click="incrementPeople" class="flex size-9 items-center justify-center" aria-label="More people"><x-store.icon name="plus" class="size-3" /></button>
            </div>
        </div>

        <div class="flex flex-col gap-2">
            <label for="cuisine" class="text-[14px] leading-5">Cuisine</label>
            <select id="cuisine" wire:model.live="cuisine" class="h-12 w-full rounded-sm border border-grayscale-40 bg-surface px-4 text-[14px] font-medium focus:border-ink focus:outline-none">
                <option value="">Any cuisine</option>
                @foreach ($cuisines as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        @if ($diets)
            <div class="flex flex-col gap-2">
                <label for="diet" class="text-[14px] leading-5">Diet (optional)</label>
                <select id="diet" wire:model.live="diet" class="h-12 w-full rounded-sm border border-grayscale-40 bg-surface px-4 text-[14px] font-medium focus:border-ink focus:outline-none">
                    <option value="">No preference</option>
                    @foreach ($diets as $tag)
                        <option value="{{ $tag }}">{{ str($tag)->replace(['-', '_'], ' ')->title() }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>

    @if ($added)
        <p class="mt-6 rounded-sm bg-stock-10 px-4 py-3 text-body-sm text-stock-50" role="status">
            Added everything for {{ $added }} to your basket. <a href="{{ route('cart') }}" class="font-bold underline">View basket</a>
        </p>
    @endif

    {{-- Results --}}
    <section class="mt-8" aria-live="polite">
        @if ($plan->options->isNotEmpty())
            <div class="grid gap-4 md:grid-cols-3">
                @foreach ($plan->options as $option)
                    @php($share = (int) round($option->totalCents() / max(1, $budgetCents) * 100))
                    <article wire:key="meal-{{ $option->key }}-{{ $people }}" class="flex flex-col overflow-hidden rounded-sm border border-grayscale-40">
                        <div class="relative flex h-[160px] flex-col justify-end bg-grayscale-90 p-5 text-white">
                            @if ($option->image())
                                <img src="{{ asset($option->image()) }}" alt="" loading="lazy" class="absolute inset-0 size-full object-cover">
                            @endif
                            <span class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></span>
                            <span class="relative text-body-xs font-semibold uppercase">{{ $option->combo ? 'Meal combo' : ($cuisines[$option->cuisine()] ?? 'Recipe') }} · serves {{ $option->people }}</span>
                            <h2 class="relative text-[22px] leading-7 font-bold">{{ $option->name }}</h2>
                        </div>
                        <div class="flex flex-1 flex-col gap-4 p-5">
                            <div class="flex items-end justify-between gap-3">
                                <div>
                                    <p class="text-[26px] leading-8 font-extrabold">{{ money($option->totalCents()) }}</p>
                                    <p class="text-body-xs text-body-muted">Estimated, confirmed at checkout</p>
                                </div>
                                <span class="rounded-full bg-stock-10 px-2.5 py-1 text-[12px] font-bold text-stock-50">{{ $share }}% of budget</span>
                            </div>

                            <details class="text-body-sm">
                                <summary class="cursor-pointer font-bold">{{ $option->itemCount() }} {{ str('item')->plural($option->itemCount()) }} from our shelves</summary>
                                <ul class="mt-2 flex flex-col gap-1 text-body-muted">
                                    @foreach ($option->lines as $line)
                                        <li class="flex justify-between gap-3">
                                            <span>{{ $line['quantity'] }} × {{ $line['variation']->product->name }} <span class="text-body-xs">{{ $line['variation']->name }}</span></span>
                                            <span class="shrink-0">{{ money($line['variation']->price_cents * $line['quantity']) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </details>

                            <div class="mt-auto flex flex-col gap-2">
                                <button type="button" wire:click="shop('{{ $option->key }}')" class="flex h-[46px] items-center justify-center gap-2 rounded-full bg-ink text-[15px] font-extrabold text-white transition-colors hover:bg-grayscale-90">
                                    Shop this meal <x-store.icon name="arrow-right" />
                                </button>
                                @if (! $option->combo)
                                    <a href="{{ route('kitchen.recipe', $option->recipes->first()) }}" class="text-center text-[13px] font-bold underline">See the recipe</a>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @elseif ($plan->closest)
            <div class="rounded-sm bg-brand-yellow-10 p-6">
                <p class="text-[18px] font-bold">Nothing fits {{ money($budgetCents) }} for {{ $people }} {{ str('person')->plural($people) }} yet</p>
                <p class="mt-1 text-body-sm">
                    The closest is <strong>{{ $plan->closest->name }}</strong> at {{ money($plan->closest->totalCents()) }},
                    {{ money($plan->closest->totalCents() - $budgetCents) }} over your budget. Try fewer people or a little more budget.
                </p>
                <button type="button" wire:click="$set('budget', '{{ number_format(ceil($plan->closest->totalCents() / 100), 0, '.', '') }}')" class="mt-4 h-10 rounded-full bg-ink px-5 text-[14px] font-bold text-white">
                    Use a {{ money((int) ceil($plan->closest->totalCents() / 100) * 100) }} budget
                </button>
            </div>
        @else
            <div class="rounded-sm bg-grayscale-0 px-6 py-12 text-center">
                <p class="text-[16px] font-bold">No meals match these choices right now</p>
                <p class="mt-1 text-body-sm text-body-muted">Try another cuisine, or browse all our recipes.</p>
                <a href="{{ route('kitchen.index') }}" class="mt-5 inline-flex h-10 items-center rounded-full bg-ink px-6 text-[14px] font-bold text-white">Browse recipes</a>
            </div>
        @endif

        @if ($plan->dropped->isNotEmpty())
            <details class="mt-6 text-body-sm text-body-muted">
                <summary class="cursor-pointer font-bold text-ink">Why some meals aren’t shown ({{ $plan->dropped->count() }})</summary>
                <ul class="mt-2 flex flex-col gap-1">
                    @foreach ($plan->dropped as $dropped)
                        <li><strong class="text-ink">{{ $dropped['name'] }}</strong>: {{ $dropped['reason'] }}.</li>
                    @endforeach
                </ul>
            </details>
        @endif
    </section>
</div>
