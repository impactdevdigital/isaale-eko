<div>
    <x-store.account-shell active="saved-meals" :crumbs="['Your account' => route('account'), 'Saved meals' => null]">
        <h1 class="text-h2-mobile md:text-h2">Saved Meals</h1>
        <p class="mt-2 text-lead-mobile font-normal text-body-muted md:text-lead">Recipes you’ve saved to cook again.</p>

        @if ($recipes->isEmpty())
            <div class="mt-10 rounded-sm bg-grayscale-0 px-6 py-12 text-center">
                <x-store.icon name="heart" class="mx-auto size-8 text-grayscale-50" />
                <p class="mt-3 text-[16px] font-bold">No saved meals yet</p>
                <p class="mt-1 text-body-sm text-body-muted">Tap “Save meal” on any recipe to keep it here.</p>
                <a href="{{ route('kitchen.index') }}" class="mt-5 inline-flex h-10 items-center rounded-full bg-ink px-6 text-[14px] font-bold text-white">Browse recipes</a>
            </div>
        @else
            <ul class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($recipes as $recipe)
                    <li wire:key="meal-{{ $recipe->id }}" class="relative overflow-hidden rounded-sm border border-grayscale-40">
                        <a href="{{ route('kitchen.recipe', $recipe) }}" class="relative flex h-[170px] flex-col justify-end bg-grayscale-90 p-5 text-white">
                            @if ($recipe->image)
                                <img src="{{ asset($recipe->image) }}" alt="" loading="lazy" class="absolute inset-0 size-full object-cover">
                            @endif
                            <span class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></span>
                            <span class="relative text-body-xs font-semibold uppercase">{{ collect([$recipe->durationLabel(), $recipe->country?->name])->filter()->join(' · ') }}</span>
                            <span class="relative text-[22px] leading-7 font-bold">{{ $recipe->title }}</span>
                        </a>
                        <button type="button" wire:click="remove({{ $recipe->id }})" class="absolute right-2 top-2 flex size-8 items-center justify-center rounded-full bg-surface text-price-40 shadow-[0_0_8px_rgb(0_0_0/0.15)]" aria-label="Remove {{ $recipe->title }} from saved meals">
                            <x-store.icon name="close" class="size-4" />
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-store.account-shell>
</div>
