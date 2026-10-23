@props(['percent', 'compact' => false])

<span {{ $attributes->class([
    'flex w-fit items-center gap-1 border border-sale-50 bg-sale-10 pl-2 uppercase text-sale-50',
    'h-[19px] text-badge-sm' => $compact,
    'h-[23px] text-badge' => ! $compact,
]) }}>
    <span class="font-medium">Estimate</span>
    <span class="flex h-full items-center bg-sale-20 px-1">&minus;{{ $percent }}%</span>
</span>
