<div class="space-y-6 sm:space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-[#111111] tracking-tight">Loyalty</h2>
            <p class="mt-1 text-xs sm:text-sm text-[#686868] max-w-2xl">
                The website side of your Square Loyalty programme: customers join with the phone number they give at checkout, paid online orders earn points,
                and signed-in customers can spend a reward online. In-store activity and full reports are in the Square Dashboard under Loyalty.
            </p>
        </div>
        @if ($enabled)
            <button type="button" wire:click="syncNow" wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-[#DF2600] text-white text-sm font-bold hover:bg-[#C42100] disabled:opacity-60 transition-colors">
                <span wire:loading.remove wire:target="syncNow">Retry waiting orders</span>
                <span wire:loading wire:target="syncNow">Sending to Square…</span>
            </button>
        @endif
    </div>

    @if ($syncMessage)
        <div class="p-4 rounded-xl bg-[#E9F8F0] border border-[#A6E3C3] text-xs sm:text-sm font-bold text-[#007D49]" role="status">{{ $syncMessage }}</div>
    @endif

    {{-- Connection --}}
    <div @class([
        'p-4 sm:p-6 rounded-2xl border',
        'bg-[#E9F8F0] border-[#A6E3C3]' => $enabled,
        'bg-[#FFF7E6] border-[#FFD98A]' => ! $enabled,
    ])>
        @if ($enabled)
            <p class="text-sm font-extrabold text-[#007D49]">Connected to Square Loyalty ({{ $env === 'production' ? 'live' : 'sandbox (test)' }})</p>
            <p class="mt-1 text-xs sm:text-sm text-[#333333]">
                Reward: <strong>{{ $tier['name'] ?? 'Reward' }}</strong> for {{ $tier['points'] ?? '?' }} points. Points per dollar follow the rules set in Square.
            </p>
        @elseif ($switchedOff)
            <p class="text-sm font-extrabold text-[#8A5800]">Switched off on the website</p>
            <p class="mt-1 text-xs sm:text-sm text-[#333333]">LOYALTY_ENABLED is set to false. Remove it (or set it to true) to turn loyalty back on.</p>
        @else
            <p class="text-sm font-extrabold text-[#8A5800]">Not active yet</p>
            <p class="mt-1 text-xs sm:text-sm text-[#333333]">
                Loyalty uses the Square account that takes card payments, which is currently <strong>{{ $env === 'production' ? 'live' : 'sandbox (test)' }}</strong>
                and has no active loyalty programme. It starts by itself once card payments run on the live account (where your programme is set up),
                or once a test programme is created in the Square sandbox. Orders paid while it’s off don’t earn points.
            </p>
        @endif
    </div>

    {{-- Last 30 days --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
        @foreach ([
            ['Orders that earned points', number_format($stats['orders'])],
            ['Points given online', number_format($stats['points'])],
            ['Customers earning online', number_format($stats['members'])],
            ['Rewards used online', number_format($stats['rewards'])],
            ['Waiting to reach Square', number_format($stats['waiting'])],
        ] as [$label, $value])
            <div class="bg-white rounded-2xl border border-[#EEEEEE] p-4 sm:p-5">
                <p class="text-[11px] font-bold uppercase tracking-wider text-[#686868]">{{ $label }}</p>
                <p class="mt-2 text-2xl font-extrabold text-[#111111]">{{ $value }}</p>
            </div>
        @endforeach
    </div>
    <p class="-mt-3 text-[11px] text-[#686868]">Last 30 days, website orders only.</p>

    {{-- Customer lookup --}}
    <div class="bg-white rounded-2xl border border-[#EEEEEE] p-4 sm:p-6">
        <h3 class="text-base sm:text-lg font-extrabold text-[#111111]">Look up a customer’s points</h3>
        <form wire:submit="lookUp" class="mt-3 flex flex-col sm:flex-row gap-2 max-w-lg">
            <label for="lookup-phone" class="sr-only">Phone number</label>
            <input id="lookup-phone" type="tel" wire:model="lookupPhone" placeholder="Phone number, e.g. 506 555 0123"
                   class="flex-1 h-11 px-4 rounded-xl border border-[#DDDDDD] text-sm focus:border-[#111111] focus:outline-none">
            <button type="submit" class="h-11 px-5 rounded-xl bg-[#111111] text-white text-sm font-bold">Look up</button>
        </form>
        @if ($lookupError)
            <p class="mt-3 text-xs sm:text-sm font-bold text-[#DF2600]">{{ $lookupError }}</p>
        @elseif ($lookup)
            <div class="mt-4 text-sm text-[#333333]">
                @if ($lookup['member'])
                    <p><strong>{{ $lookup['phone'] }}</strong> has <strong>{{ number_format($lookup['points']) }} points</strong>
                        ({{ number_format($lookup['lifetime'] ?? 0) }} earned in total{{ $lookup['since'] ? ', member since '.$lookup['since'] : '' }}).</p>
                @else
                    <p><strong>{{ $lookup['phone'] }}</strong> isn’t in the loyalty programme yet. They join with their first paid order.</p>
                @endif
            </div>
        @endif
    </div>

    {{-- Website orders --}}
    <div class="bg-white rounded-2xl border border-[#EEEEEE] overflow-hidden">
        <h3 class="px-4 sm:px-6 py-4 text-base sm:text-lg font-extrabold text-[#111111] border-b border-[#EEEEEE]">Website orders in the programme</h3>
        @if ($orders->isEmpty())
            <p class="px-4 sm:px-6 py-10 text-center text-sm text-[#686868]">No website orders have earned or used points yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="text-[11px] uppercase tracking-wider text-[#686868] bg-[#FAFAFA]">
                        <tr>
                            <th class="px-4 sm:px-6 py-3 font-bold">Order</th>
                            <th class="px-4 py-3 font-bold">Customer</th>
                            <th class="px-4 py-3 font-bold">Phone</th>
                            <th class="px-4 py-3 font-bold text-right">Points earned</th>
                            <th class="px-4 sm:px-6 py-3 font-bold">Reward</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EEEEEE]">
                        @foreach ($orders as $order)
                            <tr wire:key="loyalty-order-{{ $order->id }}">
                                <td class="px-4 sm:px-6 py-3">
                                    <span class="font-bold text-[#111111]">{{ $order->number }}</span>
                                    <span class="block text-xs text-[#686868]">{{ $order->placed_at?->timezone(config('shop.timezone'))->format('j M Y, g:i a') }}</span>
                                </td>
                                <td class="px-4 py-3">{{ $order->contact_name }}</td>
                                <td class="px-4 py-3">{{ $order->contact_phone }}</td>
                                <td class="px-4 py-3 text-right font-bold">
                                    @if ($order->loyalty_synced_at === null)
                                        <span class="text-[#8A5800]">Waiting</span>
                                    @else
                                        {{ number_format((int) $order->loyalty_points - $order->loyalty_points_reversed) }}
                                        @if ($order->loyalty_points_reversed)
                                            <span class="block text-xs font-normal text-[#686868]">{{ $order->loyalty_points_reversed }} taken back (refund)</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-4 sm:px-6 py-3">
                                    @if ($order->loyalty_reward_id)
                                        {{ match ($order->loyalty_reward_status) {
                                            'redeemed' => 'Used: '.money($order->discount_cents).' off',
                                            'released' => 'Not used, points given back',
                                            default => 'Points held until paid',
                                        } }}
                                    @else
                                        <span class="text-[#686868]">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 sm:px-6 py-3">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
