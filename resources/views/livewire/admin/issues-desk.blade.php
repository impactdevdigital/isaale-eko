<div class="space-y-6 sm:space-y-8" wire:poll.30s>
    <!-- Header with Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-extrabold uppercase tracking-wider bg-[#FFF0EC] text-[#DF2600]">
                    Customer Claims & Issues Desk
                </span>
                <span class="text-xs text-[#686868]">· 24h Policy Response SLA</span>
            </div>
            <h2 class="mt-1 text-xl sm:text-2xl font-black text-[#111111] tracking-tight">
                Order Issues & Returns Management
            </h2>
            <p class="mt-1 text-xs sm:text-sm text-[#686868]">
                Review customer claims for missing, damaged, or spoiled items. Issue refunds, schedule replacements, and manage store returns.
            </p>
        </div>

        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            <button type="button"
                    wire:click="openLogCaseModal"
                    style="background-color: #DF2600;"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-white font-extrabold text-xs shadow-xs hover:opacity-95 transition-opacity">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Log a Customer Case</span>
            </button>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session('success'))
        <div class="p-4 rounded-2xl bg-[#DAF4E7] border border-[#007D49]/20 text-[#007D49] text-sm font-bold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-xs font-bold opacity-70 hover:opacity-100">Dismiss</button>
        </div>
    @endif

    @if (session('info'))
        <div class="p-4 rounded-2xl bg-[#FFF9EA] border border-[#FFE8A3] text-[#D58700] text-sm font-bold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('info') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-xs font-bold opacity-70 hover:opacity-100">Dismiss</button>
        </div>
    @endif

    <!-- Scorecards KPI Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
        <div class="p-3.5 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-2xs cursor-pointer hover:border-[#DF2600]/40 transition-colors {{ $statusFilter === 'open' ? 'ring-2 ring-[#DF2600]' : '' }}"
             wire:click="$set('statusFilter', 'open')">
            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-[#686868]">Open Claims</span>
            <div class="mt-1 sm:mt-2 text-xl sm:text-2xl font-black text-[#111111]">{{ $counts['open'] }}</div>
            <span class="text-[10px] sm:text-[11px] text-[#686868]">Under review or awaiting</span>
        </div>

        <div class="p-3.5 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-2xs cursor-pointer hover:border-[#DF2600]/40 transition-colors {{ $statusFilter === 'new' ? 'ring-2 ring-[#DF2600]' : '' }}"
             wire:click="$set('statusFilter', 'new')">
            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-[#DF2600]">New Intake</span>
            <div class="mt-1 sm:mt-2 text-xl sm:text-2xl font-black text-[#DF2600]">{{ $counts['new'] }}</div>
            <span class="text-[10px] sm:text-[11px] text-[#686868]">Needs first response</span>
        </div>

        <div class="p-3.5 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-2xs cursor-pointer hover:border-[#DF2600]/40 transition-colors {{ $kindFilter === 'safety' ? 'ring-2 ring-[#DF2600]' : '' }}"
             wire:click="$set('kindFilter', '{{ $kindFilter === 'safety' ? '' : 'safety' }}')">
            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider {{ $counts['safety'] > 0 ? 'text-[#DF2600]' : 'text-[#686868]' }}">
                Safety / Allergens
            </span>
            <div class="mt-1 sm:mt-2 text-xl sm:text-2xl font-black {{ $counts['safety'] > 0 ? 'text-[#DF2600]' : 'text-[#111111]' }}">
                {{ $counts['safety'] }}
            </div>
            <span class="text-[10px] sm:text-[11px] text-[#686868]">Urgent direct contact</span>
        </div>

        <div class="p-3.5 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-2xs cursor-pointer hover:border-[#DF2600]/40 transition-colors"
             wire:click="$set('statusFilter', 'new')">
            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider {{ $counts['overdue'] > 0 ? 'text-[#D58700]' : 'text-[#686868]' }}">
                SLA Breached (>24h)
            </span>
            <div class="mt-1 sm:mt-2 text-xl sm:text-2xl font-black {{ $counts['overdue'] > 0 ? 'text-[#D58700]' : 'text-[#111111]' }}">
                {{ $counts['overdue'] }}
            </div>
            <span class="text-[10px] sm:text-[11px] text-[#686868]">Target 24h response</span>
        </div>

        <div class="p-3.5 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-2xs cursor-pointer hover:border-[#007D49]/40 transition-colors {{ $statusFilter === 'resolved' ? 'ring-2 ring-[#007D49]' : '' }}"
             wire:click="$set('statusFilter', 'resolved')">
            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-[#007D49]">Resolved Today</span>
            <div class="mt-1 sm:mt-2 text-xl sm:text-2xl font-black text-[#007D49]">{{ $counts['resolved_today'] }}</div>
            <span class="text-[10px] sm:text-[11px] text-[#686868]">Refunded or replaced</span>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white rounded-2xl p-4 border border-[#EEEEEE] space-y-3 shadow-2xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <!-- Search -->
            <div class="relative flex-1 max-w-md">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search by claim #, order #, or customer name..."
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs sm:text-sm text-[#111111] placeholder:text-[#686868]/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                <svg class="w-4 h-4 text-[#686868] absolute left-3.5 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <!-- Kind Filter Dropdown -->
            <div class="flex items-center gap-2 self-start sm:self-auto">
                <select wire:model.live="kindFilter"
                        class="px-3 py-2 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs font-semibold text-[#111111] focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                    <option value="">All Case Kinds</option>
                    <option value="problem">Damaged / Missing / Quality Issues</option>
                    <option value="return">Non-Food Returns (Change of Mind)</option>
                    <option value="safety">Food Safety & Allergen Alerts</option>
                </select>
            </div>
        </div>

        <!-- Horizontal Scrollable Status Tabs -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 pt-1 border-t border-[#EEEEEE] text-xs">
            @php
                $tabs = [
                    'open' => 'Open Claims',
                    'new' => 'New Intake',
                    'reviewing' => 'Under Review',
                    'awaiting_customer' => 'Awaiting Customer Choice',
                    'resolved' => 'Resolved',
                    'declined' => 'Declined',
                    'all' => 'All Claims',
                ];
            @endphp
            @foreach ($tabs as $key => $label)
                <button type="button"
                        wire:click="$set('statusFilter', '{{ $key }}')"
                        class="px-3 py-1.5 rounded-xl font-bold whitespace-nowrap transition-colors {{ $statusFilter === $key ? 'bg-[#111111] text-white shadow-xs' : 'bg-[#FAFAFA] text-[#686868] hover:bg-[#F6F6F6] hover:text-[#111111]' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Dual-View Claims Listing -->

    <!-- Mobile Card Stack (block md:hidden) -->
    <div class="block md:hidden space-y-3">
        @forelse ($issues as $issue)
            <div class="p-4 bg-white rounded-2xl border border-[#EEEEEE] shadow-2xs space-y-3 cursor-pointer hover:border-[#DF2600]/40 transition-colors"
                 wire:click="viewIssue({{ $issue->id }})">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="font-mono text-xs font-black text-[#DF2600]">{{ $issue->reference }}</span>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $issue->kind === 'safety' ? 'bg-[#FFF0EC] text-[#DF2600]' : 'bg-[#FAFAFA] text-[#686868] border border-[#EEEEEE]' }}">
                                {{ $issue->kind }}
                            </span>
                        </div>
                        <h4 class="mt-1 font-extrabold text-sm text-[#111111] leading-tight">
                            Order #{{ $issue->order->number }}
                        </h4>
                        <div class="text-xs text-[#686868]">
                            {{ $issue->order->contact_name }} · via {{ ucfirst($issue->channel) }}
                        </div>
                    </div>

                    <div class="text-right shrink-0">
                        @if ($issue->status === 'new')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-[#FFF0EC] text-[#DF2600]">
                                New Intake
                            </span>
                        @elseif ($issue->status === 'reviewing')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-[#FFF9EA] text-[#D58700]">
                                Reviewing
                            </span>
                        @elseif ($issue->status === 'awaiting_customer')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-[#EBF4FE] text-[#0055D4]">
                                Awaiting Choice
                            </span>
                        @elseif ($issue->status === 'resolved')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-[#DAF4E7] text-[#007D49]">
                                Resolved
                            </span>
                        @elseif ($issue->status === 'declined')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-[#FAFAFA] text-[#686868]">
                                Declined
                            </span>
                        @endif

                        <div class="text-[10px] font-bold mt-1 text-[#686868]">
                            {{ $issue->reported_at ? $issue->reported_at->format('M j, g:i A') : '' }}
                        </div>
                    </div>
                </div>

                @if ($issue->items->isNotEmpty())
                    <div class="text-xs text-[#686868] line-clamp-1 border-t border-[#EEEEEE] pt-2">
                        <strong class="text-[#111111]">{{ $issue->items->count() }} item(s):</strong>
                        {{ $issue->items->map(fn($i) => $i->orderItem?->name ?? 'Item')->join(', ') }}
                    </div>
                @endif
            </div>
        @empty
            <div class="p-8 text-center bg-white rounded-2xl border border-[#EEEEEE]">
                <p class="text-sm font-bold text-[#111111]">No issues matching this filter</p>
                <p class="text-xs text-[#686868] mt-1">All customer claims are resolved.</p>
            </div>
        @endforelse

        <div class="pt-2">
            {{ $issues->links() }}
        </div>
    </div>

    <!-- Desktop Table View (hidden md:block) -->
    <div class="hidden md:block bg-white rounded-2xl border border-[#EEEEEE] overflow-hidden shadow-2xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-[#FAFAFA] border-b border-[#EEEEEE] text-[11px] font-bold uppercase tracking-wider text-[#686868]">
                    <tr>
                        <th class="py-3 px-4">Claim Ref / Kind</th>
                        <th class="py-3 px-4">Order #</th>
                        <th class="py-3 px-4">Customer</th>
                        <th class="py-3 px-4">Reported At</th>
                        <th class="py-3 px-4">SLA Target (24h)</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EEEEEE]">
                    @forelse ($issues as $issue)
                        <tr class="hover:bg-[#FAFAFA] transition-colors cursor-pointer"
                            wire:click="viewIssue({{ $issue->id }})">
                            <td class="py-3.5 px-4 font-extrabold text-[#111111]">
                                <div class="font-mono text-xs text-[#DF2600]">{{ $issue->reference }}</div>
                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold uppercase {{ $issue->kind === 'safety' ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#686868]' }}">
                                    {{ $issue->kind }} · via {{ $issue->channel }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-[#111111]">
                                #{{ $issue->order->number }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-[#111111]">{{ $issue->order->contact_name }}</div>
                                <div class="text-[11px] text-[#686868]">{{ $issue->order->contact_phone ?? $issue->order->contact_email }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-xs text-[#686868]">
                                {{ $issue->reported_at ? $issue->reported_at->format('M j, g:i A') : '—' }}
                            </td>
                            <td class="py-3.5 px-4 text-xs">
                                @if ($issue->isOverdue())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-black bg-[#FFF0EC] text-[#DF2600]">
                                        Breached
                                    </span>
                                @elseif ($issue->status === 'new')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-[#FFF9EA] text-[#D58700]">
                                        Due in {{ max(0, ceil($issue->remainingSlaMinutes() / 60)) }}h
                                    </span>
                                @else
                                    <span class="text-[#007D49] font-bold text-xs">Responded</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @if ($issue->status === 'new')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-extrabold bg-[#FFF0EC] text-[#DF2600]">
                                        ● New
                                    </span>
                                @elseif ($issue->status === 'reviewing')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-extrabold bg-[#FFF9EA] text-[#D58700]">
                                        Reviewing
                                    </span>
                                @elseif ($issue->status === 'awaiting_customer')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-extrabold bg-[#EBF4FE] text-[#0055D4]">
                                        Awaiting Customer
                                    </span>
                                @elseif ($issue->status === 'resolved')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-extrabold bg-[#DAF4E7] text-[#007D49]">
                                        Resolved
                                    </span>
                                @elseif ($issue->status === 'declined')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-extrabold bg-[#FAFAFA] text-[#686868]">
                                        Declined
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <button type="button"
                                        class="px-3 py-1.5 rounded-xl border border-[#EEEEEE] font-bold text-xs text-[#111111] hover:bg-[#FAFAFA]">
                                    Inspect Case
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-[#686868]">
                                <p class="text-sm font-bold text-[#111111]">No issues matching this filter</p>
                                <p class="text-xs text-[#686868] mt-1">All customer claims are resolved.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-[#EEEEEE]">
            {{ $issues->links() }}
        </div>
    </div>

    <!-- Case Inspection Slide-Over Drawer -->
    @if ($selectedIssue)
        <div class="fixed inset-0 z-50 overflow-hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute inset-0 bg-black/50 transition-opacity" wire:click="closeDrawer"></div>

                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-0 sm:pl-10">
                    <div class="pointer-events-auto w-screen max-w-2xl bg-white shadow-2xl flex flex-col h-full" style="background-color: #ffffff;">
                        <!-- Drawer Top Bar -->
                        <div class="p-4 sm:p-6 border-b border-[#EEEEEE] flex items-center justify-between gap-3 shrink-0">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-black text-[#DF2600]">Claim {{ $selectedIssue->reference }}</span>
                                    <span class="text-xs text-[#686868]">· Order #{{ $selectedIssue->order->number }}</span>
                                </div>
                                <h3 class="text-lg sm:text-xl font-black text-[#111111] mt-0.5" id="slide-over-title">
                                    {{ ucfirst($selectedIssue->kind) }} Review
                                </h3>
                            </div>
                            <button type="button" wire:click="closeDrawer" class="p-2 rounded-xl text-[#686868] hover:bg-[#FAFAFA]">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Drawer Content Body -->
                        <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6">
                            <!-- Customer & Order Context -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="p-4 rounded-2xl border border-[#EEEEEE] space-y-1">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#686868]">Customer</span>
                                    <div class="font-extrabold text-[#111111]">{{ $selectedIssue->order->contact_name }}</div>
                                    @if ($selectedIssue->order->contact_phone)
                                        <div class="text-xs">
                                            <a href="tel:{{ $selectedIssue->order->contact_phone }}" class="text-[#DF2600] font-bold hover:underline inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                </svg>
                                                {{ $selectedIssue->order->contact_phone }}
                                            </a>
                                        </div>
                                    @endif
                                    @if ($selectedIssue->order->contact_email)
                                        <div class="text-xs text-[#686868] truncate">{{ $selectedIssue->order->contact_email }}</div>
                                    @endif
                                </div>

                                <div class="p-4 rounded-2xl border border-[#EEEEEE] space-y-1">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#686868]">Intake Details</span>
                                    <div class="text-xs font-bold text-[#111111]">Channel: {{ ucfirst($selectedIssue->channel) }}</div>
                                    <div class="text-xs text-[#686868]">Reported: {{ $selectedIssue->reported_at ? $selectedIssue->reported_at->format('M j, Y · g:i A') : '—' }}</div>
                                    <div class="text-xs {{ $selectedIssue->isOverdue() ? 'text-[#DF2600] font-bold' : 'text-[#686868]' }}">
                                        SLA Due: {{ $selectedIssue->due_at ? $selectedIssue->due_at->format('M j, g:i A') : '—' }}
                                    </div>
                                </div>
                            </div>

                            <!-- Customer Problem Description -->
                            @if ($selectedIssue->description)
                                <div class="p-4 rounded-2xl bg-[#FAFAFA] border border-[#EEEEEE] space-y-1">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#686868]">Customer's Statement</span>
                                    <p class="text-xs sm:text-sm text-[#111111] leading-relaxed">
                                        "{{ $selectedIssue->description }}"
                                    </p>
                                </div>
                            @endif

                            <!-- Claimed Items Breakdown -->
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-[#686868] mb-3">
                                    Claimed Items ({{ $selectedIssue->items->count() }})
                                </h4>
                                <div class="border border-[#EEEEEE] rounded-2xl overflow-hidden divide-y divide-[#EEEEEE]">
                                    @foreach ($selectedIssue->items as $item)
                                        <div class="p-3.5 sm:p-4 flex items-center justify-between gap-3">
                                            <div class="min-w-0">
                                                <h5 class="font-extrabold text-sm text-[#111111] truncate">
                                                    {{ $item->orderItem?->name ?? 'Ordered Item' }}
                                                </h5>
                                                @if ($item->orderItem?->variation_name)
                                                    <span class="text-xs text-[#686868] block">{{ $item->orderItem->variation_name }}</span>
                                                @endif
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#FFF0EC] text-[#DF2600]">
                                                        {{ $item->typeLabel() }}
                                                    </span>
                                                    <span class="text-xs text-[#686868]">
                                                        Qty affected: <strong class="text-[#111111]">{{ $item->quantity }}</strong>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <div class="font-extrabold text-sm text-[#111111]">
                                                    {{ money($item->orderItem?->line_total_cents ?? 0) }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Uploaded Proof Photos -->
                            @if ($selectedIssue->photos->isNotEmpty())
                                <div>
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-[#686868] mb-3">
                                        Uploaded Evidence Photos ({{ $selectedIssue->photos->count() }})
                                    </h4>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                        @foreach ($selectedIssue->photos as $photo)
                                            <a href="{{ $photo->url() }}" target="_blank" class="block aspect-square rounded-2xl border border-[#EEEEEE] overflow-hidden group relative">
                                                <img src="{{ $photo->url() }}" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                                <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold">
                                                    Open Full
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Financial Ledger Card -->
                            <div class="p-4 rounded-2xl bg-[#FAFAFA] border border-[#EEEEEE] space-y-2 text-xs">
                                <span class="font-bold uppercase tracking-wider text-[#686868] block">Order Financial Balance</span>
                                <div class="flex justify-between text-[#686868]">
                                    <span>Original Order Total</span>
                                    <span class="font-bold text-[#111111]">{{ money($selectedIssue->order->total_cents) }}</span>
                                </div>
                                <div class="flex justify-between text-[#686868]">
                                    <span>Already Refunded</span>
                                    <span class="font-bold text-[#DF2600]">-{{ money($selectedIssue->order->refunded_cents) }}</span>
                                </div>
                                <div class="flex justify-between font-extrabold text-sm text-[#111111] border-t border-[#EEEEEE] pt-2">
                                    <span>Remaining Refundable Capacity</span>
                                    <span>{{ money($selectedIssue->order->activeRefundableCents()) }}</span>
                                </div>
                            </div>

                            <!-- Case Activity Diary / Timeline -->
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-[#686868] mb-3">
                                    Case Diary & Timeline
                                </h4>
                                <div class="space-y-2.5">
                                    @foreach ($selectedIssue->events as $event)
                                        <div class="p-3 rounded-xl bg-white border border-[#EEEEEE] text-xs space-y-1">
                                            <div class="flex items-center justify-between text-[11px] text-[#686868]">
                                                <span class="font-bold text-[#111111] uppercase">{{ str_replace('_', ' ', $event->type) }}</span>
                                                <span>{{ $event->created_at ? $event->created_at->format('M j, g:i A') : '' }}</span>
                                            </div>
                                            @if ($event->note)
                                                <p class="text-[#686868]">{{ $event->note }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Add Internal Staff Note -->
                                <div class="mt-3 flex gap-2">
                                    <input type="text"
                                           wire:model="newStaffNote"
                                           placeholder="Add internal note to case diary..."
                                           wire:keydown.enter="addStaffNote"
                                           class="flex-1 px-3 py-2 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                    <button type="button"
                                            wire:click="addStaffNote"
                                            class="px-3.5 py-2 rounded-xl bg-[#111111] text-white text-xs font-bold hover:bg-black">
                                        Record Note
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Drawer Footer Resolution Buttons -->
                        <div class="p-4 sm:p-6 border-t border-[#EEEEEE] bg-[#FAFAFA] space-y-3 shrink-0">
                            @if ($selectedIssue->isPending())
                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                    <button type="button"
                                            wire:click="openResolutionModal('refund')"
                                            style="background-color: #DF2600;"
                                            class="flex-1 py-3 px-4 rounded-xl text-white font-extrabold text-xs sm:text-sm hover:opacity-90 transition-opacity shadow-xs text-center">
                                        Issue Policy Refund ({{ money($calculatedRefund['amount_cents']) }})
                                    </button>

                                    <button type="button"
                                            wire:click="openResolutionModal('replacement')"
                                            style="background-color: #007D49;"
                                            class="py-3 px-4 rounded-xl text-white font-extrabold text-xs sm:text-sm hover:opacity-90 transition-opacity shadow-xs text-center">
                                        Schedule Replacement
                                    </button>

                                    <button type="button"
                                            wire:click="openResolutionModal('offer')"
                                            style="background-color: #0055D4;"
                                            class="py-3 px-4 rounded-xl text-white font-extrabold text-xs sm:text-sm hover:opacity-90 transition-opacity shadow-xs text-center">
                                        Offer Customer Choices
                                    </button>

                                    <button type="button"
                                            wire:click="openResolutionModal('decline')"
                                            class="py-3 px-4 rounded-xl border border-[#EEEEEE] bg-white text-[#DF2600] font-bold text-xs sm:text-sm hover:bg-[#FFF0EC] transition-colors text-center">
                                        Decline Claim
                                    </button>
                                </div>
                            @else
                                <div class="py-3 px-4 rounded-xl {{ $selectedIssue->status === 'resolved' ? 'bg-[#DAF4E7] text-[#007D49]' : 'bg-[#FAFAFA] text-[#686868] border border-[#EEEEEE]' }} font-extrabold text-sm text-center">
                                    Case {{ ucfirst($selectedIssue->status) }} ({{ $selectedIssue->chosen ? 'Resolution: ' . ucfirst($selectedIssue->chosen) : 'Closed' }})
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Resolution Execution Modal -->
    @if ($showResolutionModal && $selectedIssue)
        <div class="fixed inset-0 z-60 overflow-y-auto" aria-labelledby="modal-res-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 transition-opacity" wire:click="closeResolutionModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative z-10 inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all my-4 sm:my-8 sm:align-middle sm:max-w-lg w-full p-5 sm:p-7 space-y-4" style="background-color: #ffffff;">
                    <div class="flex items-start justify-between border-b border-[#EEEEEE] pb-3">
                        <h3 class="text-base sm:text-lg font-black text-[#111111]" id="modal-res-title">
                            @if ($resolutionType === 'refund')
                                Execute Policy Refund
                            @elseif ($resolutionType === 'replacement')
                                Schedule Replacement Order
                            @elseif ($resolutionType === 'offer')
                                Send Resolution Options to Customer
                            @elseif ($resolutionType === 'decline')
                                Decline Customer Claim
                            @endif
                        </h3>
                        <button type="button" wire:click="closeResolutionModal" class="p-1 rounded-lg text-[#686868] hover:bg-[#FAFAFA]">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    @if ($resolutionType === 'refund')
                        <div class="space-y-3 text-xs sm:text-sm">
                            <p class="text-[#686868]">
                                This will initiate an electronic refund to the customer's original card through our single refund gateway and dispatch a confirmation email.
                            </p>

                            <div class="p-4 rounded-2xl bg-[#FFF0EC]/50 border border-[#DF2600]/20 space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-[#686868]">Affected Line Items:</span>
                                    <span class="font-bold text-[#111111]">{{ money($calculatedRefund['items_cents'] ?? $calculatedRefund['amount_cents']) }}</span>
                                </div>

                                @if ($selectedIssue->order->delivery_fee_cents > 0)
                                    <label class="flex items-center justify-between pt-2 border-t border-[#DF2600]/10 cursor-pointer">
                                        <span class="flex items-center gap-2">
                                            <input type="checkbox"
                                                   wire:model.live="includeDeliveryFee"
                                                   class="w-4 h-4 rounded text-[#DF2600] focus:ring-[#DF2600] border-[#EEEEEE]">
                                            <span class="font-bold text-xs text-[#111111]">Also refund delivery fee (late/undelivered)</span>
                                        </span>
                                        <span class="font-bold text-[#DF2600]">+{{ money($selectedIssue->order->delivery_fee_cents) }}</span>
                                    </label>
                                @endif

                                <div class="flex justify-between text-sm sm:text-base font-black text-[#DF2600] border-t border-[#DF2600]/20 pt-2">
                                    <span>Total Refund Amount:</span>
                                    <span>{{ money($calculatedRefund['amount_cents']) }}</span>
                                </div>
                            </div>
                        </div>

                    @elseif ($resolutionType === 'replacement')
                        <div class="space-y-3 text-xs sm:text-sm">
                            <p class="text-[#686868]">
                                A new linked replacement order (<strong>#{{ $selectedIssue->order->number }}-R1</strong>) with a zero-dollar balance will be scheduled into the fulfillment queue for kitchen/prep staff.
                            </p>
                            <div class="p-3.5 rounded-xl bg-[#DAF4E7]/40 border border-[#007D49]/20 text-[#007D49] font-bold text-xs">
                                Delivery/pickup will mirror the original order's delivery method at store expense.
                            </div>
                        </div>

                    @elseif ($resolutionType === 'offer')
                        <div class="space-y-3 text-xs sm:text-sm">
                            <p class="text-[#686868]">
                                Select which remedies the customer will be invited to choose from:
                            </p>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2.5 p-3 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] cursor-pointer">
                                    <input type="checkbox" wire:model="offeredResolutions" value="refund" class="w-4 h-4 rounded text-[#DF2600] focus:ring-[#DF2600]">
                                    <span class="font-bold text-xs text-[#111111]">Direct Refund to Original Card</span>
                                </label>
                                <label class="flex items-center gap-2.5 p-3 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] cursor-pointer">
                                    <input type="checkbox" wire:model="offeredResolutions" value="replacement" class="w-4 h-4 rounded text-[#DF2600] focus:ring-[#DF2600]">
                                    <span class="font-bold text-xs text-[#111111]">Send Replacement Product</span>
                                </label>
                                <label class="flex items-center gap-2.5 p-3 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] cursor-pointer">
                                    <input type="checkbox" wire:model="offeredResolutions" value="exchange" class="w-4 h-4 rounded text-[#DF2600] focus:ring-[#DF2600]">
                                    <span class="font-bold text-xs text-[#111111]">Exchange for Alternative Product</span>
                                </label>
                            </div>
                        </div>

                    @elseif ($resolutionType === 'decline')
                        <div class="space-y-3 text-xs sm:text-sm">
                            <div>
                                <label class="block text-xs font-bold text-[#111111] mb-1">Policy Reason</label>
                                <select wire:model="declineReasonKey" class="w-full px-3 py-2 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs font-bold text-[#111111]">
                                    <option value="food_change_of_mind">Food & Perishables: No Change of Mind</option>
                                    <option value="outside_window">Submitted Outside Policy Window (>24h)</option>
                                    <option value="no_evidence">No Verification Photos/Evidence Provided</option>
                                    <option value="not_in_order">Item Not Included in Order Record</option>
                                    <option value="damage_after_delivery">Damage Occurred After Handoff</option>
                                    <option value="other">Other Ineligibility</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-[#111111] mb-1">Additional Staff Note (Optional)</label>
                                <input type="text"
                                       wire:model="declineCustomNote"
                                       placeholder="e.g. Customer acknowledged item was consumed."
                                       class="w-full px-3 py-2 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs">
                            </div>

                            <p class="text-[11px] text-[#686868] italic border-t border-[#EEEEEE] pt-2">
                                Customer notice will automatically conclude with the statutory notice: "This decision does not limit any rights you have under applicable law."
                            </p>
                        </div>
                    @endif

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#EEEEEE]">
                        <button type="button"
                                wire:click="closeResolutionModal"
                                class="px-4 py-2.5 rounded-xl border border-[#EEEEEE] text-xs font-bold text-[#111111] hover:bg-[#FAFAFA]">
                            Cancel
                        </button>
                        <button type="button"
                                wire:click="executeResolution"
                                style="background-color: {{ $resolutionType === 'decline' ? '#111111' : '#DF2600' }};"
                                class="px-5 py-2.5 rounded-xl text-white text-xs font-extrabold hover:opacity-95 shadow-md">
                            Confirm Resolution
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- "Log a Case" Modal -->
    @if ($showLogCaseModal)
        <div class="fixed inset-0 z-60 overflow-y-auto" aria-labelledby="modal-log-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 transition-opacity" wire:click="closeLogCaseModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative z-10 inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all my-4 sm:my-8 sm:align-middle sm:max-w-xl w-full p-5 sm:p-7 space-y-4" style="background-color: #ffffff;">
                    <div class="flex items-start justify-between border-b border-[#EEEEEE] pb-3">
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-wider text-[#DF2600]">Direct Customer Intake</span>
                            <h3 class="text-base sm:text-lg font-black text-[#111111]" id="modal-log-title">
                                Log Customer Complaint
                            </h3>
                        </div>
                        <button type="button" wire:click="closeLogCaseModal" class="p-1 rounded-lg text-[#686868] hover:bg-[#FAFAFA]">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Step 1: Find Order -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-[#111111]">1. Enter Order Number</label>
                        <div class="flex gap-2">
                            <input type="text"
                                   wire:model="logCaseOrderNumber"
                                   placeholder="e.g. IE-261023-ABCD"
                                   wire:keydown.enter="findOrderForLogCase"
                                   class="flex-1 px-3 py-2 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs font-bold uppercase text-[#111111]">
                            <button type="button"
                                    wire:click="findOrderForLogCase"
                                    class="px-4 py-2 rounded-xl bg-[#111111] text-white text-xs font-bold hover:bg-black">
                                Locate Order
                            </button>
                        </div>
                        @error('logCaseOrderNumber')
                            <span class="text-xs text-[#DF2600] font-bold block">{{ $message }}</span>
                        @enderror
                    </div>

                    @if ($logCaseOrder)
                        <!-- Order Located Summary -->
                        <div class="p-3 rounded-xl bg-[#DAF4E7]/40 border border-[#007D49]/20 text-xs text-[#007D49] flex items-center justify-between">
                            <div>
                                <span class="font-bold text-[#111111]">{{ $logCaseOrder->contact_name }}</span> (Order #{{ $logCaseOrder->number }})
                                <div class="text-[11px] text-[#686868]">{{ $logCaseOrder->contact_phone }} · {{ money($logCaseOrder->total_cents) }}</div>
                            </div>
                            <span class="font-bold">{{ $logCaseOrder->items->count() }} items</span>
                        </div>

                        <!-- Intake Channel & Kind -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-[#111111] mb-1">Contact Channel</label>
                                <select wire:model="logCaseChannel" class="w-full px-3 py-1.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs font-bold">
                                    <option value="phone">Phone Call</option>
                                    <option value="whatsapp">WhatsApp</option>
                                    <option value="email">Direct Email</option>
                                    <option value="in_store">In-Store Walk In</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-[#111111] mb-1">Claim Kind</label>
                                <select wire:model="logCaseKind" class="w-full px-3 py-1.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs font-bold">
                                    <option value="problem">Quality / Damage / Missing</option>
                                    <option value="return">Non-Food Return</option>
                                    <option value="safety">Food Safety / Allergen Alert</option>
                                </select>
                            </div>
                        </div>

                        <!-- Select Affected Items -->
                        <div>
                            <label class="block text-xs font-bold text-[#111111] mb-1">2. Select Affected Line Items</label>
                            <div class="max-h-48 overflow-y-auto border border-[#EEEEEE] rounded-2xl p-2 space-y-2 bg-[#FAFAFA]/50">
                                @foreach ($logCaseOrder->items as $item)
                                    <div class="p-2.5 rounded-xl bg-white border border-[#EEEEEE] space-y-2">
                                        <label class="flex items-center justify-between gap-2 cursor-pointer">
                                            <span class="flex items-center gap-2">
                                                <input type="checkbox"
                                                       wire:model="logCaseSelectedItems.{{ $item->id }}.selected"
                                                       class="w-4 h-4 rounded text-[#DF2600] focus:ring-[#DF2600]">
                                                <span class="font-bold text-xs text-[#111111]">{{ $item->name }}</span>
                                            </span>
                                            <span class="text-xs text-[#686868]">Qty in order: {{ $item->quantity }}</span>
                                        </label>

                                        @if (! empty($logCaseSelectedItems[$item->id]['selected']))
                                            <div class="grid grid-cols-2 gap-2 pl-6 pt-1 border-t border-[#EEEEEE]">
                                                <div>
                                                    <span class="text-[10px] text-[#686868] block">Affected Qty:</span>
                                                    <input type="number"
                                                           min="1"
                                                           max="{{ $item->quantity }}"
                                                           wire:model="logCaseSelectedItems.{{ $item->id }}.quantity"
                                                           class="w-full px-2 py-1 rounded-lg border border-[#EEEEEE] text-xs font-bold">
                                                </div>
                                                <div>
                                                    <span class="text-[10px] text-[#686868] block">Problem:</span>
                                                    <select wire:model="logCaseSelectedItems.{{ $item->id }}.issue_type" class="w-full px-2 py-1 rounded-lg border border-[#EEEEEE] text-xs font-bold">
                                                        <option value="damaged">Damaged</option>
                                                        <option value="missing_item">Missing Item</option>
                                                        <option value="spoiled_defective">Spoiled / Defective</option>
                                                        <option value="wrong_product">Wrong Product</option>
                                                        <option value="wrong_quantity">Wrong Quantity</option>
                                                    </select>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            @error('logCaseItems')
                                <span class="text-xs text-[#DF2600] font-bold block mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-xs font-bold text-[#111111] mb-1">Customer Statement / Notes</label>
                            <textarea wire:model="logCaseDescription"
                                      rows="2"
                                      placeholder="Summary of customer call or complaint..."
                                      class="w-full px-3 py-2 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs"></textarea>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#EEEEEE]">
                            <button type="button" wire:click="closeLogCaseModal" class="px-4 py-2 rounded-xl border border-[#EEEEEE] text-xs font-bold">
                                Cancel
                            </button>
                            <button type="button"
                                    wire:click="saveLogCase"
                                    style="background-color: #DF2600;"
                                    class="px-5 py-2 rounded-xl text-white text-xs font-extrabold hover:opacity-95 shadow-md">
                                Open Case
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
