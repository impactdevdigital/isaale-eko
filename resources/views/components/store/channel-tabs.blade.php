{{-- Phone / Email switch for where the sign-in code goes. Only the chosen field is required. --}}
@props(['channel'])

<div class="grid grid-cols-2 rounded-lg bg-grayscale-20 p-1" role="tablist" aria-label="Verify with">
    @foreach (['phone' => ['phone', 'Phone'], 'email' => ['mail', 'Email']] as $value => [$icon, $label])
        <button type="button" role="tab" aria-selected="{{ $channel === $value ? 'true' : 'false' }}" wire:click="$set('channel', '{{ $value }}')"
                @class(['flex h-9 items-center justify-center gap-2 rounded-md text-[13px]', 'bg-surface font-bold shadow-sm' => $channel === $value, 'text-body-muted' => $channel !== $value])>
            <x-store.icon :name="$icon" class="size-4" />
            {{ $label }}
        </button>
    @endforeach
</div>
