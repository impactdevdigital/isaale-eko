<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8 sm:py-12">
    <!-- Header -->
    <div class="text-center mb-6 sm:mb-8">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 mb-3">
            <img src="{{ asset('images/logo-small.svg') }}" alt="Ìsàlèè Èkó" class="h-10 w-auto">
        </a>
        <h1 class="text-2xl sm:text-3xl font-black text-[#111111] tracking-tight">
            Item Update for Order #{{ $order->number }}
        </h1>
        <p class="mt-2 text-xs sm:text-sm text-[#686868]">
            We want to ensure your order arrives complete and fresh. Please review the item change below.
        </p>
    </div>

    @if (session('success'))
        <div class="mb-6 p-4 rounded-2xl bg-[#DAF4E7] border border-[#007D49]/20 text-[#007D49] text-sm font-bold flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('info'))
        <div class="mb-6 p-4 rounded-2xl bg-[#FAFAFA] border border-[#EEEEEE] text-[#111111] text-sm font-bold flex items-center gap-3">
            <svg class="w-5 h-5 text-[#DF2600] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('info') }}</span>
        </div>
    @endif

    <!-- Main Decision Card -->
    <div class="bg-white rounded-3xl border border-[#EEEEEE] shadow-sm overflow-hidden p-5 sm:p-8 space-y-6">
        @if ($isSubmitted)
            <!-- Already Resolved View -->
            <div class="text-center py-6 space-y-3">
                <div class="w-14 h-14 mx-auto rounded-full flex items-center justify-center {{ $submissionResult === 'approved' ? 'bg-[#DAF4E7] text-[#007D49]' : 'bg-[#FFF0EC] text-[#DF2600]' }}">
                    @if ($submissionResult === 'approved')
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    @else
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    @endif
                </div>
                <h2 class="text-lg font-black text-[#111111]">
                    @if ($submissionResult === 'approved')
                        Substitute Approved
                    @elseif ($submissionResult === 'declined')
                        Substitute Declined – Item Refunded
                    @elseif ($submissionResult === 'auto_refunded')
                        Request Expired – Item Automatically Refunded
                    @else
                        Request Closed
                    @endif
                </h2>
                <p class="text-xs sm:text-sm text-[#686868] max-w-md mx-auto">
                    @if ($submissionResult === 'approved')
                        Your order has been updated with the replacement product. Our team is continuing preparation.
                    @else
                        The unavailable item has been removed from order #{{ $order->number }}. If your order was paid online, the refund has been initiated to your original payment card.
                    @endif
                </p>
            </div>
        @else
            <!-- Timer Banner -->
            @if ($request->response_deadline_at)
                <div class="p-3.5 sm:p-4 rounded-2xl bg-[#FFF9EA] border border-[#FFE8A3] flex items-center justify-between gap-3 text-xs sm:text-sm">
                    <div class="flex items-center gap-2 text-[#D58700] font-bold">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Response window: ~{{ ceil($request->remainingSeconds() / 60) }} min remaining</span>
                    </div>
                    <span class="text-[11px] text-[#686868]">Auto-refunds if unanswered</span>
                </div>
            @endif

            <!-- Original Unavailable Item -->
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-[#DF2600] flex items-center gap-1.5 mb-2">
                    <span class="w-2 h-2 rounded-full bg-[#DF2600]"></span>
                    Currently Out of Stock
                </span>
                <div class="p-4 rounded-2xl bg-[#FFF0EC]/50 border border-[#DF2600]/20 flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <h3 class="font-extrabold text-sm sm:text-base text-[#111111] truncate">{{ $request->item->name }}</h3>
                        @if ($request->item->variation_name)
                            <div class="text-xs text-[#686868]">{{ $request->item->variation_name }}</div>
                        @endif
                        <div class="text-xs text-[#686868] mt-1">
                            Qty: <strong class="text-[#111111]">{{ $request->item->quantity }}</strong> × {{ money($request->item->unit_price_cents) }}
                        </div>
                    </div>
                    <div class="text-right shrink-0 font-extrabold text-sm sm:text-base text-[#111111]">
                        {{ money($request->item->line_total_cents) }}
                    </div>
                </div>
            </div>

            <!-- Proposed Replacement -->
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-[#007D49] flex items-center gap-1.5 mb-2">
                    <span class="w-2 h-2 rounded-full bg-[#007D49]"></span>
                    Suggested Replacement Option
                </span>
                @if ($request->substitute)
                    <div class="p-4 rounded-2xl bg-[#DAF4E7]/40 border border-[#007D49]/20 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <h3 class="font-extrabold text-sm sm:text-base text-[#111111] truncate">{{ $request->substitute->product->name }}</h3>
                            <div class="text-xs text-[#686868]">{{ $request->substitute->name }}</div>
                            <div class="text-xs text-[#686868] mt-1">
                                Qty: <strong class="text-[#111111]">{{ $request->item->quantity }}</strong> × {{ money($request->substitute->price_cents) }}
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="font-extrabold text-sm sm:text-base text-[#111111]">
                                {{ money($request->substitute->price_cents * $request->item->quantity) }}
                            </div>
                            <div class="text-[11px] font-bold mt-0.5 {{ $request->price_difference_cents > 0 ? 'text-[#DF2600]' : ($request->price_difference_cents < 0 ? 'text-[#007D49]' : 'text-[#686868]') }}">
                                @if ($request->price_difference_cents > 0)
                                    +{{ money($request->price_difference_cents) }} extra
                                @elseif ($request->price_difference_cents < 0)
                                    -{{ money(abs($request->price_difference_cents)) }} refund
                                @else
                                    No price difference
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <div class="p-4 rounded-2xl bg-[#FAFAFA] border border-[#EEEEEE] text-center text-xs text-[#686868]">
                        Our picking team checked our stock and could not find an exact in-stock match. You may choose to remove this item and receive an immediate refund.
                    </div>
                @endif
            </div>

            <!-- Staff Note if Any -->
            @if ($request->note)
                <div class="p-3.5 rounded-xl bg-[#FAFAFA] border border-[#EEEEEE] text-xs text-[#686868]">
                    <strong class="text-[#111111]">Note from store team:</strong> {{ $request->note }}
                </div>
            @endif

            <!-- Customer Choice Actions -->
            <div class="pt-2 flex flex-col sm:flex-row gap-3">
                @if ($request->substitute)
                    <button type="button"
                            wire:click="approve"
                            style="background-color: #DF2600;"
                            class="flex-1 py-3.5 px-6 rounded-2xl text-white font-extrabold text-sm hover:opacity-95 transition-opacity shadow-md text-center">
                        Accept Replacement ({{ $request->substitute->product->name }})
                    </button>
                @endif

                <button type="button"
                        wire:click="decline"
                        class="flex-1 py-3.5 px-6 rounded-2xl border border-[#EEEEEE] bg-white text-[#111111] font-extrabold text-sm hover:bg-[#FAFAFA] transition-colors text-center">
                    No Substitute (Remove & Refund Item)
                </button>
            </div>
        @endif

        <div class="border-t border-[#EEEEEE] pt-4 text-center">
            <a href="{{ route('orders.show', $order->number) }}" class="text-xs font-bold text-[#DF2600] hover:underline inline-flex items-center gap-1">
                <span>View Full Order Tracker #{{ $order->number }}</span>
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>
</div>
