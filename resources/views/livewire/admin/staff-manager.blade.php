<div class="space-y-6 sm:space-y-8" x-data="{ copyStatus: false }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-[#111111] tracking-tight">Staff & Access Management</h2>
            <p class="mt-1 text-xs sm:text-sm text-[#686868]">
                Manage store associates and managers, set permission levels, create new login credentials, and revoke access.
            </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <button type="button"
                    wire:click="openCreateModal"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-[#DF2600] text-white text-xs sm:text-sm font-bold shadow-sm hover:bg-[#b82000] transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                + Add Staff Member
            </button>
        </div>
    </div>

    <!-- Flash Notifications -->
    @if (session('success'))
        <div class="p-4 rounded-xl bg-[#DAF4E7] border border-[#007D49]/20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-[#007D49]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-xs sm:text-sm font-bold text-[#007D49]">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 rounded-xl bg-[#FFF0EC] border border-[#DF2600]/20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-[#DF2600]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span class="text-xs sm:text-sm font-bold text-[#DF2600]">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Client Handover & Roles Info Card -->
    <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-[#FFF0EC] via-white to-white border border-[#DF2600]/20 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 sm:gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-[#DF2600] text-white text-xs font-bold">
                        i
                    </span>
                    <h3 class="text-sm font-extrabold text-[#111111]">Store Operations Access Guide</h3>
                </div>
                <p class="text-xs text-[#686868] max-w-2xl leading-relaxed">
                    <strong>Store Managers</strong> have full administrative rights, including Square credentials, store settings, and staff access. 
                    <strong>Store Associates</strong> can fulfill incoming orders, update stock counts, edit categories, and publish recipes without seeing sensitive financial keys.
                </p>
            </div>
            <div class="text-xs text-[#686868] shrink-0 bg-white p-3 rounded-xl border border-[#EEEEEE]">
                <div class="font-bold text-[#111111]">Emergency Terminal Access:</div>
                <code class="text-[11px] text-[#DF2600] font-mono">php artisan staff:create</code>
            </div>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
        <div class="p-4 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-[#686868]">Total Staff</span>
                <span class="p-1.5 sm:p-2 rounded-xl bg-[#FAFAFA] text-[#111111]">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-1 sm:mt-2 text-xl sm:text-2xl font-extrabold text-[#111111]">{{ number_format($counts['total']) }}</div>
            <span class="text-[10px] sm:text-[11px] text-[#686868]">Authorized accounts</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-[#686868]">Store Managers</span>
                <span class="p-1.5 sm:p-2 rounded-xl bg-[#FFF0EC] text-[#DF2600]">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </span>
            </div>
            <div class="mt-1 sm:mt-2 text-xl sm:text-2xl font-extrabold text-[#DF2600]">{{ number_format($counts['managers']) }}</div>
            <span class="text-[10px] sm:text-[11px] text-[#686868]">Full administrative control</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-[#686868]">Associates</span>
                <span class="p-1.5 sm:p-2 rounded-xl bg-[#F0F7FF] text-[#0066CC]">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </span>
            </div>
            <div class="mt-1 sm:mt-2 text-xl sm:text-2xl font-extrabold text-[#0066CC]">{{ number_format($counts['associates']) }}</div>
            <span class="text-[10px] sm:text-[11px] text-[#686868]">Orders & kitchen inventory</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-2xl border border-[#EEEEEE] shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-[#686868]">Active Accounts</span>
                <span class="p-1.5 sm:p-2 rounded-xl bg-[#DAF4E7] text-[#007D49]">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-1 sm:mt-2 text-xl sm:text-2xl font-extrabold text-[#007D49]">{{ number_format($counts['active']) }}</div>
            <span class="text-[10px] sm:text-[11px] text-[#686868]">Can log in to portal</span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl border border-[#EEEEEE] p-4 sm:p-5 shadow-xs">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="flex-1 relative">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search staff by name, email, or phone number..."
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                <div class="absolute left-3.5 top-3 text-[#686868]">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 shrink-0">
                <select wire:model.live="roleFilter"
                        class="px-3 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs font-bold text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                    <option value="">All Roles</option>
                    <option value="manager">Store Managers</option>
                    <option value="associate">Store Associates</option>
                </select>

                <select wire:model.live="statusFilter"
                        class="px-3 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-xs font-bold text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="deactivated">Deactivated</option>
                </select>

                @if ($search || $roleFilter || $statusFilter !== '')
                    <button type="button"
                            wire:click="$set('search', ''); $set('roleFilter', ''); $set('statusFilter', '');"
                            class="col-span-2 sm:col-span-1 p-2.5 rounded-xl text-xs font-bold text-[#DF2600] bg-[#FFF0EC] sm:bg-transparent hover:bg-[#FFF0EC] transition-colors flex items-center justify-center gap-1"
                            title="Reset filters">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span class="sm:hidden">Reset Filters</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Staff Roster Container: Mobile Cards Stack + Desktop Table -->
    <div class="bg-white rounded-2xl border border-[#EEEEEE] shadow-xs overflow-hidden">
        <!-- 1. Mobile Cards Stack (Visible on screens < md) -->
        <div class="block md:hidden divide-y divide-[#EEEEEE]">
            @forelse ($staffMembers as $member)
                @php
                    $isCurrentUser = $member->id === auth()->id();
                    $isManager = in_array($member->role, ['manager', 'owner'], true);
                    $initials = strtoupper(substr($member->name ?: $member->email, 0, 2));
                @endphp
                <div class="p-4 space-y-3 hover:bg-[#FAFAFA] transition-colors {{ ! $member->is_active ? 'opacity-60 bg-[#FBFBFB]' : '' }}">
                    <!-- Top Row: Avatar, Name & Role Badge -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-full {{ $isManager ? 'bg-[#111111] text-white' : 'bg-[#E5F1FF] text-[#0066CC]' }} flex items-center justify-center text-xs font-extrabold shrink-0 shadow-xs">
                                {{ $initials }}
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <h4 class="font-extrabold text-[#111111] text-sm truncate">{{ $member->name ?: 'Staff Member' }}</h4>
                                    @if ($isCurrentUser)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-extrabold bg-[#FFF0EC] text-[#DF2600]">
                                            You
                                        </span>
                                    @endif
                                </div>
                                <span class="block text-xs text-[#686868] truncate">{{ $member->email }}</span>
                                @if ($member->phone)
                                    <a href="tel:{{ $member->phone }}" class="block text-[11px] text-[#0066CC] hover:underline font-mono">{{ $member->phone }}</a>
                                @endif
                            </div>
                        </div>

                        <!-- Role Badge -->
                        <div class="shrink-0">
                            @if ($isManager)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#111111] text-white">
                                    <svg class="w-3 h-3 text-[#DF2600]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    Manager
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#EBF4FF] text-[#0066CC]">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                    Associate
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Middle Row: Status & Created Date -->
                    <div class="flex items-center justify-between text-xs bg-[#FAFAFA] p-2.5 rounded-xl border border-[#EEEEEE]">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[#686868] block">Status</span>
                            @if ($member->is_active)
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#DAF4E7] text-[#007D49]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#007D49]"></span>
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#F6F6F6] text-[#686868]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#DF2600]"></span>
                                    Deactivated
                                </span>
                            @endif
                        </div>

                        <div class="text-right">
                            <span class="text-[10px] uppercase font-bold text-[#686868] block">Joined</span>
                            <span class="text-xs text-[#686868]">{{ $member->created_at ? $member->created_at->format('M j, Y') : 'Pre-configured' }}</span>
                        </div>
                    </div>

                    <!-- Bottom Row: Action Buttons -->
                    <div class="flex items-center justify-between gap-2 pt-1">
                        <button type="button"
                                wire:click="openEditModal({{ $member->id }})"
                                class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-2 rounded-xl border border-[#EEEEEE] text-xs font-bold text-[#111111] hover:bg-[#FAFAFA] transition-colors">
                            <svg class="w-3.5 h-3.5 text-[#686868]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                            Edit & Password
                        </button>

                        @if (! $isCurrentUser)
                            <button type="button"
                                    wire:click="toggleStatus({{ $member->id }})"
                                    wire:confirm="Are you sure you want to change access for {{ $member->name }}?"
                                    class="px-3 py-2 rounded-xl border border-[#EEEEEE] text-xs font-bold {{ $member->is_active ? 'text-[#DF2600] hover:bg-[#FFF0EC]' : 'text-[#007D49] hover:bg-[#DAF4E7]' }} transition-colors">
                                {{ $member->is_active ? 'Deactivate' : 'Reactivate' }}
                            </button>

                            <button type="button"
                                    wire:click="openDeleteModal({{ $member->id }})"
                                    class="p-2 rounded-xl text-[#686868] hover:text-[#DF2600] hover:bg-[#FFF0EC] transition-colors border border-[#EEEEEE]"
                                    title="Revoke access">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-xs text-[#686868]">
                    No staff members matched your search or filter criteria.
                </div>
            @endforelse
        </div>

        <!-- 2. Desktop Table View (Visible on md+) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-sm min-w-[650px]">
                <thead>
                    <tr class="border-b border-[#EEEEEE] bg-[#FAFAFA] text-[11px] font-extrabold uppercase tracking-wider text-[#686868]">
                        <th class="py-3 px-6">Staff Member</th>
                        <th class="py-3 px-6">Role & Scope</th>
                        <th class="py-3 px-6">Status</th>
                        <th class="py-3 px-6">Created On</th>
                        <th class="py-3 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EEEEEE]">
                    @forelse ($staffMembers as $member)
                        @php
                            $isCurrentUser = $member->id === auth()->id();
                            $isManager = in_array($member->role, ['manager', 'owner'], true);
                            $initials = strtoupper(substr($member->name ?: $member->email, 0, 2));
                        @endphp
                        <tr class="hover:bg-[#FAFAFA] transition-colors {{ ! $member->is_active ? 'opacity-60 bg-[#FBFBFB]' : '' }}">
                            <!-- Staff Member Info -->
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full {{ $isManager ? 'bg-[#111111] text-white' : 'bg-[#E5F1FF] text-[#0066CC]' }} flex items-center justify-center text-xs font-extrabold shrink-0 shadow-xs">
                                        {{ $initials }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-extrabold text-[#111111] truncate">{{ $member->name ?: 'Staff Member' }}</span>
                                            @if ($isCurrentUser)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-extrabold bg-[#FFF0EC] text-[#DF2600]">
                                                    You
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-[#686868] truncate">{{ $member->email }}</div>
                                        @if ($member->phone)
                                            <div class="text-[11px] text-[#686868]">{{ $member->phone }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Role Badge -->
                            <td class="py-4 px-6">
                                @if ($isManager)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#111111] text-white">
                                        <svg class="w-3.5 h-3.5 text-[#DF2600]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                        Store Manager
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#EBF4FF] text-[#0066CC]">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                        </svg>
                                        Store Associate
                                    </span>
                                @endif
                            </td>

                            <!-- Status Pill -->
                            <td class="py-4 px-6">
                                @if ($member->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-[#DAF4E7] text-[#007D49]">
                                        <span class="w-2 h-2 rounded-full bg-[#007D49]"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-[#F6F6F6] text-[#686868]">
                                        <span class="w-2 h-2 rounded-full bg-[#DF2600]"></span>
                                        Deactivated
                                    </span>
                                @endif
                            </td>

                            <!-- Created Date -->
                            <td class="py-4 px-6 text-xs text-[#686868]">
                                {{ $member->created_at ? $member->created_at->format('M j, Y') : 'Pre-configured' }}
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-6 text-right">
                                <div class="inline-flex items-center gap-1.5 justify-end">
                                    <!-- Edit & Reset Password -->
                                    <button type="button"
                                            wire:click="openEditModal({{ $member->id }})"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-[#EEEEEE] text-xs font-bold text-[#111111] hover:bg-[#FAFAFA] transition-colors"
                                            title="Edit staff details and reset password">
                                        <svg class="w-3.5 h-3.5 text-[#686868]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                        Edit
                                    </button>

                                    <!-- Deactivate / Reactivate Toggle -->
                                    @if ($isCurrentUser)
                                        <button type="button"
                                                disabled
                                                class="px-2.5 py-1.5 rounded-lg text-xs font-bold text-[#CCCCCC] cursor-not-allowed"
                                                title="Security guard: You cannot deactivate your active session">
                                            Toggle
                                        </button>
                                    @else
                                        <button type="button"
                                                wire:click="toggleStatus({{ $member->id }})"
                                                wire:confirm="Are you sure you want to change access for {{ $member->name }}?"
                                                class="px-2.5 py-1.5 rounded-lg border border-[#EEEEEE] text-xs font-bold {{ $member->is_active ? 'text-[#DF2600] hover:bg-[#FFF0EC]' : 'text-[#007D49] hover:bg-[#DAF4E7]' }} transition-colors">
                                            {{ $member->is_active ? 'Deactivate' : 'Reactivate' }}
                                        </button>
                                    @endif

                                    <!-- Delete / Revoke Access -->
                                    @if ($isCurrentUser)
                                        <span class="w-8"></span>
                                    @else
                                        <button type="button"
                                                wire:click="openDeleteModal({{ $member->id }})"
                                                class="p-1.5 rounded-lg text-[#686868] hover:text-[#DF2600] hover:bg-[#FFF0EC] transition-colors"
                                                title="Revoke access and remove staff member">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-xs text-[#686868]">
                                No staff members matched your search or filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($staffMembers->hasPages())
            <div class="p-4 sm:p-5 border-t border-[#EEEEEE]">
                {{ $staffMembers->links() }}
            </div>
        @endif
    </div>

    <!-- Create Staff Modal -->
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="create-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-3 sm:px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 transition-opacity" wire:click="$set('showCreateModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all my-4 sm:my-8 align-middle max-w-lg w-full mx-auto" style="background-color: #ffffff;">
                    <!-- Modal Header -->
                    <div class="p-4 sm:p-6 border-b border-[#EEEEEE] flex items-center justify-between">
                        <div>
                            <h3 class="text-base sm:text-lg font-extrabold text-[#111111]" id="create-modal-title">
                                Add New Staff Member
                            </h3>
                            <p class="mt-0.5 text-xs text-[#686868]">
                                Generate login credentials and assign permissions for store operations.
                            </p>
                        </div>
                        <button type="button" wire:click="$set('showCreateModal', false)" class="text-[#686868] hover:text-[#111111] p-1.5 rounded-lg hover:bg-[#FAFAFA]">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Form -->
                    <form wire:submit="createStaff">
                        <div class="p-4 sm:p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                            <!-- Full Name -->
                            <div>
                                <label class="block text-xs font-bold text-[#111111] mb-1">Full Name *</label>
                                <input type="text"
                                       wire:model="create_name"
                                       placeholder="e.g. Bukola Adeleke"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                @error('create_name') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Email Address -->
                            <div>
                                <label class="block text-xs font-bold text-[#111111] mb-1">Email Address (Login Username) *</label>
                                <input type="email"
                                       wire:model="create_email"
                                       placeholder="e.g. bukola@isaleeeko.ca"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                @error('create_email') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Phone Number -->
                            <div>
                                <label class="block text-xs font-bold text-[#111111] mb-1">Contact Phone Number (Optional)</label>
                                <input type="text"
                                       wire:model="create_phone"
                                       placeholder="e.g. +1 (506) 555-0192"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                @error('create_phone') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Role Selection Cards -->
                            <div>
                                <label class="block text-xs font-bold text-[#111111] mb-2">Access Role & Permissions *</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <label class="p-3.5 rounded-xl border cursor-pointer transition-all flex flex-col justify-between {{ $create_role === 'associate' ? 'border-[#0066CC] bg-[#F0F7FF] ring-2 ring-[#0066CC]/20' : 'border-[#EEEEEE] bg-white hover:bg-[#FAFAFA]' }}">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-xs font-extrabold text-[#111111]">Store Associate</span>
                                            <input type="radio" wire:model="create_role" value="associate" class="text-[#0066CC] focus:ring-[#0066CC]">
                                        </div>
                                        <p class="text-[11px] text-[#686868] leading-tight">
                                            Can fulfill orders, update inventory stock, recipes, and categories. Square tokens and staff settings are hidden.
                                        </p>
                                    </label>

                                    <label class="p-3.5 rounded-xl border cursor-pointer transition-all flex flex-col justify-between {{ $create_role === 'manager' ? 'border-[#DF2600] bg-[#FFF0EC] ring-2 ring-[#DF2600]/20' : 'border-[#EEEEEE] bg-white hover:bg-[#FAFAFA]' }}">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-xs font-extrabold text-[#111111]">Store Manager</span>
                                            <input type="radio" wire:model="create_role" value="manager" class="text-[#DF2600] focus:ring-[#DF2600]">
                                        </div>
                                        <p class="text-[11px] text-[#686868] leading-tight">
                                            Full administrative privileges. Can manage other staff members, view payment gateways, Square tokens, and hours.
                                        </p>
                                    </label>
                                </div>
                                @error('create_role') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Temporary Password with Copy -->
                            <div class="p-4 rounded-xl bg-[#FAFAFA] border border-[#EEEEEE] space-y-2">
                                <label class="block text-xs font-bold text-[#111111]">Initial Temporary Password *</label>
                                <div class="flex items-center gap-2">
                                    <input type="text"
                                           wire:model="create_password"
                                           class="w-full px-3 py-2 rounded-lg border border-[#EEEEEE] bg-white text-sm font-mono text-[#111111] focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                    <button type="button"
                                            @click="navigator.clipboard.writeText($wire.create_password); copyStatus = true; setTimeout(() => copyStatus = false, 2000)"
                                            class="px-3 py-2 rounded-lg bg-white border border-[#EEEEEE] text-xs font-bold text-[#111111] hover:bg-[#FAFAFA] shrink-0 transition-colors">
                                        <span x-show="!copyStatus">Copy</span>
                                        <span x-show="copyStatus" class="text-[#007D49]">Copied!</span>
                                    </button>
                                </div>
                                <p class="text-[11px] text-[#686868]">
                                    Copy this temporary password and send it to your staff member. They will sign in at <strong class="text-[#111111]">/admin/login</strong> and can change it anytime in their profile.
                                </p>
                                @error('create_password') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Modal Actions -->
                        <div class="p-4 sm:p-6 border-t border-[#EEEEEE] bg-[#FAFAFA] flex items-center justify-end gap-3">
                            <button type="button"
                                    wire:click="$set('showCreateModal', false)"
                                    class="px-4 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-xs font-bold text-[#686868] hover:text-[#111111] transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#DF2600] text-white text-xs font-bold shadow-sm hover:bg-[#b82000] transition-colors disabled:opacity-50">
                                <span wire:loading wire:target="createStaff" class="animate-spin w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full"></span>
                                Create Staff Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Staff & Reset Password Modal -->
    @if ($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="edit-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-3 sm:px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 transition-opacity" wire:click="$set('showEditModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all my-4 sm:my-8 align-middle max-w-lg w-full mx-auto" style="background-color: #ffffff;">
                    <!-- Modal Header -->
                    <div class="p-4 sm:p-6 border-b border-[#EEEEEE] flex items-center justify-between">
                        <div>
                            <h3 class="text-base sm:text-lg font-extrabold text-[#111111]" id="edit-modal-title">
                                Edit Staff Member: {{ $edit_name }}
                            </h3>
                            <p class="mt-0.5 text-xs text-[#686868]">
                                Update role permissions or immediately reset employee password.
                            </p>
                        </div>
                        <button type="button" wire:click="$set('showEditModal', false)" class="text-[#686868] hover:text-[#111111] p-1.5 rounded-lg hover:bg-[#FAFAFA]">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Form -->
                    <form wire:submit="updateStaff">
                        <div class="p-4 sm:p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                            <!-- Full Name -->
                            <div>
                                <label class="block text-xs font-bold text-[#111111] mb-1">Full Name *</label>
                                <input type="text"
                                       wire:model="edit_name"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                @error('edit_name') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Email Address -->
                            <div>
                                <label class="block text-xs font-bold text-[#111111] mb-1">Email Address *</label>
                                <input type="email"
                                       wire:model="edit_email"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                @error('edit_email') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Phone Number -->
                            <div>
                                <label class="block text-xs font-bold text-[#111111] mb-1">Contact Phone Number</label>
                                <input type="text"
                                       wire:model="edit_phone"
                                       placeholder="+1 (506) 555-0192"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                @error('edit_phone') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Role Selection Cards -->
                            <div>
                                <label class="block text-xs font-bold text-[#111111] mb-2">Access Role & Scope *</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <label class="p-3.5 rounded-xl border cursor-pointer transition-all flex flex-col justify-between {{ $edit_role === 'associate' ? 'border-[#0066CC] bg-[#F0F7FF] ring-2 ring-[#0066CC]/20' : 'border-[#EEEEEE] bg-white hover:bg-[#FAFAFA]' }}">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-xs font-extrabold text-[#111111]">Store Associate</span>
                                            <input type="radio" wire:model="edit_role" value="associate" class="text-[#0066CC] focus:ring-[#0066CC]">
                                        </div>
                                        <p class="text-[11px] text-[#686868] leading-tight">
                                            Orders, Inventory, Recipes, Categories. Sensitive store keys hidden.
                                        </p>
                                    </label>

                                    <label class="p-3.5 rounded-xl border cursor-pointer transition-all flex flex-col justify-between {{ $edit_role === 'manager' ? 'border-[#DF2600] bg-[#FFF0EC] ring-2 ring-[#DF2600]/20' : 'border-[#EEEEEE] bg-white hover:bg-[#FAFAFA]' }}">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-xs font-extrabold text-[#111111]">Store Manager</span>
                                            <input type="radio" wire:model="edit_role" value="manager" class="text-[#DF2600] focus:ring-[#DF2600]">
                                        </div>
                                        <p class="text-[11px] text-[#686868] leading-tight">
                                            Full administrative privileges including Staff Roster and Square tokens.
                                        </p>
                                    </label>
                                </div>
                                @error('edit_role') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Password Override Section -->
                            <div class="p-4 rounded-xl bg-[#FFF9EA] border border-[#D58700]/20 space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold text-[#111111]">Reset Password (Optional)</label>
                                    <button type="button"
                                            wire:click="$set('edit_password', 'eko' + Math.floor(1000 + Math.random() * 9000) + '!')"
                                            class="text-[11px] font-bold text-[#D58700] hover:underline">
                                        Generate Random
                                    </button>
                                </div>
                                <input type="text"
                                       wire:model="edit_password"
                                       placeholder="Leave blank to keep existing password"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-sm font-mono text-[#111111] focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                <p class="text-[11px] text-[#686868]">
                                    Only enter a password if the staff member forgot their credentials and asked for a reset.
                                </p>
                                @error('edit_password') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Modal Actions -->
                        <div class="p-4 sm:p-6 border-t border-[#EEEEEE] bg-[#FAFAFA] flex items-center justify-end gap-3">
                            <button type="button"
                                    wire:click="$set('showEditModal', false)"
                                    class="px-4 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-xs font-bold text-[#686868] hover:text-[#111111] transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#DF2600] text-white text-xs font-bold shadow-sm hover:bg-[#b82000] transition-colors disabled:opacity-50">
                                <span wire:loading wire:target="updateStaff" class="animate-spin w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full"></span>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="delete-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-3 sm:px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 transition-opacity" wire:click="$set('showDeleteModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all my-4 sm:my-8 align-middle max-w-md w-full mx-auto" style="background-color: #ffffff;">
                    <div class="p-5 sm:p-6 space-y-4">
                        <div class="w-12 h-12 rounded-full bg-[#FFF0EC] text-[#DF2600] flex items-center justify-center mx-auto">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>

                        <div class="text-center">
                            <h3 class="text-base sm:text-lg font-extrabold text-[#111111]" id="delete-modal-title">
                                Revoke Access for {{ $deletingUserName }}?
                            </h3>
                            <p class="mt-2 text-xs text-[#686868] leading-relaxed">
                                Are you sure you want to remove this staff member? 
                                <br><br>
                                If they have processed customer orders or recorded transactions, their account will be safely deactivated and admin permissions revoked to preserve historical audit logs.
                            </p>
                        </div>
                    </div>

                    <div class="p-4 sm:p-6 border-t border-[#EEEEEE] bg-[#FAFAFA] flex items-center justify-end gap-3">
                        <button type="button"
                                wire:click="$set('showDeleteModal', false)"
                                class="px-4 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-xs font-bold text-[#686868] hover:text-[#111111] transition-colors">
                            Cancel
                        </button>
                        <button type="button"
                                wire:click="confirmDelete"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#DF2600] text-white text-xs font-bold shadow-sm hover:bg-[#b82000] transition-colors disabled:opacity-50">
                            <span wire:loading wire:target="confirmDelete" class="animate-spin w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full"></span>
                            Yes, Revoke Access
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
