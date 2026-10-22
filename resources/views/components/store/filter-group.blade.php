@props(['title'])

<div x-data="{ open: true }" class="border-b border-grayscale-40 pb-5 last-of-type:border-b-0">
    <button type="button" @click="open = ! open" :aria-expanded="open"
            class="flex w-full items-center justify-between border-b border-grayscale-40 px-2 pt-5 pb-2.5 text-[12px] leading-4 font-extrabold uppercase">
        {{ $title }}
        <x-store.icon name="minus" x-show="open" />
        <x-store.icon name="plus" class="size-3" x-show="! open" x-cloak />
    </button>
    <div x-show="open" x-collapse class="flex flex-col gap-1.5 px-4 pt-3.5">
        {{ $slot }}
    </div>
</div>
