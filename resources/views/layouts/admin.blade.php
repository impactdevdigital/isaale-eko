<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#FAFAFA]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Admin' }} — {{ config('app.name', 'Ìsàlèè Èkó') }} Operations</title>

    <!-- Google Fonts: DM Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">

    <!-- Styles & Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans antialiased text-[#111111] bg-[#FAFAFA]" x-data="{ mobileNavOpen: false }">
    @php
        $sidebarActiveOrders = \App\Models\Order::whereIn('status', [\App\Models\Order::STATUS_PLACED, \App\Models\Order::STATUS_PAID])
            ->where(fn ($q) => $q->whereNull('fulfilment_status')->orWhere('fulfilment_status', '!=', \App\Models\Order::FULFILMENT_COMPLETED))
            ->count();
        $sidebarOpenIssues = \App\Models\OrderIssue::whereIn('status', [\App\Models\OrderIssue::STATUS_NEW, \App\Models\OrderIssue::STATUS_REVIEWING, \App\Models\OrderIssue::STATUS_AWAITING_CUSTOMER, \App\Models\OrderIssue::STATUS_AWAITING_RETURN])->count();
        $sidebarCategoriesCount = \App\Models\Category::count();
        $sidebarRecipesCount = \App\Models\Recipe::count();
        $isManager = auth()->user()?->isManager();
        $adminName = auth()->user()->name ?? 'Staff';
        $initials = strtoupper(substr($adminName, 0, 2));
        $userRoleLabel = auth()->user()?->isManager() ? 'Store Manager' : 'Store Associate';
    @endphp


    <!-- Mobile Navigation Drawer (Off-Canvas) -->
    <div x-show="mobileNavOpen" 
         x-cloak
         class="fixed inset-0 z-50 lg:hidden flex" 
         role="dialog" 
         aria-modal="true">
        <!-- Backdrop with Blur -->
        <div x-show="mobileNavOpen"
             x-transition:enter="transition-opacity ease-linear duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/50 backdrop-blur-xs"
             @click="mobileNavOpen = false"
             aria-hidden="true"></div>

        <!-- Slide-over panel -->
        <div x-show="mobileNavOpen"
             x-transition:enter="transition ease-in-out duration-250 transform"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in-out duration-200 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="relative flex-1 w-full max-w-[300px] bg-white flex flex-col justify-between shadow-2xl z-10">
            <!-- Brand Header & Close button -->
            <div class="p-5 border-b border-[#EEEEEE] flex items-center justify-between bg-white">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3" @click="mobileNavOpen = false">
                    <img src="{{ asset('images/logo-small.svg') }}" alt="Ìsàlèè Èkó" class="h-9 w-auto">
                    <div>
                        <span class="block text-sm font-extrabold tracking-tight text-[#111111]">Ìsàlèè Èkó</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-[#FFF0EC] text-[#DF2600]">
                            Operations Portal
                        </span>
                    </div>
                </a>
                <button type="button" 
                        @click="mobileNavOpen = false"
                        class="p-2 rounded-xl text-[#686868] hover:text-[#111111] hover:bg-[#F6F6F6] transition-colors"
                        aria-label="Close navigation">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Mobile Navigation Links -->
            <nav class="flex-1 p-4 space-y-5 overflow-y-auto" style="-webkit-overflow-scrolling: touch;">
                <!-- Group: Executive Command -->
                <div>
                    <a href="{{ route('admin.dashboard') }}" 
                       @click="mobileNavOpen = false"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#111111] hover:bg-[#F6F6F6]' }}">
                        <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        Operations Overview
                    </a>
                </div>

                <!-- Group: Operations & Fulfillment -->
                <div>
                    <h3 class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-[#686868]">
                        Fulfillment & Operations
                    </h3>
                    <div class="mt-1.5 space-y-1">
                        <a href="{{ route('admin.orders') }}" 
                           @click="mobileNavOpen = false"
                           class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.orders') ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#111111] hover:bg-[#F6F6F6]' }}">
                            <span class="flex items-center gap-3">
                                <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                                Orders Queue
                            </span>
                            @if ($sidebarActiveOrders > 0)
                                <span class="text-[11px] font-bold bg-[#DF2600] text-white px-2 py-0.5 rounded-full">
                                    {{ $sidebarActiveOrders }}
                                </span>
                            @endif
                        </a>

                        <a href="{{ route('admin.issues') }}" 
                           @click="mobileNavOpen = false"
                           class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.issues') ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#111111] hover:bg-[#F6F6F6]' }}">
                            <span class="flex items-center gap-3">
                                <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                Issues Desk
                            </span>
                            @if ($sidebarOpenIssues > 0)
                                <span class="text-[11px] font-bold bg-[#DF2600] text-white px-2 py-0.5 rounded-full">
                                    {{ $sidebarOpenIssues }}
                                </span>
                            @endif
                        </a>
                    </div>
                </div>


                <!-- Group: Catalog & Content -->
                <div>
                    <h3 class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-[#686868]">
                        Catalog & Content
                    </h3>
                    <div class="mt-1.5 space-y-1">
                        <a href="{{ route('admin.products') }}" 
                           @click="mobileNavOpen = false"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.products') ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#111111] hover:bg-[#F6F6F6]' }}">
                            <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            Products & Inventory
                        </a>

                        <a href="{{ route('admin.categories') }}" 
                           @click="mobileNavOpen = false"
                           class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.categories') ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#111111] hover:bg-[#F6F6F6]' }}">
                            <span class="flex items-center gap-3">
                                <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                Categories & Sub-Cats
                            </span>
                            @if ($sidebarCategoriesCount > 0)
                                <span class="text-[11px] font-bold bg-[#F6F6F6] text-[#111111] px-2 py-0.5 rounded-full">
                                    {{ $sidebarCategoriesCount }}
                                </span>
                            @endif
                        </a>

                        <a href="{{ route('admin.recipes') }}" 
                           @click="mobileNavOpen = false"
                           class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.recipes') ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#111111] hover:bg-[#F6F6F6]' }}">
                            <span class="flex items-center gap-3">
                                <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                Kitchen Recipes
                            </span>
                            @if ($sidebarRecipesCount > 0)
                                <span class="text-[11px] font-bold bg-[#F6F6F6] text-[#111111] px-2 py-0.5 rounded-full">
                                    {{ $sidebarRecipesCount }}
                                </span>
                            @endif
                        </a>
                    </div>
                </div>

                <!-- Group: Store Controls -->
                <div>
                    <h3 class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-[#686868]">
                        Store Controls
                    </h3>
                    <div class="mt-1.5 space-y-1">
                        @if ($isManager)
                            <a href="{{ route('admin.staff') }}" 
                               @click="mobileNavOpen = false"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.staff') ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#111111] hover:bg-[#F6F6F6]' }}">
                                <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                Staff & Access
                            </a>

                            <a href="{{ route('admin.settings') }}" 
                               @click="mobileNavOpen = false"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.settings') ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#111111] hover:bg-[#F6F6F6]' }}">
                                <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                </svg>
                                Store Settings
                            </a>
                        @endif

                        <a href="{{ route('admin.hours') }}" 
                           @click="mobileNavOpen = false"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.hours') ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#111111] hover:bg-[#F6F6F6]' }}">
                            <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Hours & Closures
                        </a>

                        @if ($isManager)
                            <a href="{{ route('admin.payments') }}" 
                               @click="mobileNavOpen = false"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.payments') ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#111111] hover:bg-[#F6F6F6]' }}">
                                <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                Payment Methods
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Storefront link shortcut -->
                <div class="pt-2 border-t border-[#EEEEEE]">
                    <a href="{{ route('home') }}" 
                       target="_blank"
                       class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold text-[#686868] hover:text-[#111111] hover:bg-[#F6F6F6]">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#DF2600]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            Open Storefront
                        </span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </nav>

            <!-- Mobile Drawer User Profile & Logout Footer -->
            <div class="p-4 border-t border-[#EEEEEE] bg-[#FAFAFA] space-y-3">
                @auth
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-full bg-[#111111] text-white flex items-center justify-center text-xs font-bold shrink-0">
                                {{ $initials }}
                            </div>
                            <div class="min-w-0">
                                <span class="block text-xs font-bold text-[#111111] truncate">{{ $adminName }}</span>
                                <span class="block text-[10px] text-[#686868] font-semibold">{{ $userRoleLabel }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-1 shrink-0">
                            <button type="button"
                                    x-data
                                    @click="mobileNavOpen = false; $dispatch('open-change-password-modal')"
                                    class="p-2 rounded-lg text-[#686868] hover:text-[#111111] hover:bg-white transition-colors"
                                    title="Change Password">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                </svg>
                            </button>
                            <form method="POST" action="{{ route('admin.logout') }}" class="inline">
                                @csrf
                                <button type="submit"
                                        class="p-2 rounded-lg text-[#686868] hover:text-[#DF2600] hover:bg-[#FFF0EC] transition-colors"
                                        title="Sign Out">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth
                <div class="flex items-center gap-2 text-[11px] font-semibold text-[#686868]">
                    <span class="w-2 h-2 rounded-full bg-[#007D49]"></span>
                    <span>Square Catalog: Ready</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Layout Container -->
    <div class="min-h-full flex flex-col lg:flex-row">
        <!-- Desktop Fixed Sidebar Navigation (hidden on mobile, visible on lg+) -->
        <aside class="hidden lg:flex w-72 bg-white border-r border-[#EEEEEE] flex-col shrink-0 min-h-screen sticky top-0 h-screen">
            <!-- Brand Header -->
            <div class="p-6 border-b border-[#EEEEEE] flex items-center justify-between">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                    <img src="{{ asset('images/logo-small.svg') }}" alt="Ìsàlèè Èkó" class="h-10 w-auto">
                    <div>
                        <span class="block text-base font-extrabold tracking-tight text-[#111111]">Ìsàlèè Èkó</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-[#FFF0EC] text-[#DF2600]">
                            Operations
                        </span>
                    </div>
                </a>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 p-4 space-y-6 overflow-y-auto">
                <!-- Group: Executive Command -->
                <div>
                    <a href="{{ route('admin.dashboard') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#111111] hover:bg-[#F6F6F6]' }}">
                        <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        Operations Overview
                    </a>
                </div>

                <!-- Group: Operations & Fulfillment -->
                <div>
                    <h3 class="px-3 text-[11px] font-extrabold uppercase tracking-wider text-[#686868]">
                        Fulfillment & Operations
                    </h3>
                    <div class="mt-2 space-y-1">
                        <a href="{{ route('admin.orders') }}" 
                           class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.orders') ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#111111] hover:bg-[#F6F6F6]' }}">
                            <span class="flex items-center gap-3">
                                <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                                Orders Queue
                            </span>
                            @if ($sidebarActiveOrders > 0)
                                <span class="text-[11px] font-bold bg-[#DF2600] text-white px-2 py-0.5 rounded-full">
                                    {{ $sidebarActiveOrders }}
                                </span>
                            @endif
                        </a>

                        <a href="{{ route('admin.issues') }}" 
                           class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.issues') ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#111111] hover:bg-[#F6F6F6]' }}">
                            <span class="flex items-center gap-3">
                                <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                Issues Desk
                            </span>
                            @if ($sidebarOpenIssues > 0)
                                <span class="text-[11px] font-bold bg-[#DF2600] text-white px-2 py-0.5 rounded-full">
                                    {{ $sidebarOpenIssues }}
                                </span>
                            @endif
                        </a>
                    </div>
                </div>


                <!-- Group: Catalog & Content -->
                <div>
                    <h3 class="px-3 text-[11px] font-extrabold uppercase tracking-wider text-[#686868]">
                        Catalog & Content
                    </h3>
                    <div class="mt-2 space-y-1">
                        <a href="{{ route('admin.products') }}" 
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.products') ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#111111] hover:bg-[#F6F6F6]' }}">
                            <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            Products & Inventory
                        </a>

                        <a href="{{ route('admin.categories') }}" 
                           class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.categories') ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#111111] hover:bg-[#F6F6F6]' }}">
                            <span class="flex items-center gap-3">
                                <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                Categories & Sub-Cats
                            </span>
                            @if ($sidebarCategoriesCount > 0)
                                <span class="text-[11px] font-bold bg-[#F6F6F6] text-[#111111] px-2 py-0.5 rounded-full">
                                    {{ $sidebarCategoriesCount }}
                                </span>
                            @endif
                        </a>

                        <a href="{{ route('admin.recipes') }}" 
                           class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.recipes') ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#111111] hover:bg-[#F6F6F6]' }}">
                            <span class="flex items-center gap-3">
                                <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                Kitchen Recipes
                            </span>
                            @if ($sidebarRecipesCount > 0)
                                <span class="text-[11px] font-bold bg-[#F6F6F6] text-[#111111] px-2 py-0.5 rounded-full">
                                    {{ $sidebarRecipesCount }}
                                </span>
                            @endif
                        </a>

                        <div class="flex items-center justify-between px-3 py-2.5 text-sm font-medium text-[#686868] opacity-60">
                            <span class="flex items-center gap-3">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                                Knowledge Base
                            </span>
                            <span class="text-[10px] uppercase font-bold bg-[#F6F6F6] text-[#686868] px-2 py-0.5 rounded">Documentation</span>
                        </div>
                    </div>
                </div>

                <!-- Group: Store Controls (Settings, Hours, Payments, Staff) -->
                <div>
                    <h3 class="px-3 text-[11px] font-extrabold uppercase tracking-wider text-[#686868]">
                        Store Controls
                    </h3>
                    <div class="mt-2 space-y-1">
                        @if ($isManager)
                            <a href="{{ route('admin.staff') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.staff') ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#111111] hover:bg-[#F6F6F6]' }}">
                                <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                Staff & Access
                            </a>

                            <a href="{{ route('admin.settings') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.settings') ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#111111] hover:bg-[#F6F6F6]' }}">
                                <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                </svg>
                                Store Settings
                            </a>
                        @endif

                        <a href="{{ route('admin.hours') }}" 
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.hours') ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#111111] hover:bg-[#F6F6F6]' }}">
                            <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Hours & Closures
                        </a>

                        @if ($isManager)
                            <a href="{{ route('admin.payments') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.payments') ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#111111] hover:bg-[#F6F6F6]' }}">
                                <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                Payment Methods
                            </a>
                        @endif
                    </div>
                </div>
            </nav>

            <!-- Store Quick Status Footer -->
            <div class="p-4 border-t border-[#EEEEEE] bg-[#FAFAFA]">
                <div class="flex items-center gap-2 text-xs font-semibold text-[#686868]">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#007D49]"></span>
                    <span>Square Catalog: Ready</span>
                </div>
                <div class="mt-1 text-[11px] text-[#686868]">
                    Currency: <span class="font-bold text-[#111111]">{{ config('shop.currency', 'CAD') }}</span> · Timezone: <span class="font-bold text-[#111111]">{{ config('shop.timezone', 'America/Moncton') }}</span>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Mobile Sticky Topbar (visible only on mobile/tablet < lg) -->
            <header class="h-16 bg-white border-b border-[#EEEEEE] px-4 flex items-center justify-between lg:hidden sticky top-0 z-30 shrink-0">
                <div class="flex items-center gap-3">
                    <button type="button" 
                            @click="mobileNavOpen = true"
                            class="p-2 -ml-1 rounded-xl text-[#111111] hover:bg-[#F6F6F6] transition-colors relative"
                            aria-label="Open operations menu">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        @if ($sidebarActiveOrders > 0)
                            <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 rounded-full bg-[#DF2600] ring-2 ring-white"></span>
                        @endif
                    </button>

                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                        <img src="{{ asset('images/logo-small.svg') }}" alt="Ìsàlèè Èkó" class="h-8 w-auto">
                        <span class="text-sm font-black tracking-tight text-[#111111]">Ìsàlèè Èkó</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-[#FFF0EC] text-[#DF2600]">
                            Ops
                        </span>
                    </a>
                </div>

                <div class="flex items-center gap-2">
                    @if ($sidebarActiveOrders > 0)
                        <a href="{{ route('admin.orders') }}"
                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-[#FFF0EC] text-[#DF2600]">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#DF2600] animate-pulse"></span>
                            <span>{{ $sidebarActiveOrders }}</span>
                        </a>
                    @endif

                    @auth
                        <div class="flex items-center gap-1.5 pl-2 border-l border-[#EEEEEE]">
                            <button type="button"
                                    x-data
                                    @click="$dispatch('open-change-password-modal')"
                                    class="p-1.5 rounded-lg text-[#686868] hover:text-[#111111] hover:bg-[#F6F6F6] transition-colors"
                                    title="Change My Password">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                </svg>
                            </button>

                            <form method="POST" action="{{ route('admin.logout') }}" class="inline">
                                @csrf
                                <button type="submit"
                                        class="p-1.5 rounded-lg text-[#686868] hover:text-[#DF2600] hover:bg-[#FFF0EC] transition-colors"
                                        title="Sign Out">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @endauth
                </div>
            </header>

            <!-- Desktop Topbar (hidden on mobile, visible on lg+) -->
            <header class="hidden lg:flex h-16 bg-white border-b border-[#EEEEEE] px-6 lg:px-10 items-center justify-between sticky top-0 z-20 shrink-0">
                <div class="flex items-center gap-3">
                    <h1 class="text-lg font-extrabold text-[#111111]">
                        {{ $header ?? 'Store Management' }}
                    </h1>
                </div>

                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-[#DAF4E7] text-[#007D49]">
                        ● Active Store
                    </span>

                    @auth
                        <div class="flex items-center gap-3 pl-3 border-l border-[#EEEEEE]">
                            <div class="w-8 h-8 rounded-full bg-[#111111] text-white flex items-center justify-center text-xs font-bold" title="{{ $adminName }}">
                                {{ $initials }}
                            </div>
                            <div class="hidden sm:block text-left">
                                <span class="block text-xs font-bold text-[#111111] leading-tight">{{ $adminName }}</span>
                                <span class="block text-[10px] text-[#686868] leading-tight font-semibold">{{ $userRoleLabel }}</span>
                            </div>

                            <!-- Change Password Button (Triggers Self-Service Modal) -->
                            <button type="button"
                                    x-data
                                    @click="$dispatch('open-change-password-modal')"
                                    class="p-1.5 rounded-lg text-[#686868] hover:text-[#111111] hover:bg-[#F6F6F6] transition-colors"
                                    title="Change My Password">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                </svg>
                            </button>

                            <form method="POST" action="{{ route('admin.logout') }}" class="inline">
                                @csrf
                                <button type="submit"
                                        class="p-1.5 rounded-lg text-[#686868] hover:text-[#DF2600] hover:bg-[#FFF0EC] transition-colors"
                                        title="Sign Out of Operations">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="w-8 h-8 rounded-full bg-[#111111] text-white flex items-center justify-center text-xs font-bold">
                            AD
                        </div>
                    @endauth
                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 p-4 sm:p-6 lg:p-10 min-w-0">
                {{ $slot }}
            </main>
        </div>
    </div>

    @auth
        @livewire('admin.change-password-modal')
    @endauth
    @livewireScripts
</body>
</html>
