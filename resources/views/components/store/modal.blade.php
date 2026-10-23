{{-- Dialog used by checkout. Closing (X, Escape, backdrop) calls the component's close(), which keeps entered data. --}}
@props(['title', 'subtitle' => null, 'width' => 'max-w-[600px]'])

<div class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-0 sm:items-center sm:p-4"
     x-data x-trap.noscroll="true" @keydown.escape.window="$wire.close()" wire:click.self="close"
     role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div {{ $attributes->class([$width, 'relative max-h-[92vh] w-full overflow-y-auto rounded-t-lg bg-surface p-6 sm:rounded-lg']) }}>
        <button type="button" wire:click="close" class="absolute right-5 top-5" aria-label="Close">
            <x-store.icon name="close" class="size-5" />
        </button>
        <h2 id="modal-title" class="pr-10 text-[24px] leading-8 font-bold">{{ $title }}</h2>
        @if ($subtitle)
            <p class="mt-1.5 text-body text-body-muted">{{ $subtitle }}</p>
        @endif
        <div class="mt-6">
            {{ $slot }}
        </div>
    </div>
</div>
