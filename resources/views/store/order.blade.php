@php
    use App\Livewire\Store\Checkout;
    use App\Models\Order;

    $justPlaced = session('order_placed') === $order->number;
    $receiptTo = $order->contact_email ?? $order->contact_phone;
    $steps = $order->trackerSteps();
    $current = $order->trackerStep();
@endphp

<x-layouts::store :title="'Order '.$order->number">
    <div class="page-container pt-6 pb-20 md:pt-10">
        <nav aria-label="Breadcrumb" class="text-[11px] leading-[14px] font-semibold text-body-muted">
            <ol class="flex items-center gap-1.5">
                <li><a href="{{ route('home') }}" class="hover:text-ink">Home</a></li>
                <li aria-hidden="true">/</li>
                <li class="font-bold text-ink" aria-current="page">{{ $justPlaced ? 'Order Confirmation' : 'Order '.$order->number }}</li>
            </ol>
        </nav>

        <section class="mx-auto mt-8 flex max-w-[684px] flex-col items-center rounded-lg border border-grayscale-30 px-6 py-8 text-center md:px-8">
            @if ($order->isConfirmed())
                <span class="flex size-[72px] items-center justify-center rounded-full bg-brand-yellow-0 text-ink">
                    <x-store.icon name="box" class="size-8" />
                </span>
                <h1 class="mt-5 text-h2-mobile md:text-h2">{{ $justPlaced ? 'Thank You, Your Order Is Confirmed' : 'Order '.$order->number }}</h1>
                <p class="mt-3 max-w-[480px] text-lead-mobile font-normal text-body-muted md:text-lead">
                    Your order <strong class="text-ink">{{ $order->number }}</strong> is in.
                    @if ($receiptTo)
                        A receipt is on its way to {{ $receiptTo }}.
                    @endif
                </p>
            @elseif ($order->isAwaitingETransfer())
                <span class="flex size-[72px] items-center justify-center rounded-full bg-brand-yellow-0 text-ink">
                    <x-store.icon name="mail" class="size-8" />
                </span>
                <h1 class="mt-5 text-h2-mobile md:text-h2">{{ $justPlaced ? 'Order Placed — Send Your e-Transfer' : 'Order '.$order->number }}</h1>
                <p class="mt-3 max-w-[480px] text-lead-mobile font-normal text-body-muted md:text-lead">
                    Your order <strong class="text-ink">{{ $order->number }}</strong> is reserved. Send the Interac e-Transfer below and we’ll confirm it by email.
                </p>
                <x-store.etransfer-instructions :order="$order" class="mt-6" />
            @elseif ($order->status === Order::STATUS_PAYMENT_FAILED)
                <span class="flex size-[72px] items-center justify-center rounded-full bg-sale-10 text-price-40"><x-store.icon name="card" class="size-8" /></span>
                <h1 class="mt-5 text-h2-mobile md:text-h2">Payment Not Completed</h1>
                <p class="mt-3 max-w-[480px] text-lead-mobile font-normal text-body-muted md:text-lead">This order wasn’t placed because the payment didn’t go through. Your basket kept your items.</p>
            @else
                <span class="flex size-[72px] items-center justify-center rounded-full bg-brand-yellow-0"><span class="size-10 animate-spin rounded-full border-[3px] border-grayscale-30 border-t-ink"></span></span>
                <h1 class="mt-5 text-h2-mobile md:text-h2">Confirming Your Payment</h1>
                <p class="mt-3 max-w-[480px] text-lead-mobile font-normal text-body-muted md:text-lead">We’re waiting for the payment service to confirm order {{ $order->number }}. Please don’t pay again — refresh this page in a few minutes.</p>
            @endif

            <div class="mt-6 grid w-full max-w-[492px] gap-3 sm:grid-cols-2">
                <a href="#order-details" class="flex h-[42px] items-center justify-center rounded-full bg-ink text-[15px] font-bold text-white">View order</a>
                <a href="{{ route('shop') }}" class="flex h-[42px] items-center justify-center rounded-full border border-ink text-[15px] font-bold">Keep shopping</a>
            </div>

            @if ($order->isConfirmed())
                <div class="mt-8 w-full rounded-lg border border-grayscale-30 p-6 text-left">
                    <p class="text-[15px] font-bold">Order status</p>
                    <p class="mt-1 text-body-sm text-body-muted">
                        Your order is confirmed. We’ll let you know when it’s {{ $order->fulfilment === 'pickup' ? 'ready to collect' : 'on its way' }}.
                    </p>
                    <ol class="mt-6 grid grid-cols-4">
                        @foreach ($steps as $i => $step)
                            <li class="relative flex flex-col items-center gap-3 text-center">
                                @if ($i > 0)
                                    <span @class(['absolute right-1/2 top-3 h-px w-full -translate-x-3', 'bg-ink' => $i <= $current, 'bg-grayscale-40' => $i > $current]) aria-hidden="true"></span>
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
                                <span @class(['text-[12px] leading-4', 'font-medium text-ink' => $i <= $current, 'text-body-muted' => $i > $current])>{{ $step }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </section>

        <section id="order-details" class="mx-auto mt-8 max-w-[684px] scroll-mt-6 rounded-lg border border-grayscale-40" aria-label="Order details">
            <div class="flex flex-wrap justify-between gap-3 border-b border-grayscale-40 px-6 py-4 text-[14px]">
                <div>
                    @if ($order->fulfilment === 'pickup')
                        <p class="font-bold">Store collection · {{ config('shop.pickup.location') }}</p>
                        <p class="mt-1">{{ Checkout::windowLabel(\Carbon\CarbonImmutable::parse($order->pickup_starts_at)) }}</p>
                    @else
                        <p class="font-bold">Delivery to {{ $order->delivery_address['recipient_name'] }}</p>
                        <p class="mt-1">{{ $order->delivery_address['street'] }}, {{ $order->delivery_address['city'] }} {{ $order->delivery_address['postal_code'] }}</p>
                    @endif
                </div>
                <div class="text-right">
                    <p class="font-bold">{{ match ($order->status) {
                        Order::STATUS_PAID => $order->payment_method === 'etransfer' ? 'Paid by e-Transfer' : 'Paid online',
                        Order::STATUS_PLACED => 'Pay at pickup',
                        Order::STATUS_PAYMENT_FAILED => 'Payment declined',
                        Order::STATUS_CANCELLED => 'Cancelled',
                        default => $order->payment_method === 'etransfer' ? 'Awaiting e-Transfer' : 'Awaiting payment',
                    } }}</p>
                    <p class="mt-1 text-body-muted">Placed {{ $order->placed_at->setTimezone(config('shop.timezone'))->format('j M Y, H:i') }}</p>
                </div>
            </div>

            <ul class="flex flex-col gap-3 border-b border-grayscale-40 px-6 py-4">
                @foreach ($order->items as $item)
                    <li class="flex items-center gap-3 text-[14px]">
                        @if ($item->image)
                            <img src="{{ asset($item->image) }}" alt="" class="size-10 rounded-sm object-cover">
                        @endif
                        <span class="flex-1">{{ $item->name }} <span class="text-body-muted">· Qty {{ $item->quantity }}</span></span>
                        <span class="font-bold">{{ money($item->line_total_cents) }}</span>
                    </li>
                @endforeach
            </ul>

            <dl class="flex flex-col gap-2 px-6 py-4 text-[14px]">
                <div class="flex justify-between"><dt class="text-body-muted">Subtotal</dt><dd>{{ money($order->subtotal_cents) }}</dd></div>
                @if ($order->savings_cents)
                    <div class="flex justify-between"><dt class="text-body-muted">Product savings (included)</dt><dd class="text-stock-50">&minus;{{ money($order->savings_cents) }}</dd></div>
                @endif
                @if ($order->discount_cents)
                    <div class="flex justify-between"><dt class="text-body-muted">Coupon {{ $order->coupon_code }}</dt><dd class="text-stock-50">&minus;{{ money($order->discount_cents) }}</dd></div>
                @endif
                <div class="flex justify-between"><dt class="text-body-muted">{{ $order->fulfilment === 'pickup' ? 'Pickup fee' : 'Delivery fee' }}</dt><dd>{{ money($order->delivery_fee_cents) }}</dd></div>
                <div class="mt-2 flex justify-between text-[18px] font-bold"><dt>Total</dt><dd>{{ money($order->total_cents) }}</dd></div>
            </dl>
        </section>
    </div>
</x-layouts::store>
