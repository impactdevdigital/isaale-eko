@php
    use App\Livewire\Store\Checkout;
    use App\Models\Order;
    use Carbon\CarbonImmutable;

    $steps = $order->trackerSteps();
    $current = $order->trackerStep();
    $count = $order->itemCount();
    $contact = $order->contact_email ?? $order->contact_phone;
    $statusText = match (true) {
        $order->status === Order::STATUS_CANCELLED => 'This order was cancelled'.($order->cancelled_at ? ' on '.$order->cancelled_at->setTimezone(config('shop.timezone'))->format('j M Y') : '').'. You haven’t been charged.',
        $order->status === Order::STATUS_AWAITING_PAYMENT => 'We’re waiting for the payment service to confirm your payment. Please don’t pay again — this page updates once it’s confirmed.',
        $current === 3 => $order->fulfilment === 'pickup' ? 'Collected. Enjoy your groceries!' : 'Delivered. Enjoy your groceries!',
        $current === 2 => $order->fulfilment === 'pickup' ? 'Your order is ready to collect. Bring your order number.' : 'Your order is out for delivery.',
        $current === 1 => 'We’re preparing your order now.',
        default => $order->fulfilment === 'pickup'
            ? 'Your order is confirmed. We’ll let you know when it’s ready to collect.'
            : 'Your order is confirmed. We’ll let you know when it’s on its way.',
    };
@endphp

