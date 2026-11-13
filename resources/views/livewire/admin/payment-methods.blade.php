<div class="space-y-6 sm:space-y-8">
    <!-- Header -->
    <div>
        <h2 class="text-xl sm:text-2xl font-extrabold text-[#111111] tracking-tight">Payment Methods Controls</h2>
        <p class="mt-1 text-xs sm:text-sm text-[#686868]">
            Enable or disable customer payment options and adjust their display order at checkout. Handlers are fixed in codebase to ensure payment gateway security.
        </p>
    </div>

    @if (session('saved'))
        <div class="p-4 rounded-xl bg-[#DAF4E7] border border-[#A8DFC4] flex items-center gap-3">
            <svg class="w-5 h-5 text-[#007D49]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span class="text-xs sm:text-sm font-bold text-[#007D49]">{{ session('saved') }}</span>
        </div>
    @endif

    @error('toggle')
        <div class="p-4 rounded-xl bg-[#FFF0EC] border border-[#FFB79F] text-xs font-bold text-[#DF2600]">
            {{ $message }}
        </div>
    @enderror

    <!-- Delivery switch -->
    <div class="bg-white rounded-2xl border border-[#EEEEEE] shadow-xs p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <h3 class="text-base sm:text-lg font-extrabold text-[#111111]">Delivery</h3>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-[#E0F1FF] text-[#007FE8]">delivery</span>
            </div>
            <p class="mt-0.5 text-xs text-[#686868]">
                Let customers choose Delivery at checkout. When it's off, checkout offers Pickup only and the basket stops showing delivery fees. Orders already placed for delivery are not affected.
            </p>
        </div>
        <div class="flex items-center justify-between sm:justify-end gap-3 pt-2 sm:pt-0 border-t border-[#F6F6F6] sm:border-0">
            <span class="sm:hidden text-xs font-bold text-[#686868]">Status:</span>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox"
                       wire:click="toggleDelivery"
                       {{ $deliveryEnabled ? 'checked' : '' }}
                       aria-label="Offer delivery at checkout"
                       class="sr-only peer">
                <div class="w-11 h-6 bg-[#DEDEDE] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#007D49]"></div>
                <span class="ml-2.5 text-xs font-bold {{ $deliveryEnabled ? 'text-[#007D49]' : 'text-[#686868]' }}">
                    {{ $deliveryEnabled ? 'Enabled' : 'Disabled' }}
                </span>
            </label>
        </div>
    </div>

    <!-- Methods Table Card -->
    <div class="bg-white rounded-2xl border border-[#EEEEEE] shadow-xs overflow-hidden">
        <div class="p-4 sm:p-6 border-b border-[#EEEEEE] bg-[#FAFAFA]/50">
            <h3 class="text-base sm:text-lg font-extrabold text-[#111111]">Active Payment Channels</h3>
            <p class="text-xs text-[#686868]">Toggle switches affect new checkouts immediately. Existing placed orders keep their recorded payment method.</p>
        </div>

        <div class="divide-y divide-[#EEEEEE]">
            @foreach ($methods as $m)
                <div class="p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 hover:bg-[#FAFAFA]/40 transition-colors">
                    <div class="flex items-start sm:items-center gap-3 sm:gap-4">
                        <!-- Up/Down sorting buttons -->
                        <div class="flex flex-col gap-1 shrink-0 mt-0.5 sm:mt-0">
                            <button type="button" 
                                    wire:click="move({{ $m->id }}, -1)"
                                    title="Move Up"
                                    class="p-1.5 rounded-lg hover:bg-[#EEEEEE] text-[#686868] hover:text-[#111111] transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
                                </svg>
                            </button>
                            <button type="button" 
                                    wire:click="move({{ $m->id }}, 1)"
                                    title="Move Down"
                                    class="p-1.5 rounded-lg hover:bg-[#EEEEEE] text-[#686868] hover:text-[#111111] transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </div>

                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h4 class="text-sm sm:text-base font-extrabold text-[#111111]">{{ $m->label() }}</h4>
                                <span class="px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-mono bg-[#F6F6F6] text-[#686868]">
                                    {{ $m->code }}
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs text-[#686868]">{{ $m->description() }}</p>

                            <!-- Fulfilment Badges -->
                            <div class="mt-2 flex items-center gap-1.5 sm:gap-2 flex-wrap">
                                @foreach ($m->allowedFulfilments() as $f)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $f === 'pickup' ? 'bg-[#DAF4E7] text-[#007D49]' : 'bg-[#E0F1FF] text-[#007FE8]' }}">
                                        {{ $f }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Toggle Control -->
                    <div class="flex items-center justify-between sm:justify-end gap-3 pt-2 sm:pt-0 border-t border-[#F6F6F6] sm:border-0">
                        <span class="sm:hidden text-xs font-bold text-[#686868]">Status:</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" 
                                   wire:click="toggle({{ $m->id }})"
                                   {{ $m->is_enabled ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-[#DEDEDE] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#007D49]"></div>
                            <span class="ml-2.5 text-xs font-bold {{ $m->is_enabled ? 'text-[#007D49]' : 'text-[#686868]' }}">
                                {{ $m->is_enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </label>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="p-4 sm:p-6 bg-[#FAFAFA] border-t border-[#EEEEEE] text-xs text-[#686868] flex items-start sm:items-center gap-3">
            <svg class="w-5 h-5 text-[#686868] shrink-0 mt-0.5 sm:mt-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>
                Notice: To prevent runtime checkout errors, new payment methods can only be introduced by developers through registered gateway handlers in <code class="bg-white px-1.5 py-0.5 rounded border border-[#EEEEEE] font-mono">config/payments.php</code>.
            </span>
        </div>
    </div>
</div>
