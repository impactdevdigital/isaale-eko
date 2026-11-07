@php($etransfer = config('payments.etransfer'))
@if ($order->isAwaitingETransfer())
Thank you, {{ str($order->contact_name)->before(' ') }} — we’ve reserved your order {{ $order->number }}.

To complete it, send an Interac e-Transfer:
- To: {{ $etransfer['email'] }} ({{ $etransfer['recipient'] }})
- Amount: {{ money($order->total_cents) }}
- Message: {{ $order->number }}

We start preparing your order once the transfer arrives. Please send it by {{ $order->placed_at->copy()->addHours($etransfer['hold_hours'])->setTimezone(config('shop.timezone'))->format('l j M, H:i') }} or the order may be cancelled.
@else
Thank you, {{ str($order->contact_name)->before(' ') }} — your order {{ $order->number }} is confirmed.
@endif

@foreach ($order->items as $item)
- {{ $item->name }} × {{ $item->quantity }}: {{ money($item->line_total_cents) }}
@endforeach

Subtotal: {{ money($order->subtotal_cents) }}
@if ($order->discount_cents)
Coupon {{ $order->coupon_code }}: -{{ money($order->discount_cents) }}
@endif
{{ $order->fulfilment === 'pickup' ? 'Pickup' : 'Delivery' }} fee: {{ money($order->delivery_fee_cents) }}
Total: {{ money($order->total_cents) }} {{ match (true) {
    $order->status === \App\Models\Order::STATUS_PAID => $order->payment_method === 'etransfer' ? '(paid by e-Transfer)' : '(paid)',
    $order->isAwaitingETransfer() => '(to send by Interac e-Transfer)',
    default => '(pay at pickup)',
} }}

@if ($order->fulfilment === 'pickup')
Collect from our {{ config('shop.pickup.location') }} store: {{ \App\Livewire\Store\Checkout::windowLabel(\Carbon\CarbonImmutable::parse($order->pickup_starts_at)) }}
@else
Delivering to: {{ $order->delivery_address['street'] }}, {{ $order->delivery_address['city'] }} {{ $order->delivery_address['postal_code'] }}
@endif

View your order: {{ route('orders.show', $order) }}
