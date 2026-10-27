@php
    $columns = [
        'Shop' => [
            'All products' => route('shop'),
            'New arrivals' => route('shop', ['sort' => 'new']),
            'Best sellers' => route('shop', ['sort' => 'bestsellers']),
            'Deals' => route('shop', ['sort' => 'discounted']),
        ],
        'Discover' => [
            'Kitchen & Recipes' => route('kitchen.index'),
            'Cook With What I Have' => route('kitchen.pantry'),
            'Brand Updates' => '#',
        ],
        'Help' => [
            'Delivery & pickup' => '#',
            'Privacy Policy' => route('privacy-policy'),
            'Refund Policy' => route('refund-policy'),
        ],
        'Community' => [
            'About Ìsàlèè Èkó' => '#',
            'Join our community' => '#',
        ],
    ];
@endphp

<footer class="bg-ink px-4 pt-10 pb-6 md:px-[30px] md:pt-[55px]">
    <div class="grid grid-cols-2 gap-x-4 gap-y-10 lg:grid-cols-[1fr_repeat(4,212px)] lg:gap-x-12">
        <div class="flex flex-col gap-6">
            <img src="{{ asset('images/logo-small-light.png') }}" alt="Ìsàlèè Èkó" class="h-[39px] w-[61px]">
            <p class="max-w-[211px] text-body-sm font-normal text-grayscale-60">African and Caribbean food, delivered with care.</p>
        </div>

        @foreach ($columns as $heading => $links)
            <div>
                <h3 class="pb-[26px] text-[12px] leading-4 font-bold text-white">{{ $heading }}</h3>
                <ul class="flex flex-col gap-2.5">
                    @foreach ($links as $label => $url)
                        <li><a href="{{ $url }}" class="block text-[11px] leading-[14px] text-grayscale-50 transition-colors hover:text-white">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>

    <div class="mt-[45px] flex flex-wrap items-center justify-between gap-4 border-t border-grayscale-90 pt-4 text-body-xs font-normal text-grayscale-60">
        <p>&copy; {{ date('Y') }} Ìsàlèè Èkó. All rights reserved.</p>
        <div class="flex items-center gap-4">
            <a href="{{ route('privacy-policy') }}" class="transition-colors hover:text-white">Privacy Policy</a>
            <span>·</span>
            <a href="{{ route('refund-policy') }}" class="transition-colors hover:text-white">Refund Policy</a>
            <span>·</span>
            <span>Saint John, NB · CAD</span>
        </div>
    </div>
</footer>
