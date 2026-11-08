{{-- Grey results bar under story and update lists (Figma: "Showing 1–3 of 20 results" + page arrows). --}}
@props(['paginator'])

<div {{ $attributes->class('flex items-center justify-between gap-4 rounded-[4px] bg-[#F6F6F6] px-4 py-2') }}>
    <p class="py-2 text-[14px] leading-5 text-body-muted">Showing {{ \App\Livewire\Store\ProductListing::rangeLabel($paginator) }} {{ str('result')->plural($paginator->total()) }}</p>
    @if ($paginator->hasPages())
        <div class="flex items-center gap-2 border-l border-[#DEDEDE] pl-2">
            <button type="button" wire:click="previousPage" @disabled($paginator->onFirstPage()) aria-label="Previous page"
                    class="flex size-9 items-center justify-center rounded-sm border border-grayscale-40 bg-surface text-ink disabled:bg-transparent disabled:text-grayscale-50">
                <x-store.icon name="arrow-right" class="size-4 rotate-180" />
            </button>
            <button type="button" wire:click="nextPage" @disabled(! $paginator->hasMorePages()) aria-label="Next page"
                    class="flex size-9 items-center justify-center rounded-sm border border-grayscale-40 bg-surface text-ink disabled:bg-transparent disabled:text-grayscale-50">
                <x-store.icon name="arrow-right" class="size-4" />
            </button>
        </div>
    @endif
</div>
