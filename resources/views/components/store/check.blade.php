{{-- Checkbox bound to a boolean Livewire property. --}}
@props(['model', 'label', 'live' => false])

<label class="flex cursor-pointer items-start gap-3 text-[15px]">
    <input type="checkbox" @if ($live) wire:model.live="{{ $model }}" @else wire:model="{{ $model }}" @endif
           class="mt-0.5 size-4 shrink-0 rounded-[3px] border-grayscale-50 accent-ink">
    <span>{{ $label }}</span>
</label>
