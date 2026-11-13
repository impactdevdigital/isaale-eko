@php
    use App\Services\Baskets\BasketLine;

    $crumbs = $basketId
        ? ['Your account' => route('account'), 'Saved baskets' => route('account.saved-baskets'), $title => null]
        : ['Your account' => route('account'), 'Buy again' => route('account.buy-again'), 'Rebuild '.$title => null];
    $difference = $nowTotal - $thenTotal;
@endphp

<div>
    <x-store.account-shell :active="$basketId ? 'saved-baskets' : 'buy-again'" :crumbs="$crumbs">
        <h1 class="text-h2-mobile md:text-h2">{{ $basketId ? $title : 'Rebuild '.$title }}</h1>
        <p class="mt-2 text-lead-mobile font-normal text-body-muted md:text-lead">Repriced at today’s prices. Change anything you like, then add it to your basket.</p>

        <div class="mt-6 grid grid-cols-2 gap-3 sm:max-w-[520px]">
            <div class="rounded-sm border border-grayscale-40 p-4">
                <p class="text-[11px] font-bold uppercase tracking-[0.5px] text-grayscale-80">{{ $thenLabel }}</p>
                <p class="mt-1 text-[22px] font-extrabold">{{ money($thenTotal) }}</p>
            </div>
            <div class="rounded-sm bg-brand-yellow-10 p-4">
                <p class="text-[11px] font-bold uppercase tracking-[0.5px] text-grayscale-80">Current basket</p>
                <p class="mt-1 text-[22px] font-extrabold">{{ money($nowTotal) }}</p>
                @if ($difference !== 0)
                    <p class="text-body-xs font-bold {{ $difference > 0 ? 'text-price-40' : 'text-stock-50' }}">{{ $difference > 0 ? '+' : '−' }}{{ money(abs($difference)) }}</p>
                @endif
            </div>
        </div>
        <p class="mt-2 text-body-xs text-body-muted">Estimated, confirmed at checkout.</p>

        @if ($unavailableCount > 0)
            <p class="mt-6 rounded-sm bg-brand-yellow-10 px-4 py-3 text-body-sm text-brand-yellow-90" role="status">
                {{ $unavailableCount === 1 ? '1 item isn’t' : $unavailableCount.' items aren’t' }} available today. We won’t add {{ $unavailableCount === 1 ? 'it' : 'them' }} unless you choose a suggested swap.
            </p>
        @endif

        <ul class="mt-6 divide-y divide-grayscale-30 border-y border-grayscale-40">
            @foreach ($repriced as $line)
                @php($state = $lines[$line->key])
                <li wire:key="rebuild-{{ $line->key }}" @class(['flex flex-col gap-3 py-4 sm:flex-row sm:items-center', 'opacity-50' => $state['removed']])>
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <div class="size-16 shrink-0 overflow-hidden rounded-sm border border-grayscale-40 bg-grayscale-0">
                            @if ($line->image())
                                <img src="{{ asset($line->image()) }}" alt="" @class(['size-full object-cover', 'grayscale' => ! $line->available()])>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p @class(['truncate text-[15px] font-bold', 'line-through' => $state['removed']])>{{ $line->name }}</p>
                            <p class="text-body-xs text-body-muted">{{ $line->variationName }}</p>
                            @if ($state['removed'])
                                <p class="mt-1 text-body-xs font-bold">Removed</p>
                            @elseif ($line->status === BasketLine::UNAVAILABLE)
                                <p class="mt-1 text-body-xs font-bold text-price-40">Not available today</p>
                                @if ($line->substitute)
                                    <button type="button" wire:click="toggleSubstitute('{{ $line->key }}')"
                                            @class(['mt-2 inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[12px] font-bold', 'border-stock-50 bg-stock-10 text-stock-50' => $state['swap'], 'border-grayscale-40' => ! $state['swap']])>
                                        @if ($state['swap'])
                                            <x-store.icon name="check" class="size-3" /> Using {{ $line->substitute->product->name }} {{ $line->substitute->name }} · {{ money($line->substitute->price_cents) }}
                                        @else
                                            Swap for {{ $line->substitute->product->name }} {{ $line->substitute->name }} · {{ money($line->substitute->price_cents) }}
                                        @endif
                                    </button>
                                @endif
                            @elseif ($line->status === BasketLine::PRICE_CHANGED)
                                <p class="mt-1 text-body-xs font-bold {{ $line->variation->price_cents > $line->thenUnitCents ? 'text-price-40' : 'text-stock-50' }}">
                                    Was {{ money($line->thenUnitCents) }}, now {{ money($line->variation->price_cents) }}
                                </p>
                            @elseif ($line->status === BasketLine::STOCK_LOWERED)
                                <p class="mt-1 text-body-xs font-bold text-brand-yellow-90">Only {{ $line->quantity }} left — we lowered the quantity</p>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-4 pl-[76px] sm:pl-0">
                        @unless ($state['removed'])
                            <div class="flex h-9 items-center rounded-full border border-grayscale-40">
                                <button type="button" wire:click="decrement('{{ $line->key }}')" class="flex size-9 items-center justify-center" aria-label="Decrease {{ $line->name }}"><x-store.icon name="minus" class="size-3" /></button>
                                <span class="w-6 text-center text-[14px] font-bold">{{ $line->available() ? $line->quantity : $state['quantity'] }}</span>
                                <button type="button" wire:click="increment('{{ $line->key }}')" class="flex size-9 items-center justify-center" aria-label="Increase {{ $line->name }}"><x-store.icon name="plus" class="size-3" /></button>
                            </div>
                            <span class="w-20 text-right text-[16px] font-extrabold">
                                @if ($line->available())
                                    {{ money($line->nowTotalCents()) }}
                                @elseif ($state['swap'] && $line->substitute)
                                    {{ money($line->substitute->price_cents * $line->wantedQuantity) }}
                                @else
                                    —
                                @endif
                            </span>
                            <button type="button" wire:click="remove('{{ $line->key }}')" class="text-[12px] font-bold underline">Remove</button>
                        @else
                            <button type="button" wire:click="restore('{{ $line->key }}')" class="text-[12px] font-bold underline">Undo</button>
                        @endunless
                    </div>
                </li>
            @endforeach
        </ul>

        @error('basket')
            <p class="mt-4 text-body-sm text-price-40">{{ $message }}</p>
        @enderror

        <div class="mt-6 flex flex-wrap items-center gap-4">
            <button type="button" wire:click="addToBasket" class="h-[50px] rounded-full bg-ink px-8 text-[15px] font-extrabold text-white transition-colors hover:bg-grayscale-90">
                Add to basket · {{ money($nowTotal) }}
            </button>
            <a href="{{ $basketId ? route('account.saved-baskets') : route('account.buy-again') }}" class="text-[14px] font-bold underline">Back</a>
        </div>
    </x-store.account-shell>
</div>
