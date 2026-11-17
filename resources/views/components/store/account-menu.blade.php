@props(['user' => null])

@php
    $link = fn (string $name, array $params = []) => Route::has($name) ? route($name, $params) : '#';
    $items = $user
        ? [['profile', __('My Account'), $link('account')], ['receipt', __('My Orders'), $link('account.orders')], ['heart', __('My Wishlist'), $link('account.wishlist')], ['gift', __('My Rewards'), $link('account.rewards')], ['bag-timer', __('Buy Again'), $link('account.buy-again')]]
        : [['profile', __('My Account'), $link('account')], ['receipt', __('My Orders'), $link('account.orders')], ['heart', __('My Wishlist'), $link('account.wishlist')]];
    $current = app()->getLocale();
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
            <p class="text-[16px] leading-5 font-bold">{{ __('Hi, :name', ['name' => str($user->name)->before(' ')]) }}</p>
            <p class="truncate text-body-sm font-normal text-body-muted">{{ $user->email ?? $user->phone }}</p>
        @else
            <button type="button" role="menuitem" @click="open = false; $dispatch('sign-in-open')" class="flex h-[34px] w-full items-center justify-center rounded-full bg-brand-yellow text-button-sm transition-colors hover:bg-brand-yellow-60">{{ __('Sign in') }}</button>
        @endif
    </div>

    <ul class="py-2">
        @foreach ($items as [$icon, $label, $href])
            <li>
                <a href="{{ $href }}" role="menuitem" class="flex items-center gap-3 px-4 py-2.5 text-body hover:bg-grayscale-0">
                    <x-store.icon :name="$icon" class="size-4" />
                    {{ $label }}
                </a>
            </li>
        @endforeach
        <li class="flex items-center gap-3 px-4 py-2.5 text-body">
            <x-store.icon name="global" class="size-4" />
            <span class="sr-only">{{ __('Language') }}:</span>
            @foreach (config('locales.supported') as $code => $language)
                @if ($code === $current)
                    <span role="menuitem" aria-current="true" class="font-bold">{{ $language }}</span>
                @else
                    <a href="{{ route('language', $code) }}" role="menuitem" lang="{{ $code }}" hreflang="{{ $code }}" class="text-body-muted underline hover:text-ink">{{ $language }}</a>
                @endif
                @unless ($loop->last)<span aria-hidden="true" class="text-grayscale-50">|</span>@endunless
            @endforeach
        </li>
    </ul>

    @if ($user)
        <form method="POST" action="{{ route('sign-out') }}" class="border-t border-grayscale-30 py-2">
            @csrf
            <button type="submit" role="menuitem" class="flex w-full items-center gap-3 px-4 py-2.5 text-body font-bold hover:bg-grayscale-0">
                <x-store.icon name="logout" />
                {{ __('Sign out') }}
            </button>
        </form>
    @endif
</div>
