{{-- Visual radio dot; the parent element carries the role and state. --}}
@props(['checked' => false, 'disabled' => false])

<span @class([
    'flex size-5 shrink-0 items-center justify-center rounded-full border',
    'border-ink' => $checked,
    'border-grayscale-50' => ! $checked && ! $disabled,
    'border-grayscale-40' => $disabled,
])>
    @if ($checked)
        <span class="size-3 rounded-full bg-ink"></span>
    @endif
</span>
