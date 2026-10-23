<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#FAFAFA]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
<body class="h-full font-sans antialiased text-[#111111] bg-[#FAFAFA]">
    <div class="min-h-full flex flex-col lg:flex-row">
        <!-- Sidebar Navigation -->
        <aside class="w-full lg:w-72 bg-white border-r border-[#EEEEEE] flex flex-col shrink-0">
            <!-- Brand Header -->
            <div class="p-6 border-b border-[#EEEEEE] flex items-center justify-between">
                <a href="{{ route('admin.settings') }}" class="flex items-center gap-3">
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
                <!-- Group: Store Settings -->
                <div>
                    <h3 class="px-3 text-[11px] font-extrabold uppercase tracking-wider text-[#686868]">
                        Store Controls
                    </h3>
                    <div class="mt-2 space-y-1">
                        <a href="{{ route('admin.settings') }}" 
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.settings') ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#111111] hover:bg-[#F6F6F6]' }}">
                            <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                            </svg>
                            Store Settings
                        </a>

                        <a href="{{ route('admin.hours') }}" 
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.hours') ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#111111] hover:bg-[#F6F6F6]' }}">
                            <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Hours & Closures
                        </a>

                        <a href="{{ route('admin.payments') }}" 
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.payments') ? 'bg-[#FFF0EC] text-[#DF2600]' : 'text-[#111111] hover:bg-[#F6F6F6]' }}">
                            <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            Payment Methods
                        </a>
                    </div>
                </div>

                <!-- Group: Operations -->
                <div>
                    <h3 class="px-3 text-[11px] font-extrabold uppercase tracking-wider text-[#686868]">
                        Fulfillment & Claims
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
                            @php
                                $sidebarActiveOrders = \App\Models\Order::whereIn('status', [\App\Models\Order::STATUS_PLACED, \App\Models\Order::STATUS_PAID])
                                    ->where(fn ($q) => $q->whereNull('fulfilment_status')->orWhere('fulfilment_status', '!=', \App\Models\Order::FULFILMENT_COMPLETED))
                                    ->count();
                            @endphp
                            @if ($sidebarActiveOrders > 0)
                                <span class="text-[11px] font-bold bg-[#DF2600] text-white px-2 py-0.5 rounded-full">
                                    {{ $sidebarActiveOrders }}
                                </span>
                            @endif
                        </a>

                        <div class="flex items-center justify-between px-3 py-2.5 text-sm font-medium text-[#686868] opacity-60">
                            <span class="flex items-center gap-3">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                Issues Desk
                            </span>
                            <span class="text-[10px] uppercase font-bold bg-[#F6F6F6] text-[#686868] px-2 py-0.5 rounded">Phase E11</span>
                        </div>
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
                            @php
                                $sidebarCategoriesCount = \App\Models\Category::count();
                            @endphp
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
                            @php
                                $sidebarRecipesCount = \App\Models\Recipe::count();
                            @endphp
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
            <!-- Topbar -->
            <header class="h-16 bg-white border-b border-[#EEEEEE] px-6 lg:px-10 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h1 class="text-lg font-extrabold text-[#111111]">
                        {{ $header ?? 'Store Management' }}
                    </h1>
                </div>

                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-[#DAF4E7] text-[#007D49]">
                        ● Active Store
                    </span>
                    <div class="w-8 h-8 rounded-full bg-[#111111] text-white flex items-center justify-center text-xs font-bold">
                        AD
                    </div>
                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 p-6 lg:p-10">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
