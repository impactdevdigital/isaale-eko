{{-- Single-choice filter row. Clicking the selected option again clears that filter. --}}
@props(['name', 'value', 'label', 'flag' => null])

<button type="button" role="radio"
        :aria-checked="$wire.{{ $name }} === @js($value)"
        @click="$wire.set(@js($name), $wire.{{ $name }} === @js($value) ? '' : @js($value))"
        class="group flex items-center gap-2 py-1 text-left text-[13px] leading-5 text-grayscale-90 hover:text-ink">
    <span class="flex size-4 shrink-0 items-center justify-center rounded-full border border-grayscale-50 group-aria-checked:border-ink">
        <span class="size-2.5 rounded-full bg-ink opacity-0 group-aria-checked:opacity-100"></span>
    </span>
    @if ($flag)
        <img src="{{ $flag }}" alt="" class="h-2.5 w-[14px] object-cover">
    @endif
    {{ $label }}
</button>
