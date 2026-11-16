{{--
    Build My Basket (budget engine Modes 1 and 3).
    Used by CartPage (variant "page", at the top of the basket) and CartDrawer
    (variant "drawer"). Both components use the BuildsBasketToBudget trait,
    which gives them budgetInput, setBudget, clearBudget and applySuggestion.
--}}
@props(['budget', 'suggestions', 'variant' => 'page'])

@php
    $drawer = $variant === 'drawer';
    $id = $drawer ? 'drawer-budget' : 'page-budget';
    $pct = $budget ? min(100, (int) round($budget->spent / max(1, $budget->budget) * 100)) : 0;
    $shownSuggestions = $drawer ? $suggestions->take(2) : $suggestions;
@endphp

<section
    wire:key="{{ $id }}-{{ $budget?->budget ?? 'none' }}"
    {{ $attributes->class(['rounded-sm border border-brand-yellow-50 bg-brand-yellow-10', 'p-4' => $drawer, 'p-5 md:p-6' => ! $drawer]) }}
    aria-labelledby="{{ $id }}-title"
    x-data="{ editing: @js(! $budget || $errors->has('budgetInput')) }"
>
    <div class="flex items-start justify-between gap-3">
        <div class="flex items-center gap-3">
            <span @class(['flex shrink-0 items-center justify-center rounded-full bg-brand-yellow-50', 'size-8' => $drawer, 'size-10' => ! $drawer])>
                <x-store.icon name="shopping-bag" @class(['size-4' => $drawer, 'size-5' => ! $drawer]) />
            </span>
            <div>
                <h2 id="{{ $id }}-title" @class(['font-extrabold', 'text-[15px] leading-5' => $drawer, 'text-[18px] leading-6 md:text-[20px]' => ! $drawer])>Build My Basket</h2>
                @unless ($drawer && $budget)
                    <p class="text-body-xs text-body-muted">Set a budget. We’ll show what’s left as you shop.</p>
                @endunless
            </div>
        </div>
        @if ($budget)
            <div class="flex shrink-0 items-center gap-3 text-[12px] font-bold">
                <button type="button" x-show="! editing" @click="editing = true; $nextTick(() => $refs.input.focus())" class="underline">Change</button>
                <button type="button" wire:click="clearBudget" class="text-body-muted underline">Clear</button>
            </div>
        @endif
    </div>

    <form wire:submit="setBudget" x-show="editing" @if ($budget) x-cloak @endif class="mt-3 flex items-start gap-2">
        <div class="flex-1">
            <label for="{{ $id }}-input" class="sr-only">Budget in dollars</label>
            <div class="relative">
                <span class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-[14px] font-bold">$</span>
                <input id="{{ $id }}-input" x-ref="input" type="text" inputmode="decimal" wire:model="budgetInput" placeholder="e.g. 50"
                       @class(['w-full rounded-full border bg-surface pr-4 pl-8 text-[14px] font-bold focus:border-ink focus:outline-none', 'h-10' => $drawer, 'h-11' => ! $drawer, 'border-price-40' => $errors->has('budgetInput'), 'border-grayscale-40' => ! $errors->has('budgetInput')])>
            </div>
            @error('budgetInput')
                <p class="mt-1 text-body-xs text-price-40">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" @class(['shrink-0 rounded-full bg-ink px-5 text-[14px] font-bold text-white', 'h-10' => $drawer, 'h-11' => ! $drawer])>{{ $budget ? 'Update' : 'Set budget' }}</button>
    </form>

    @if ($budget)
        <div class="mt-4">
            <p class="flex items-baseline justify-between gap-2">
                @if ($budget->over)
                    <strong @class(['font-extrabold text-price-40', 'text-[16px]' => $drawer, 'text-[22px] leading-7' => ! $drawer])>{{ money($budget->over) }} over budget</strong>
                @else
                    <strong @class(['font-extrabold text-stock-50', 'text-[16px]' => $drawer, 'text-[22px] leading-7' => ! $drawer])>You have {{ money($budget->left) }} left</strong>
                @endif
                <span class="text-body-sm text-body-muted">{{ money($budget->spent) }} of {{ money($budget->budget) }}</span>
            </p>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-surface" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}" aria-label="Budget used">
                <div @class(['h-full rounded-full', 'bg-stock-50' => ! $budget->over, 'bg-price-40' => $budget->over]) style="width: {{ $pct }}%"></div>
            </div>
            @unless ($drawer)
                <p class="mt-2 text-body-xs text-body-muted">Estimated, confirmed at checkout. Any tax is added at checkout.</p>
            @endunless
        </div>

        @if ($budget->over && $shownSuggestions->isNotEmpty())
            <div class="mt-4 border-t border-brand-yellow-30 pt-4">
                <p class="text-[14px] font-bold">Ways to get back under budget</p>
                <ul class="mt-2 flex flex-col gap-2">
                    @foreach ($shownSuggestions as $suggestion)
                        <li wire:key="{{ $id }}-{{ $suggestion->key }}" class="flex items-center justify-between gap-3 text-body-sm">
                            <span>
                                {{ $suggestion->label }}
                                <span class="block text-body-xs font-bold text-stock-50">Saves {{ money($suggestion->saving) }}{{ $suggestion->closesGap ? ' · back under budget' : '' }}</span>
                            </span>
                            <button type="button" wire:click="applySuggestion('{{ $suggestion->key }}')" class="h-8 shrink-0 rounded-full border border-ink bg-surface px-3 text-[12px] font-bold transition-colors hover:bg-ink hover:text-white">Apply</button>
                        </li>
                    @endforeach
                </ul>
                @if ($drawer && $suggestions->count() > $shownSuggestions->count())
                    <a href="{{ route('cart') }}" class="mt-3 inline-block text-[13px] font-bold underline">See all {{ $suggestions->count() }} ways to save</a>
                @else
                    <a href="{{ route('kitchen.budget') }}" class="mt-3 inline-block text-[13px] font-bold underline">Or pick another meal that fits</a>
                @endif
            </div>
        @elseif (! $budget->over && $budget->left > 0)
            <a href="{{ route('kitchen.budget', ['budget' => number_format($budget->left / 100, 2, '.', '')]) }}" class="mt-3 inline-block text-[13px] font-bold underline">What can I cook with {{ money($budget->left) }}?</a>
        @endif
    @endif
</section>
