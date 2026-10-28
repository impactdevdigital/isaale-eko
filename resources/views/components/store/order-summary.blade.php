{{--
    Order Summary card shared by the basket and checkout. Lives inside a Livewire
    component that has couponCode / couponResult and applyCoupon / removeCoupon.
--}}
@props([
    'lines',
    'totals',
    'couponResult' => null,
    'feeLabel' => 'Estimated delivery',
    'feeValue' => 'Calculated at checkout',
    'totalLabel' => null,
    'showFreeDeliveryHint' => false,
    'savingsLabel' => 'Product savings (included)',
    'note' => null,
    'noteTone' => 'ok',
])

@php
    $couponMessages = [
        'invalid' => 'This code is not valid. Check it and try again.',
        'expired' => 'This code has expired. Try another code.',
        'minimum' => 'Your basket doesn’t reach this code’s minimum spend yet.',
    ];
@endphp

<aside {{ $attributes->merge(['class' => 'overflow-hidden rounded-sm border border-grayscale-40 bg-surface']) }} aria-label="Order summary">
    <h2 class="bg-ink px-6 py-[18px] text-[18px] leading-6 font-bold text-white">Order Summary</h2>

    @if ($lines->isNotEmpty())
        <ul class="flex max-h-[200px] flex-col gap-4 overflow-y-auto border-b border-grayscale-40 px-6 py-5">
            @foreach ($lines as $line)
                <li class="flex items-center gap-2.5">
                    <img src="{{ asset($line->variation->product->image) }}" alt="" class="size-10 shrink-0 rounded-sm object-cover">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-h5">{{ $line->variation->product->name }}</p>
                        <p class="text-body-xs font-normal text-body-muted">{{ collect([$line->variation->name, 'Qty '.$line->quantity])->filter()->join(' · ') }}</p>
                    </div>
                    <span class="text-[14px] font-bold">{{ money($line->variation->price_cents * $line->quantity) }}</span>
                </li>
            @endforeach
        </ul>

        <form wire:submit="applyCoupon" class="flex flex-col gap-2 border-b border-grayscale-40 px-6 py-5">
            <label for="coupon-code" class="text-[12px] leading-4 font-bold">Promo / voucher code</label>
            <div class="flex">
                <input id="coupon-code" type="text" wire:model="couponCode" placeholder="Enter code" autocomplete="off"
                       @readonly($totals->coupon)
                       class="h-9 min-w-0 flex-1 rounded-l-sm border border-r-0 border-grayscale-40 bg-grayscale-0 px-3 text-[14px] uppercase placeholder:normal-case focus:border-ink focus:outline-none">
                @if ($totals->coupon)
                    <button type="button" wire:click="removeCoupon" class="h-9 rounded-r-sm bg-ink px-4 text-button-sm text-white" title="Remove code">Applied</button>
                @else
                    <button type="submit" class="h-9 rounded-r-sm bg-ink px-4 text-button-sm text-white">Apply</button>
                @endif
            </div>
            @error('couponCode') <p class="text-[11px] font-bold text-price-40">{{ $message }}</p> @enderror
            @if ($totals->coupon)
                <p class="text-[11px] font-bold text-stock-50">{{ $totals->coupon->code }} applied — you saved {{ money($totals->discount) }}.</p>
            @elseif ($couponResult && ($couponMessages[$couponResult] ?? null))
                <p class="text-[11px] font-bold text-price-40" role="alert">{{ $couponMessages[$couponResult] }}</p>
            @endif
        </form>
    @endif

    <dl class="flex flex-col gap-3 border-b border-grayscale-40 px-6 py-5 text-[14px]">
        <div class="flex justify-between">
            <dt class="text-grayscale-80">Subtotal ({{ $totals->itemCount }} {{ str('item')->plural($totals->itemCount) }})</dt>
            <dd class="font-bold">{{ money($totals->subtotal) }}</dd>
        </div>
        @if ($totals->savings)
            <div class="flex justify-between">
                <dt class="text-grayscale-80">{{ $savingsLabel }}</dt>
                <dd class="font-bold text-stock-50">&minus;{{ money($totals->savings) }}</dd>
            </div>
        @endif
        <div class="flex justify-between gap-4">
            <dt class="text-grayscale-80">{{ $feeLabel }}</dt>
            <dd class="text-right text-[12px] font-bold">{{ $feeValue }}</dd>
        </div>
        @if ($totals->discount)
            <div class="flex justify-between">
                <dt class="text-grayscale-80">Coupon discount</dt>
                <dd class="font-bold text-stock-50">&minus;{{ money($totals->discount) }}</dd>
            </div>
        @endif
        @if ($showFreeDeliveryHint && $totals->subtotal > 0)
            <div class="rounded-sm bg-stock-0 px-3 py-1.5 text-[11px] font-medium text-stock-50">
                @if ($totals->untilFreeDelivery())
                    🚚 Add {{ money($totals->untilFreeDelivery()) }} more for FREE delivery
                @else
                    🚚 You’ve unlocked FREE delivery
                @endif
            </div>
        @endif
        @if ($note)
            <div @class([
                'rounded-sm px-3 py-1.5 text-[11px] font-medium',
                'bg-stock-0 text-stock-50' => $noteTone === 'ok',
                'bg-brand-yellow-10 text-brand-yellow-90' => $noteTone === 'pending',
                'bg-sale-0 text-price-40' => $noteTone === 'error',
            ]) role="status">{{ $note }}</div>
        @endif
    </dl>

    <div class="flex items-center justify-between border-b border-grayscale-40 px-6 py-6">
        <span class="text-[16px] font-bold">Total</span>
        @if ($totalLabel)
            <span class="text-[14px] font-bold">{{ $totalLabel }}</span>
        @else
            <strong class="text-[32px] leading-9 font-bold tracking-[-1.25px]">{{ money($totals->total()) }}</strong>
        @endif
    </div>

    <div class="flex flex-col gap-3 px-6 py-6">
        {{ $slot }}
        <p class="flex items-center justify-center gap-1.5 text-[11px] text-stock-50">
            <x-store.icon name="shield" class="size-3.5" /> Secure checkout
        </p>
    </div>

    <div class="bg-grayscale-0 px-6 py-5">
        <p class="text-eyebrow uppercase text-body-muted">We accept</p>
        <ul class="mt-3 flex flex-wrap gap-1.5 text-[10px] font-bold">
            @foreach (['Visa', 'Mastercard', 'Amex', 'Apple Pay', 'Google Pay', 'Interac'] as $brand)
                <li class="rounded-[3px] border border-grayscale-40 bg-surface px-2 py-1">{{ $brand }}</li>
            @endforeach
        </ul>
        <p class="mt-3 text-[10px] text-body-muted">All transactions are encrypted and secure.</p>
    </div>
</aside>
