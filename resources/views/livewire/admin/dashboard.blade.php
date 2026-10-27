<div class="space-y-6 sm:space-y-8">
    <!-- Welcome Header Banner -->
    <div class="bg-white rounded-2xl p-4 sm:p-6 lg:p-8 border border-[#EEEEEE] flex flex-col md:flex-row md:items-center justify-between gap-4 sm:gap-6 shadow-2xs">
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-extrabold uppercase tracking-wider bg-[#FFF0EC] text-[#DF2600]">
                    Operations Overview
                </span>
                <span class="text-xs text-[#686868]">· Saint John, NB Storefront</span>
            </div>
            <h2 class="mt-2 text-xl sm:text-2xl font-black text-[#111111] tracking-tight">
                Welcome back, {{ auth()->user()?->name ?? 'Store Manager' }}
            </h2>
            <p class="mt-1 text-xs text-[#686868] max-w-xl">
                Here is the live operational heartbeat across your store inventory, incoming orders, and kitchen recipes today.
            </p>
        </div>

        <div class="flex items-center gap-2.5 sm:gap-3 shrink-0 flex-wrap">
            <a href="{{ route('admin.orders') }}"
               style="background-color: #DF2600;"
               class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-white font-extrabold text-xs shadow-xs hover:opacity-95 transition-opacity">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <span>Live Orders Queue</span>
                @if ($kpis['active_fulfillment_count'] > 0)
                    <span class="px-1.5 py-0.2 bg-white text-[#DF2600] font-black rounded-full text-[10px]">
                        {{ $kpis['active_fulfillment_count'] }}
                    </span>
                @endif
            </a>

            <a href="{{ route('home') }}"
               target="_blank"
               class="inline-flex items-center justify-center gap-2 px-3.5 py-2.5 rounded-xl bg-[#FAFAFA] border border-[#EEEEEE] text-xs font-bold text-[#111111] hover:bg-[#F6F6F6] transition-colors">
                <svg class="w-4 h-4 text-[#686868]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                <span>View Storefront</span>
            </a>
        </div>
    </div>

    <!-- 5 Live Scorecard KPI Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
        <!-- KPI 1: Today's Gross Sales -->
        <div class="p-3.5 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] space-y-1.5 sm:space-y-2 shadow-2xs">
            <div class="flex items-center justify-between text-xs text-[#686868]">
                <span class="font-extrabold uppercase tracking-wider text-[10px] sm:text-[11px]">Today's Sales</span>
                <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-[#DAF4E7] text-[#007D49] flex items-center justify-center font-bold text-xs sm:text-sm">
                    $
                </span>
            </div>
            <div class="text-xl sm:text-2xl font-black text-[#111111]">
                {{ money($kpis['today_sales_cents']) }}
            </div>
            <div class="text-[11px] sm:text-xs text-[#686868]">
                <span class="font-bold text-[#111111]">{{ $kpis['today_orders_count'] }}</span> {{ \Illuminate\Support\Str::plural('order', $kpis['today_orders_count']) }} today
            </div>
        </div>

        <!-- KPI 2: Active Fulfillment Queue -->
        <div class="p-3.5 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] space-y-1.5 sm:space-y-2 shadow-2xs">
            <div class="flex items-center justify-between text-xs text-[#686868]">
                <span class="font-extrabold uppercase tracking-wider text-[10px] sm:text-[11px]">Backlog</span>
                <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-[#FFF0EC] text-[#DF2600] flex items-center justify-center font-bold">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </span>
            </div>
            <div class="text-xl sm:text-2xl font-black text-[#111111]">
                {{ $kpis['active_fulfillment_count'] }}
            </div>
            <div class="text-[11px] sm:text-xs">
                @if ($kpis['active_fulfillment_count'] > 0)
                    <span class="inline-flex items-center gap-1 font-bold text-[#DF2600]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#DF2600]"></span>
                        Action needed
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 font-bold text-[#007D49]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#007D49]"></span>
                        All fulfilled
                    </span>
                @endif
            </div>
        </div>

        <!-- KPI 3: Uncollected Orders Desk -->
        <a href="{{ route('admin.orders', ['statusFilter' => 'uncollected']) }}"
           class="p-3.5 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] space-y-1.5 sm:space-y-2 shadow-2xs hover:border-[#DF2600]/40 transition-colors block">
            <div class="flex items-center justify-between text-xs text-[#686868]">
                <span class="font-extrabold uppercase tracking-wider text-[10px] sm:text-[11px] text-[#DF2600]">Uncollected</span>
                <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-[#FFF0EC] text-[#DF2600] flex items-center justify-center font-bold">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </span>
            </div>
            <div class="text-xl sm:text-2xl font-black {{ $kpis['uncollected_orders_count'] > 0 ? 'text-[#DF2600]' : 'text-[#111111]' }}">
                {{ $kpis['uncollected_orders_count'] }}
            </div>
            <div class="text-[11px] sm:text-xs">
                @if ($kpis['uncollected_orders_count'] > 0)
                    <span class="font-bold text-[#DF2600]">Needs Review &rarr;</span>
                @else
                    <span class="font-bold text-[#007D49]">None overdue</span>
                @endif
            </div>
        </a>

        <!-- KPI 4: Inventory Alerts -->
        <div class="p-3.5 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] space-y-1.5 sm:space-y-2 shadow-2xs">
            <div class="flex items-center justify-between text-xs text-[#686868]">
                <span class="font-extrabold uppercase tracking-wider text-[10px] sm:text-[11px]">Low Stock</span>
                <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-[#FFF9E6] text-[#B27B00] flex items-center justify-center font-bold">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </span>
            </div>
            <div class="text-xl sm:text-2xl font-black text-[#111111]">
                {{ $kpis['low_stock_count'] }}
            </div>
            <div class="text-[11px] sm:text-xs text-[#686868] truncate">
                @if ($kpis['low_stock_count'] > 0)
                    <span class="text-[#B27B00] font-bold">≤ 5 units left</span>
                @else
                    <span class="text-[#007D49] font-bold">In stock</span>
                @endif
            </div>
        </div>

        <!-- KPI 5: Store Status -->
        <div class="p-3.5 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] space-y-1.5 sm:space-y-2 shadow-2xs">
            <div class="flex items-center justify-between text-xs text-[#686868]">
                <span class="font-extrabold uppercase tracking-wider text-[10px] sm:text-[11px]">Store Status</span>
                <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl {{ $kpis['store_is_open'] ? 'bg-[#DAF4E7] text-[#007D49]' : 'bg-[#FAFAFA] text-[#686868]' }} flex items-center justify-center font-bold">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full {{ $kpis['store_is_open'] ? 'bg-[#007D49]' : 'bg-[#686868]' }} shrink-0"></span>
                <span class="text-sm sm:text-base font-black text-[#111111] truncate">
                    {{ $kpis['store_is_open'] ? 'Open' : 'Closed' }}
                </span>
            </div>
            <div class="text-[10px] sm:text-xs text-[#686868] truncate">
                {{ $kpis['store_status_text'] }}
            </div>
        </div>
    </div>

    <!-- Main Operational Grid (8 cols left, 4 cols right) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8">
        <!-- Left Column: Urgent Orders & Recent Activity (8 cols) -->
        <div class="lg:col-span-8 space-y-6 sm:space-y-8">
            <!-- Urgent Fulfillment Desk -->
            <div class="bg-white rounded-2xl border border-[#EEEEEE] overflow-hidden shadow-2xs">
                <div class="p-4 sm:p-6 border-b border-[#EEEEEE] flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm sm:text-base font-extrabold text-[#111111]">
                            Urgent Orders Requiring Packing
                        </h3>
                        <p class="text-xs text-[#686868]">
                            Oldest unfulfilled orders awaiting merchant preparation and packing slip generation.
                        </p>
                    </div>
                    <a href="{{ route('admin.orders') }}" class="text-xs font-bold text-[#DF2600] hover:underline shrink-0 self-start sm:self-auto">
                        View All Orders &rarr;
                    </a>
                </div>

                @if ($urgentOrders->isNotEmpty())
                    <div class="divide-y divide-[#EEEEEE]">
                        @foreach ($urgentOrders as $order)
                            <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-[#FAFAFA]/70 transition-colors">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-[#FFF0EC] text-[#DF2600] flex items-center justify-center font-bold text-xs shrink-0">
                                        #{{ substr($order->number ?? (string) $order->id, -4) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-sm font-black text-[#111111]">
                                                {{ $order->number ? '#'.$order->number : 'Order #'.$order->id }}
                                            </span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ ($order->fulfilment ?? 'pickup') === 'delivery' ? 'bg-[#FFF9E6] text-[#B27B00]' : 'bg-[#DAF4E7] text-[#007D49]' }}">
                                                {{ ucfirst($order->fulfilment ?? 'pickup') }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-[#686868] mt-0.5 truncate">
                                            <span>{{ $order->contact_name ?? ($order->user?->name ?? 'Guest Shopper') }}</span>
                                            <span class="mx-1">·</span>
                                            <span>{{ $order->items->count() }} {{ \Illuminate\Support\Str::plural('item', $order->items->count()) }}</span>
                                            <span class="mx-1">·</span>
                                            <span class="font-semibold text-[#111111]">{{ money($order->total_cents) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-[#EEEEEE]/50">
                                    <span class="text-[11px] text-[#686868]">
                                        {{ $order->placed_at ? $order->placed_at->diffForHumans() : 'Just now' }}
                                    </span>
                                    <a href="{{ route('admin.orders', ['search' => $order->number ?? $order->id]) }}"
                                       class="px-4 py-2 sm:py-1.5 rounded-xl bg-white border border-[#EEEEEE] text-xs font-bold text-[#111111] hover:border-[#111111] hover:bg-[#F6F6F6] transition-colors shadow-2xs">
                                        Pack Order
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 sm:p-10 text-center">
                        <div class="w-12 h-12 rounded-full bg-[#DAF4E7] text-[#007D49] flex items-center justify-center mx-auto mb-2">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h4 class="text-sm font-bold text-[#111111]">Fulfillment Queue is Clear!</h4>
                        <p class="text-xs text-[#686868] mt-0.5">
                            All incoming customer orders have been packed and handed off.
                        </p>
                    </div>
                @endif
            </div>

            <!-- Recent Store Activity Feed -->
            <div class="bg-white rounded-2xl border border-[#EEEEEE] overflow-hidden shadow-2xs">
                <div class="p-4 sm:p-6 border-b border-[#EEEEEE]">
                    <h3 class="text-sm sm:text-base font-extrabold text-[#111111]">
                        Recent Customer Transactions
                    </h3>
                    <p class="text-xs text-[#686868]">
                        Latest completed and updated checkout orders placed across Saint John, NB.
                    </p>
                </div>

                @if ($recentOrders->isNotEmpty())
                    <div class="overflow-x-auto" style="-webkit-overflow-scrolling: touch;">
                        <table class="w-full text-left text-xs min-w-[500px]">
                            <thead class="bg-[#FAFAFA] text-[11px] font-extrabold uppercase tracking-wider text-[#686868] border-b border-[#EEEEEE]">
                                <tr>
                                    <th class="py-2.5 px-4 sm:px-6">Order</th>
                                    <th class="py-2.5 px-3 sm:px-4">Payment</th>
                                    <th class="py-2.5 px-3 sm:px-4">Status</th>
                                    <th class="py-2.5 px-3 sm:px-4 text-right">Total</th>
                                    <th class="py-2.5 px-4 sm:px-6 text-right">Placed</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#EEEEEE]">
                                @foreach ($recentOrders as $recent)
                                    <tr class="hover:bg-[#FAFAFA]/70 transition-colors">
                                        <td class="py-3 px-4 sm:px-6 font-bold text-[#111111]">
                                            <a href="{{ route('admin.orders', ['search' => $recent->number ?? $recent->id]) }}" class="hover:text-[#DF2600]">
                                                {{ $recent->number ? '#'.$recent->number : 'Order #'.$recent->id }}
                                            </a>
                                        </td>
                                        <td class="py-3 px-3 sm:px-4 font-mono text-[11px] text-[#686868]">
                                            {{ ucfirst(str_replace('_', ' ', $recent->payment_method ?? 'card')) }}
                                        </td>
                                        <td class="py-3 px-3 sm:px-4">
                                            @php
                                                $statusClasses = match ($recent->status) {
                                                    'completed', 'paid' => 'bg-[#DAF4E7] text-[#007D49]',
                                                    'placed' => 'bg-[#FFF0EC] text-[#DF2600]',
                                                    'cancelled' => 'bg-[#FAFAFA] text-[#686868]',
                                                    default => 'bg-[#FFF9E6] text-[#B27B00]',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $statusClasses }}">
                                                {{ $recent->status }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3 sm:px-4 text-right font-black text-[#111111]">
                                            {{ money($recent->total_cents) }}
                                        </td>
                                        <td class="py-3 px-4 sm:px-6 text-right text-[#686868]">
                                            {{ $recent->placed_at ? $recent->placed_at->format('M j, g:i A') : 'N/A' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-8 text-center text-xs text-[#686868]">
                        No orders recorded yet.
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Column: Quick Action Hub & Low Stock Warnings (4 cols) -->
        <div class="lg:col-span-4 space-y-6 sm:space-y-8">
            <!-- 1-Click Quick Action Hub -->
            <div class="bg-white rounded-2xl border border-[#EEEEEE] p-4 sm:p-6 shadow-2xs space-y-4">
                <div>
                    <h3 class="text-sm sm:text-base font-extrabold text-[#111111]">
                        Quick Actions
                    </h3>
                    <p class="text-xs text-[#686868]">
                        Frequent tasks and merchandising shortcuts.
                    </p>
                </div>

                <div class="space-y-2">
                    <a href="{{ route('admin.products') }}"
                       class="flex items-center justify-between p-3 rounded-xl bg-[#FAFAFA] border border-[#EEEEEE] text-xs font-bold text-[#111111] hover:bg-white hover:border-[#DF2600] transition-colors">
                        <span class="flex items-center gap-2.5">
                            <span class="w-7 h-7 rounded-lg bg-white border border-[#EEEEEE] flex items-center justify-center text-[#DF2600] shrink-0">
                                +
                            </span>
                            <span class="truncate">Manage Products &amp; Stock</span>
                        </span>
                        <svg class="w-4 h-4 text-[#686868] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>

                    <a href="{{ route('admin.categories') }}"
                       class="flex items-center justify-between p-3 rounded-xl bg-[#FAFAFA] border border-[#EEEEEE] text-xs font-bold text-[#111111] hover:bg-white hover:border-[#DF2600] transition-colors">
                        <span class="flex items-center gap-2.5">
                            <span class="w-7 h-7 rounded-lg bg-white border border-[#EEEEEE] flex items-center justify-center text-[#DF2600] shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                            </span>
                            <span class="truncate">Departments &amp; Categories</span>
                        </span>
                        <svg class="w-4 h-4 text-[#686868] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>

                    <a href="{{ route('admin.recipes') }}"
                       class="flex items-center justify-between p-3 rounded-xl bg-[#FAFAFA] border border-[#EEEEEE] text-xs font-bold text-[#111111] hover:bg-white hover:border-[#DF2600] transition-colors">
                        <span class="flex items-center gap-2.5">
                            <span class="w-7 h-7 rounded-lg bg-white border border-[#EEEEEE] flex items-center justify-center text-[#DF2600] shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </span>
                            <span class="truncate">Kitchen Recipes &amp; Bundles</span>
                        </span>
                        <svg class="w-4 h-4 text-[#686868] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>

                    <a href="{{ route('admin.hours') }}"
                       class="flex items-center justify-between p-3 rounded-xl bg-[#FAFAFA] border border-[#EEEEEE] text-xs font-bold text-[#111111] hover:bg-white hover:border-[#DF2600] transition-colors">
                        <span class="flex items-center gap-2.5">
                            <span class="w-7 h-7 rounded-lg bg-white border border-[#EEEEEE] flex items-center justify-center text-[#DF2600] shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <span class="truncate">Adjust Store Hours &amp; Closures</span>
                        </span>
                        <svg class="w-4 h-4 text-[#686868] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Depleted & Low Stock SKUs Warning Card -->
            <div class="bg-white rounded-2xl border border-[#EEEEEE] p-4 sm:p-6 shadow-2xs space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm sm:text-base font-extrabold text-[#111111]">
                            Stock Depletion Alerts
                        </h3>
                        <p class="text-xs text-[#686868]">
                            Items with 5 or fewer units in store.
                        </p>
                    </div>
                    @if ($kpis['low_stock_count'] > 0)
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#FFF9E6] text-[#B27B00]">
                            {{ $kpis['low_stock_count'] }} Alerts
                        </span>
                    @endif
                </div>

                @if ($lowStockVariations->isNotEmpty())
                    <div class="divide-y divide-[#EEEEEE]">
                        @foreach ($lowStockVariations as $var)
                            <div class="py-2.5 flex items-center justify-between gap-2">
                                <div class="truncate min-w-0">
                                    <span class="text-xs font-bold text-[#111111] truncate block">
                                        {{ $var->product?->name ?? 'Product SKU' }}
                                    </span>
                                    <span class="text-[10px] text-[#686868] font-mono truncate block">
                                        {{ $var->sku ?? 'No SKU' }} · {{ $var->name ?? 'Standard' }}
                                    </span>
                                </div>

                                <div class="shrink-0 flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ $var->stock_quantity <= 0 ? 'bg-[#FFF0EC] text-[#DF2600]' : 'bg-[#FFF9E6] text-[#B27B00]' }}">
                                        {{ $var->stock_quantity <= 0 ? 'Out of stock' : $var->stock_quantity.' left' }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <a href="{{ route('admin.products', ['stockFilter' => 'low']) }}" class="block text-center text-xs font-bold text-[#DF2600] hover:underline pt-2 border-t border-[#EEEEEE]">
                        View all low stock inventory &rarr;
                    </a>
                @else
                    <div class="p-6 text-center text-xs text-[#007D49] font-bold">
                        ✓ All tracked inventory is above threshold!
                    </div>
                @endif
            </div>

            <!-- Operations System Card -->
            <div class="p-4 rounded-2xl bg-[#FAFAFA] border border-[#EEEEEE] space-y-2 text-xs">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-[#111111]">Square POS Catalog Sync</span>
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-[#007D49]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#007D49]"></span>
                        Ready
                    </span>
                </div>
                <div class="text-[11px] text-[#686868]">
                    Operating Store Currency: <span class="font-bold text-[#111111]">{{ config('shop.currency', 'CAD') }}</span>
                </div>
                <div class="text-[11px] text-[#686868]">
                    Store Timezone: <span class="font-bold text-[#111111]">{{ config('shop.timezone', 'America/Moncton') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
