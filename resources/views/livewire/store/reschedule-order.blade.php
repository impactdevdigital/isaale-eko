<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8 sm:py-12">
    <!-- Header -->
    <div class="text-center mb-6 sm:mb-8">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 mb-3">
            <img src="{{ asset('images/logo-small.svg') }}" alt="Ìsàlèè Èkó" class="h-10 w-auto">
        </a>
        <h1 class="text-2xl sm:text-3xl font-black text-[#111111] tracking-tight">
            Reschedule Order #{{ $order->number }}
        </h1>
        <p class="mt-2 text-xs sm:text-sm text-[#686868]">
            Choose a new collection date and time that works best for your schedule.
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

    @if ($errorMessage)
        <div class="mb-6 p-4 rounded-2xl bg-[#FFF0EC] border border-[#DF2600]/20 text-[#DF2600] text-sm font-bold flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>{{ $errorMessage }}</span>
        </div>
    @endif

    <div class="bg-white rounded-3xl border border-[#EEEEEE] shadow-sm overflow-hidden p-5 sm:p-8 space-y-6">
        @if ($isSubmitted)
            <!-- Rescheduled Success Card -->
            <div class="text-center py-6 space-y-4">
                <div class="w-16 h-16 mx-auto rounded-full bg-[#DAF4E7] text-[#007D49] flex items-center justify-center">
                    <svg class="w-9 h-9" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 class="text-xl font-black text-[#111111]">
                    Pickup Time Updated!
                </h2>
                <p class="text-sm text-[#686868] max-w-md mx-auto">
                    Your collection window has been confirmed for:
                </p>
                <div class="inline-block p-4 rounded-2xl bg-[#FAFAFA] border border-[#EEEEEE]">
                    <div class="text-base font-extrabold text-[#111111]">
                        {{ $order->pickup_starts_at?->timezone(config('shop.timezone', 'America/Moncton'))->format('l, F j, Y') }}
                    </div>
                    <div class="text-sm font-bold text-[#DF2600] mt-0.5">
                        {{ $order->pickup_starts_at?->timezone(config('shop.timezone', 'America/Moncton'))->format('g:i A') }} – 
                        {{ $order->pickup_ends_at?->timezone(config('shop.timezone', 'America/Moncton'))->format('g:i A') }}
                    </div>
                </div>
                <div class="pt-4 text-xs text-[#686868]">
                    We will have your order ready and held securely at the store. See you soon!
                </div>
                <div class="pt-2">
                    <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#111111] text-white text-xs font-bold hover:bg-[#333333] transition-colors">
                        View Order Details
                    </a>
                </div>
            </div>
        @elseif (in_array($order->status, [\App\Models\Order::STATUS_CANCELLED, \App\Models\Order::STATUS_REFUNDED], true) || $order->fulfilment_status === \App\Models\Order::FULFILMENT_COMPLETED)
            <!-- Cannot Reschedule Card -->
            <div class="text-center py-6 space-y-3">
                <div class="w-14 h-14 mx-auto rounded-full bg-[#FFF0EC] text-[#DF2600] flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h2 class="text-lg font-black text-[#111111]">
                    Order Not Eligible for Rescheduling
                </h2>
                <p class="text-xs sm:text-sm text-[#686868] max-w-md mx-auto">
                    This order has already been completed, cancelled, or refunded. Please contact our team if you need any assistance.
                </p>
                <div class="pt-2">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#111111] text-white text-xs font-bold hover:bg-[#333333] transition-colors">
                        Return to Store
                    </a>
                </div>
            </div>
        @else
            <!-- Current Scheduled Time Notice -->
            <div class="p-4 rounded-2xl bg-[#FFF9EA] border border-[#FFE8A3] flex items-start gap-3">
                <svg class="w-5 h-5 text-[#D58700] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-xs sm:text-sm">
                    <div class="font-bold text-[#111111]">Previously Scheduled Pickup:</div>
                    <div class="text-[#686868] mt-0.5">
                        {{ $order->pickup_starts_at ? $order->pickup_starts_at->timezone(config('shop.timezone', 'America/Moncton'))->format('l, F j, Y \a\t g:i A') : 'Not set' }}
                        @if ($order->status === \App\Models\Order::STATUS_UNCOLLECTED)
                            <span class="inline-block ml-2 px-2 py-0.5 rounded-md bg-[#FFF0EC] text-[#DF2600] font-bold text-[10px]">Uncollected – Awaiting Reschedule</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Date Selector -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-[#111111] mb-2.5">
                    1. Select Date
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-2.5">
                    @foreach ($days as $day)
                        @php
                            $dateStr = $day->toDateString();
                            $isSelected = $selectedDate === $dateStr;
                        @endphp
                        <button
                            type="button"
                            wire:click="$set('selectedDate', '{{ $dateStr }}')"
                            class="p-3 rounded-2xl border text-left transition-all {{ $isSelected ? 'border-[#DF2600] bg-[#FFF0EC] shadow-sm' : 'border-[#EEEEEE] bg-white hover:border-[#CCCCCC]' }}"
                        >
                            <div class="text-[11px] font-bold uppercase {{ $isSelected ? 'text-[#DF2600]' : 'text-[#686868]' }}">
                                {{ $day->isToday() ? 'Today' : ($day->isTomorrow() ? 'Tomorrow' : $day->format('D')) }}
                            </div>
                            <div class="text-xs sm:text-sm font-extrabold text-[#111111] mt-0.5">
                                {{ $day->format('M j') }}
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Time Slots -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-[#111111] mb-2.5">
                    2. Select Time Window
                </label>
                @if ($availableSlots->isEmpty())
                    <div class="p-4 rounded-2xl bg-[#FAFAFA] border border-[#EEEEEE] text-center text-xs text-[#686868]">
                        No pickup slots available for this date. Please select another date.
                    </div>
                @else
                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-2 max-h-56 overflow-y-auto pr-1">
                        @foreach ($availableSlots as $slot)
                            @php
                                $slotStr = $slot->format('H:i');
                                $isSelected = $selectedSlot === $slotStr;
                            @endphp
                            <button
                                type="button"
                                wire:click="$set('selectedSlot', '{{ $slotStr }}')"
                                class="py-2.5 px-2 rounded-xl border text-xs font-bold transition-all {{ $isSelected ? 'border-[#DF2600] bg-[#DF2600] text-white shadow-sm' : 'border-[#EEEEEE] bg-white text-[#111111] hover:border-[#CCCCCC]' }}"
                            >
                                {{ $slot->format('g:i A') }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Order Summary Preview -->
            <div class="pt-4 border-t border-[#EEEEEE]">
                <h3 class="text-xs font-bold uppercase tracking-wider text-[#686868] mb-3">Items in this order ({{ $order->items->count() }})</h3>
                <div class="space-y-2">
                    @foreach ($order->items as $item)
                        <div class="flex items-center justify-between text-xs py-1.5">
                            <div class="min-w-0 pr-3">
                                <span class="font-extrabold text-[#111111]">{{ $item->quantity }}×</span>
                                <span class="text-[#111111] truncate">{{ $item->name }}</span>
                                @if ($item->variation_name)
                                    <span class="text-[#686868] text-[11px]">({{ $item->variation_name }})</span>
                                @endif
                            </div>
                            <span class="font-bold text-[#111111] shrink-0">{{ money($item->line_total_cents) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Confirm Button -->
            <div class="pt-2">
                <button
                    type="button"
                    wire:click="submit"
                    wire:loading.attr="disabled"
                    class="w-full py-3.5 px-6 rounded-2xl bg-[#DF2600] text-white text-sm font-extrabold shadow-md hover:bg-[#BF2000] active:scale-[0.99] transition-all flex items-center justify-center gap-2 disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="submit">Confirm New Collection Time</span>
                    <span wire:loading wire:target="submit" class="inline-flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Updating Pickup Window...
                    </span>
                </button>
            </div>
        @endif
    </div>
</div>
