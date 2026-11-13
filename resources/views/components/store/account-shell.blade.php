{{-- Account area frame (Figma 06): breadcrumb, sidebar navigation and the page body. --}}
@props(['active', 'crumbs' => []])

@php
    $user = auth()->user();
    $unpaid = $user->orders()->visibleToCustomer()->inTab('unpaid')->count();
    $link = fn (string $name, array $params = []) => Route::has($name) ? route($name, $params) : '#';

    $sections = [
        'Account' => [
            ['overview', 'profile', 'Overview', $link('account')],
            ['manage', 'card', 'Account management', $link('account.manage')],
            ['addresses', 'shield', 'Address book', $link('account.addresses')],
        ],
        'Loyalty' => [
            ['rewards', 'magic-star', 'Rewards & points', $link('account.rewards')],
        ],
        'Orders' => [
            ['orders', 'receipt', 'All orders', $link('account.orders')],
            ['unpaid', 'card-remove', 'Unpaid', $link('account.orders', ['tab' => 'unpaid']), $unpaid],
            ['processing', 'box', 'Processing', $link('account.orders', ['tab' => 'processing'])],
            ['shipped', 'bus', 'Shipped', $link('account.orders', ['tab' => 'shipped'])],
        ],
        'Favourites' => [
            ['wishlist', 'heart', 'Wishlist', $link('account.wishlist')],
            ['buy-again', 'bag', 'Buy again & usuals', $link('account.buy-again')],
            ['saved-baskets', 'shopping-bag', 'Saved baskets', $link('account.saved-baskets')],
            ['saved-meals', 'receipt', 'Saved meals', $link('account.saved-meals')],
        ],
        '' => [
            ['preferences', 'mail', 'Updates & preferences', $link('account.preferences')],
        ],
    ];
@endphp

<div class="page-container pt-6 pb-20 md:pt-10">
    <nav aria-label="Breadcrumb" class="text-[11px] leading-[14px] font-semibold text-body-muted">
        <ol class="flex flex-wrap items-center gap-1.5">
            <li><a href="{{ route('home') }}" class="hover:text-ink">Home</a></li>
            @foreach ($crumbs as $label => $href)
                <li aria-hidden="true">/</li>
                @if ($loop->last)
                    <li class="font-bold text-ink" aria-current="page">{{ $label }}</li>
                @else
                    <li><a href="{{ $href }}" class="hover:text-ink">{{ $label }}</a></li>
                @endif
            @endforeach
        </ol>
    </nav>

    <div class="mt-8 lg:grid lg:grid-cols-[240px_minmax(0,1fr)] lg:items-start lg:gap-8">
        <aside class="hidden rounded-lg border border-grayscale-30 lg:block" aria-label="Your account">
            <div class="flex items-center gap-3 border-b border-grayscale-30 px-5 py-6">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-yellow text-[16px] font-bold">{{ str($user->name)->substr(0, 1)->upper() }}</span>
                <span class="min-w-0">
                    <span class="block truncate text-[15px] font-bold">{{ $user->name }}</span>
                    <span class="block truncate text-body-xs text-body-muted">{{ $user->email ?? $user->phone }}</span>
                </span>
            </div>

            @foreach ($sections as $heading => $items)
                <div @class(['py-4', 'border-t border-grayscale-30' => ! $loop->first])>
                    @if ($heading)
                        <p class="px-5 pb-2 text-[10px] leading-3 font-bold uppercase tracking-[0.5px] text-grayscale-80">{{ $heading }}</p>
                    @endif
                    <ul>
                        @foreach ($items as [$key, $icon, $label, $href])
                            <li>
                                <a href="{{ $href }}" @if ($active === $key) aria-current="page" @endif
                                   @class([
                                       'flex items-center gap-3 border-l-[3px] px-5 py-2 text-[15px]',
                                       'border-brand-yellow font-bold text-ink' => $active === $key,
                                       'border-transparent text-grayscale-80 hover:text-ink' => $active !== $key,
                                   ])>
                                    <x-store.icon :name="$icon" class="size-4 shrink-0" />
                                    <span class="flex-1 whitespace-nowrap">{{ $label }}</span>
                                    @if (($items[$loop->index][4] ?? 0) > 0)
                                        <span class="flex size-[18px] items-center justify-center rounded-full bg-price-40 text-[10px] font-bold text-white">{{ $items[$loop->index][4] }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <form method="POST" action="{{ route('sign-out') }}" class="border-t border-grayscale-30 px-5 py-4">
                @csrf
                <button type="submit" class="flex items-center gap-3 text-[15px] text-grayscale-80 hover:text-ink">
                    <x-store.icon name="logout" class="size-4" />
                    Sign out
                </button>
            </form>
        </aside>

        {{-- Small screens: the same links as a scrolling row. --}}
        <nav class="-mx-4 mb-6 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none] lg:hidden" aria-label="Your account">
            @foreach (collect($sections)->flatten(1) as [$key, $icon, $label, $href])
                <a href="{{ $href }}" @if ($active === $key) aria-current="page" @endif
                   @class(['flex shrink-0 items-center gap-2 rounded-full border px-4 py-2 text-body-sm', 'border-ink bg-ink font-bold text-white' => $active === $key, 'border-grayscale-40' => $active !== $key])>
                    <x-store.icon :name="$icon" class="size-4" />
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        <div class="min-w-0">
            {{ $slot }}
        </div>
    </div>
</div>
