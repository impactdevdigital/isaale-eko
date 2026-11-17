<div class="space-y-6 sm:space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-[#111111] tracking-tight">Square Catalogue Check</h2>
            <p class="mt-1 text-xs sm:text-sm text-[#686868] max-w-2xl">
                Compares the items, categories, sub-categories and prices in Square with what the website shows. Nothing is changed on either side.
                Connected to Square <strong class="text-[#111111]">{{ $squareEnv === 'production' ? 'live' : 'sandbox (test)' }}</strong>.
            </p>
        </div>
        <button type="button" wire:click="check" wire:loading.attr="disabled"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-[#DF2600] text-white text-sm font-bold hover:bg-[#C42100] disabled:opacity-60 transition-colors">
            <span wire:loading.remove wire:target="check">{{ $report ? 'Check again' : 'Run check' }}</span>
            <span wire:loading wire:target="check">Checking Square…</span>
        </button>
    </div>

    @if ($error)
        <div class="p-4 rounded-xl bg-[#FFF0EC] border border-[#FFB79F] text-xs sm:text-sm font-bold text-[#DF2600]">{{ $error }}</div>
    @endif

    {{-- Pull the whole catalogue from Square (no Shell on Render's free plan) --}}
    <div class="bg-white rounded-2xl border border-[#EEEEEE] p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
         @if (($pull['state'] ?? null) === 'running') wire:poll.5s @endif>
        <div class="min-w-0">
            <h3 class="text-base sm:text-lg font-extrabold text-[#111111]">Pull everything from Square</h3>
            <p class="mt-1 text-xs sm:text-sm text-[#686868] max-w-2xl">
                Copies every item, category, price, photo and stock count from Square onto the website, and hides demo products that aren't in Square.
                Use it after switching Square accounts (e.g. to live). Changes the website only, never Square.
            </p>
            @if ($pull)
                <p @class([
                    'mt-2 text-xs font-bold',
                    'text-[#8A5800]' => $pull['state'] === 'running',
                    'text-[#007D49]' => $pull['state'] === 'done',
                    'text-[#DF2600]' => $pull['state'] === 'failed',
                ])>
                    @if ($pull['state'] === 'running')
                        Pulling from Square… this page updates by itself.
                    @elseif ($pull['state'] === 'done')
                        Last pull finished {{ \Illuminate\Support\Carbon::parse($pull['at'])->timezone('America/Moncton')->format('j M Y, g:i a') }}.
                    @else
                        Last pull failed {{ \Illuminate\Support\Carbon::parse($pull['at'])->timezone('America/Moncton')->format('j M Y, g:i a') }}.
                    @endif
                </p>
                @if (filled($pull['output'] ?? null))
                    <pre class="mt-2 max-h-48 overflow-auto rounded-xl bg-[#F6F6F6] p-3 text-[11px] leading-4 text-[#444444] whitespace-pre-wrap">{{ $pull['output'] }}</pre>
                @endif
            @endif
        </div>
        <button type="button" wire:click="pullFromSquare" wire:loading.attr="disabled" @disabled(($pull['state'] ?? null) === 'running')
                wire:confirm="Pull every item, category, price and stock count from Square {{ $squareEnv === 'production' ? 'LIVE' : 'sandbox' }} onto the website, and hide demo products? Square itself is not changed."
                class="shrink-0 inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl border-2 border-[#111111] text-[#111111] text-sm font-bold hover:bg-[#111111] hover:text-white disabled:opacity-50 transition-colors">
            <span wire:loading.remove wire:target="pullFromSquare">Pull from Square now</span>
            <span wire:loading wire:target="pullFromSquare">Starting…</span>
        </button>
    </div>

    @if ($report)
        @php($summary = $report['summary'])
        <div @class([
            'p-4 rounded-xl border text-xs sm:text-sm font-bold',
            'bg-[#DAF4E7] border-[#A8DFC4] text-[#007D49]' => $summary['differences'] === 0,
            'bg-[#FFF9EA] border-[#FFE8A3] text-[#8A5800]' => $summary['differences'] > 0,
        ])>
            @if ($summary['differences'] === 0)
                Square and the website are in step.
            @else
                {{ $summary['differences'] }} {{ str('difference')->plural($summary['differences']) }} found. A Square sync fixes most of them; each one is listed below.
            @endif
            <span class="font-medium opacity-80">Checked {{ $checkedAt }}.</span>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            @foreach ([
                'Square categories' => $summary['square_categories'],
                'Square sub-categories' => $summary['square_subcategories'],
                'Square items for sale' => $summary['square_items_for_sale'],
                'Square items not for sale' => $summary['square_items_not_for_sale'],
                'Website products for sale' => $summary['site_products_for_sale'],
            ] as $label => $count)
                <div class="bg-white rounded-2xl border border-[#EEEEEE] p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-[#686868]">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-extrabold text-[#111111]">{{ $count }}</p>
                </div>
            @endforeach
        </div>

        <div class="space-y-3">
            @foreach ($labels as $key => $label)
                @php($list = data_get($report, $key, []))
                @if ($list !== [])
                    <details class="bg-white rounded-2xl border border-[#EEEEEE] p-4" @if (count($list) <= 10) open @endif>
                        <summary class="cursor-pointer flex items-center justify-between gap-3 text-sm font-extrabold text-[#111111]">
                            <span>{{ $label }}</span>
                            <span class="px-2 py-0.5 rounded-full bg-[#F6F6F6] text-xs">{{ count($list) }}</span>
                        </summary>
                        <ul class="mt-3 space-y-1 text-xs sm:text-sm text-[#444444] list-disc pl-5">
                            @foreach ($list as $line)
                                <li class="break-words">{{ $line }}</li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            @endforeach
        </div>
    @endif
</div>
