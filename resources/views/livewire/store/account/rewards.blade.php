@php
    use App\Models\PointTransaction;
@endphp

<div>
    <x-store.account-shell active="rewards" :crumbs="['Your account' => route('account'), 'Rewards & points' => null]">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <h1 class="text-h2-mobile md:text-h2">Rewards &amp; Points</h1>
            <p class="text-body text-body-muted">Earn {{ config('loyalty.points_per_dollar') }} points for every $1 you spend</p>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-[3fr_3fr]">
            <div class="relative h-[130px] overflow-hidden rounded-sm bg-[repeating-linear-gradient(115deg,var(--color-brand-yellow-40)_0_70px,var(--color-brand-yellow-20)_70px_140px)] p-5">
                <img src="{{ asset('images/account/diamond-heart.svg') }}" alt="" class="absolute right-4 top-1/2 size-[120px] -translate-y-1/2">
                <p class="relative text-[28px] leading-8 font-bold">{{ number_format($balance) }} pts</p>
                <p class="relative mt-2 flex items-center gap-2 text-[11px] leading-[14px] font-bold uppercase tracking-[1px]">
                    <x-store.icon name="lovely" class="size-[18px]" /> Loyalty balance
                </p>
                <p class="relative mt-3 text-[13px] font-bold">{{ $balance >= $cost ? 'You have a reward ready' : number_format($cost - $balance).' points to your next reward' }}</p>
            </div>
            <div class="h-[130px] rounded-sm border border-grayscale-40 p-5">
                <p class="flex items-center gap-2 text-[11px] leading-[14px] font-bold uppercase tracking-[0.5px] text-grayscale-80">
                    <x-store.icon name="gift" class="size-[18px]" /> Expiring soon
                </p>
                <p class="mt-3 text-[28px] leading-8 font-bold">0</p>
                <p class="mt-2 text-[13px] text-body-muted">Your points don’t expire while your account is open.</p>
            </div>
        </div>

        <h2 class="mt-8 text-[18px] font-bold">Available rewards</h2>

        @if ($redeemedCode)
            <p class="mt-4 flex flex-wrap items-center gap-2 rounded-sm bg-stock-0 px-4 py-3 text-body-sm text-stock-50" role="status">
                <x-store.icon name="check" />
                Redeemed! Code <strong class="font-mono">{{ $redeemedCode }}</strong> is on your basket and takes {{ money($rewardValue) }} off at checkout.
            </p>
        @endif
        @if ($problem)
            <p class="mt-4 rounded-sm bg-sale-0 px-4 py-3 text-body-sm text-price-40" role="alert">{{ $problem }}</p>
        @endif

        <div class="mt-4 flex flex-col gap-4 rounded-sm bg-brand-purple px-6 py-5 text-white sm:flex-row sm:items-center">
            <span class="flex size-[52px] shrink-0 items-center justify-center rounded-full bg-white/20">
                <x-store.icon name="receipt" class="size-6" />
            </span>
            <div class="flex-1">
                <p class="text-[22px] leading-7 font-bold">{{ money($rewardValue) }} off your next order</p>
                <p class="mt-1 text-[13px] text-white/80">Redeemable at checkout · uses {{ number_format($cost) }} points · you have {{ number_format($balance) }}</p>
            </div>
            <button type="button" wire:click="redeem" wire:loading.attr="disabled" @disabled($balance < $cost)
                    class="h-[42px] shrink-0 rounded-full bg-white px-5 text-[15px] font-bold text-ink disabled:cursor-not-allowed disabled:opacity-60">
                {{ $balance < $cost ? number_format($cost - $balance).' more points' : 'Redeem now' }}
            </button>
        </div>

        @if ($codes->isNotEmpty())
            <div class="mt-4 rounded-sm border border-grayscale-40">
                <p class="border-b border-grayscale-30 px-5 py-3 text-[15px] font-bold">Your reward codes</p>
                <ul class="divide-y divide-grayscale-30">
                    @foreach ($codes as $coupon)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-[14px]">
                            <span><strong class="font-mono">{{ $coupon->code }}</strong> · {{ money($coupon->value) }} off</span>
                            <span class="text-body-muted">
                                {{ $appliedCode === $coupon->code ? 'On your basket · ' : '' }}Use by {{ $coupon->expires_at->setTimezone(config('shop.timezone'))->format('j M Y') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-8 flex flex-wrap items-end justify-between gap-3">
            <h2 class="text-[18px] font-bold">Points history</h2>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex" role="tablist" aria-label="Points history">
                @foreach (['all' => 'All', 'earned' => 'Earned', 'used' => 'Used'] as $key => $label)
                    <button type="button" role="tab" wire:click="$set('tab', '{{ $key }}')" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                            @class(['min-w-[90px] border-b-[3px] px-4 py-2.5 text-[15px]', 'border-brand-yellow font-bold' => $tab === $key, 'border-transparent text-grayscale-80 hover:text-ink' => $tab !== $key])>
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            <p class="text-[12px] text-body-muted">{{ $transactions->count() }} {{ str('transaction')->plural($transactions->count()) }} · newest first</p>
        </div>

        @if ($transactions->isEmpty())
            <div class="mt-4 rounded-sm bg-grayscale-0 px-6 py-10 text-center">
                <p class="text-[16px] font-bold">{{ $tab === 'used' ? 'No points used yet' : 'No points yet' }}</p>
                <p class="mt-1 text-body-sm text-body-muted">You earn points on every paid order — online, or at the counter when you collect.</p>
            </div>
        @else
            <div class="mt-4 overflow-x-auto rounded-sm border border-grayscale-40">
                <table class="w-full min-w-[560px] text-left text-[14px]">
                    <thead class="text-[12px] text-body-muted">
                        <tr class="border-b border-grayscale-30">
                            <th scope="col" class="px-4 py-3 font-normal">Date</th>
                            <th scope="col" class="px-4 py-3 font-normal">Activity</th>
                            <th scope="col" class="px-4 py-3 font-normal">Order</th>
                            <th scope="col" class="px-4 py-3 text-right font-normal">Points</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-grayscale-30">
                        @foreach ($transactions as $row)
                            <tr>
                                <td class="px-4 py-3.5 text-grayscale-80">{{ $row->created_at->setTimezone(config('shop.timezone'))->format('M j, Y') }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="block font-bold">{{ match ($row->type) { PointTransaction::TYPE_REDEEMED => 'Reward used', PointTransaction::TYPE_EXPIRED => 'Expired', default => 'Purchase' } }}</span>
                                    <span class="block">{{ $row->description }}</span>
                                </td>
                                <td class="px-4 py-3.5">
                                    @if ($row->order)
                                        <a href="{{ route('account.orders.show', $row->order) }}" class="hover:underline">Order {{ $row->order->number }}</a>
                                    @else
                                        <span class="text-body-muted">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-right font-bold">{{ $row->points > 0 ? '+' : '−' }}{{ number_format(abs($row->points)) }} points</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3 flex flex-wrap justify-between gap-2 px-4 text-[12px] text-body-muted">
                <span>{{ number_format($earned) }} points earned · {{ number_format($used) }} points used</span>
                <span>Current balance: {{ number_format($balance) }} points</span>
            </div>
        @endif
    </x-store.account-shell>
</div>
