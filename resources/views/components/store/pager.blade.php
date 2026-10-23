@props(['paginator'])

<div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    <span class="pr-2 text-lead-mobile font-normal text-body-muted md:text-lead">{{ \App\Livewire\Store\ProductListing::rangeLabel($paginator) }}</span>
    <button type="button" wire:click="previousPage" @disabled($paginator->onFirstPage()) aria-label="Previous page"
            class="flex size-9 items-center justify-center rounded-sm border border-grayscale-40 text-ink disabled:text-grayscale-50">
        <x-store.icon name="arrow-right" class="size-4 rotate-180" />
    </button>
    <button type="button" wire:click="nextPage" @disabled(! $paginator->hasMorePages()) aria-label="Next page"
            class="flex size-9 items-center justify-center rounded-sm border border-grayscale-40 text-ink disabled:text-grayscale-50">
        <x-store.icon name="arrow-right" />
    </button>
</div>
