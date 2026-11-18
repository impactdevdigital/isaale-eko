<div>
    <x-store.account-shell active="rewards" :crumbs="['Your account' => route('account'), 'Rewards' => null]">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <h1 class="text-h2-mobile md:text-h2">Rewards</h1>
            @if ($progress->enabled)
                <p class="text-body text-body-muted">{{ $progress->rewardPoints }} points = {{ $progress->rewardName }}</p>
            @endif
        </div>

        @if (! $progress->enabled)
            <div class="mt-8 rounded-sm bg-grayscale-0 px-6 py-10 text-center">
                <p class="text-[16px] font-bold">Our rewards programme is coming to the website soon</p>
                <p class="mt-1 text-body-sm text-body-muted">You can already collect and use points in store with your phone number. Your website orders will count too once it’s switched on here.</p>
            </div>
        @elseif (! $progress->phone)
            <div class="mt-8 rounded-sm bg-grayscale-0 px-6 py-10 text-center">
                <p class="text-[16px] font-bold">Add your phone number to see your points</p>
                <p class="mt-1 text-body-sm text-body-muted">Your rewards account is under your phone number, the same one you give at the till.</p>
                <a href="{{ route('account.manage') }}" class="mt-4 inline-flex h-[42px] items-center rounded-full bg-ink px-5 text-[15px] font-bold text-white">Add phone number</a>
            </div>
        @elseif (! $progress->available)
            <div class="mt-8 rounded-sm bg-grayscale-0 px-6 py-10 text-center" role="status">
                <p class="text-[16px] font-bold">We can’t load your points right now</p>
                <p class="mt-1 text-body-sm text-body-muted">Please try again in a few minutes. Your points are safe.</p>
            </div>
        @else
            <div class="mt-8 grid gap-4 md:grid-cols-[3fr_3fr]">
                <div class="relative overflow-hidden rounded-sm bg-[repeating-linear-gradient(115deg,var(--color-brand-yellow-40)_0_70px,var(--color-brand-yellow-20)_70px_140px)] p-5">
                    <img src="{{ asset('images/account/diamond-heart.svg') }}" alt="" class="absolute right-4 top-1/2 size-[110px] -translate-y-1/2">
                    <p class="relative flex items-center gap-2 text-[11px] leading-[14px] font-bold uppercase tracking-[1px]">
                        <x-store.icon name="lovely" class="size-[18px]" /> Your points
                    </p>
                    <p class="relative mt-3 text-[28px] leading-8 font-bold">{{ number_format($progress->points) }} <span class="text-[18px]">{{ str('point')->plural($progress->points) }}</span></p>
                    <div class="relative mt-3 h-2 max-w-[260px] overflow-hidden rounded-full bg-white/60" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress->percent() }}" aria-label="Progress to your next reward">
                        <div class="h-full rounded-full bg-ink" style="width: {{ $progress->percent() }}%"></div>
                    </div>
                    <p class="relative mt-2 text-[13px] font-bold">{{ $progress->remaining() }} more {{ str('point')->plural($progress->remaining()) }} to your next reward</p>
                </div>
                <div class="rounded-sm border border-grayscale-40 p-5">
                    <p class="flex items-center gap-2 text-[11px] leading-[14px] font-bold uppercase tracking-[0.5px] text-grayscale-80">
                        <x-store.icon name="gift" class="size-[18px]" /> Ready to use
                    </p>
                    <p class="mt-3 text-[28px] leading-8 font-bold">{{ $progress->rewardsReady() }}</p>
                    <p class="mt-2 text-[13px] text-body-muted">Use them online at checkout, or in store with your phone number.</p>
                </div>
            </div>

            @if ($appliedNotice)
                <p class="mt-4 flex flex-wrap items-center gap-2 rounded-sm bg-stock-0 px-4 py-3 text-body-sm text-stock-50" role="status">
                    <x-store.icon name="check" /> {{ $appliedNotice }}
                </p>
            @endif

            @if ($progress->rewardsReady())
                <div class="mt-4 flex flex-col gap-4 rounded-sm bg-brand-purple px-6 py-5 text-white sm:flex-row sm:items-center">
                    <span class="flex size-[52px] shrink-0 items-center justify-center rounded-full bg-white/20">
                        <x-store.icon name="receipt" class="size-6" />
                    </span>
                    <div class="flex-1">
                        <p class="text-[22px] leading-7 font-bold">{{ $progress->rewardName }}</p>
                        <p class="mt-1 text-[13px] text-white/80">Uses {{ $progress->rewardPoints }} points · one reward per order · can’t be combined with other offers</p>
                    </div>
                    @if ($onBasket)
                        <button type="button" wire:click="dropReward" class="flex h-[42px] shrink-0 items-center gap-1 rounded-full bg-white/20 px-5 text-[15px] font-bold"><x-store.icon name="check" class="size-4" /> On your basket</button>
                    @else
                        <button type="button" wire:click="useReward" class="h-[42px] shrink-0 rounded-full bg-white px-5 text-[15px] font-bold text-ink">Use on my basket</button>
                    @endif
                </div>
            @endif

            <p class="mt-3 text-[12px] text-body-muted">
                Points are added once an order is paid (online, or at the counter when you collect). Delivery fees and tax don’t earn points. Refunds take back the points they earned. Your account is under {{ $progress->phone }}.
            </p>

            <h2 class="mt-8 text-[18px] font-bold">Recent activity</h2>

            @if ($progress->history->isEmpty())
                <div class="mt-4 rounded-sm bg-grayscale-0 px-6 py-10 text-center">
                    <p class="text-[16px] font-bold">Nothing here yet</p>
                    <p class="mt-1 text-body-sm text-body-muted">Your points will show here after your next paid order, online or in store.</p>
                </div>
            @else
                <div class="mt-4 overflow-x-auto rounded-sm border border-grayscale-40">
                    <table class="w-full min-w-[480px] text-left text-[14px]">
                        <thead class="text-[12px] text-body-muted">
                            <tr class="border-b border-grayscale-30">
                                <th scope="col" class="px-4 py-3 font-normal">Date</th>
                                <th scope="col" class="px-4 py-3 font-normal">Activity</th>
                                <th scope="col" class="px-4 py-3 font-normal">Where</th>
                                <th scope="col" class="px-4 py-3 text-right font-normal">Points</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-grayscale-30">
                            @foreach ($progress->history as $row)
                                <tr>
                                    <td class="px-4 py-3.5 text-grayscale-80">{{ $row->date->setTimezone(config('shop.timezone'))->format('M j, Y') }}</td>
                                    <td class="px-4 py-3.5 font-bold">{{ $row->label }}</td>
                                    <td class="px-4 py-3.5">{{ $row->where }}</td>
                                    <td class="px-4 py-3.5 text-right font-bold">{{ $row->points > 0 ? '+' : ($row->points < 0 ? '−' : '') }}{{ number_format(abs($row->points)) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </x-store.account-shell>
</div>