<div>
    <x-store.account-shell active="orders" :crumbs="['Your orders' => route('account.orders'), 'Order details' => null]">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-h2-mobile md:text-h2">Order Details</h1>
                <p class="mt-2 text-[14px] text-body-muted">Order number: {{ $order->number }}</p>
            </div>
            <a href="{{ route('shop') }}" class="flex h-[42px] items-center rounded-full bg-ink px-12 text-[15px] font-bold text-white">Keep shopping</a>
        </div>

        <dl class="mt-8 grid grid-cols-2 gap-y-4 rounded-sm border border-grayscale-40 bg-grayscale-0 px-5 py-5 md:grid-cols-4 md:divide-x md:divide-grayscale-40">
            <div class="md:pr-5"><dt class="text-[12px] text-body-muted">Order number</dt><dd class="mt-1 text-[15px] font-bold">{{ $order->number }}</dd></div>
            <div class="md:px-5 md:text-center"><dt class="text-[12px] text-body-muted">Order date</dt><dd class="mt-1 text-[15px] font-bold">{{ $order->placed_at->setTimezone(config('shop.timezone'))->format('d M Y') }}</dd></div>
            <div class="md:px-5 md:text-center"><dt class="text-[12px] text-body-muted">Items</dt><dd class="mt-1 text-[15px] font-bold">{{ $count }} {{ str('item')->plural($count) }}</dd></div>
            <div class="md:pl-5 md:text-right"><dt class="text-[12px] text-body-muted">Order total</dt><dd class="mt-1 text-[18px] font-bold">{{ money($order->total_cents) }}</dd></div>
        </dl>

        <section id="order-status" class="mt-8 scroll-mt-6 rounded-sm border border-grayscale-40 p-5 md:p-6" aria-labelledby="status-title">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 id="status-title" class="text-[15px] font-bold">{{ $order->status === Order::STATUS_CANCELLED ? 'Order cancelled' : ($order->status === Order::STATUS_AWAITING_PAYMENT ? 'Payment pending' : 'Order status') }}</h2>
                    <p class="mt-1 text-body-sm text-body-muted">{{ $statusText }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($order->canBeCancelled())
                        <button type="button" wire:click="cancel" wire:confirm="Cancel order {{ $order->number }}? This can’t be undone."
                                class="rounded-full border border-ink px-4 py-1.5 text-[13px] font-bold">Cancel order</button>
                    @endif
                    @if ($order->status !== Order::STATUS_AWAITING_PAYMENT)
                        <button type="button" wire:click="buyAgain" class="rounded-full border border-ink px-4 py-1.5 text-[13px] font-bold">Buy again</button>
                    @endif
                </div>
            </div>

            @if ($order->status !== Order::STATUS_CANCELLED)
                <ol class="mt-6 grid grid-cols-4">
                    @foreach ($steps as $i => $step)
                        <li class="relative flex flex-col gap-3 text-left">
                            @if ($i < count($steps) - 1)
                                <span @class(['absolute left-8 right-2 top-3 h-px', 'bg-ink' => $i < $current, 'bg-grayscale-40' => $i >= $current]) aria-hidden="true"></span>
                            @endif
                            <span @class([
                                'relative flex size-6 items-center justify-center rounded-full text-[11px] font-bold',
                                'bg-ink text-white' => $i <= $current,
                                'border border-grayscale-40 bg-surface text-body-muted' => $i > $current,
                            ])>
                                @if ($i <= $current)
                                    <x-store.icon name="check" />
                                @else
                                    {{ $i + 1 }}
                                @endif
                            </span>
                            <span @class(['pr-2 text-[12px] leading-4 md:text-[14px]', 'font-medium text-ink' => $i <= $current, 'text-body-muted' => $i > $current])>
                                {{ $step }}
                                @if ($i === $current)<span class="sr-only">(current step)</span>@endif
                            </span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        <section class="mt-8 overflow-hidden rounded-sm border border-grayscale-40" aria-labelledby="items-title">
            <h2 id="items-title" class="px-5 py-5 text-[18px] font-bold">Items in your order ({{ $count }})</h2>
            <table class="w-full text-left text-[14px]">
                <thead class="bg-grayscale-0 text-[11px] font-bold uppercase tracking-[0.3px] text-grayscale-80">
                    <tr>
                        <th scope="col" class="px-5 py-2.5">Product</th>
                        <th scope="col" class="px-3 py-2.5 text-center">Quantity</th>
                        <th scope="col" class="hidden px-3 py-2.5 md:table-cell">Unit price</th>
                        <th scope="col" class="px-5 py-2.5 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-grayscale-30">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-4">
                                    <span class="flex size-[72px] shrink-0 items-center justify-center rounded-sm border border-grayscale-40">
                                        @if ($item->image)
                                            <img src="{{ asset($item->image) }}" alt="" class="size-full rounded-sm object-cover">
                                        @endif
                                    </span>
                                    <span>
                                        <span class="block text-[15px] font-bold">{{ $item->name }}</span>
                                        @if ($item->variation_name)
                                            <span class="block text-[12px] text-body-muted">{{ $item->variation_name }}</span>
                                        @endif
                                    </span>
                                </div>
                            </td>
                            <td class="px-3 py-4 text-center">{{ $item->quantity }}</td>
                            <td class="hidden px-3 py-4 text-body-muted md:table-cell">{{ money($item->unit_price_cents) }}</td>
                            <td class="px-5 py-4 text-right text-[16px] font-bold">{{ money($item->line_total_cents) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <div class="mt-8 grid gap-6 md:grid-cols-2 md:items-start">
            <section class="rounded-sm border border-grayscale-40" aria-labelledby="fulfilment-title">
                <h2 id="fulfilment-title" class="border-b border-grayscale-30 px-5 py-5 text-[18px] font-bold">Fulfillment information</h2>
                <div class="flex flex-col gap-5 px-5 py-5 text-[14px]">
                    <p class="flex items-center gap-2 text-[15px] font-bold">
                        <x-store.icon name="location" class="size-5" />
                        {{ $order->fulfilment === 'pickup' ? 'Store pickup' : 'Delivery' }}
                    </p>

                    @if ($order->fulfilment === 'pickup')
                        <p class="-mt-2 text-body-muted">{{ config('shop.pickup.location') }}, Canada · Pickup fee {{ money(0) }}</p>
                    @endif

                    <div>
                        <p class="text-[15px] font-bold">{{ $order->contact_name }}</p>
                        @if ($order->contact_email)<p class="mt-1 text-body-muted">{{ $order->contact_email }}</p>@endif
                        @if ($order->contact_phone)<p class="mt-1 text-body-muted">{{ $order->contact_phone }}</p>@endif
                    </div>

                    @if ($order->fulfilment === 'pickup' && $order->pickup_starts_at)
                        <div>
                            <p class="text-[11px] font-medium uppercase tracking-[0.3px] text-grayscale-80">Selected pickup slot</p>
                            <p class="mt-1 text-[16px]">{{ Checkout::windowLabel(CarbonImmutable::parse($order->pickup_starts_at)) }}</p>
                        </div>
                        <p class="text-body-muted">We’ll let you know when your groceries are ready. Bring your order number when collecting.</p>
                    @elseif ($address = $order->delivery_address)
                        <div>
                            <p class="text-[15px] font-bold">Delivery address</p>
                            <p class="mt-1 text-body-muted">{{ $address['recipient_name'] }}</p>
                            <p class="text-body-muted">{{ $address['street'] }}, {{ $address['city'] }}{{ ! empty($address['region']) ? ', '.$address['region'] : '' }}, Canada</p>
                            <p class="text-body-muted">Postcode: {{ $address['postal_code'] }}</p>
                        </div>
                        <p class="text-body-muted">We’ll let you know as your groceries are prepared and go out for delivery.</p>
                    @endif

                    <p class="border-t border-grayscale-30 pt-4 text-body-muted">Order updates go to {{ $contact }}.</p>
                </div>
            </section>

            <section class="rounded-sm border border-grayscale-40" aria-labelledby="payment-title">
                <h2 id="payment-title" class="border-b border-grayscale-30 px-5 py-5 text-[18px] font-bold">Payment information</h2>
                <dl class="flex flex-col divide-y divide-grayscale-30 px-5 py-2 text-[14px]">
                    @if ($order->savings_cents)
                        <div class="flex justify-between py-3"><dt class="text-body-muted">Retail price ({{ $count }} {{ str('item')->plural($count) }})</dt><dd class="text-body-muted line-through">{{ money($order->subtotal_cents + $order->savings_cents) }}</dd></div>
                    @endif
                    <div class="flex justify-between py-3"><dt class="text-body-muted">Subtotal</dt><dd>{{ money($order->subtotal_cents) }}</dd></div>
                    <div class="flex justify-between py-3"><dt class="text-body-muted">{{ $order->fulfilment === 'pickup' ? 'Pickup fee' : 'Delivery fee' }}</dt><dd>{{ money($order->delivery_fee_cents) }}</dd></div>
                    @if ($order->discount_cents)
                        <div class="flex justify-between py-3"><dt class="text-body-muted">Coupon {{ $order->coupon_code }}</dt><dd class="text-stock-50">&minus;{{ money($order->discount_cents) }}</dd></div>
                    @endif
                    <div class="flex justify-between py-4 text-[18px] font-bold"><dt>Total</dt><dd>{{ money($order->total_cents) }}</dd></div>
                </dl>
                <p class="flex items-center gap-2 px-5 pb-5 text-[13px] text-body-muted">
                    <x-store.icon name="card" class="size-4" />
                    @if ($order->status === Order::STATUS_PAID)
                        Payment method: <strong class="text-ink">{{ \App\Models\PaymentMethod::where('code', $order->payment_method)->first()?->label() ?? str($order->payment_method)->headline() }}</strong> · paid {{ $order->paid_at?->setTimezone(config('shop.timezone'))->format('j M Y') }}
                    @elseif ($order->status === Order::STATUS_CANCELLED)
                        Nothing was charged for this order.
                    @elseif ($order->isUnpaid() && $order->status === Order::STATUS_PLACED)
                        Pay at the counter when you collect.
                    @else
                        Waiting for the payment service to confirm.
                    @endif
                </p>
            </section>
        </div>
    </x-store.account-shell>
</div>
