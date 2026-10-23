@php
    $user = auth()->user();
    $points = $user->pointsBalance();
    $statusLinks = [
        ['all', 'box', 'All orders', 0],
        ['unpaid', 'card-remove', 'Unpaid', $tabCounts['unpaid']],
        ['processing', 'bag-timer', 'Processing', 0],
        ['shipped', 'bus', 'Shipped', 0],
        ['returns', 'returns', 'Return', 0],
    ];
@endphp

<x-layouts::store title="Your account">
    <x-store.account-shell active="overview" :crumbs="['Your account' => route('account'), 'Overview' => null]">
        <h1 class="text-h2-mobile md:text-h2">Hi, {{ str($user->name)->before(' ') }}.</h1>
        <p class="mt-2 text-lead-mobile font-normal text-body-muted md:text-lead">Everything you need to get good food on the table.</p>

        <div class="mt-8 grid gap-4 md:grid-cols-3">
            <a href="{{ Route::has('account.rewards') ? route('account.rewards') : '#' }}" class="group relative block h-[130px] overflow-hidden rounded-sm bg-[repeating-linear-gradient(115deg,var(--color-brand-yellow-40)_0_70px,var(--color-brand-yellow-20)_70px_140px)] p-5">
                <img src="{{ asset('images/account/diamond-heart.svg') }}" alt="" class="absolute -right-2 top-1/2 size-[120px] -translate-y-1/2">
                <span class="relative flex items-center gap-2 text-[11px] leading-[14px] font-bold uppercase tracking-[1px]">
                    <x-store.icon name="lovely" class="size-[18px]" /> Loyalty balance
                </span>
                <span class="relative mt-3 block text-[28px] leading-8 font-bold">{{ number_format($points) }} pts</span>
                <span class="relative mt-3 flex items-center gap-1 text-[13px] font-bold">See rewards <x-store.icon name="chevron-right" class="size-3.5" /></span>
            </a>

            <a href="{{ route('account.orders') }}" class="block h-[130px] rounded-sm border border-grayscale-40 p-5 hover:border-ink">
                <span class="flex items-center gap-2 text-[11px] leading-[14px] font-bold uppercase tracking-[1px] text-body-muted">
                    <x-store.icon name="receipt" class="size-[18px]" /> Orders
                </span>
                <span class="mt-3 block text-[28px] leading-8 font-bold">{{ $orderCount }}</span>
                <span class="mt-3 flex items-center gap-1 text-[13px] font-bold">View history <x-store.icon name="chevron-right" class="size-3.5" /></span>
            </a>

            <a href="{{ Route::has('account.buy-again') ? route('account.buy-again') : '#' }}" class="block h-[130px] rounded-sm border border-grayscale-40 p-5 hover:border-ink">
                <span class="flex items-center gap-2 text-[11px] leading-[14px] font-bold uppercase tracking-[1px] text-body-muted">
                    <x-store.icon name="bag" class="size-[18px]" /> Quick reorder
                </span>
                <span class="mt-3 block text-[28px] leading-8 font-bold">Buy again</span>
                <span class="mt-3 flex items-center gap-1 text-[13px] font-bold">Open favourites <x-store.icon name="chevron-right" class="size-3.5" /></span>
            </a>
        </div>

        <section class="mt-8 rounded-sm border border-grayscale-40 p-5" aria-labelledby="my-orders">
            <div class="flex items-center justify-between">
                <h2 id="my-orders" class="text-[18px] font-bold">My Orders</h2>
                <a href="{{ route('account.orders') }}" class="flex items-center gap-1 text-[14px] font-bold">View All <x-store.icon name="chevron-right" class="size-3.5" /></a>
            </div>

            <ul class="mt-6 grid grid-cols-5 gap-2">
                @foreach ($statusLinks as [$tab, $icon, $label, $badge])
                    <li>
                        <a href="{{ route('account.orders', $tab === 'all' ? [] : ['tab' => $tab]) }}" class="flex flex-col items-center gap-3 text-center text-[13px] md:text-[15px]">
                            <span class="relative">
                                <x-store.icon :name="$icon" class="size-7 text-grayscale-80" />
                                @if ($badge > 0)
                                    <span class="absolute -right-2 -top-2 flex size-[18px] items-center justify-center rounded-full bg-price-40 text-[10px] font-bold text-white">{{ $badge }}</span>
                                @endif
                            </span>
                            {{ $label }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6 flex flex-col gap-3">
                @forelse ($recent as $order)
                    <div class="overflow-hidden rounded-sm border border-dashed border-grayscale-40">
                        <p class="bg-grayscale-0 px-4 py-2.5 text-[12px] text-body-muted">{{ $order->number }} · {{ $order->fulfilment === 'pickup' ? 'Store pickup' : 'Delivery' }}</p>
                        <div class="grid grid-cols-[48px_1fr_auto] items-center gap-4 px-4 py-3 text-[14px] md:grid-cols-[48px_1fr_1fr_1fr_auto]">
                            @if ($image = $order->items->first()?->image)
                                <img src="{{ asset($image) }}" alt="" class="size-11 rounded-sm object-cover">
                            @else
                                <span class="size-11 rounded-sm bg-grayscale-0"></span>
                            @endif
                            <span>{{ $order->itemCount() }} {{ str('item')->plural($order->itemCount()) }}</span>
                            <span class="hidden font-bold md:block">{{ money($order->total_cents) }}</span>
                            <span class="hidden text-body-muted md:block">{{ $order->statusLabel() }}</span>
                            <a href="{{ route('account.orders.show', $order) }}" class="flex items-center gap-1 font-bold text-brand-blue">Order Details <x-store.icon name="chevron-right" class="size-3.5" /></a>
                        </div>
                    </div>
                @empty
                    <div class="rounded-sm bg-grayscale-0 px-6 py-10 text-center">
                        <p class="text-[16px] font-bold">No orders yet</p>
                        <p class="mt-1 text-body-sm text-body-muted">When you place an order, you can track it here.</p>
                        <a href="{{ route('shop') }}" class="mt-4 inline-flex h-10 items-center rounded-full bg-ink px-6 text-[14px] font-bold text-white">Start shopping</a>
                    </div>
                @endforelse
            </div>
        </section>
    </x-store.account-shell>
</x-layouts::store>
