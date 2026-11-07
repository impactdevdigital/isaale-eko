@php
    use App\Models\Order;
@endphp

<div>
    <x-store.account-shell :active="$tab === 'all' ? 'orders' : $tab" :crumbs="['Your orders' => route('account.orders'), Order::TABS[$tab] => null]">
        <h1 class="text-h2-mobile md:text-h2">Order History</h1>
        <p class="mt-2 text-lead-mobile font-normal text-body-muted md:text-lead">Track an active order or revisit something good.</p>

        <div class="mt-8 flex flex-col-reverse gap-4 md:flex-row md:items-center md:justify-between">
            <div class="-mx-4 flex overflow-x-auto px-4 [scrollbar-width:none] md:mx-0 md:px-0" role="tablist" aria-label="Order status">
                @foreach (Order::TABS as $key => $label)
                    <button type="button" role="tab" wire:click="$set('tab', '{{ $key }}')" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                            @class(['flex shrink-0 items-center gap-2 border-b-[3px] px-4 py-2.5 text-[15px]', 'border-brand-yellow font-bold' => $tab === $key, 'border-transparent text-grayscale-80 hover:text-ink' => $tab !== $key])>
                        {{ $label }}
                        @if ($key === 'unpaid' && $unpaidCount > 0)
                            <span class="flex size-[18px] items-center justify-center rounded-full bg-price-40 text-[10px] font-bold text-white">{{ $unpaidCount }}</span>
                        @endif
                    </button>
                @endforeach
            </div>

            <label class="flex h-10 items-center gap-2.5 rounded-sm border border-grayscale-40 px-3 md:w-[260px]">
                <x-store.icon name="receipt" class="size-4 shrink-0 text-body-muted" />
                <span class="sr-only">Search orders</span>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search orders..." class="w-full bg-transparent text-[14px] placeholder:text-body-muted focus:outline-none">
            </label>
        </div>

        <div class="mt-8 flex flex-col gap-4">
            @forelse ($orders as $order)
                <article wire:key="order-{{ $order->id }}" class="rounded-sm border border-grayscale-40">
                    <div class="flex items-center justify-between px-5 pb-4 pt-5 text-[14px]">
                        <p>Date Initiated: <strong>{{ $order->placed_at->setTimezone(config('shop.timezone'))->format('M j, Y') }}</strong></p>
                        <a href="{{ route('account.orders.show', $order) }}" class="text-brand-blue hover:underline">Order Details</a>
                    </div>

                    @if ($order->isAwaitingETransfer())
                        <p class="flex items-center gap-2 bg-brand-yellow-0 px-5 py-2.5 text-[13px]">
                            <x-store.icon name="mail" class="size-4" />
                            Send {{ money($order->total_cents) }} by Interac e-Transfer to {{ config('payments.etransfer.email') }} with message {{ $order->number }}.
                        </p>
                    @elseif ($order->status === Order::STATUS_AWAITING_PAYMENT)
                        <p class="flex items-center gap-2 bg-sale-10 px-5 py-2.5 text-[13px] text-price-40">
                            <x-store.icon name="card" class="size-4" />
                            We’re waiting for the payment service to confirm this order. Please don’t pay again.
                        </p>
                    @elseif ($order->isUnpaid())
                        <p class="flex items-center gap-2 bg-brand-yellow-0 px-5 py-2.5 text-[13px]">
                            <x-store.icon name="store" class="size-4" />
                            Pay at the counter when you collect.
                        </p>
                    @endif

                    <div class="flex flex-col gap-4 px-5 py-4 md:flex-row md:items-center md:justify-between">
                        <div class="flex gap-3">
                            @foreach ($order->items->take(4) as $item)
                                <span class="flex size-[90px] items-center justify-center rounded-sm border border-grayscale-40 bg-surface">
                                    @if ($item->image)
                                        <img src="{{ asset($item->image) }}" alt="{{ $item->name }}" class="size-full rounded-sm object-cover">
                                    @endif
                                </span>
                            @endforeach
                            @if ($order->items->count() > 4)
                                <span class="flex size-[90px] items-center justify-center rounded-sm bg-grayscale-0 text-[14px] font-bold">+{{ $order->items->count() - 4 }}</span>
                            @endif
                        </div>

                        <div class="flex flex-col gap-2.5 md:w-[180px]">
                            @if ($order->status !== Order::STATUS_AWAITING_PAYMENT)
                                <button type="button" wire:click="buyAgain({{ $order->id }})" class="h-[30px] rounded-full bg-ink text-[13px] font-bold text-white">Buy again</button>
                            @endif
                            @if ($order->isConfirmed())
                                <a href="{{ route('account.orders.show', $order) }}#order-status" class="flex h-[30px] items-center justify-center rounded-full border border-grayscale-40 text-[13px] font-bold">Track order</a>
                            @endif
                            @if ($order->canBeCancelled())
                                <button type="button" wire:click="cancel({{ $order->id }})" wire:confirm="Cancel order {{ $order->number }}? This can’t be undone."
                                        class="h-[30px] rounded-full border border-grayscale-40 text-[13px] font-bold">Cancel order</button>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-x-4 gap-y-1 border-t border-grayscale-30 px-5 py-4 text-[13px]">
                        <span>{{ $order->number }} · {{ $order->fulfilment === 'pickup' ? 'Store pickup' : 'Delivery' }}</span>
                        <span class="text-[15px]">{{ $order->itemCount() }} {{ str('item')->plural($order->itemCount()) }}: <strong>{{ money($order->total_cents) }}</strong> total</span>
                        <span>{{ $order->statusLabel() }}</span>
                    </div>
                </article>
            @empty
                <div class="rounded-sm bg-grayscale-0 px-6 py-12 text-center">
                    <p class="text-[16px] font-bold">
                        {{ $search !== '' ? 'No orders match “'.$search.'”' : match ($tab) {
                            'unpaid' => 'Nothing to pay',
                            'processing' => 'Nothing being prepared right now',
                            'shipped' => 'Nothing on its way yet',
                            'returns' => 'No returns',
                            default => 'No orders yet',
                        } }}
                    </p>
                    <p class="mt-1 text-body-sm text-body-muted">
                        {{ $tab === 'returns' ? 'Need to return something? Contact us and we’ll sort it out.' : 'Orders you place will show up here.' }}
                    </p>
                </div>
            @endforelse

            @if ($orders->total() > 0)
                <div class="flex flex-wrap items-center justify-center gap-4 rounded-sm border border-grayscale-40 bg-grayscale-0 px-5 py-5 text-[14px] text-body-muted">
                    <span class="flex items-center gap-2"><x-store.icon name="receipt" class="size-5" /> Showing {{ $orders->lastItem() }} of {{ $orders->total() }} orders</span>
                    @if ($orders->hasPages())
                        <span class="flex gap-2">
                            <button type="button" wire:click="previousPage" @disabled($orders->onFirstPage()) class="rounded-full border border-grayscale-40 bg-surface px-4 py-1.5 font-bold text-ink disabled:opacity-40">Newer</button>
                            <button type="button" wire:click="nextPage" @disabled(! $orders->hasMorePages()) class="rounded-full border border-grayscale-40 bg-surface px-4 py-1.5 font-bold text-ink disabled:opacity-40">Older</button>
                        </span>
                    @endif
                </div>
            @endif
        </div>
    </x-store.account-shell>
</div>
