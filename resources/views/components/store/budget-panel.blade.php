{{--
    Budget card under the basket's order summary (budget engine Modes 1 and 3).
    Lives inside CartPage, which has budgetInput, setBudget, clearBudget and applySuggestion.
--}}
@props(['budget', 'suggestions'])

<section class="rounded-sm border border-grayscale-40 p-5" aria-labelledby="budget-title">
    <div class="flex items-center justify-between gap-3">
        <h2 id="budget-title" class="text-[16px] font-bold">Shopping to a budget?</h2>
        @if ($budget)
            <button type="button" wire:click="clearBudget" class="text-[12px] font-bold text-body-muted underline">Clear</button>
        @endif
    </div>

    <form wire:submit="setBudget" class="mt-3 flex items-start gap-2">
        <div class="flex-1">
            <label for="budget-input" class="sr-only">Budget in dollars</label>
            <div class="relative">
                <span class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-[14px] font-bold">$</span>
                <input id="budget-input" type="text" inputmode="decimal" wire:model="budgetInput" placeholder="e.g. 50"
                       @class(['h-11 w-full rounded-full border bg-surface pr-4 pl-8 text-[14px] font-bold focus:border-ink focus:outline-none', 'border-price-40' => $errors->has('budgetInput'), 'border-grayscale-40' => ! $errors->has('budgetInput')])>
            </div>
            @error('budgetInput')
                <p class="mt-1 text-body-xs text-price-40">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="h-11 shrink-0 rounded-full bg-ink px-5 text-[14px] font-bold text-white">{{ $budget ? 'Update' : 'Set budget' }}</button>
    </form>

    @if ($budget)
        @php($pct = min(100, (int) round($budget->spent / max(1, $budget->budget) * 100)))
        <div class="mt-4">
            <div class="h-2 overflow-hidden rounded-full bg-grayscale-30" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}" aria-label="Budget used">
                <div @class(['h-full rounded-full', 'bg-stock-50' => ! $budget->over, 'bg-price-40' => $budget->over]) style="width: {{ $pct }}%"></div>
            </div>
            <p class="mt-2 flex justify-between text-body-sm">
                <span>{{ money($budget->spent) }} of {{ money($budget->budget) }}</span>
                @if ($budget->over)
                    <strong class="text-price-40">{{ money($budget->over) }} over budget</strong>
                @else
                    <strong class="text-stock-50">You have {{ money($budget->left) }} left</strong>
                @endif
            </p>
            <p class="mt-1 text-body-xs text-body-muted">Estimated, confirmed at checkout. Any tax is added at checkout.</p>
        </div>

        @if ($budget->over && $suggestions->isNotEmpty())
            <div class="mt-4 border-t border-grayscale-30 pt-4">
                <p class="text-[14px] font-bold">Ways to get back under budget</p>
                <ul class="mt-2 flex flex-col gap-2">
                    @foreach ($suggestions as $suggestion)
                        <li wire:key="budget-{{ $suggestion->key }}" class="flex items-center justify-between gap-3 text-body-sm">
                            <span>
                                {{ $suggestion->label }}
                                <span class="block text-body-xs font-bold text-stock-50">Saves {{ money($suggestion->saving) }}{{ $suggestion->closesGap ? ' · back under budget' : '' }}</span>
                            </span>
                            <button type="button" wire:click="applySuggestion('{{ $suggestion->key }}')" class="h-8 shrink-0 rounded-full border border-ink px-3 text-[12px] font-bold transition-colors hover:bg-ink hover:text-white">Apply</button>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('kitchen.budget') }}" class="mt-3 inline-block text-[13px] font-bold underline">Or pick another meal that fits</a>
            </div>
        @elseif (! $budget->over && $budget->left > 0)
            <a href="{{ route('kitchen.budget', ['budget' => number_format($budget->left / 100, 2, '.', '')]) }}" class="mt-3 inline-block text-[13px] font-bold underline">What can I cook with {{ money($budget->left) }}?</a>
        @endif
    @else
        <p class="mt-2 text-body-xs text-body-muted">Set a budget and we’ll show what’s left as you shop, and ways to stay under it.</p>
    @endif
</section>
