<div>
    <x-store.account-shell active="buy-again" :crumbs="['Your account' => route('account'), 'Buy again' => null]">
        <h1 class="text-h2-mobile md:text-h2">Buy Again</h1>
        <p class="mt-2 text-lead-mobile font-normal text-body-muted md:text-lead">Your usual groceries, most recent first.</p>

        @if ($usuals->isNotEmpty())
            <section class="mt-8 rounded-sm border border-grayscale-40" aria-labelledby="usuals-title">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-grayscale-40 p-5">
                    <div>
                        <h2 id="usuals-title" class="text-[18px] font-bold">Your usuals</h2>
                        <p class="mt-1 text-body-sm text-body-muted">What you buy most often, at your usual quantities and today’s prices.</p>
                    </div>
                    @if ($usuals->contains->available())
                        <button type="button" wire:click="reorderUsuals" class="h-[42px] rounded-full bg-ink px-6 text-[15px] font-bold text-white transition-colors hover:bg-grayscale-90">
                            Reorder your usuals · {{ money($usualsTotal) }}
                        </button>
                    @endif
                </div>
                <ul class="grid divide-y divide-grayscale-30 sm:grid-cols-2 sm:divide-y-0">
                    @foreach ($usuals as $line)
                        <li wire:key="usual-{{ $line->key }}" class="flex items-center gap-3 p-4 sm:border-b sm:border-grayscale-30">
                            <div class="size-14 shrink-0 overflow-hidden rounded-sm border border-grayscale-40 bg-grayscale-0">
                                @if ($line->image())
                                    <img src="{{ asset($line->image()) }}" alt="" @class(['size-full object-cover', 'opacity-50 grayscale' => ! $line->available()])>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[15px] font-bold">{{ $line->name }}</p>
                                <p class="text-body-xs text-body-muted">{{ $line->variationName }} · usually {{ $line->wantedQuantity }}</p>
                                @if (! $line->available())
                                    <p class="mt-1 text-body-xs font-bold text-price-40">Not available today — left out</p>
                                @elseif ($line->status === \App\Services\Baskets\BasketLine::STOCK_LOWERED)
                                    <p class="mt-1 text-body-xs font-bold text-brand-yellow-90">Only {{ $line->quantity }} left</p>
                                @endif
                            </div>
                            @if ($line->available())
                                <span class="text-[15px] font-extrabold">{{ money($line->nowTotalCents()) }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($lastOrder)
            <a href="{{ route('account.orders.rebuild', $lastOrder) }}" class="mt-4 flex items-center justify-between gap-4 rounded-sm bg-grayscale-0 p-5 transition-colors hover:bg-grayscale-20">
                <span>
                    <span class="block text-[15px] font-bold">Rebuild your last basket</span>
                    <span class="block text-body-sm text-body-muted">Order {{ $lastOrder->number }} · {{ money($lastOrder->total_cents) }} — see it at today’s prices and edit before checkout.</span>
                </span>
                <x-store.icon name="arrow-right" class="size-5 shrink-0" />
            </a>
        @endif

        @if ($products->isEmpty())
            <div class="mt-10 rounded-sm bg-grayscale-0 px-6 py-12 text-center">
                <p class="text-[16px] font-bold">Nothing to reorder yet</p>
                <p class="mt-1 text-body-sm text-body-muted">Products from your orders will show up here for a quick reorder.</p>
                <a href="{{ route('shop') }}" class="mt-5 inline-flex h-10 items-center rounded-full bg-ink px-6 text-[14px] font-bold text-white">Start shopping</a>
            </div>
        @else
            <h2 class="mt-10 text-[18px] font-bold">Bought before</h2>
            <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                @foreach ($products as $product)
                    <x-store.product-card :product="$product" wire:key="again-{{ $product->id }}" />
                @endforeach
            </div>
        @endif
    </x-store.account-shell>
</div>
