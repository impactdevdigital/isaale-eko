@php
    use App\Models\PointTransaction;

    $value = money($progress->rewardValueCents);
    $block = money($progress->blockCents);
    $soonest = $progress->rewards->first();
@endphp

<div>
    <x-store.account-shell active="rewards" :crumbs="['Your account' => route('account'), 'Rewards' => null]">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <h1 class="text-h2-mobile md:text-h2">Rewards</h1>
            <p class="text-body text-body-muted">Every {{ $block }} you spend unlocks {{ $value }} off</p>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-[3fr_3fr]">
            <div class="relative overflow-hidden rounded-sm bg-[repeating-linear-gradient(115deg,var(--color-brand-yellow-40)_0_70px,var(--color-brand-yellow-20)_70px_140px)] p-5">
                <img src="{{ asset('images/account/diamond-heart.svg') }}" alt="" class="absolute right-4 top-1/2 size-[110px] -translate-y-1/2">
                <p class="relative flex items-center gap-2 text-[11px] leading-[14px] font-bold uppercase tracking-[1px]">
                    <x-store.icon name="lovely" class="size-[18px]" /> Towards your next reward
                </p>
                <p class="relative mt-3 text-[28px] leading-8 font-bold">{{ money($progress->countedCents) }} <span class="text-[18px]">/ {{ $block }}</span></p>
                <div class="relative mt-3 h-2 max-w-[260px] overflow-hidden rounded-full bg-white/60" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress->percent() }}" aria-label="Progress to your next reward">
                    <div class="h-full rounded-full bg-ink" style="width: {{ $progress->percent() }}%"></div>
                </div>
                <p class="relative mt-2 text-[13px] font-bold">{{ $progress->remainingCents() ? money($progress->remainingCents()).' more to unlock '.$value.' off' : $value.' off unlocks with your next paid order' }}</p>
            </div>
            <div class="rounded-sm border border-grayscale-40 p-5">
                <p class="flex items-center gap-2 text-[11px] leading-[14px] font-bold uppercase tracking-[0.5px] text-grayscale-80">
                    <x-store.icon name="gift" class="size-[18px]" /> Ready to use
                </p>
                <p class="mt-3 text-[28px] leading-8 font-bold">{{ $progress->rewards->count() }}</p>
                <p class="mt-2 text-[13px] text-body-muted">
                    {{ $soonest ? 'Your next one expires '.$soonest->expires_at->setTimezone(config('shop.timezone'))->format('j M Y').'.' : 'Rewards last 30 days once unlocked.' }}
                </p>
            </div>
        </div>

        <h2 class="mt-8 text-[18px] font-bold">Your rewards</h2>

        @if ($appliedNotice)
            <p class="mt-4 flex flex-wrap items-center gap-2 rounded-sm bg-stock-0 px-4 py-3 text-body-sm text-stock-50" role="status">
                <x-store.icon name="check" /> {{ $appliedNotice }}
            </p>
        @endif

        @forelse ($progress->rewards as $reward)
            <div wire:key="reward-{{ $reward->id }}" class="mt-4 flex flex-col gap-4 rounded-sm bg-brand-purple px-6 py-5 text-white sm:flex-row sm:items-center">
                <span class="flex size-[52px] shrink-0 items-center justify-center rounded-full bg-white/20">
                    <x-store.icon name="receipt" class="size-6" />
                </span>
                <div class="flex-1">
                    <p class="text-[22px] leading-7 font-bold">{{ money($reward->value) }} off an order of {{ money($reward->min_subtotal_cents) }}+</p>
                    <p class="mt-1 text-[13px] text-white/80">
                        Code <span class="font-mono">{{ $reward->code }}</span> · use by {{ $reward->expires_at->setTimezone(config('shop.timezone'))->format('j M Y') }} · can’t be combined with other offers
                    </p>
                </div>
                @if ($appliedCode === $reward->code)
                    <span class="flex h-[42px] shrink-0 items-center gap-1 rounded-full bg-white/20 px-5 text-[15px] font-bold"><x-store.icon name="check" class="size-4" /> On your basket</span>
                @else
                    <button type="button" wire:click="useReward({{ $reward->id }})" class="h-[42px] shrink-0 rounded-full bg-white px-5 text-[15px] font-bold text-ink">Use on my basket</button>
                @endif
            </div>
        @empty
            <div class="mt-4 rounded-sm bg-grayscale-0 px-6 py-8 text-center">
                <p class="text-[16px] font-bold">No rewards ready yet</p>
                <p class="mt-1 text-body-sm text-body-muted">{{ $progress->remainingCents() ? 'Spend '.money($progress->remainingCents()).' more and '.$value.' off unlocks automatically.' : $value.' off unlocks with your next paid order.' }}</p>
            </div>
        @endforelse

        <p class="mt-3 text-[12px] text-body-muted">
            Your spend counts once an order is paid (online, or at the counter when you collect). Delivery fees and tax don’t count. Anything over {{ $block }} carries over to the next reward. Refunds take back what they earned.
        </p>

        <div class="mt-8 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-[18px] font-bold">History</h2>
            <div class="flex" role="tablist" aria-label="Rewards history">
                @foreach (['all' => 'All', 'earned' => 'Counted', 'used' => 'Unlocked & refunds'] as $key => $label)
                    <button type="button" role="tab" wire:click="$set('tab', '{{ $key }}')" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                            @class(['border-b-[3px] px-4 py-2.5 text-[15px]', 'border-brand-yellow font-bold' => $tab === $key, 'border-transparent text-grayscale-80 hover:text-ink' => $tab !== $key])>
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        @if ($transactions->isEmpty())
            <div class="mt-4 rounded-sm bg-grayscale-0 px-6 py-10 text-center">
                <p class="text-[16px] font-bold">Nothing here yet</p>
                <p class="mt-1 text-body-sm text-body-muted">Your paid orders will show here as they count towards rewards.</p>
            </div>
        @else
            <div class="mt-4 overflow-x-auto rounded-sm border border-grayscale-40">
                <table class="w-full min-w-[560px] text-left text-[14px]">
                    <thead class="text-[12px] text-body-muted">
                        <tr class="border-b border-grayscale-30">
                            <th scope="col" class="px-4 py-3 font-normal">Date</th>
                            <th scope="col" class="px-4 py-3 font-normal">Activity</th>
                            <th scope="col" class="px-4 py-3 font-normal">Order</th>
                            <th scope="col" class="px-4 py-3 text-right font-normal">Towards rewards</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-grayscale-30">
                        @foreach ($transactions as $row)
                            <tr wire:key="tx-{{ $row->id }}">
                                <td class="px-4 py-3.5 text-grayscale-80">{{ $row->created_at->setTimezone(config('shop.timezone'))->format('M j, Y') }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="block font-bold">{{ match ($row->type) { PointTransaction::TYPE_REDEEMED => 'Reward unlocked', PointTransaction::TYPE_REVERSED => 'Refund', PointTransaction::TYPE_EXPIRED => 'Expired', default => 'Purchase' } }}</span>
                                    <span class="block">{{ $row->description }}</span>
                                </td>
                                <td class="px-4 py-3.5">
                                    @if ($row->order)
                                        <a href="{{ route('account.orders.show', $row->order) }}" class="hover:underline">Order {{ $row->order->number }}</a>
                                    @else
                                        <span class="text-body-muted">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-right font-bold">{{ $row->points > 0 ? '+' : '−' }}{{ money(intdiv(abs($row->points) * 100, $perDollar)) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-store.account-shell>
</div>
