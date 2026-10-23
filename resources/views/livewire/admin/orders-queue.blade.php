<div class="space-y-8" wire:poll.30s>
    <!-- Header with Live Indicator -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-extrabold text-[#111111] tracking-tight">Orders & Fulfillment Queue</h2>
            <p class="mt-1 text-sm text-[#686868]">
                Real-time store orders for collection and local delivery. Track preparation, notify customers, and record handoffs.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#DAF4E7] text-xs font-bold text-[#007D49]">
                <span class="w-2 h-2 rounded-full bg-[#007D49] animate-pulse"></span>
                Live Queue Auto-Refresh
            </span>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="p-4 bg-[#DAF4E7] border border-[#007D49]/20 rounded-2xl flex items-center justify-between text-[#007D49] text-sm font-semibold">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-xs font-bold opacity-70 hover:opacity-100">Dismiss</button>
        </div>
    @endif

    @if (session()->has('warning'))
        <div class="p-4 bg-[#FFF9EA] border border-[#D58700]/20 rounded-2xl flex items-center justify-between text-[#D58700] text-sm font-semibold">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>{{ session('warning') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-xs font-bold opacity-70 hover:opacity-100">Dismiss</button>
        </div>
    @endif

    <!-- Quick Stat KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs cursor-pointer hover:border-[#DF2600]/40 transition-colors {{ $statusFilter === 'active' ? 'ring-2 ring-[#DF2600]' : '' }}"
             wire:click="$set('statusFilter', 'active')">
            <span class="text-xs font-bold uppercase tracking-wider text-[#686868]">Active Orders</span>
            <div class="mt-2 text-2xl font-extrabold text-[#111111]">{{ number_format($counts['active']) }}</div>
            <span class="text-[11px] text-[#686868]">Need fulfillment</span>
        </div>

        <div class="p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs cursor-pointer hover:border-[#DF2600]/40 transition-colors {{ $statusFilter === 'new' ? 'ring-2 ring-[#DF2600]' : '' }}"
             wire:click="$set('statusFilter', 'new')">
            <span class="text-xs font-bold uppercase tracking-wider text-[#DF2600]">New / Unprepared</span>
            <div class="mt-2 text-2xl font-extrabold text-[#DF2600]">{{ number_format($counts['new']) }}</div>
            <span class="text-[11px] text-[#686868]">Action required</span>
        </div>

        <div class="p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs cursor-pointer hover:border-[#D58700]/40 transition-colors {{ $statusFilter === 'preparing' ? 'ring-2 ring-[#D58700]' : '' }}"
             wire:click="$set('statusFilter', 'preparing')">
            <span class="text-xs font-bold uppercase tracking-wider text-[#D58700]">In Preparation</span>
            <div class="mt-2 text-2xl font-extrabold text-[#D58700]">{{ number_format($counts['preparing']) }}</div>
            <span class="text-[11px] text-[#686868]">Packing in store</span>
        </div>

        <div class="p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs cursor-pointer hover:border-[#0055D4]/40 transition-colors {{ $statusFilter === 'ready' ? 'ring-2 ring-[#0055D4]' : '' }}"
             wire:click="$set('statusFilter', 'ready')">
            <span class="text-xs font-bold uppercase tracking-wider text-[#0055D4]">Ready for Handover</span>
            <div class="mt-2 text-2xl font-extrabold text-[#0055D4]">{{ number_format($counts['ready']) }}</div>
            <span class="text-[11px] text-[#686868]">Customer notified</span>
        </div>

        <div class="p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-[#007D49]">Completed Today</span>
            <div class="mt-2 text-2xl font-extrabold text-[#007D49]">{{ number_format($counts['completed_today']) }}</div>
            <span class="text-[11px] text-[#686868]">Fulfillment closed</span>
        </div>
    </div>

    <!-- Filters and Search Bar -->
    <div class="p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs space-y-4">
        <!-- Search and Dropdowns -->
        <div class="flex flex-col md:flex-row gap-3">
            <div class="flex-1 relative">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search by order #, customer name, phone, or email..."
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] placeholder-[#686868] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                <svg class="w-4 h-4 text-[#686868] absolute left-3.5 top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                @if ($search)
                    <button type="button" wire:click="$set('search', '')" class="absolute right-3.5 top-3.5 text-xs text-[#686868] hover:text-[#111111]">Clear</button>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <select wire:model.live="fulfilmentFilter" class="px-3 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-xs font-semibold text-[#111111] focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20">
                    <option value="">All Fulfillment (Pickup & Delivery)</option>
                    <option value="pickup">In-Store Pickup Only</option>
                    <option value="delivery">Local Delivery Only</option>
                </select>

                <select wire:model.live="paymentFilter" class="px-3 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-xs font-semibold text-[#111111] focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20">
                    <option value="">All Payments</option>
                    <option value="paid">Paid Online</option>
                    <option value="unpaid">Pay at Counter</option>
                </select>
            </div>
        </div>

        <!-- Status Filter Tabs -->
        <div class="flex items-center gap-1 overflow-x-auto pt-2 border-t border-[#EEEEEE] text-xs font-bold text-[#686868]">
            <button type="button"
                    wire:click="$set('statusFilter', 'active')"
                    class="px-3.5 py-1.5 rounded-lg transition-colors {{ $statusFilter === 'active' ? 'bg-[#111111] text-white' : 'hover:bg-[#F6F6F6] text-[#686868]' }}">
                Active Queue ({{ $counts['active'] }})
            </button>

            <button type="button"
                    wire:click="$set('statusFilter', 'new')"
                    class="px-3.5 py-1.5 rounded-lg transition-colors {{ $statusFilter === 'new' ? 'bg-[#DF2600] text-white' : 'hover:bg-[#F6F6F6] text-[#686868]' }}">
                New ({{ $counts['new'] }})
            </button>

            <button type="button"
                    wire:click="$set('statusFilter', 'preparing')"
                    class="px-3.5 py-1.5 rounded-lg transition-colors {{ $statusFilter === 'preparing' ? 'bg-[#D58700] text-white' : 'hover:bg-[#F6F6F6] text-[#686868]' }}">
                Preparing ({{ $counts['preparing'] }})
            </button>

            <button type="button"
                    wire:click="$set('statusFilter', 'ready')"
                    class="px-3.5 py-1.5 rounded-lg transition-colors {{ $statusFilter === 'ready' ? 'bg-[#0055D4] text-white' : 'hover:bg-[#F6F6F6] text-[#686868]' }}">
                Ready ({{ $counts['ready'] }})
            </button>

            <button type="button"
                    wire:click="$set('statusFilter', 'completed')"
                    class="px-3.5 py-1.5 rounded-lg transition-colors {{ $statusFilter === 'completed' ? 'bg-[#007D49] text-white' : 'hover:bg-[#F6F6F6] text-[#686868]' }}">
                Completed
            </button>

            <button type="button"
                    wire:click="$set('statusFilter', 'cancelled')"
                    class="px-3.5 py-1.5 rounded-lg transition-colors {{ $statusFilter === 'cancelled' ? 'bg-[#686868] text-white' : 'hover:bg-[#F6F6F6] text-[#686868]' }}">
                Cancelled
            </button>

            <button type="button"
                    wire:click="$set('statusFilter', 'all')"
                    class="px-3.5 py-1.5 rounded-lg transition-colors {{ $statusFilter === 'all' ? 'bg-[#111111] text-white' : 'hover:bg-[#F6F6F6] text-[#686868]' }}">
                All History
            </button>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-2xl border border-[#EEEEEE] shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-[#111111]">
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
                                    @if ($order->status === \App\Models\Order::STATUS_PAID)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#DAF4E7] text-[#007D49]">
                                            Paid
                                        </span>
                                    @elseif ($order->status === \App\Models\Order::STATUS_CANCELLED)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#F6F6F6] text-[#686868]">
                                            Cancelled
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
                                @if ($order->status === \App\Models\Order::STATUS_CANCELLED)
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
                                    @if ($order->status !== \App\Models\Order::STATUS_CANCELLED && $order->fulfilment_status !== \App\Models\Order::FULFILMENT_COMPLETED)
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

    <!-- Order Detail Slide-Over Drawer -->
    @if ($selectedOrder)
        <div class="fixed inset-0 z-50 overflow-hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/40 backdrop-blur-xs transition-opacity" wire:click="closeDrawer"></div>

            <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
                <div class="w-screen max-w-xl bg-white shadow-2xl flex flex-col justify-between">
                    <!-- Drawer Header -->
                    <div class="p-6 border-b border-[#EEEEEE] flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-3">
                                <h3 class="text-xl font-extrabold text-[#111111]" id="slide-over-title">
                                    Order #{{ $selectedOrder->number }}
                                </h3>
                                @if ($selectedOrder->status === \App\Models\Order::STATUS_PAID)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-[#DAF4E7] text-[#007D49]">
                                        Paid
                                    </span>
                                @elseif ($selectedOrder->status === \App\Models\Order::STATUS_CANCELLED)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-[#F6F6F6] text-[#686868]">
                                        Cancelled
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-[#FFF9EA] text-[#D58700]">
                                        Pay at Counter
                                    </span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-[#686868]">
                                Placed on {{ $selectedOrder->placed_at ? $selectedOrder->placed_at->setTimezone(config('shop.timezone'))->format('M j, Y \a\t g:i A') : $selectedOrder->created_at->format('M j, Y') }}
                            </p>
                        </div>

                        <button type="button" wire:click="closeDrawer" class="text-[#686868] hover:text-[#111111] p-2 rounded-xl hover:bg-[#FAFAFA]">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Drawer Content (Scrollable) -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-6">
                        <!-- Fulfillment Status Tracker Banner -->
                        <div class="p-4 rounded-2xl border border-[#EEEEEE] bg-[#FAFAFA] space-y-3">
                            <span class="text-xs font-bold uppercase tracking-wider text-[#686868]">Current Stage</span>
                            <div class="flex items-center justify-between">
                                <span class="text-base font-extrabold text-[#111111]">
                                    {{ $selectedOrder->statusLabel() }}
                                </span>
                                <span class="text-xs font-semibold text-[#686868]">
                                    Fulfillment: <strong class="text-[#111111] capitalize">{{ $selectedOrder->fulfilment }}</strong>
                                </span>
                            </div>

                            <!-- Progress Track -->
                            <div class="grid grid-cols-4 gap-2 pt-2">
                                @php
                                    $stepIndex = $selectedOrder->trackerStep();
                                @endphp
                                @foreach ($selectedOrder->trackerSteps() as $idx => $stepLabel)
                                    <div class="flex flex-col gap-1">
                                        <div class="h-1.5 rounded-full {{ $idx <= $stepIndex ? 'bg-[#007D49]' : 'bg-[#EEEEEE]' }}"></div>
                                        <span class="text-[10px] leading-tight {{ $idx <= $stepIndex ? 'font-bold text-[#111111]' : 'text-[#686868]' }}">
                                            {{ $stepLabel }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Customer Details & Fulfillment Slot -->
                        <div class="grid grid-cols-2 gap-4">
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
                                    <div class="p-4 flex items-center justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                            @if ($item->image)
                                                <img src="{{ asset($item->image) }}" alt="" class="w-12 h-12 object-cover rounded-xl border border-[#EEEEEE] shrink-0">
                                            @else
                                                <div class="w-12 h-12 rounded-xl bg-[#FAFAFA] border border-[#EEEEEE] flex items-center justify-center text-xs font-bold text-[#686868] shrink-0">
                                                    {{ substr($item->name, 0, 2) }}
                                                </div>
                                            @endif
                                            <div>
                                                <h5 class="font-extrabold text-sm text-[#111111]">{{ $item->name }}</h5>
                                                @if ($item->variation_name)
                                                    <span class="text-xs text-[#686868]">{{ $item->variation_name }}</span>
                                                @endif
                                                <div class="text-xs text-[#686868] mt-0.5">
                                                    Qty: <strong class="text-[#111111]">{{ $item->quantity }}</strong> × {{ money($item->unit_price_cents) }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="font-extrabold text-sm text-[#111111] text-right">
                                            {{ money($item->line_total_cents) }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Payment & Price Breakdown -->
                        <div class="p-5 rounded-2xl bg-[#FAFAFA] border border-[#EEEEEE] space-y-2 text-sm">
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
                    <div class="p-6 border-t border-[#EEEEEE] bg-[#FAFAFA] space-y-3">
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                            @if ($selectedOrder->status !== \App\Models\Order::STATUS_CANCELLED && $selectedOrder->fulfilment_status !== \App\Models\Order::FULFILMENT_COMPLETED)
                                @if ($selectedOrder->fulfilment_status === null)
                                    <button type="button"
                                            wire:click="startPreparing({{ $selectedOrder->id }})"
                                            style="background-color: #DF2600;"
                                            class="flex-1 py-3 px-4 rounded-xl text-white font-extrabold text-sm hover:opacity-90 transition-opacity shadow-xs text-center">
                                        Start Preparing Order
                                    </button>
                                    <button type="button"
                                            wire:click="markAsReady({{ $selectedOrder->id }})"
                                            style="background-color: #0055D4;"
                                            class="py-3 px-4 rounded-xl text-white font-extrabold text-sm hover:opacity-90 transition-opacity shadow-xs text-center">
                                        Mark Ready
                                    </button>
                                @elseif ($selectedOrder->fulfilment_status === \App\Models\Order::FULFILMENT_PREPARING)
                                    <button type="button"
                                            wire:click="markAsReady({{ $selectedOrder->id }})"
                                            style="background-color: #0055D4;"
                                            class="flex-1 py-3 px-4 rounded-xl text-white font-extrabold text-sm hover:opacity-90 transition-opacity shadow-xs text-center">
                                        Mark Ready for {{ $selectedOrder->fulfilment === 'pickup' ? 'Collection' : 'Delivery' }}
                                    </button>
                                    <button type="button"
                                            wire:click="markAsCompleted({{ $selectedOrder->id }})"
                                            style="background-color: #007D49;"
                                            class="py-3 px-4 rounded-xl text-white font-extrabold text-sm hover:opacity-90 transition-opacity shadow-xs text-center">
                                        {{ $selectedOrder->fulfilment === 'pickup' ? 'Hand Over (Collected)' : 'Mark Delivered' }}
                                    </button>
                                @elseif ($selectedOrder->fulfilment_status === \App\Models\Order::FULFILMENT_READY)
                                    <button type="button"
                                            wire:click="markAsCompleted({{ $selectedOrder->id }})"
                                            style="background-color: #007D49;"
                                            class="flex-1 py-3 px-4 rounded-xl text-white font-extrabold text-sm hover:opacity-90 transition-opacity shadow-xs text-center">
                                        {{ $selectedOrder->fulfilment === 'pickup' ? 'Complete & Hand Over (Collected)' : 'Mark Delivered' }}
                                    </button>
                                @endif

                                <button type="button"
                                        wire:click="openCancelModal({{ $selectedOrder->id }})"
                                        class="py-3 px-4 rounded-xl border border-[#EEEEEE] bg-white text-[#DF2600] font-bold text-sm hover:bg-[#FFF0EC] transition-colors">
                                    Cancel
                                </button>
                            @else
                                <div class="flex-1 py-3 px-4 rounded-xl bg-[#DAF4E7] text-[#007D49] font-extrabold text-sm text-center">
                                    Order Completed ({{ $selectedOrder->fulfilment === 'pickup' ? 'Collected by Customer' : 'Delivered' }})
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Cancel Confirmation Modal -->
    @if ($showCancelModal)
        <div class="fixed inset-0 z-60 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/40 backdrop-blur-xs transition-opacity" wire:click="$set('showCancelModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6 space-y-4">
                    <div class="flex items-center gap-3 text-[#DF2600]">
                        <div class="w-10 h-10 rounded-full bg-[#FFF0EC] flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-extrabold text-[#111111]" id="modal-title">
                            Cancel Order #{{ $selectedOrder?->number }}?
                        </h3>
                    </div>

                    <p class="text-sm text-[#686868]">
                        Are you sure you want to cancel this order? This will mark the order as cancelled in the customer's portal and stop fulfillment.
                    </p>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#EEEEEE]">
                        <button type="button"
                                wire:click="$set('showCancelModal', false)"
                                class="px-4 py-2.5 rounded-xl border border-[#EEEEEE] text-sm font-bold text-[#111111] hover:bg-[#FAFAFA]">
                            Keep Order
                        </button>
                        <button type="button"
                                wire:click="confirmCancel"
                                class="px-4 py-2.5 rounded-xl bg-[#DF2600] text-white text-sm font-bold hover:bg-[#C22200]">
                            Yes, Cancel Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
