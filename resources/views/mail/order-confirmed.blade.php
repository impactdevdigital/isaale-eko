Thank you, {{ str($order->contact_name)->before(' ') }} — your order {{ $order->number }} is confirmed.

@foreach ($order->items as $item)
- {{ $item->name }} × {{ $item->quantity }}: {{ money($item->line_total_cents) }}
@endforeach

Subtotal: {{ money($order->subtotal_cents) }}
@if ($order->discount_cents)
Coupon {{ $order->coupon_code }}: -{{ money($order->discount_cents) }}
@endif
{{ $order->fulfilment === 'pickup' ? 'Pickup' : 'Delivery' }} fee: {{ money($order->delivery_fee_cents) }}
Total: {{ money($order->total_cents) }} {{ $order->status === \App\Models\Order::STATUS_PAID ? '(paid)' : '(pay at pickup)' }}

@if ($order->fulfilment === 'pickup')
Collect from our {{ config('shop.pickup.location') }} store: {{ \App\Livewire\Store\Checkout::windowLabel(\Carbon\CarbonImmutable::parse($order->pickup_starts_at)) }}
@else
Delivering to: {{ $order->delivery_address['street'] }}, {{ $order->delivery_address['city'] }} {{ $order->delivery_address['postal_code'] }}
@endif

View your order: {{ route('orders.show', $order) }}
