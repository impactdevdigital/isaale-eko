@php
    use App\Services\ConsentService;
    $tz = config('shop.timezone');
@endphp

<div class="space-y-6 sm:space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-[#111111] tracking-tight">Newsletter sign-ups</h2>
            <p class="mt-1 text-xs sm:text-sm text-[#686868] max-w-2xl">
                Everyone who asked for Ìsàlèè Èkó emails, which form they used and the page they were on. Each sign-up is also copied into the Brevo newsletter list.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="syncNow" wire:loading.attr="disabled" wire:target="syncNow"
                    class="px-4 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-sm font-bold text-[#111111] hover:border-[#111111] disabled:opacity-60">
                Send to Brevo now
            </button>
            <button type="button" wire:click="export"
                    class="px-4 py-2.5 rounded-xl bg-[#111111] text-sm font-bold text-white hover:bg-[#333333]">
                Download CSV
            </button>
        </div>
    </div>

    <div @class([
        'p-4 sm:p-5 rounded-2xl border text-xs sm:text-sm',
        'bg-[#E9F8F0] border-[#A6E3C3] text-[#007D49]' => $brevoConnected,
        'bg-[#FFF7E6] border-[#FFD98A] text-[#8A5800]' => ! $brevoConnected,
    ])>
        @if ($brevoConnected)
            <strong>Brevo is connected.</strong> New sign-ups are sent straight away, with first name, last name, sign-up source and page. Anything that didn't go through is retried every hour.
            @if ($unsynced)
                <span class="font-bold">{{ $unsynced }} {{ str('sign-up')->plural($unsynced) }} still waiting to reach Brevo.</span>
            @endif
        @else
            <strong>Brevo is not connected yet.</strong> Sign-ups are saved here and will be sent to Brevo as soon as the Brevo API key and newsletter list ID (BREVO_API_KEY, BREVO_LIST_ID) are added to the hosting settings.
        @endif
    </div>

    @if ($notice)
        <p class="p-3 rounded-xl bg-[#F6F6F6] text-sm text-[#111111]" role="status">{{ $notice }}</p>
    @endif

    {{-- Headline --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white rounded-2xl border border-[#EEEEEE] p-4 sm:p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-[#686868]">Subscribers</p>
            <p class="mt-2 text-2xl font-extrabold text-[#111111]">{{ number_format($total) }}</p>
            <p class="mt-1 text-[11px] text-[#686868]">Currently signed up</p>
        </div>
        <div class="bg-white rounded-2xl border border-[#EEEEEE] p-4 sm:p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-[#686868]">Last 7 days</p>
            <p class="mt-2 text-2xl font-extrabold text-[#111111]">{{ number_format($last7) }}</p>
            <p class="mt-1 text-[11px] text-[#686868]">New sign-ups</p>
        </div>
        <div class="col-span-2 bg-white rounded-2xl border border-[#EEEEEE] p-4 sm:p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-[#686868]">Where they signed up</p>
            @if ($bySource->isEmpty())
                <p class="mt-2 text-sm text-[#686868]">No sign-ups yet.</p>
            @else
                <ul class="mt-2 space-y-1 text-sm">
                    @foreach ($bySource as $src => $count)
                        <li class="flex justify-between gap-3"><span class="text-[#686868]">{{ ConsentService::sourceLabel($src) }}</span><strong class="text-[#111111]">{{ number_format($count) }}</strong></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-2">
        <label class="flex-1">
            <span class="sr-only">Search sign-ups</span>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search by name or email"
                   class="w-full px-3 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
        </label>
        <label>
            <span class="sr-only">Source</span>
            <select wire:model.live="source" class="w-full sm:w-auto px-3 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-sm font-bold">
                <option value="">All sources</option>
                @foreach ($sources as $src)
                    <option value="{{ $src }}">{{ ConsentService::sourceLabel($src) }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span class="sr-only">Status</span>
            <select wire:model.live="status" class="w-full sm:w-auto px-3 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-sm font-bold">
                <option value="active">Subscribed</option>
                <option value="unsubscribed">Unsubscribed</option>
                <option value="all">All</option>
            </select>
        </label>
    </div>

    {{-- List --}}
    <section class="bg-white rounded-2xl border border-[#EEEEEE] overflow-hidden">
        @if ($signups->isEmpty())
            <p class="p-6 text-sm text-[#686868]">No sign-ups match.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead class="bg-[#FAFAFA]">
                        <tr class="text-left text-[11px] font-bold uppercase tracking-wider text-[#686868]">
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Source</th>
                            <th class="px-4 py-3">Page</th>
                            <th class="px-4 py-3">Signed up</th>
                            <th class="px-4 py-3">Brevo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EEEEEE]">
                        @foreach ($signups as $c)
                            <tr wire:key="signup-{{ $c->id }}">
                                <td class="px-4 py-3 font-bold text-[#111111]">{{ $c->name ?: ($c->user?->name ?? '') }}</td>
                                <td class="px-4 py-3 text-[#111111]">{{ $c->email ?: $c->user?->email }}</td>
                                <td class="px-4 py-3 text-[#686868]">{{ ConsentService::sourceLabel($c->source) }}</td>
                                <td class="px-4 py-3">
                                    @if ($c->page)
                                        <a href="{{ url($c->page) }}" target="_blank" rel="noopener" class="font-mono text-xs text-[#111111] hover:underline">{{ $c->page }}</a>
                                    @else
                                        <span class="text-[#A0A0A0]">Not recorded</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-[#686868]">
                                    {{ $c->granted_at?->timezone($tz)->format('j M Y, g:ia') }}
                                    @if ($c->revoked_at)
                                        <span class="block text-[11px] font-bold text-[#DF2600]">Unsubscribed {{ $c->revoked_at->timezone($tz)->format('j M') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($c->brevo_synced_at)
                                        <span class="rounded-full bg-[#E9F8F0] px-2 py-0.5 text-[11px] font-bold text-[#007D49]">Sent</span>
                                    @elseif ($c->revoked_at)
                                        <span class="text-[#A0A0A0]">Not needed</span>
                                    @else
                                        <span class="rounded-full bg-[#FFF7E6] px-2 py-0.5 text-[11px] font-bold text-[#8A5800]">Waiting</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-[#EEEEEE]">{{ $signups->links() }}</div>
        @endif
    </section>

    <p class="text-[11px] text-[#686868]">Pages are recorded for sign-ups from {{ now($tz)->format('j F Y') }} onwards.</p>
</div>
