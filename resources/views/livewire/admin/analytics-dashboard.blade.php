@php
    use App\Livewire\Admin\AnalyticsDashboard;

    $first = max(1, $funnel->first()['sessions'] ?? 0);
@endphp

<div class="space-y-6 sm:space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-[#111111] tracking-tight">Analytics &amp; Measurement</h2>
            <p class="mt-1 text-xs sm:text-sm text-[#686868] max-w-2xl">
                What shoppers did on the website: how they found products, how far they got through checkout, and how they chose pickup.
                Counts come from the website's own event log, so they appear even before Google Analytics is connected.
            </p>
        </div>
        <label class="flex items-center gap-2 text-sm font-bold text-[#111111]">
            <span class="sr-only">Date range</span>
            <select wire:model.live="days" class="px-3 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-sm font-bold focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                @foreach (AnalyticsDashboard::RANGES as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <div @class([
        'p-4 sm:p-5 rounded-2xl border text-xs sm:text-sm',
        'bg-[#E9F8F0] border-[#A6E3C3] text-[#007D49]' => $googleConnected,
        'bg-[#FFF7E6] border-[#FFD98A] text-[#8A5800]' => ! $googleConnected,
    ])>
        @if ($googleConnected)
            <strong>Google Analytics 4 is connected.</strong> The same events are sent there for deeper reports.
        @else
            <strong>Google Analytics is ready but not connected yet.</strong> Add the GA4 measurement ID (GA_MEASUREMENT_ID) in the hosting settings and these events will also flow to Google Analytics, with no other changes.
        @endif
    </div>

    {{-- Headline --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        @foreach ([
            ['Visitors', number_format($visitors), 'Unique browsing sessions'],
            ['Page views', number_format($counts['page_view'] ?? 0), 'Store pages opened'],
            ['Orders completed', number_format($counts['purchase'] ?? 0), money((int) round($revenue * 100)).' in orders'],
            ['Shopper conversion', $visitors ? round(($funnel['purchase']['sessions'] ?? 0) / $visitors * 100, 1).'%' : '—', 'Visitors who completed an order'],
        ] as [$label, $value, $hint])
            <div class="bg-white rounded-2xl border border-[#EEEEEE] p-4 sm:p-5">
                <p class="text-[11px] font-bold uppercase tracking-wider text-[#686868]">{{ $label }}</p>
                <p class="mt-2 text-2xl font-extrabold text-[#111111]">{{ $value }}</p>
                <p class="mt-1 text-[11px] text-[#686868]">{{ $hint }}</p>
            </div>
        @endforeach
    </div>

    {{-- Event groups --}}
    <div class="grid gap-4 lg:grid-cols-3">
        @foreach (AnalyticsDashboard::GROUPS as $group => $events)
            <section class="bg-white rounded-2xl border border-[#EEEEEE] p-4 sm:p-6" aria-labelledby="group-{{ Str::slug($group) }}">
                <h3 id="group-{{ Str::slug($group) }}" class="text-sm font-extrabold text-[#111111]">{{ $group }}</h3>
                <dl class="mt-3 divide-y divide-[#EEEEEE]">
                    @foreach ($events as $name => $label)
                        <div class="flex items-center justify-between py-2 text-sm">
                            <dt class="text-[#686868]">{{ $label }}</dt>
                            <dd class="font-extrabold text-[#111111]">{{ number_format($counts[$name] ?? 0) }}</dd>
                        </div>
                    @endforeach
                    @if ($group === 'Pickup')
                        <div class="flex items-center justify-between py-2 text-sm">
                            <dt class="text-[#686868]">Average pickup slots available</dt>
                            <dd class="font-extrabold text-[#111111]">{{ $averageSlots ?? '—' }}</dd>
                        </div>
                    @endif
                </dl>
            </section>
        @endforeach
    </div>

    {{-- Funnel --}}
    <section class="bg-white rounded-2xl border border-[#EEEEEE] p-4 sm:p-6" aria-labelledby="funnel-title">
        <h3 id="funnel-title" class="text-sm font-extrabold text-[#111111]">Shopping funnel</h3>
        <p class="mt-1 text-xs text-[#686868]">Browsing sessions that reached each step.</p>
        <ol class="mt-4 space-y-3">
            @foreach ($funnel as $step)
                @php($share = round($step['sessions'] / $first * 100))
                <li>
                    <div class="flex justify-between text-sm">
                        <span class="font-bold text-[#111111]">{{ $loop->iteration }}. {{ $step['label'] }}</span>
                        <span class="text-[#686868]"><strong class="text-[#111111]">{{ number_format($step['sessions']) }}</strong> · {{ $share }}%</span>
                    </div>
                    <div class="mt-1.5 h-2.5 rounded-full bg-[#F6F6F6]" role="presentation">
                        <div class="h-2.5 rounded-full bg-[#DF2600]" style="width: {{ $share }}%"></div>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Top lists --}}
    <div class="grid gap-4 md:grid-cols-2">
        @foreach ([
            ['Top searches', $topSearches, 'No searches yet.'],
            ['Searches with no results', $noResultSearches, 'Every search found something.'],
            ['Most viewed products', $topProducts, 'No product views yet.'],
            ['Most visited pages', $topPages, 'No page views yet.'],
        ] as [$title, $rows, $empty])
            <section class="bg-white rounded-2xl border border-[#EEEEEE] p-4 sm:p-6">
                <h3 class="text-sm font-extrabold text-[#111111]">{{ $title }}</h3>
                @if ($rows->isEmpty())
                    <p class="mt-3 text-xs text-[#686868]">{{ $empty }}</p>
                @else
                    <ol class="mt-3 divide-y divide-[#EEEEEE]">
                        @foreach ($rows as $value => $total)
                            <li class="flex items-center justify-between gap-3 py-2 text-sm">
                                <span class="truncate text-[#111111]">{{ $value }}</span>
                                <span class="shrink-0 font-extrabold text-[#111111]">{{ number_format($total) }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
                @if ($title === 'Searches with no results' && $rows->isNotEmpty())
                    <p class="mt-3 text-[11px] text-[#686868]">Ideas for new stock, or words to add to product names and French names.</p>
                @endif
            </section>
        @endforeach
    </div>
</div>
