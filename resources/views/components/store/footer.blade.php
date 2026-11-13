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
            'Cook on a Budget' => route('kitchen.budget'),
            'Food Stories' => route('stories.index'),
            'Brand updates' => route('updates.index'),
        ],
        'Help' => [
            'Delivery & pickup' => '#',
            'Privacy Policy' => route('privacy-policy'),
            'Refund Policy' => route('refund-policy'),
        ],
        'Community' => [
            'About Ìsàlèè Èkó' => '#',
            'Join our community' => route('community'),
        ],
    ];
@endphp

<footer class="bg-[#F6F6F6] pb-8">
    <!-- Links Row: pt-[52px], pb-12 matching Figma 07.01 footer -->
    <div class="page-container pt-[52px] grid grid-cols-2 gap-x-6 gap-y-10 lg:grid-cols-[210px_repeat(4,197px)] lg:justify-between">
        <div class="col-span-2 lg:col-span-1 flex flex-col gap-6">
            <img src="{{ asset('images/logo-small.svg') }}" alt="Ìsàlèè Èkó" class="h-[40px] w-[62px]">
            <p class="max-w-[211px] text-[12px] leading-[18px] font-normal text-[#686868]">African and Caribbean food, delivered with care.</p>
        </div>

        @foreach ($columns as $heading => $links)
            <div>
                <h3 class="pb-4 text-[12px] leading-[16px] font-bold text-[#111111]">{{ $heading }}</h3>
                <ul class="flex flex-col gap-2.5">
                    @foreach ($links as $label => $url)
                        <li><a href="{{ $url }}" class="block text-[11px] leading-[14px] font-normal text-[#686868] transition-colors hover:text-[#111111]">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>

    <!-- Bottom Bar: border-t border-[#DEDEDE], pt-6, text-[10px] #777777 -->
    <div class="page-container mt-[52px]">
        <div class="flex flex-wrap items-center justify-between gap-4 border-t border-[#DEDEDE] pt-6 text-[10px] leading-[13px] font-normal text-[#777777]">
            <p>&copy; {{ date('Y') }} Ìsàlèè Èkó. All rights reserved.</p>
            <div class="flex items-center gap-4">
                <a href="{{ route('privacy-policy') }}" class="transition-colors hover:text-[#111111]">Privacy Policy</a>
                <span>·</span>
                <a href="{{ route('refund-policy') }}" class="transition-colors hover:text-[#111111]">Refund Policy</a>
                <span>·</span>
                <span>Saint John, NB, Canada · CAD · English</span>
            </div>
        </div>
    </div>
</footer>
