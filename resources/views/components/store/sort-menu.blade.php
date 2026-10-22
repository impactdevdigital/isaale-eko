@props(['sorts', 'current'])

<div x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false" class="relative w-fit">
    <button type="button" @click="open = ! open" :aria-expanded="open" aria-haspopup="listbox"
            class="flex h-9 min-w-[205px] items-center justify-between gap-3 rounded-sm border border-grayscale-40 px-3.5 text-[14px] text-body-muted">
        <span>Sort by: <strong class="font-bold text-ink">{{ $sorts[$current] }}</strong></span>
        <x-store.icon name="arrow-down" class="size-3 text-ink transition-transform" x-bind:class="open && 'rotate-180'" />
    </button>

    <ul x-show="open" x-cloak x-transition.opacity role="listbox"
        class="absolute left-0 top-full z-20 mt-1.5 w-full rounded-sm border border-grayscale-40 bg-surface py-2 shadow-[0_8px_24px_rgb(0_0_0/0.08)]">
        @foreach ($sorts as $value => $label)
            <li>
                <button type="button" role="option" aria-selected="{{ $value === $current ? 'true' : 'false' }}"
                        wire:click="$set('sort', '{{ $value }}')" @click="open = false"
                        @class(['w-full px-4 py-2 text-left text-[14px] hover:bg-grayscale-0', 'font-bold' => $value === $current])>
                    {{ $label }}
                </button>
            </li>
        @endforeach
    </ul>
</div>
