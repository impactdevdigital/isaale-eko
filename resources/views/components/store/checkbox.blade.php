{{-- Visual checkbox square; the parent element carries role="checkbox". --}}
@props(['checked' => false])

<span @class([
    'flex size-5 shrink-0 items-center justify-center rounded-sm text-white',
    'bg-ink' => $checked,
    'border border-grayscale-50 bg-surface' => ! $checked,
])>
    @if ($checked)
        <x-store.icon name="check" />
    @endif
</span>
