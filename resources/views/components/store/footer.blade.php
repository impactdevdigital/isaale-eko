@php
    $columns = [
        'Shop' => ['All products', 'New arrivals', 'Best sellers', 'Deals'],
        'Discover' => ['Recipes', 'Food stories', 'Product stories'],
        'Help' => ['Delivery & pickup', 'FAQs', 'Contact us'],
        'Community' => ['Our market', 'About Ìsàlèè Èkó', 'Wholesale portal', 'Join our community'],
    ];
@endphp

<footer class="bg-ink px-[30px] pt-10 pb-6 md:pt-[55px]">
    <div class="grid grid-cols-2 gap-x-4 gap-y-10 lg:grid-cols-[1fr_repeat(4,212px)] lg:gap-x-12">
        <div class="flex flex-col gap-6">
            <img src="{{ asset('images/logo-small-light.png') }}" alt="Ìsàlèè Èkó" class="h-[39px] w-[61px]">
            <p class="max-w-[211px] text-body-sm font-normal text-grayscale-60">African and Caribbean food, delivered with care.</p>
        </div>

        @foreach ($columns as $heading => $links)
            <div>
                <h3 class="pb-[26px] text-[12px] leading-4 font-bold text-white">{{ $heading }}</h3>
                <ul class="flex flex-col gap-2.5">
                    @foreach ($links as $label)
                        <li><a href="#" class="block text-[11px] leading-[14px] text-grayscale-50 transition-colors hover:text-white">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>

    <div class="mt-[45px] flex flex-wrap items-center justify-between gap-2 border-t border-grayscale-90 pt-4 text-body-xs font-normal text-grayscale-60">
        <p>&copy; {{ date('Y') }} Ìsàlèè Èkó. All rights reserved.</p>
        <p>Saint John · EUR · English</p>
    </div>
</footer>
