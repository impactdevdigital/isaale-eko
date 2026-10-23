@php
    $canWhatsApp = $user->phone && $user->phone_verified_at;

    $groups = [
        [
            'icon' => 'mail', 'title' => 'Email notifications',
            'tag' => $user->email, 'disabled' => ! $user->email,
            'items' => [
                ['recipes', 'Recipe ideas & product highlights', 'Seasonal recipes, new arrivals and ingredient inspiration — sent weekly at most.', false],
                ['rewards', 'Rewards & points updates', 'Know when your points are earned, when coupons arrive, and when rewards are ready.', false],
                ['offers', 'Special offers & sale alerts', 'Promotional discounts and flash sales. You can turn this off at any time.', true],
            ],
            'note' => '<strong class="font-bold text-ink">Always on:</strong> Order confirmations, dispatch notices, delivery updates and account security emails are always sent regardless of your choices above.',
        ],
        [
            'icon' => 'phone', 'title' => 'WhatsApp & community',
            'tag' => 'Optional', 'disabled' => ! $canWhatsApp,
            'items' => [
                ['whatsappOrders', 'Order updates on WhatsApp', 'Get a quick message when your order ships or is ready to collect. Opt in to enable.', false],
                ['whatsappCommunity', 'Community & food stories', 'Occasional posts about African & Caribbean food culture, community events and seasonal guides. Opt in to join.', false],
            ],
            'note' => $canWhatsApp
                ? 'WhatsApp messages go to '.e($user->phone).'. Standard data rates may apply.'
                : 'WhatsApp preferences only take effect once you have added and verified a mobile number in <a href="'.route('account.manage').'" class="font-bold text-ink underline">Account management</a>. Standard data rates may apply.',
        ],
    ];
@endphp

<div>
    <x-store.account-shell active="preferences" :crumbs="['Your account' => route('account'), 'Updates & preferences' => null]">
        <h1 class="text-h2-mobile md:text-h2">Updates &amp; Preferences</h1>
        <p class="mt-2 text-lead-mobile font-normal text-body-muted md:text-lead">Choose what you hear about — and how we reach you.</p>

        <form wire:submit="save" class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px] xl:items-start">
            <div class="flex flex-col gap-6">
                @foreach ($groups as $group)
                    <section class="rounded-sm border border-grayscale-40" aria-label="{{ $group['title'] }}">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-grayscale-30 px-5 py-4">
                            <h2 class="flex items-center gap-2 text-[15px] font-bold"><x-store.icon :name="$group['icon']" class="size-[18px]" /> {{ $group['title'] }}</h2>
                            @if ($group['tag'])
                                <span class="rounded-full bg-stock-0 px-2.5 py-0.5 text-[11px] font-bold text-stock-50">{{ $group['tag'] }}</span>
                            @endif
                        </div>
                        <ul class="divide-y divide-grayscale-30">
                            @foreach ($group['items'] as [$key, $label, $description, $optional])
                                <li>
                                    <label @class(['flex items-center justify-between gap-6 px-5 py-4', 'cursor-pointer' => ! $group['disabled'], 'opacity-60' => $group['disabled']])>
                                        <span>
                                            <span class="flex items-center gap-2 text-[15px] font-bold">
                                                {{ $label }}
                                                @if ($optional)
                                                    <span class="rounded-[4px] bg-brand-yellow-0 px-1.5 py-0.5 text-[10px] font-bold uppercase">Optional</span>
                                                @endif
                                            </span>
                                            <span class="mt-1 block text-body-sm text-body-muted">{{ $description }}</span>
                                        </span>
                                        <input type="checkbox" role="switch" wire:model.live="choices.{{ $key }}" @disabled($group['disabled']) class="peer sr-only">
                                        <span aria-hidden="true" class="relative h-6 w-[38px] shrink-0 rounded-full bg-grayscale-30 transition-colors after:absolute after:left-[3px] after:top-[3px] after:size-[18px] after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-stock-50 peer-checked:after:translate-x-[14px] peer-focus-visible:ring-2 peer-focus-visible:ring-ink peer-focus-visible:ring-offset-2"></span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                        <p class="flex gap-2 bg-grayscale-0 px-5 py-3.5 text-[12px] leading-[18px] text-body-muted">
                            <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-stock-50"></span>
                            <span>{!! $group['note'] !!}</span>
                        </p>
                    </section>
                @endforeach

                <div class="flex flex-wrap items-center gap-4">
                    <button type="submit" class="h-[46px] rounded-full bg-brand-yellow px-6 text-[15px] font-bold transition-colors hover:bg-brand-yellow-60">Save preferences</button>
                    @if ($saved)
                        <p class="flex items-center gap-2 text-body-sm text-stock-50" role="status"><x-store.icon name="check" /> Preferences saved.</p>
                    @else
                        <p class="text-body-sm text-body-muted">Only selected preferences will be saved. You can change these choices at any time.</p>
                    @endif
                </div>
            </div>

            <aside class="flex flex-col gap-6">
                <section class="rounded-sm border border-grayscale-40">
                    <h2 class="border-b border-grayscale-30 px-5 py-4 text-[15px] font-bold">Your consent choices</h2>
                    <div class="flex flex-col gap-3 px-5 py-4 text-body-sm text-body-muted">
                        <p>We only send marketing messages when you have opted in. You can change your choices at any time.</p>
                        <p>Your choices control <strong class="text-ink">marketing only</strong>. Transactional emails — like order confirmations and account security — are unaffected.</p>
                    </div>
                </section>
                <section class="rounded-sm border border-grayscale-40">
                    <h2 class="border-b border-grayscale-30 px-5 py-4 text-[15px] font-bold">Essential communications</h2>
                    <div class="px-5 py-4 text-body-sm">
                        <p class="text-body-muted">The following are always sent regardless of your preferences above:</p>
                        <ul class="mt-3 flex flex-col gap-2">
                            @foreach (['Order confirmations & receipts', 'Dispatch & delivery updates', 'Return & refund notifications', 'Account security & sign-in codes'] as $item)
                                <li class="flex items-center gap-2"><span class="size-1.5 rounded-full bg-stock-50"></span> {{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                </section>
            </aside>
        </form>
    </x-store.account-shell>
</div>
