<div>
    <x-store.account-shell active="saved-baskets" :crumbs="['Your account' => route('account'), 'Saved baskets' => null]">
        <h1 class="text-h2-mobile md:text-h2">Saved Baskets</h1>
        <p class="mt-2 text-lead-mobile font-normal text-body-muted md:text-lead">Baskets you’ve named and kept. Rebuild one at today’s prices whenever you need it.</p>

        @if ($baskets->isEmpty())
            <div class="mt-10 rounded-sm bg-grayscale-0 px-6 py-12 text-center">
                <x-store.icon name="shopping-bag" class="mx-auto size-8 text-grayscale-50" />
                <p class="mt-3 text-[16px] font-bold">No saved baskets yet</p>
                <p class="mt-1 text-body-sm text-body-muted">Fill your basket, then choose “Save this basket” to keep it here.</p>
                <a href="{{ route('shop') }}" class="mt-5 inline-flex h-10 items-center rounded-full bg-ink px-6 text-[14px] font-bold text-white">Start shopping</a>
            </div>
        @else
            <ul class="mt-8 grid gap-4 md:grid-cols-2">
                @foreach ($baskets as $row)
                    <li wire:key="basket-{{ $row->basket->id }}" class="flex flex-col gap-4 rounded-sm border border-grayscale-40 p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="truncate text-[18px] font-bold">{{ $row->basket->name }}</h2>
                                <p class="text-body-xs text-body-muted">{{ $row->count }} {{ str('item')->plural($row->count) }} · saved {{ $row->basket->created_at->format('j M Y') }}</p>
                            </div>
                            <button type="button" wire:click="delete({{ $row->basket->id }})" wire:confirm="Delete “{{ $row->basket->name }}”?" class="shrink-0 text-[12px] font-bold text-body-muted underline">Delete</button>
                        </div>

                        @if ($row->images->isNotEmpty())
                            <div class="flex gap-2">
                                @foreach ($row->images as $image)
                                    <img src="{{ asset($image) }}" alt="" class="size-12 rounded-sm border border-grayscale-40 object-cover">
                                @endforeach
                            </div>
                        @endif

                        <p class="text-body-sm">
                            Saved at <strong>{{ money($row->savedTotal) }}</strong> · today <strong>{{ money($row->nowTotal) }}</strong>
                            @if ($row->unavailable)
                                <span class="block text-body-xs font-bold text-price-40">{{ $row->unavailable }} {{ str('item')->plural($row->unavailable) }} not available today</span>
                            @endif
                        </p>

                        <a href="{{ route('account.saved-baskets.show', $row->basket) }}" class="mt-auto flex h-[42px] items-center justify-center rounded-full bg-ink text-[15px] font-bold text-white transition-colors hover:bg-grayscale-90">Review &amp; rebuild</a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-store.account-shell>
</div>
