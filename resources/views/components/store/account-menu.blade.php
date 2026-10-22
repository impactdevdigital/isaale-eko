@props(['user' => null])

@php
    $items = $user
        ? [['profile', 'My Account'], ['receipt', 'My Orders'], ['heart', 'My Wishlist'], ['gift', 'My Rewards'], ['bag-timer', 'Buy Again'], ['global', 'Language: English']]
        : [['profile', 'My Account'], ['receipt', 'My Orders'], ['heart', 'My Wishlist'], ['global', 'Language: English']];
@endphp

<div
    x-show="open"
    x-cloak
    x-transition.origin.top.right
    id="account-menu"
    role="menu"
    class="absolute right-0 top-full z-40 mt-2 w-[200px] overflow-hidden rounded-sm border border-grayscale-30 bg-surface shadow-[0_8px_24px_rgb(0_0_0/0.08)]"
>
    <div class="border-b border-grayscale-30 p-4">
        @if ($user)
            <p class="text-[16px] leading-5 font-bold">Hi, {{ str($user->name)->before(' ') }}</p>
            <p class="truncate text-body-sm font-normal text-body-muted">{{ $user->email }}</p>
        @else
            <a href="#" role="menuitem" class="flex h-[34px] items-center justify-center rounded-full bg-brand-yellow text-button-sm transition-colors hover:bg-brand-yellow-60">Sign in</a>
        @endif
    </div>

    <ul class="py-2">
        @foreach ($items as [$icon, $label])
            <li>
                <a href="#" role="menuitem" class="flex items-center gap-3 px-4 py-2.5 text-body hover:bg-grayscale-0">
                    <x-store.icon :name="$icon" class="size-4" />
                    {{ $label }}
                </a>
            </li>
        @endforeach
    </ul>

    @if ($user)
        <div class="border-t border-grayscale-30 py-2">
            <a href="#" role="menuitem" class="flex items-center gap-3 px-4 py-2.5 text-body font-bold hover:bg-grayscale-0">
                <x-store.icon name="logout" />
                Sign out
            </a>
        </div>
    @endif
</div>
