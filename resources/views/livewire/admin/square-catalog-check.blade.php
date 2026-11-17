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
