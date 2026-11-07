@props(['order'])

@php
    $etransfer = config('payments.etransfer');
    $payBy = $order->placed_at->copy()->addHours($etransfer['hold_hours'])->setTimezone(config('shop.timezone'));
@endphp

{{-- Interac e-Transfer details for an order that's waiting for the shopper's transfer. --}}
<div {{ $attributes->class('w-full rounded-lg border border-[#fdb913] bg-brand-yellow-0 p-5 text-left md:p-6') }}>
    <p class="flex items-center gap-2 text-[15px] font-bold">
        <span class="rounded-[3px] bg-[#fdb913] px-1.5 py-0.5 text-[9px] font-extrabold text-black">INTERAC</span>
        Send your e-Transfer to complete this order
    </p>

    <dl class="mt-4 grid gap-3 text-[14px] sm:grid-cols-2">
        <div>
            <dt class="text-[12px] text-body-muted">Send to</dt>
            <dd class="mt-0.5 font-bold break-all">{{ $etransfer['email'] }}</dd>
        </div>
        <div>
            <dt class="text-[12px] text-body-muted">Recipient name</dt>
            <dd class="mt-0.5 font-bold">{{ $etransfer['recipient'] }}</dd>
        </div>
        <div>
            <dt class="text-[12px] text-body-muted">Amount</dt>
            <dd class="mt-0.5 font-bold">{{ money($order->total_cents) }}</dd>
        </div>
        <div>
            <dt class="text-[12px] text-body-muted">Message</dt>
            <dd class="mt-0.5 font-bold">{{ $order->number }}</dd>
        </div>
    </dl>

    <p class="mt-4 text-body-sm text-body-muted">
        Put your order number in the message so we can match your payment. We start preparing your order once the transfer arrives.
        Please send it by <strong class="text-ink">{{ $payBy->format('l j M, H:i') }}</strong> or the order may be cancelled.
    </p>
</div>
