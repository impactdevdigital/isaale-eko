{{-- Labelled text input bound to a Livewire property. --}}
@props(['model', 'label', 'type' => 'text', 'autocomplete' => null, 'hint' => null])

<div {{ $attributes->only('class') }}>
    <label for="field-{{ $model }}" class="mb-2 block text-[11px] leading-[14px] font-medium uppercase tracking-[0.3px] text-grayscale-80">{{ $label }}</label>
    <input id="field-{{ $model }}" type="{{ $type }}" wire:model="{{ $model }}" @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
           {{ $attributes->except('class') }}
           @class([
               'h-11 w-full rounded-sm border bg-grayscale-0 px-4 text-[16px] focus:border-ink focus:outline-none',
               'border-price-40' => $errors->has($model),
               'border-grayscale-30' => ! $errors->has($model),
           ])>
    @error($model)
        <p class="mt-1.5 text-body-xs text-price-40">{{ $message }}</p>
    @elseif ($hint)
        <p class="mt-1.5 text-body-xs text-body-muted">{{ $hint }}</p>
    @enderror
</div>
