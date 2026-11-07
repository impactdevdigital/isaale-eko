<div class="space-y-6 sm:space-y-8" wire:poll.30s>
    <!-- Header with Live Indicator -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-[#111111] tracking-tight">Orders & Fulfillment Queue</h2>
            <p class="mt-1 text-xs sm:text-sm text-[#686868]">
                Real-time store orders for collection and local delivery. Track preparation, notify customers, and record handoffs.
            </p>
        </div>

        <div class="flex items-center gap-3 self-start sm:self-auto">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#DAF4E7] text-xs font-bold text-[#007D49]">
                <span class="w-2 h-2 rounded-full bg-[#007D49] animate-pulse"></span>
                Live Queue Auto-Refresh
            </span>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="p-3.5 sm:p-4 bg-[#DAF4E7] border border-[#007D49]/20 rounded-2xl flex items-center justify-between text-[#007D49] text-xs sm:text-sm font-semibold">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-xs font-bold opacity-70 hover:opacity-100 ml-2">Dismiss</button>
        </div>
    @endif

    @if (session()->has('warning'))
        <div class="p-3.5 sm:p-4 bg-[#FFF9EA] border border-[#D58700]/20 rounded-2xl flex items-center justify-between text-[#D58700] text-xs sm:text-sm font-semibold">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>{{ session('warning') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-xs font-bold opacity-70 hover:opacity-100 ml-2">Dismiss</button>
        </div>
    @endif

    <!-- Quick Stat KPI Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
        <div class="p-3.5 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs cursor-pointer hover:border-[#DF2600]/40 transition-colors {{ $statusFilter === 'active' ? 'ring-2 ring-[#DF2600]' : '' }}"
             wire:click="$set('statusFilter', 'active')">
            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-[#686868]">Active Orders</span>
            <div class="mt-1 sm:mt-2 text-xl sm:text-2xl font-extrabold text-[#111111]">{{ number_format($counts['active']) }}</div>
            <span class="text-[10px] sm:text-[11px] text-[#686868]">Need fulfillment</span>
        </div>

        <div class="p-3.5 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs cursor-pointer hover:border-[#DF2600]/40 transition-colors {{ $statusFilter === 'new' ? 'ring-2 ring-[#DF2600]' : '' }}"
             wire:click="$set('statusFilter', 'new')">
            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-[#DF2600]">New Orders</span>
            <div class="mt-1 sm:mt-2 text-xl sm:text-2xl font-extrabold text-[#DF2600]">{{ number_format($counts['new']) }}</div>
            <span class="text-[10px] sm:text-[11px] text-[#686868]">Action required</span>
        </div>

        <div class="p-3.5 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs cursor-pointer hover:border-[#D58700]/40 transition-colors {{ $statusFilter === 'preparing' ? 'ring-2 ring-[#D58700]' : '' }}"
             wire:click="$set('statusFilter', 'preparing')">
            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-[#D58700]">Preparing</span>
            <div class="mt-1 sm:mt-2 text-xl sm:text-2xl font-extrabold text-[#D58700]">{{ number_format($counts['preparing']) }}</div>
            <span class="text-[10px] sm:text-[11px] text-[#686868]">Packing in store</span>
        </div>

        <div class="p-3.5 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs cursor-pointer hover:border-[#0055D4]/40 transition-colors {{ $statusFilter === 'ready' ? 'ring-2 ring-[#0055D4]' : '' }}"
             wire:click="$set('statusFilter', 'ready')">
            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-[#0055D4]">Ready Handover</span>
            <div class="mt-1 sm:mt-2 text-xl sm:text-2xl font-extrabold text-[#0055D4]">{{ number_format($counts['ready']) }}</div>
            <span class="text-[10px] sm:text-[11px] text-[#686868]">Customer notified</span>
        </div>

        <div class="p-3.5 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs col-span-2 sm:col-span-1">
            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-[#007D49]">Completed</span>
            <div class="mt-1 sm:mt-2 text-xl sm:text-2xl font-extrabold text-[#007D49]">{{ number_format($counts['completed_today']) }}</div>
            <span class="text-[10px] sm:text-[11px] text-[#686868]">Today's completed</span>
        </div>
    </div>

    <!-- Filters and Search Bar -->
    <div class="p-4 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs space-y-3 sm:space-y-4">
        <!-- Search and Dropdowns -->
        <div class="flex flex-col md:flex-row gap-3">
            <div class="flex-1 relative">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search order #, customer name, phone, or email..."
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] placeholder-[#686868] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                <svg class="w-4 h-4 text-[#686868] absolute left-3.5 top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                @if ($search)
                    <button type="button" wire:click="$set('search', '')" class="absolute right-3.5 top-3.5 text-xs text-[#686868] hover:text-[#111111]">Clear</button>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:flex items-center gap-2">
                <select wire:model.live="fulfilmentFilter" class="w-full px-3 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-xs font-semibold text-[#111111] focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20">
                    <option value="">All Fulfillment (Pickup & Delivery)</option>
                    <option value="pickup">In-Store Pickup Only</option>
                    <option value="delivery">Local Delivery Only</option>
                </select>

                <select wire:model.live="paymentFilter" class="w-full px-3 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-xs font-semibold text-[#111111] focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20">
                    <option value="">All Payments</option>
                    <option value="paid">Paid Online</option>
                    <option value="unpaid">Unpaid (Counter or e-Transfer)</option>
                </select>
            </div>
        </div>

        <!-- Status Filter Tabs (Horizontal touch scrollable on mobile) -->
        <div class="flex items-center gap-1.5 overflow-x-auto pt-2 border-t border-[#EEEEEE] text-xs font-bold text-[#686868] pb-1" style="-webkit-overflow-scrolling: touch;">
            <button type="button"
                    wire:click="$set('statusFilter', 'active')"
                    class="px-3.5 py-1.5 rounded-lg transition-colors shrink-0 {{ $statusFilter === 'active' ? 'bg-[#111111] text-white' : 'hover:bg-[#F6F6F6] text-[#686868]' }}">
                Active Queue ({{ $counts['active'] }})
            </button>

            <button type="button"
                    wire:click="$set('statusFilter', 'new')"
                    class="px-3.5 py-1.5 rounded-lg transition-colors shrink-0 {{ $statusFilter === 'new' ? 'bg-[#DF2600] text-white' : 'hover:bg-[#F6F6F6] text-[#686868]' }}">
                New ({{ $counts['new'] }})
            </button>

            <button type="button"
                    wire:click="$set('statusFilter', 'preparing')"
                    class="px-3.5 py-1.5 rounded-lg transition-colors shrink-0 {{ $statusFilter === 'preparing' ? 'bg-[#D58700] text-white' : 'hover:bg-[#F6F6F6] text-[#686868]' }}">
                Preparing ({{ $counts['preparing'] }})
            </button>

            <button type="button"
                    wire:click="$set('statusFilter', 'ready')"
                    class="px-3.5 py-1.5 rounded-lg transition-colors shrink-0 {{ $statusFilter === 'ready' ? 'bg-[#0055D4] text-white' : 'hover:bg-[#F6F6F6] text-[#686868]' }}">
                Ready ({{ $counts['ready'] }})
            </button>

            <button type="button"
                    wire:click="$set('statusFilter', 'uncollected')"
                    class="px-3.5 py-1.5 rounded-lg transition-colors shrink-0 {{ $statusFilter === 'uncollected' ? 'bg-[#DF2600] text-white' : 'hover:bg-[#F6F6F6] text-[#686868]' }}">
                Uncollected ({{ $counts['uncollected'] }})
            </button>

            <button type="button"
                    wire:click="$set('statusFilter', 'etransfer')"
                    class="px-3.5 py-1.5 rounded-lg transition-colors shrink-0 {{ $statusFilter === 'etransfer' ? 'bg-[#7A5A00] text-white' : 'hover:bg-[#F6F6F6] text-[#686868]' }}">
                e-Transfers to Confirm ({{ $counts['etransfer'] }})
            </button>

            <button type="button"
                    wire:click="$set('statusFilter', 'completed')"
                    class="px-3.5 py-1.5 rounded-lg transition-colors shrink-0 {{ $statusFilter === 'completed' ? 'bg-[#007D49] text-white' : 'hover:bg-[#F6F6F6] text-[#686868]' }}">
                Completed
            </button>

            <button type="button"
                    wire:click="$set('statusFilter', 'cancelled')"
                    class="px-3.5 py-1.5 rounded-lg transition-colors shrink-0 {{ $statusFilter === 'cancelled' ? 'bg-[#686868] text-white' : 'hover:bg-[#F6F6F6] text-[#686868]' }}">
                Cancelled
            </button>

            <button type="button"
                    wire:click="$set('statusFilter', 'all')"
                    class="px-3.5 py-1.5 rounded-lg transition-colors shrink-0 {{ $statusFilter === 'all' ? 'bg-[#111111] text-white' : 'hover:bg-[#F6F6F6] text-[#686868]' }}">
                All History
            </button>
        </div>
    </div>

    <!-- Orders Container: Card Stack for Mobile, Table for Desktop -->
    <div class="bg-white rounded-2xl border border-[#EEEEEE] shadow-xs overflow-hidden">
        <!-- MOBILE CARD STACK (< md screens) -->
        <div class="block md:hidden divide-y divide-[#EEEEEE]">
            @forelse ($orders as $order)
                <div class="p-4 space-y-3 hover:bg-[#FAFAFA] transition-colors {{ $selectedOrderId === $order->id ? 'bg-[#FFF0EC]/30' : '' }}">
                    <!-- Header Row: Order Number, Badge, Time -->
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="font-extrabold text-[#111111] text-base">#{{ $order->number }}</span>
                            @if ($order->status === \App\Models\Order::STATUS_UNCOLLECTED)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#FFF0EC] text-[#DF2600]">
                                    Uncollected
                                </span>
                            @elseif ($order->status === \App\Models\Order::STATUS_REFUNDED)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#FAFAFA] border border-[#EEEEEE] text-[#686868]">
                                    Refunded
                                </span>
                            @elseif ($order->status === \App\Models\Order::STATUS_PAID)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#DAF4E7] text-[#007D49]">
                                    Paid
                                </span>
                            @elseif ($order->status === \App\Models\Order::STATUS_CANCELLED)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#F6F6F6] text-[#686868]">
                                    Cancelled
                                </span>
                            @elseif ($order->isAwaitingETransfer())
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#FFF4CC] text-[#7A5A00]">
                                    Awaiting e-Transfer
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#FFF9EA] text-[#D58700]">
                                    Pay at Counter
                                </span>
                            @endif
                        </div>
                        <span class="text-[11px] text-[#686868]">
                            {{ $order->placed_at ? $order->placed_at->setTimezone(config('shop.timezone'))->format('g:i A') : $order->created_at->diffForHumans() }}
                        </span>
                    </div>

                    <!-- Customer & Total Summary -->
                    <div class="flex items-start justify-between gap-2 text-xs">
                        <div>
                            <div class="font-bold text-[#111111]">{{ $order->contact_name }}</div>
                            @if ($order->contact_phone)
                                <a href="tel:{{ $order->contact_phone }}" class="text-[#DF2600] font-medium hover:underline text-[11px] block mt-0.5">
                                    {{ $order->contact_phone }}
                                </a>
                            @endif
                        </div>
                        <div class="text-right shrink-0">
                            <div class="font-black text-sm text-[#111111]">{{ money($order->total_cents) }}</div>
                            <div class="text-[11px] text-[#686868]">{{ $order->itemCount() }} {{ str('item')->plural($order->itemCount()) }}</div>
                        </div>
                    </div>

                    <!-- Stage & Fulfillment Badge -->
                    <div class="flex items-center justify-between gap-2 pt-1">
                        @if ($order->fulfilment === 'pickup')
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#F6F6F6] text-[11px] font-bold text-[#111111]">
                                <svg class="w-3.5 h-3.5 text-[#007D49]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                In-Store Pickup
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#EBF3FF] text-[11px] font-bold text-[#0055D4]">
                                <svg class="w-3.5 h-3.5 text-[#0055D4]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                                </svg>
                                Local Delivery
                            </span>
                        @endif

                        <!-- Stage Badge -->
                        @if ($order->status === \App\Models\Order::STATUS_UNCOLLECTED)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-bold bg-[#FFF0EC] text-[#DF2600]">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#DF2600]"></span>
                                Uncollected
                            </span>
                        @elseif ($order->status === \App\Models\Order::STATUS_REFUNDED)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-bold bg-[#FAFAFA] border border-[#EEEEEE] text-[#686868]">
                                Refunded
                            </span>
                        @elseif ($order->status === \App\Models\Order::STATUS_CANCELLED)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-bold bg-[#F6F6F6] text-[#686868]">
                                Cancelled
                            </span>
                        @elseif ($order->fulfilment_status === \App\Models\Order::FULFILMENT_COMPLETED)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-bold bg-[#DAF4E7] text-[#007D49]">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#007D49]"></span>
                                {{ $order->fulfilment === 'pickup' ? 'Collected' : 'Delivered' }}
                            </span>
                        @elseif ($order->fulfilment_status === \App\Models\Order::FULFILMENT_READY)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-bold bg-[#EBF3FF] text-[#0055D4]">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#0055D4]"></span>
                                Ready
                            </span>
                        @elseif ($order->fulfilment_status === \App\Models\Order::FULFILMENT_PREPARING)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-bold bg-[#FFF9EA] text-[#D58700]">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#D58700]"></span>
                                Preparing
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-bold bg-[#FFF0EC] text-[#DF2600]">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#DF2600] animate-ping"></span>
                                New Order
                            </span>
                        @endif
                    </div>

                    <!-- Touch Actions for Mobile -->
                    <div class="flex items-center gap-2 pt-2 border-t border-[#EEEEEE]">
                        @if ($order->status === \App\Models\Order::STATUS_UNCOLLECTED)
                            <button type="button"
                                    wire:click="viewOrder({{ $order->id }})"
                                    style="background-color: #0055D4;"
                                    class="flex-1 py-2 px-3 rounded-xl text-white text-xs font-bold shadow-xs text-center active:scale-98 transition-transform">
                                Review & Reschedule
                            </button>
                        @elseif ($order->isAwaitingETransfer())
                            <button type="button"
                                    wire:click="viewOrder({{ $order->id }})"
                                    class="flex-1 py-2 px-3 rounded-xl border border-[#FFE8A3] bg-[#FFF4CC] text-[#7A5A00] text-xs font-bold text-center">
                                Check e-Transfer
                            </button>
                        @elseif ($order->status !== \App\Models\Order::STATUS_CANCELLED && $order->status !== \App\Models\Order::STATUS_REFUNDED && $order->fulfilment_status !== \App\Models\Order::FULFILMENT_COMPLETED)
                            @if ($order->fulfilment_status === null)
                                <button type="button"
                                        wire:click="startPreparing({{ $order->id }})"
                                        style="background-color: #DF2600;"
                                        class="flex-1 py-2 px-3 rounded-xl text-white text-xs font-bold shadow-xs text-center active:scale-98 transition-transform">
                                    Start Preparing
                                </button>
                            @elseif ($order->fulfilment_status === \App\Models\Order::FULFILMENT_PREPARING)
                                <button type="button"
                                        wire:click="markAsReady({{ $order->id }})"
                                        style="background-color: #0055D4;"
                                        class="flex-1 py-2 px-3 rounded-xl text-white text-xs font-bold shadow-xs text-center active:scale-98 transition-transform">
                                    Mark Ready
                                </button>
                                <button type="button"
                                        wire:click="markAsCompleted({{ $order->id }})"
                                        style="background-color: #007D49;"
                                        class="py-2 px-3 rounded-xl text-white text-xs font-bold shadow-xs text-center active:scale-98 transition-transform">
                                    {{ $order->fulfilment === 'pickup' ? 'Hand Over' : 'Delivered' }}
                                </button>
                            @elseif ($order->fulfilment_status === \App\Models\Order::FULFILMENT_READY)
                                <button type="button"
                                        wire:click="markAsCompleted({{ $order->id }})"
                                        style="background-color: #007D49;"
                                        class="flex-1 py-2 px-3 rounded-xl text-white text-xs font-bold shadow-xs text-center active:scale-98 transition-transform">
                                    {{ $order->fulfilment === 'pickup' ? 'Hand Over' : 'Delivered' }}
                                </button>
                                @if ($order->fulfilment === 'pickup')
                                    <button type="button"
                                            wire:click="markAsUncollected({{ $order->id }})"
                                            class="py-2 px-2.5 rounded-xl border border-[#DF2600]/30 bg-[#FFF0EC] text-[#DF2600] text-xs font-bold shadow-xs text-center">
                                        Uncollected
                                    </button>
                                @endif
                            @endif
                        @endif

                        <button type="button"
                                wire:click="viewOrder({{ $order->id }})"
                                class="py-2 px-3.5 rounded-xl bg-[#FAFAFA] border border-[#EEEEEE] text-xs font-bold text-[#111111] hover:bg-[#EEEEEE] transition-colors {{ ($order->status === \App\Models\Order::STATUS_CANCELLED || $order->fulfilment_status === \App\Models\Order::FULFILMENT_COMPLETED) ? 'w-full' : '' }}">
                            Details
                        </button>
                    </div>
                </div>
            @empty
                <div class="py-12 px-4 text-center">
                    <h3 class="text-base font-extrabold text-[#111111]">No orders in this view</h3>
                    <p class="text-xs text-[#686868] mt-1">
                        Change filter tabs or search terms to inspect more history.
                    </p>
                </div>
            @endforelse
        </div>

        <!-- DESKTOP ORDERS TABLE (hidden on mobile, visible on md+) -->
        <div class="hidden md:block overflow-x-auto" style="-webkit-overflow-scrolling: touch;">
            <table class="w-full text-left text-sm text-[#111111] min-w-[700px]">
                <thead class="bg-[#FAFAFA] border-b border-[#EEEEEE] text-[11px] font-extrabold uppercase tracking-wider text-[#686868]">
                    <tr>
                        <th class="py-3.5 px-5">Order</th>
                        <th class="py-3.5 px-4">Customer</th>
                        <th class="py-3.5 px-4">Fulfillment</th>
                        <th class="py-3.5 px-4">Items & Total</th>
                        <th class="py-3.5 px-4">Stage</th>
                        <th class="py-3.5 px-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EEEEEE]">
                    @forelse ($orders as $order)
                        <tr class="hover:bg-[#FAFAFA] transition-colors {{ $selectedOrderId === $order->id ? 'bg-[#FFF0EC]/30' : '' }}">
                            <!-- Order Number & Payment Badge -->
                            <td class="py-4 px-5">
                                <div class="flex items-center gap-2">
                                    <span class="font-extrabold text-[#111111] text-[15px]">#{{ $order->number }}</span>
                                    @if ($order->status === \App\Models\Order::STATUS_UNCOLLECTED)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#FFF0EC] text-[#DF2600]">
                                            Uncollected
                                        </span>
                                    @elseif ($order->status === \App\Models\Order::STATUS_REFUNDED)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#FAFAFA] border border-[#EEEEEE] text-[#686868]">
                                            Refunded
                                        </span>
                                    @elseif ($order->status === \App\Models\Order::STATUS_PAID)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#DAF4E7] text-[#007D49]">
                                            Paid
                                        </span>
                                    @elseif ($order->status === \App\Models\Order::STATUS_CANCELLED)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#F6F6F6] text-[#686868]">
                                            Cancelled
                                        </span>
                                    @elseif ($order->isAwaitingETransfer())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#FFF4CC] text-[#7A5A00]">
                                            Awaiting e-Transfer
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#FFF9EA] text-[#D58700]">
                                            Pay at Counter
                                        </span>
                                    @endif
                                </div>
                                <div class="mt-1 text-xs text-[#686868]">
                                    Placed {{ $order->placed_at ? $order->placed_at->setTimezone(config('shop.timezone'))->format('M j, g:i A') : $order->created_at->diffForHumans() }}
                                </div>
                            </td>

                            <!-- Customer Information -->
                            <td class="py-4 px-4">
                                <div class="font-bold text-[#111111]">{{ $order->contact_name }}</div>
                                <div class="mt-0.5 text-xs text-[#686868] flex items-center gap-2">
                                    @if ($order->contact_phone)
                                        <a href="tel:{{ $order->contact_phone }}" class="hover:text-[#DF2600] underline font-medium">
                                            {{ $order->contact_phone }}
                                        </a>
                                    @endif
                                    @if ($order->contact_email)
                                        <span class="truncate max-w-[150px]">{{ $order->contact_email }}</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Fulfillment Method -->
                            <td class="py-4 px-4">
                                @if ($order->fulfilment === 'pickup')
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#F6F6F6] text-xs font-bold text-[#111111]">
                                        <svg class="w-3.5 h-3.5 text-[#007D49]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        In-Store Pickup
                                    </div>
                                    @if ($order->pickup_starts_at && $order->pickup_ends_at)
                                        <div class="mt-1 text-[11px] text-[#686868]">
                                            Slot: {{ $order->pickup_starts_at->setTimezone(config('shop.timezone'))->format('g:i A') }} - {{ $order->pickup_ends_at->setTimezone(config('shop.timezone'))->format('g:i A') }}
                                        </div>
                                    @endif
                                @else
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#EBF3FF] text-xs font-bold text-[#0055D4]">
                                        <svg class="w-3.5 h-3.5 text-[#0055D4]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                                        </svg>
                                        Local Delivery
                                    </div>
                                    @if (is_array($order->delivery_address))
                                        <div class="mt-1 text-[11px] text-[#686868] truncate max-w-[180px]">
                                            {{ $order->delivery_address['street'] ?? '' }}, {{ $order->delivery_address['city'] ?? '' }}
                                        </div>
                                    @endif
                                @endif
                            </td>

                            <!-- Items & Total -->
                            <td class="py-4 px-4">
                                <div class="font-extrabold text-[#111111] text-sm">
                                    {{ money($order->total_cents) }}
                                </div>
                                <div class="mt-0.5 text-xs text-[#686868]">
                                    {{ $order->itemCount() }} {{ str('item')->plural($order->itemCount()) }}
                                </div>
                            </td>

                            <!-- Stage Badge -->
                            <td class="py-4 px-4">
                                @if ($order->status === \App\Models\Order::STATUS_UNCOLLECTED)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-bold bg-[#FFF0EC] text-[#DF2600]">
                                        <span class="w-2 h-2 rounded-full bg-[#DF2600]"></span>
                                        Uncollected
                                    </span>
                                @elseif ($order->status === \App\Models\Order::STATUS_REFUNDED)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-bold bg-[#FAFAFA] border border-[#EEEEEE] text-[#686868]">
                                        Refunded
                                    </span>
                                @elseif ($order->status === \App\Models\Order::STATUS_CANCELLED)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-bold bg-[#F6F6F6] text-[#686868]">
                                        Cancelled
                                    </span>
                                @elseif ($order->fulfilment_status === \App\Models\Order::FULFILMENT_COMPLETED)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-bold bg-[#DAF4E7] text-[#007D49]">
                                        <span class="w-2 h-2 rounded-full bg-[#007D49]"></span>
                                        {{ $order->fulfilment === 'pickup' ? 'Collected' : 'Delivered' }}
                                    </span>
                                @elseif ($order->fulfilment_status === \App\Models\Order::FULFILMENT_READY)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-bold bg-[#EBF3FF] text-[#0055D4]">
                                        <span class="w-2 h-2 rounded-full bg-[#0055D4]"></span>
                                        Ready for {{ $order->fulfilment === 'pickup' ? 'Pickup' : 'Delivery' }}
                                    </span>
                                @elseif ($order->fulfilment_status === \App\Models\Order::FULFILMENT_PREPARING)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-bold bg-[#FFF9EA] text-[#D58700]">
                                        <span class="w-2 h-2 rounded-full bg-[#D58700]"></span>
                                        Preparing Order
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-bold bg-[#FFF0EC] text-[#DF2600]">
                                        <span class="w-2 h-2 rounded-full bg-[#DF2600] animate-ping"></span>
                                        New Order
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($order->status === \App\Models\Order::STATUS_UNCOLLECTED)
                                        <button type="button"
                                                wire:click="viewOrder({{ $order->id }})"
                                                style="background-color: #0055D4;"
                                                class="px-3 py-1.5 rounded-xl text-white text-xs font-bold hover:opacity-90 transition-opacity shadow-xs">
                                            Review & Reschedule
                                        </button>
                                    @elseif ($order->isAwaitingETransfer())
                                        <button type="button"
                                                wire:click="viewOrder({{ $order->id }})"
                                                class="flex-1 py-2 px-3 rounded-xl border border-[#FFE8A3] bg-[#FFF4CC] text-[#7A5A00] text-xs font-bold text-center">
                                            Check e-Transfer
                                        </button>
                                    @elseif ($order->status !== \App\Models\Order::STATUS_CANCELLED && $order->status !== \App\Models\Order::STATUS_REFUNDED && $order->fulfilment_status !== \App\Models\Order::FULFILMENT_COMPLETED)
                                        @if ($order->fulfilment_status === null)
                                            <button type="button"
                                                    wire:click="startPreparing({{ $order->id }})"
                                                    style="background-color: #DF2600;"
                                                    class="px-3 py-1.5 rounded-xl text-white text-xs font-bold hover:opacity-90 transition-opacity shadow-xs">
                                                Start Preparing
                                            </button>
                                        @elseif ($order->fulfilment_status === \App\Models\Order::FULFILMENT_PREPARING)
                                            <button type="button"
                                                    wire:click="markAsReady({{ $order->id }})"
                                                    style="background-color: #0055D4;"
                                                    class="px-3 py-1.5 rounded-xl text-white text-xs font-bold hover:opacity-90 transition-opacity shadow-xs">
                                                Mark Ready
                                            </button>
                                            <button type="button"
                                                    wire:click="markAsCompleted({{ $order->id }})"
                                                    style="background-color: #007D49;"
                                                    class="px-3 py-1.5 rounded-xl text-white text-xs font-bold hover:opacity-90 transition-opacity shadow-xs">
                                                {{ $order->fulfilment === 'pickup' ? 'Hand Over' : 'Delivered' }}
                                            </button>
                                        @elseif ($order->fulfilment_status === \App\Models\Order::FULFILMENT_READY)
                                            <button type="button"
                                                    wire:click="markAsCompleted({{ $order->id }})"
                                                    style="background-color: #007D49;"
                                                    class="px-3 py-1.5 rounded-xl text-white text-xs font-bold hover:opacity-90 transition-opacity shadow-xs">
                                                {{ $order->fulfilment === 'pickup' ? 'Hand Over (Collected)' : 'Mark Delivered' }}
                                            </button>
                                            @if ($order->fulfilment === 'pickup')
                                                <button type="button"
                                                        wire:click="markAsUncollected({{ $order->id }})"
                                                        class="px-2.5 py-1.5 rounded-xl border border-[#DF2600]/30 bg-[#FFF0EC] text-[#DF2600] text-xs font-bold hover:bg-[#FFE0D8] transition-colors">
                                                    Mark Uncollected
                                                </button>
                                            @endif
                                        @endif
                                    @endif

                                    <button type="button"
                                            wire:click="viewOrder({{ $order->id }})"
                                            class="px-3 py-1.5 rounded-xl bg-[#FAFAFA] border border-[#EEEEEE] text-xs font-bold text-[#111111] hover:bg-[#EEEEEE] transition-colors">
                                        View Details
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 px-6 text-center">
                                <div class="max-w-sm mx-auto space-y-3">
                                    <div class="w-12 h-12 mx-auto rounded-full bg-[#FAFAFA] border border-[#EEEEEE] flex items-center justify-center text-[#686868]">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-base font-extrabold text-[#111111]">No orders in this view</h3>
                                    <p class="text-xs text-[#686868]">
                                        There are currently no orders matching your selected filters. Change filter tabs or search terms to inspect more history.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="p-4 border-t border-[#EEEEEE] bg-[#FAFAFA]">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    <!-- Order Detail Slide-Over Drawer (Fully Responsive for Mobile & Desktop) -->
    @if ($selectedOrder)
        <div class="fixed inset-0 z-50 overflow-hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/40 backdrop-blur-xs transition-opacity" wire:click="closeDrawer"></div>

            <div class="fixed inset-y-0 right-0 max-w-full flex pl-0 sm:pl-10">
                <div class="w-screen max-w-full sm:max-w-xl bg-white shadow-2xl flex flex-col justify-between">
                    <!-- Drawer Header -->
                    <div class="p-4 sm:p-6 border-b border-[#EEEEEE] flex items-center justify-between bg-white shrink-0">
                        <div>
                            <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
                                <h3 class="text-lg sm:text-xl font-extrabold text-[#111111]" id="slide-over-title">
                                    Order #{{ $selectedOrder->number }}
                                </h3>
                                @if ($selectedOrder->status === \App\Models\Order::STATUS_UNCOLLECTED)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-bold bg-[#FFF0EC] text-[#DF2600]">
                                        Uncollected
                                    </span>
                                @elseif ($selectedOrder->status === \App\Models\Order::STATUS_REFUNDED)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-bold bg-[#FAFAFA] border border-[#EEEEEE] text-[#686868]">
                                        Refunded
                                    </span>
                                @elseif ($selectedOrder->status === \App\Models\Order::STATUS_PAID)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-bold bg-[#DAF4E7] text-[#007D49]">
                                        Paid
                                    </span>
                                @elseif ($selectedOrder->status === \App\Models\Order::STATUS_CANCELLED)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-bold bg-[#F6F6F6] text-[#686868]">
                                        Cancelled
                                    </span>
                                @elseif ($selectedOrder->isAwaitingETransfer())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-bold bg-[#FFF4CC] text-[#7A5A00]">
                                        Awaiting e-Transfer
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-bold bg-[#FFF9EA] text-[#D58700]">
                                        Pay at Counter
                                    </span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-[#686868]">
                                Placed on {{ $selectedOrder->placed_at ? $selectedOrder->placed_at->setTimezone(config('shop.timezone'))->format('M j, Y \a\t g:i A') : $selectedOrder->created_at->format('M j, Y') }}
                            </p>

                            @if ($selectedOrder->status === \App\Models\Order::STATUS_UNCOLLECTED)
                                <div class="mt-3 p-3 rounded-xl bg-[#FFF0EC] border border-[#DF2600]/20 flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-[#DF2600] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <div class="text-xs">
                                        <div class="font-extrabold text-[#DF2600]">Order Uncollected Past Grace Period</div>
                                        <div class="text-[#686868] mt-0.5">
                                            Scheduled: {{ $selectedOrder->pickup_starts_at ? $selectedOrder->pickup_starts_at->setTimezone(config('shop.timezone'))->format('M j, g:i A') : 'N/A' }}
                                            ({{ $selectedOrder->pickup_starts_at ? $selectedOrder->pickup_starts_at->diffForHumans() : '' }}). Customer was notified to reschedule.
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <button type="button" wire:click="closeDrawer" class="text-[#686868] hover:text-[#111111] p-2 rounded-xl hover:bg-[#FAFAFA] transition-colors" aria-label="Close drawer">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Drawer Content (Scrollable) -->
                    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5 sm:space-y-6" style="-webkit-overflow-scrolling: touch;">
                        <!-- Fulfillment Status Tracker Banner -->
                        <div class="p-4 rounded-2xl border border-[#EEEEEE] bg-[#FAFAFA] space-y-3">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-[#686868]">Current Stage</span>
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <span class="text-base font-extrabold text-[#111111]">
                                    {{ $selectedOrder->statusLabel() }}
                                </span>
                                <span class="text-xs font-semibold text-[#686868]">
                                    Fulfillment: <strong class="text-[#111111] capitalize">{{ $selectedOrder->fulfilment }}</strong>
                                </span>
                            </div>

                            <!-- Progress Track -->
                            <div class="grid grid-cols-4 gap-1.5 sm:gap-2 pt-2">
                                @php
                                    $stepIndex = $selectedOrder->trackerStep();
                                @endphp
                                @foreach ($selectedOrder->trackerSteps() as $idx => $stepLabel)
                                    <div class="flex flex-col gap-1">
                                        <div class="h-1.5 rounded-full {{ $idx <= $stepIndex ? 'bg-[#007D49]' : 'bg-[#EEEEEE]' }}"></div>
                                        <span class="text-[9px] sm:text-[10px] leading-tight {{ $idx <= $stepIndex ? 'font-bold text-[#111111]' : 'text-[#686868]' }}">
                                            {{ $stepLabel }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Customer Details & Fulfillment Slot -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                            <div class="p-4 rounded-2xl border border-[#EEEEEE] space-y-1">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-[#686868]">Customer</span>
                                <div class="font-extrabold text-[#111111]">{{ $selectedOrder->contact_name }}</div>
                                @if ($selectedOrder->contact_phone)
                                    <div class="text-xs">
                                        <a href="tel:{{ $selectedOrder->contact_phone }}" class="text-[#DF2600] font-bold hover:underline">
                                            {{ $selectedOrder->contact_phone }}
                                        </a>
                                    </div>
                                @endif
                                @if ($selectedOrder->contact_email)
                                    <div class="text-xs text-[#686868] truncate">{{ $selectedOrder->contact_email }}</div>
                                @endif
                            </div>

                            <div class="p-4 rounded-2xl border border-[#EEEEEE] space-y-1">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-[#686868]">
                                    {{ $selectedOrder->fulfilment === 'pickup' ? 'Collection Window' : 'Delivery Address' }}
                                </span>
                                @if ($selectedOrder->fulfilment === 'pickup')
                                    <div class="font-extrabold text-[#111111]">
                                        {{ $selectedOrder->pickup_starts_at ? $selectedOrder->pickup_starts_at->setTimezone(config('shop.timezone'))->format('D, M j') : 'Scheduled' }}
                                    </div>
                                    @if ($selectedOrder->pickup_starts_at && $selectedOrder->pickup_ends_at)
                                        <div class="text-xs text-[#007D49] font-bold">
                                            {{ $selectedOrder->pickup_starts_at->setTimezone(config('shop.timezone'))->format('g:i A') }} – {{ $selectedOrder->pickup_ends_at->setTimezone(config('shop.timezone'))->format('g:i A') }}
                                        </div>
                                    @endif
                                @else
                                    @if (is_array($selectedOrder->delivery_address))
                                        <div class="font-extrabold text-[#111111] text-xs leading-tight">
                                            {{ $selectedOrder->delivery_address['street'] ?? '' }}
                                        </div>
                                        <div class="text-xs text-[#686868]">
                                            {{ $selectedOrder->delivery_address['city'] ?? '' }}, {{ $selectedOrder->delivery_address['postal_code'] ?? '' }}
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>

                        <!-- Itemized Order Lines -->
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-[#686868] mb-3">
                                Ordered Items ({{ $selectedOrder->itemCount() }})
                            </h4>
                            <div class="border border-[#EEEEEE] rounded-2xl overflow-hidden divide-y divide-[#EEEEEE]">
                                @foreach ($selectedOrder->items as $item)
                                    <div class="p-3.5 sm:p-4 space-y-3 {{ $item->isRemoved() ? 'bg-[#FAFAFA]/70 opacity-60' : '' }}">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex items-start gap-3 min-w-0">
                                                @if ($item->image)
                                                    <img src="{{ asset($item->image) }}" alt="" class="w-11 h-11 sm:w-12 sm:h-12 object-cover rounded-xl border border-[#EEEEEE] shrink-0">
                                                @else
                                                    <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-[#FAFAFA] border border-[#EEEEEE] flex items-center justify-center text-xs font-bold text-[#686868] shrink-0">
                                                        {{ substr($item->name, 0, 2) }}
                                                    </div>
                                                @endif
                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <h5 class="font-extrabold text-sm text-[#111111] truncate {{ $item->isRemoved() || $item->isSubstituted() ? 'line-through text-[#686868]' : '' }}">
                                                            {{ $item->name }}
                                                        </h5>
                                                        @if ($item->is_substitute)
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#DAF4E7] text-[#007D49]">
                                                                Replacement
                                                            </span>
                                                        @elseif ($item->isSubstituted())
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#FAFAFA] text-[#686868] border border-[#EEEEEE]">
                                                                Substituted
                                                            </span>
                                                        @elseif ($item->isRemoved())
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#FFF0EC] text-[#DF2600]">
                                                                Removed & Refunded
                                                            </span>
                                                        @endif
                                                    </div>
                                                    @if ($item->variation_name)
                                                        <span class="text-xs text-[#686868] truncate block">{{ $item->variation_name }}</span>
                                                    @endif
                                                    <div class="text-xs text-[#686868] mt-0.5">
                                                        Qty: <strong class="text-[#111111]">{{ $item->quantity }}</strong> × {{ money($item->unit_price_cents) }}
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="text-right shrink-0">
                                                <div class="font-extrabold text-sm text-[#111111] {{ $item->isRemoved() || $item->isSubstituted() ? 'line-through text-[#686868]' : '' }}">
                                                    {{ money($item->line_total_cents) }}
                                                </div>

                                                <!-- Item Unavailable Action (Only for active items during picking) -->
                                                @if ($item->isActive() && ! $item->is_substitute && ! $item->activeSubstitutionRequest && in_array($selectedOrder->fulfilment_status, [null, \App\Models\Order::FULFILMENT_PREPARING]) && $selectedOrder->status !== \App\Models\Order::STATUS_CANCELLED)
                                                    <button type="button"
                                                            wire:click="openSubstitutionModal({{ $item->id }})"
                                                            class="mt-1.5 px-2.5 py-1 rounded-lg border border-[#DF2600]/30 bg-[#FFF0EC] text-[#DF2600] text-[11px] font-bold hover:bg-[#DF2600] hover:text-white transition-all inline-flex items-center gap-1">
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                        </svg>
                                                        Item Unavailable
                                                    </button>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Substitution Desk Workflow Card if Active -->
                                        @if ($item->activeSubstitutionRequest)
                                            <div class="p-3.5 rounded-xl bg-[#FFF9EA] border border-[#FFE8A3] space-y-3">
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="flex items-center gap-2">
                                                        <span class="w-2.5 h-2.5 rounded-full bg-[#D58700] animate-pulse"></span>
                                                        <span class="text-xs font-black text-[#8A5800] uppercase tracking-wider">
                                                            {{ $item->activeSubstitutionRequest->isAwaitingReply() ? 'Awaiting Customer Confirmation' : 'Substitution: Call Customer' }}
                                                        </span>
                                                    </div>
                                                    @if ($item->activeSubstitutionRequest->isAwaitingReply())
                                                        <span class="text-[11px] font-bold text-[#8A5800] bg-white px-2 py-0.5 rounded-md border border-[#FFE8A3]">
                                                            ~{{ ceil($item->activeSubstitutionRequest->remainingSeconds() / 60) }} min left
                                                        </span>
                                                    @endif
                                                </div>

                                                <!-- Proposed Item Info -->
                                                <div class="text-xs text-[#111111] bg-white p-2.5 rounded-lg border border-[#FFE8A3] space-y-0.5">
                                                    <div class="font-extrabold text-[#111111]">
                                                        Proposed: {{ $item->activeSubstitutionRequest->substitute ? $item->activeSubstitutionRequest->substitute->product->name . ' (' . $item->activeSubstitutionRequest->substitute->name . ')' : 'No substitute (Refund only)' }}
                                                    </div>
                                                    <div class="text-[#686868]">
                                                        Difference: 
                                                        @if ($item->activeSubstitutionRequest->price_difference_cents > 0)
                                                            <strong class="text-[#DF2600]">+{{ money($item->activeSubstitutionRequest->price_difference_cents) }}</strong> (Customer owes difference)
                                                        @elseif ($item->activeSubstitutionRequest->price_difference_cents < 0)
                                                            <strong class="text-[#007D49]">-{{ money(abs($item->activeSubstitutionRequest->price_difference_cents)) }}</strong> (Customer gets refund)
                                                        @else
                                                            <strong class="text-[#686868]">No price difference</strong>
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- Step 1: Call Customer First -->
                                                <div class="space-y-1.5 pt-1">
                                                    <div class="flex items-center justify-between text-[11px]">
                                                        <span class="font-bold text-[#8A5800]">1. Phone Call Check:</span>
                                                        @if ($selectedOrder->contact_phone)
                                                            <a href="tel:{{ $selectedOrder->contact_phone }}" class="text-[#DF2600] font-black hover:underline inline-flex items-center gap-1">
                                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                                </svg>
                                                                {{ $selectedOrder->contact_phone }}
                                                            </a>
                                                        @else
                                                            <span class="text-[#686868]">No phone recorded</span>
                                                        @endif
                                                    </div>
                                                    <div class="flex gap-2">
                                                        <button type="button"
                                                                wire:click="logCall({{ $item->activeSubstitutionRequest->id }}, true)"
                                                                class="flex-1 py-1.5 px-2 rounded-lg bg-white border border-[#FFE8A3] text-[11px] font-bold text-[#007D49] hover:bg-[#DAF4E7] transition-colors">
                                                            ✓ Log: Reached
                                                        </button>
                                                        <button type="button"
                                                                wire:click="logCall({{ $item->activeSubstitutionRequest->id }}, false)"
                                                                class="flex-1 py-1.5 px-2 rounded-lg bg-white border border-[#FFE8A3] text-[11px] font-bold text-[#686868] hover:bg-[#F6F6F6] transition-colors">
                                                            ✗ Log: No Answer
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Step 2: Record Phone Decision if Reached -->
                                                <div class="pt-2 border-t border-[#FFE8A3]">
                                                    <span class="block text-[11px] font-bold text-[#8A5800] mb-1.5">2. Record Customer Phone Decision:</span>
                                                    <div class="flex gap-2">
                                                        @if ($item->activeSubstitutionRequest->substitute)
                                                            <button type="button"
                                                                    wire:click="recordCallDecision({{ $item->activeSubstitutionRequest->id }}, 'approve')"
                                                                    class="flex-1 py-1.5 px-2 rounded-lg bg-[#007D49] text-white text-[11px] font-extrabold hover:opacity-95 shadow-xs">
                                                                Customer Approved
                                                            </button>
                                                        @endif
                                                        <button type="button"
                                                                wire:click="recordCallDecision({{ $item->activeSubstitutionRequest->id }}, 'decline')"
                                                                class="flex-1 py-1.5 px-2 rounded-lg bg-white border border-[#D58700] text-[#8A5800] text-[11px] font-extrabold hover:bg-[#FFF0EC]">
                                                            Customer Declined (Refund)
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Step 3: Send Written Notice if No Answer -->
                                                <div class="pt-2 border-t border-[#FFE8A3] flex items-center justify-between gap-2">
                                                    @if (! $item->activeSubstitutionRequest->isAwaitingReply())
                                                        <button type="button"
                                                                wire:click="sendCustomerMessage({{ $item->activeSubstitutionRequest->id }})"
                                                                class="w-full py-2 px-3 rounded-xl bg-[#DF2600] text-white text-xs font-extrabold hover:bg-[#C22200] shadow-xs flex items-center justify-center gap-1.5">
                                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                            </svg>
                                                            Send Email Notice (Start 15-Min Timer)
                                                        </button>
                                                    @else
                                                        <span class="text-[11px] text-[#8A5800] truncate">Email sent to {{ $selectedOrder->contact_email }}</span>
                                                        <button type="button"
                                                                wire:click="sendCustomerMessage({{ $item->activeSubstitutionRequest->id }})"
                                                                class="text-[11px] font-bold text-[#DF2600] hover:underline shrink-0">
                                                            Resend
                                                        </button>
                                                    @endif
                                                </div>

                                                <!-- Cancel / Revert -->
                                                <div class="text-right pt-1">
                                                    <button type="button"
                                                            wire:click="cancelSubstitution({{ $item->activeSubstitutionRequest->id }})"
                                                            class="text-[10px] text-[#686868] hover:text-[#111111] hover:underline">
                                                        Cancel request (item found on shelf)
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Payment & Price Breakdown -->
                        <div class="p-4 sm:p-5 rounded-2xl bg-[#FAFAFA] border border-[#EEEEEE] space-y-2 text-sm">
                            <div class="flex justify-between text-[#686868]">
                                <span>Subtotal</span>
                                <span>{{ money($selectedOrder->subtotal_cents) }}</span>
                            </div>
                            @if ($selectedOrder->discount_cents > 0)
                                <div class="flex justify-between text-[#DF2600]">
                                    <span>Discount ({{ $selectedOrder->coupon_code ?? 'Promo' }})</span>
                                    <span>-{{ money($selectedOrder->discount_cents) }}</span>
                                </div>
                            @endif
                            @if ($selectedOrder->delivery_fee_cents > 0)
                                <div class="flex justify-between text-[#686868]">
                                    <span>Delivery Fee</span>
                                    <span>{{ money($selectedOrder->delivery_fee_cents) }}</span>
                                </div>
                            @endif
                            <div class="pt-2 border-t border-[#EEEEEE] flex justify-between font-extrabold text-base text-[#111111]">
                                <span>Grand Total</span>
                                <span>{{ money($selectedOrder->total_cents) }}</span>
                            </div>
                            <div class="pt-2 text-xs text-[#686868] flex items-center justify-between">
                                <span>Payment Method:</span>
                                <strong class="text-[#111111] capitalize">{{ str_replace('_', ' ', $selectedOrder->payment_method) }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Drawer Footer Actions -->
                    <div class="p-4 sm:p-6 border-t border-[#EEEEEE] bg-[#FAFAFA] space-y-3 shrink-0">
                        @if ($selectedOrder->hasPendingSubstitutions())
                            <div class="p-3 rounded-xl bg-[#FFF9EA] border border-[#FFE8A3] text-xs font-bold text-[#8A5800] flex items-start gap-2">
                                <svg class="w-4 h-4 text-[#D58700] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span>1+ substitution request is pending. Advancing to Ready or Completed will automatically refund unanswered substitutions under the store policy.</span>
                            </div>
                        @endif

                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">

                            @if ($selectedOrder->isAwaitingETransfer())
                                <div class="w-full space-y-2">
                                    <p class="text-xs text-[#686868]">
                                        Check the store’s bank account for an Interac e-Transfer of <strong class="text-[#111111]">{{ money($selectedOrder->total_cents) }}</strong>
                                        with message <strong class="text-[#111111]">{{ $selectedOrder->number }}</strong>, sent to {{ config('payments.etransfer.email') }}.
                                    </p>
                                    <div class="flex flex-col sm:flex-row gap-2">
                                        <button type="button"
                                                wire:click="confirmETransfer({{ $selectedOrder->id }})"
                                                wire:confirm="Confirm you received {{ money($selectedOrder->total_cents) }} by e-Transfer for #{{ $selectedOrder->number }}?"
                                                style="background-color: #007D49;"
                                                class="flex-1 py-3 px-4 rounded-xl text-white font-extrabold text-sm hover:opacity-90 transition-opacity shadow-xs text-center active:scale-98">
                                            Confirm e-Transfer Received
                                        </button>
                                        <button type="button"
                                                wire:click="cancelUnpaidETransfer({{ $selectedOrder->id }})"
                                                wire:confirm="Cancel #{{ $selectedOrder->number }} because the e-Transfer never arrived?"
                                                class="py-3 px-4 rounded-xl border border-[#EEEEEE] bg-white text-[#DF2600] font-bold text-sm hover:bg-[#FFF0EC] transition-colors text-center">
                                            Cancel (Not Paid)
                                        </button>
                                    </div>
                                </div>
                            @elseif ($selectedOrder->status === \App\Models\Order::STATUS_UNCOLLECTED)
                                <button type="button"
                                        wire:click="openRescheduleModal({{ $selectedOrder->id }})"
                                        style="background-color: #0055D4;"
                                        class="flex-1 py-3 px-4 rounded-xl text-white font-extrabold text-sm hover:opacity-90 transition-opacity shadow-xs text-center active:scale-98">
                                    Reschedule Pickup
                                </button>
                                <button type="button"
                                        wire:click="markAsCompleted({{ $selectedOrder->id }})"
                                        style="background-color: #007D49;"
                                        class="py-3 px-4 rounded-xl text-white font-extrabold text-sm hover:opacity-90 transition-opacity shadow-xs text-center active:scale-98">
                                    Hand Over (Collected)
                                </button>
                                <button type="button"
                                        wire:click="openCancelModal({{ $selectedOrder->id }})"
                                        class="py-3 px-4 rounded-xl border border-[#EEEEEE] bg-white text-[#DF2600] font-bold text-sm hover:bg-[#FFF0EC] transition-colors text-center">
                                    Cancel & Policy Refund
                                </button>
                            @elseif ($selectedOrder->status !== \App\Models\Order::STATUS_CANCELLED && $selectedOrder->status !== \App\Models\Order::STATUS_REFUNDED && $selectedOrder->fulfilment_status !== \App\Models\Order::FULFILMENT_COMPLETED)
                                @if ($selectedOrder->fulfilment_status === null)
                                    <button type="button"
                                            wire:click="startPreparing({{ $selectedOrder->id }})"
                                            style="background-color: #DF2600;"
                                            class="flex-1 py-3 px-4 rounded-xl text-white font-extrabold text-sm hover:opacity-90 transition-opacity shadow-xs text-center active:scale-98">
                                        Start Preparing Order
                                    </button>
                                    <button type="button"
                                            wire:click="markAsReady({{ $selectedOrder->id }})"
                                            style="background-color: #0055D4;"
                                            class="py-3 px-4 rounded-xl text-white font-extrabold text-sm hover:opacity-90 transition-opacity shadow-xs text-center active:scale-98">
                                        Mark Ready
                                    </button>
                                @elseif ($selectedOrder->fulfilment_status === \App\Models\Order::FULFILMENT_PREPARING)
                                    <button type="button"
                                            wire:click="markAsReady({{ $selectedOrder->id }})"
                                            style="background-color: #0055D4;"
                                            class="flex-1 py-3 px-4 rounded-xl text-white font-extrabold text-sm hover:opacity-90 transition-opacity shadow-xs text-center active:scale-98">
                                        Mark Ready for {{ $selectedOrder->fulfilment === 'pickup' ? 'Collection' : 'Delivery' }}
                                    </button>
                                    <button type="button"
                                            wire:click="markAsCompleted({{ $selectedOrder->id }})"
                                            style="background-color: #007D49;"
                                            class="py-3 px-4 rounded-xl text-white font-extrabold text-sm hover:opacity-90 transition-opacity shadow-xs text-center active:scale-98">
                                        {{ $selectedOrder->fulfilment === 'pickup' ? 'Hand Over (Collected)' : 'Mark Delivered' }}
                                    </button>
                                @elseif ($selectedOrder->fulfilment_status === \App\Models\Order::FULFILMENT_READY)
                                    <button type="button"
                                            wire:click="markAsCompleted({{ $selectedOrder->id }})"
                                            style="background-color: #007D49;"
                                            class="flex-1 py-3 px-4 rounded-xl text-white font-extrabold text-sm hover:opacity-90 transition-opacity shadow-xs text-center active:scale-98">
                                        {{ $selectedOrder->fulfilment === 'pickup' ? 'Complete & Hand Over' : 'Mark Delivered' }}
                                    </button>
                                    @if ($selectedOrder->fulfilment === 'pickup')
                                        <button type="button"
                                                wire:click="markAsUncollected({{ $selectedOrder->id }})"
                                                class="py-3 px-4 rounded-xl border border-[#DF2600]/30 bg-[#FFF0EC] text-[#DF2600] font-bold text-sm hover:bg-[#FFE0D8] transition-colors text-center active:scale-98">
                                            Mark as Uncollected
                                        </button>
                                    @endif
                                @endif

                                <button type="button"
                                        wire:click="openCancelModal({{ $selectedOrder->id }})"
                                        class="py-3 px-4 rounded-xl border border-[#EEEEEE] bg-white text-[#DF2600] font-bold text-sm hover:bg-[#FFF0EC] transition-colors text-center">
                                    Cancel
                                </button>
                            @else
                                <div class="flex-1 py-3 px-4 rounded-xl bg-[#DAF4E7] text-[#007D49] font-extrabold text-sm text-center">
                                    Order {{ $selectedOrder->status === \App\Models\Order::STATUS_CANCELLED ? 'Cancelled' : ($selectedOrder->status === \App\Models\Order::STATUS_REFUNDED ? 'Refunded' : 'Completed') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Cancel & Refund Confirmation Modal -->
    @if ($showCancelModal)
        <div class="fixed inset-0 z-60 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 transition-opacity" wire:click="$set('showCancelModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full max-w-sm sm:max-w-md p-5 sm:p-6 space-y-4" style="background-color: #ffffff;">
                    <div class="flex items-center gap-3 text-[#DF2600]">
                        <div class="w-10 h-10 rounded-full bg-[#FFF0EC] flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3 class="text-base sm:text-lg font-extrabold text-[#111111]" id="modal-title">
                            Cancel Order #{{ $selectedOrder?->number }}
                        </h3>
                    </div>

                    <p class="text-xs sm:text-sm text-[#686868]">
                        This will cancel order #{{ $selectedOrder?->number }} and process any eligible policy refund via the Single Refund Door.
                    </p>

                    <!-- Reason -->
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-[#111111] uppercase tracking-wider">Cancellation Reason</label>
                        <select wire:model="cancelReason" class="w-full px-3 py-2 rounded-xl border border-[#EEEEEE] text-xs font-medium text-[#111111] focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20">
                            <option value="not_collected">Customer did not collect order (Grace period expired)</option>
                            <option value="customer_request">Customer requested cancellation</option>
                            <option value="out_of_stock">Store unable to fulfill / Item unavailable</option>
                            <option value="other">Other reason</option>
                        </select>
                    </div>

                    @if ($selectedOrder && ($selectedOrder->payment_id || $selectedOrder->payment_method !== 'pay_at_pickup') && $selectedOrder->activeRefundableCents() > 0)
                        <!-- Policy Refund Selector -->
                        <div class="space-y-2 p-3.5 rounded-xl bg-[#FAFAFA] border border-[#EEEEEE]">
                            <label class="block text-xs font-bold text-[#111111] uppercase tracking-wider">
                                Policy Refund Percentage
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <button type="button"
                                        wire:click="$set('cancelRefundPercent', 100)"
                                        class="py-2 px-2 rounded-lg border text-xs font-bold transition-all {{ $cancelRefundPercent === 100 ? 'border-[#007D49] bg-[#DAF4E7] text-[#007D49]' : 'border-[#EEEEEE] bg-white text-[#111111]' }}">
                                    100% Full
                                </button>
                                <button type="button"
                                        wire:click="$set('cancelRefundPercent', 50)"
                                        class="py-2 px-2 rounded-lg border text-xs font-bold transition-all {{ $cancelRefundPercent === 50 ? 'border-[#D58700] bg-[#FFF9EA] text-[#D58700]' : 'border-[#EEEEEE] bg-white text-[#111111]' }}">
                                    50% Restock
                                </button>
                                <button type="button"
                                        wire:click="$set('cancelRefundPercent', 0)"
                                        class="py-2 px-2 rounded-lg border text-xs font-bold transition-all {{ $cancelRefundPercent === 0 ? 'border-[#DF2600] bg-[#FFF0EC] text-[#DF2600]' : 'border-[#EEEEEE] bg-white text-[#111111]' }}">
                                    0% None
                                </button>
                            </div>
                            <div class="text-[11px] text-[#686868]">
                                Refund to issue:
                                <strong class="text-[#111111]">
                                    {{ money((int) intdiv($selectedOrder->total_cents * $cancelRefundPercent, 100)) }}
                                </strong>
                                of {{ money($selectedOrder->total_cents) }} paid.
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#EEEEEE]">
                        <button type="button"
                                wire:click="$set('showCancelModal', false)"
                                class="px-4 py-2.5 rounded-xl border border-[#EEEEEE] text-xs sm:text-sm font-bold text-[#111111] hover:bg-[#FAFAFA]">
                            Keep Order
                        </button>
                        <button type="button"
                                wire:click="confirmCancel"
                                class="px-4 py-2.5 rounded-xl bg-[#DF2600] text-white text-xs sm:text-sm font-bold hover:bg-[#C22200]">
                            Confirm Cancellation & Refund
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Reschedule Modal (Part E6b) -->
    @if ($showRescheduleModal && $selectedOrder)
        <div class="fixed inset-0 z-60 overflow-y-auto" aria-labelledby="reschedule-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 transition-opacity" wire:click="$set('showRescheduleModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full max-w-sm sm:max-w-md p-5 sm:p-6 space-y-4" style="background-color: #ffffff;">
                    <div class="flex items-center justify-between border-b border-[#EEEEEE] pb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-[#0055D4]/10 text-[#0055D4] flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h3 class="text-base font-extrabold text-[#111111]" id="reschedule-modal-title">
                                Reschedule Order #{{ $selectedOrder->number }}
                            </h3>
                        </div>
                        <button type="button" wire:click="$set('showRescheduleModal', false)" class="text-[#686868] hover:text-[#111111] text-xs font-bold">
                            ✕
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-[#111111] uppercase tracking-wider mb-2">
                                1. Select New Date
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                @foreach ($rescheduleDays as $day)
                                    @php
                                        $dStr = $day->toDateString();
                                        $sel = $rescheduleDate === $dStr;
                                    @endphp
                                    <button type="button"
                                            wire:click="$set('rescheduleDate', '{{ $dStr }}')"
                                            class="p-2.5 rounded-xl border text-left transition-all {{ $sel ? 'border-[#0055D4] bg-[#0055D4]/5' : 'border-[#EEEEEE] bg-white hover:border-[#CCCCCC]' }}">
                                        <div class="text-[10px] font-bold uppercase {{ $sel ? 'text-[#0055D4]' : 'text-[#686868]' }}">
                                            {{ $day->isToday() ? 'Today' : ($day->isTomorrow() ? 'Tmrw' : $day->format('D')) }}
                                        </div>
                                        <div class="text-xs font-extrabold text-[#111111] mt-0.5">
                                            {{ $day->format('M j') }}
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-[#111111] uppercase tracking-wider mb-2">
                                2. Select Time Window
                            </label>
                            @if ($rescheduleSlots->isEmpty())
                                <div class="p-3 rounded-xl bg-[#FAFAFA] border border-[#EEEEEE] text-center text-xs text-[#686868]">
                                    No slots available for this date.
                                </div>
                            @else
                                <div class="grid grid-cols-3 sm:grid-cols-4 gap-1.5 max-h-48 overflow-y-auto pr-1">
                                    @foreach ($rescheduleSlots as $slot)
                                        @php
                                            $sStr = $slot->format('H:i');
                                            $sel = $rescheduleSlot === $sStr;
                                        @endphp
                                        <button type="button"
                                                wire:click="$set('rescheduleSlot', '{{ $sStr }}')"
                                                class="py-2 px-1.5 rounded-lg border text-xs font-bold text-center transition-all {{ $sel ? 'border-[#0055D4] bg-[#0055D4] text-white' : 'border-[#EEEEEE] bg-white text-[#111111] hover:border-[#CCCCCC]' }}">
                                            {{ $slot->format('g:i A') }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#EEEEEE]">
                        <button type="button"
                                wire:click="$set('showRescheduleModal', false)"
                                class="px-4 py-2.5 rounded-xl border border-[#EEEEEE] text-xs sm:text-sm font-bold text-[#111111] hover:bg-[#FAFAFA]">
                            Cancel
                        </button>
                        <button type="button"
                                wire:click="confirmReschedule"
                                style="background-color: #0055D4;"
                                class="px-4 py-2.5 rounded-xl text-white text-xs sm:text-sm font-bold hover:opacity-90 transition-opacity">
                            Save Rescheduled Time
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Substitution Proposal Modal (Part E9) -->
    @if ($showSubstitutionModal && $substitutionItemId)
        @php
            $modalItem = \App\Models\OrderItem::with('product')->find($substitutionItemId);
        @endphp
        @if ($modalItem)
            <div class="fixed inset-0 z-60 overflow-y-auto" aria-labelledby="modal-sub-title" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-black/50 transition-opacity" wire:click="closeSubstitutionModal"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div class="relative z-10 inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all my-4 sm:my-8 sm:align-middle sm:max-w-xl w-full p-5 sm:p-7 space-y-5" style="background-color: #ffffff;">
                        <div class="flex items-start justify-between gap-3 border-b border-[#EEEEEE] pb-4">
                            <div>
                                <span class="text-[11px] font-black uppercase tracking-wider text-[#DF2600]">Store Picking Desk</span>
                                <h3 class="text-lg sm:text-xl font-black text-[#111111]" id="modal-sub-title">
                                    Item Unavailable: {{ $modalItem->name }}
                                </h3>
                                <p class="text-xs text-[#686868] mt-0.5">
                                    Under store policy, we phone the customer first. If unavailable, an automated 15-minute confirmation notice will be sent.
                                </p>
                            </div>
                            <button type="button" wire:click="closeSubstitutionModal" class="p-1 rounded-lg text-[#686868] hover:bg-[#FAFAFA]">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Current Item Summary -->
                        <div class="p-3.5 rounded-2xl bg-[#FFF0EC]/50 border border-[#DF2600]/20 flex items-center justify-between text-xs sm:text-sm">
                            <div>
                                <span class="text-[11px] font-bold text-[#DF2600] uppercase tracking-wider block">Out of Stock Item</span>
                                <span class="font-extrabold text-[#111111]">{{ $modalItem->name }} {{ $modalItem->variation_name ? "({$modalItem->variation_name})" : '' }}</span>
                                <span class="text-[#686868] block mt-0.5">Qty: {{ $modalItem->quantity }} × {{ money($modalItem->unit_price_cents) }}</span>
                            </div>
                            <div class="font-black text-[#111111] text-right">
                                {{ money($modalItem->line_total_cents) }}
                            </div>
                        </div>

                        <!-- Choose Replacement Product -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold uppercase tracking-wider text-[#111111]">
                                    Select Replacement Option
                                </label>
                                <span class="text-[11px] text-[#686868]">In-stock catalogue matches</span>
                            </div>

                            <input type="text"
                                   wire:model.live.debounce.300ms="substituteSearch"
                                   placeholder="Search alternative products or sizes..."
                                   class="w-full px-3.5 py-2 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs text-[#111111] placeholder:text-[#686868]/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">

                            <div class="max-h-56 overflow-y-auto space-y-2 pr-1 border border-[#EEEEEE] rounded-2xl p-2 bg-[#FAFAFA]/50">
                                <!-- Option: No Substitute / Direct Refund -->
                                <label class="p-3 rounded-xl border transition-all cursor-pointer flex items-center justify-between gap-3 {{ $selectedSubstituteVariationId === null ? 'bg-white border-[#DF2600] ring-1 ring-[#DF2600]' : 'bg-white/80 border-[#EEEEEE] hover:border-[#DF2600]/40' }}">
                                    <div class="flex items-center gap-2.5">
                                        <input type="radio"
                                               name="substitute_variation"
                                               value=""
                                               wire:click="$set('selectedSubstituteVariationId', null)"
                                               @checked($selectedSubstituteVariationId === null)
                                               class="w-4 h-4 text-[#DF2600] focus:ring-[#DF2600] border-[#EEEEEE]">
                                        <div>
                                            <span class="font-bold text-xs text-[#111111] block">No Substitute (Refund Item Only)</span>
                                            <span class="text-[11px] text-[#686868]">Customer will be refunded {{ money($modalItem->line_total_cents) }}</span>
                                        </div>
                                    </div>
                                    <span class="text-[11px] font-bold text-[#007D49] bg-[#DAF4E7] px-2 py-0.5 rounded-md">
                                        Full Refund
                                    </span>
                                </label>

                                <!-- Available Candidate Variations -->
                                @forelse ($availableSubstitutes as $candidate)
                                    @php
                                        $candDiff = ($candidate->price_cents * $modalItem->quantity) - $modalItem->line_total_cents;
                                    @endphp
                                    <label class="p-3 rounded-xl border transition-all cursor-pointer flex items-center justify-between gap-3 {{ $selectedSubstituteVariationId === $candidate->id ? 'bg-white border-[#DF2600] ring-1 ring-[#DF2600]' : 'bg-white/80 border-[#EEEEEE] hover:border-[#DF2600]/40' }}">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <input type="radio"
                                                   name="substitute_variation"
                                                   value="{{ $candidate->id }}"
                                                   wire:click="$set('selectedSubstituteVariationId', {{ $candidate->id }})"
                                                   @checked($selectedSubstituteVariationId === $candidate->id)
                                                   class="w-4 h-4 text-[#DF2600] focus:ring-[#DF2600] border-[#EEEEEE] shrink-0">
                                            <div class="min-w-0">
                                                <span class="font-bold text-xs text-[#111111] truncate block">{{ $candidate->product->name }}</span>
                                                <span class="text-[11px] text-[#686868]">{{ $candidate->name }} · {{ money($candidate->price_cents) }} / unit</span>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="font-extrabold text-xs text-[#111111] block">{{ money($candidate->price_cents * $modalItem->quantity) }}</span>
                                            <span class="text-[10px] font-bold {{ $candDiff > 0 ? 'text-[#DF2600]' : ($candDiff < 0 ? 'text-[#007D49]' : 'text-[#686868]') }}">
                                                {{ $candDiff > 0 ? '+' . money($candDiff) : ($candDiff < 0 ? '-' . money(abs($candDiff)) : 'Same price') }}
                                            </span>
                                        </div>
                                    </label>
                                @empty
                                    <div class="p-4 text-center text-xs text-[#686868]">
                                        No alternative variations found in this category. You can choose "No Substitute (Refund Item Only)" or search by name above.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Optional Staff Note -->
                        <div>
                            <label class="block text-xs font-bold text-[#111111] mb-1">
                                Staff Note for Customer (Optional)
                            </label>
                            <input type="text"
                                   wire:model="substitutionNote"
                                   placeholder="e.g. Out of 500g bags; offering fresh 1kg size at standard rate."
                                   class="w-full px-3.5 py-2 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs text-[#111111] placeholder:text-[#686868]/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#EEEEEE]">
                            <button type="button"
                                    wire:click="closeSubstitutionModal"
                                    class="px-4 py-2.5 rounded-xl border border-[#EEEEEE] text-xs sm:text-sm font-bold text-[#111111] hover:bg-[#FAFAFA]">
                                Cancel
                            </button>
                            <button type="button"
                                    wire:click="createSubstitution"
                                    style="background-color: #DF2600;"
                                    class="px-5 py-2.5 rounded-xl text-white text-xs sm:text-sm font-extrabold hover:opacity-95 shadow-md">
                                Initiate Substitution (Call Customer)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>

