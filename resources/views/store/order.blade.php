{{-- Interim order page; the designed confirmation and payment states arrive with Figma section 05. --}}
@php
    use App\Livewire\Store\Checkout;
    use App\Models\Order;
@endphp

<x-layouts::store :title="'Order '.$order->number">
    <div class="page-container flex flex-col items-center pt-10 pb-20 text-center">
        <span class="flex size-16 items-center justify-center rounded-full bg-stock-0 text-stock-50">
            <x-store.icon name="check" class="size-7" />
        </span>
        <h1 class="mt-6 text-h2-mobile md:text-h2">Thanks, {{ str($order->contact_name)->before(' ') }}!</h1>
        <p class="mt-2 text-lead text-body-muted">Order <strong class="text-ink">{{ $order->number }}</strong></p>

        <p class="mt-4 max-w-[520px] text-body text-body-muted">
            @if ($order->status === Order::STATUS_PLACED)
                Your order is confirmed. Pay at the counter when you collect it.
            @else
                Your order is saved and waiting for payment. Online card payment is coming in the next update — we’ll be in touch to complete it.
            @endif
        </p>

        <div class="mt-8 w-full max-w-[560px] rounded-sm border border-grayscale-40 text-left">
            <div class="border-b border-grayscale-40 px-6 py-4 text-[14px]">
                @if ($order->fulfilment === 'pickup')
                    <p class="font-bold">Store collection · {{ config('shop.pickup.location') }}</p>
                    <p class="mt-1">{{ Checkout::windowLabel(\Carbon\CarbonImmutable::parse($order->pickup_starts_at)) }}</p>
                @else
                    <p class="font-bold">Delivery to {{ $order->delivery_address['recipient_name'] }}</p>
                    <p class="mt-1">{{ $order->delivery_address['street'] }}, {{ $order->delivery_address['city'] }} {{ $order->delivery_address['postal_code'] }}</p>
                @endif
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
                @if ($order->discount_cents)
                    <div class="flex justify-between"><dt class="text-body-muted">Coupon {{ $order->coupon_code }}</dt><dd class="text-stock-50">&minus;{{ money($order->discount_cents) }}</dd></div>
                @endif
                <div class="flex justify-between"><dt class="text-body-muted">{{ $order->fulfilment === 'pickup' ? 'Pickup fee' : 'Delivery fee' }}</dt><dd>{{ money($order->delivery_fee_cents) }}</dd></div>
                <div class="mt-2 flex justify-between text-[18px] font-bold"><dt>Total</dt><dd>{{ money($order->total_cents) }}</dd></div>
            </dl>
        </div>

        <a href="{{ route('shop') }}" class="mt-8 rounded-full bg-ink px-6 py-3 text-[15px] font-bold text-white">Keep shopping</a>
    </div>
</x-layouts::store>
