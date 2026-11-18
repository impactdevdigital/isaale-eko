{{--
    Order Summary card shared by the basket and checkout. Lives inside a Livewire
    component that uses SpendsLoyaltyReward (useReward / dropReward).
--}}
@props([
    'lines',
    'totals',
    'rewardOffer' => null,
    'feeLabel' => 'Estimated delivery',
    'feeValue' => 'Calculated at checkout',
    'totalLabel' => null,
    'showFreeDeliveryHint' => false,
    'note' => null,
    'noteTone' => 'ok',
])

<aside {{ $attributes->merge(['class' => 'overflow-hidden rounded-sm border border-grayscale-40 bg-surface']) }} aria-label="Order summary">
    <h2 class="bg-ink px-6 py-[18px] text-[18px] leading-6 font-bold text-white">Order Summary</h2>

    @if ($lines->isNotEmpty())
        <ul class="flex max-h-[200px] flex-col gap-4 overflow-y-auto border-b border-grayscale-40 px-6 py-5">
            @foreach ($lines as $line)
                <li class="flex items-center gap-2.5">
                    <img src="{{ asset($line->variation->product->image) }}" alt="" class="size-10 shrink-0 rounded-sm object-cover">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-h5">{{ $line->variation->product->localized_name }}</p>
                        <p class="text-body-xs font-normal text-body-muted">{{ collect([$line->variation->name, 'Qty '.$line->quantity])->filter()->join(' · ') }}</p>
                    </div>
                    <span class="text-[14px] font-bold">{{ money($line->variation->price_cents * $line->quantity) }}</span>
                </li>
            @endforeach
        </ul>

        @if ($rewardOffer)
            @php $rewardOn = $totals->reward || $totals->setAsideReward; @endphp
            <div class="flex items-center gap-3 border-b border-grayscale-40 px-6 py-5">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-purple text-white"><x-store.icon name="gift" class="size-[18px]" /></span>
                <div class="min-w-0 flex-1">
                    <p class="text-[12px] leading-4 font-bold">{{ $rewardOn ? 'Loyalty reward applied' : 'You have a loyalty reward' }}</p>
                    <p class="text-[11px] text-body-muted">{{ $rewardOffer->name }} · uses {{ $rewardOffer->points }} points</p>
                </div>
                @if ($rewardOn)
                    <button type="button" wire:click="dropReward" class="h-9 shrink-0 rounded-sm border border-ink px-3 text-button-sm">Remove</button>
                @else
                    <button type="button" wire:click="useReward" class="h-9 shrink-0 rounded-sm bg-ink px-4 text-button-sm text-white">Use it</button>
                @endif
            </div>
        @endif
    @endif

    <dl class="flex flex-col gap-3 border-b border-grayscale-40 px-6 py-5 text-[14px]">
        <div class="flex justify-between">
            <dt class="text-grayscale-80">Subtotal ({{ $totals->itemCount }} {{ str('item')->plural($totals->itemCount) }})</dt>
            <dd class="font-bold">{{ money($totals->subtotal) }}</dd>
        </div>
        <div class="flex justify-between gap-4">
            <dt class="text-grayscale-80">{{ $feeLabel }}</dt>
            <dd class="text-right text-[12px] font-bold">{{ $feeValue }}</dd>
        </div>
        @if ($totals->discount)
            <div class="flex justify-between gap-4">
                <dt class="text-grayscale-80">{{ $totals->promotion ? $totals->promotion->promotion->name : 'Loyalty reward' }}</dt>
                <dd class="font-bold text-stock-50">&minus;{{ money($totals->discount) }}</dd>
            </div>
        @endif
        @if ($totals->setAsideReward)
            <div class="rounded-sm bg-brand-yellow-10 px-3 py-1.5 text-[11px] font-medium text-brand-yellow-90" role="status">
                Your reward isn’t used and your points stay saved: {{ $totals->promotion->promotion->name }} saves you more. One discount per order.
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
