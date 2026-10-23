<div class="sm:mx-auto sm:w-full sm:max-w-md">
    <!-- Brand Header -->
    <div class="text-center">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
            <img src="{{ asset('images/logo-small.svg') }}" alt="Ìsàlèè Èkó" class="h-12 w-auto mx-auto">
        </a>
        <h2 class="mt-4 text-2xl font-black tracking-tight text-[#111111]">
            Ìsàlèè Èkó
        </h2>
        <div class="mt-1 flex items-center justify-center gap-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-extrabold uppercase tracking-wider bg-[#FFF0EC] text-[#DF2600]">
                Operations Portal
            </span>
            <span class="text-xs text-[#686868]">· Saint John, NB</span>
        </div>
        <p class="mt-2 text-xs text-[#686868]">
            Sign in with authorized staff credentials to manage orders, inventory, and store settings.
        </p>
    </div>

    <!-- Sign-In Card -->
    <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-6 shadow-sm border border-[#EEEEEE] sm:rounded-2xl sm:px-10 space-y-6">
            <!-- Flash Alerts -->
            @if (session('warning'))
                <div class="p-3.5 rounded-xl bg-[#FFF9E6] border border-[#FFE8A3] flex items-start gap-3">
                    <svg class="w-5 h-5 text-[#B27B00] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span class="text-xs font-semibold text-[#805900] leading-snug">
                        {{ session('warning') }}
                    </span>
                </div>
            @endif

            @if (session('success'))
                <div class="p-3.5 rounded-xl bg-[#DAF4E7] border border-[#007D49]/20 flex items-start gap-3">
                    <svg class="w-5 h-5 text-[#007D49] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="text-xs font-semibold text-[#007D49] leading-snug">
                        {{ session('success') }}
                    </span>
                </div>
            @endif

            <form wire:submit="login" class="space-y-4">
                <!-- Email Address -->
                <div>
                    <label for="admin-email" class="block text-xs font-bold text-[#111111] mb-1">
                        Staff Email Address
                    </label>
                    <div class="relative">
                        <input id="admin-email"
                               type="email"
                               wire:model="email"
                               autocomplete="email"
                               autofocus
                               required
                               placeholder="e.g. admin@isaleeeko.ca"
                               class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] placeholder:text-[#686868]/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                        <svg class="w-4 h-4 text-[#686868] absolute left-3.5 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                        </svg>
                    </div>
                    @error('email')
                        <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="admin-password" class="block text-xs font-bold text-[#111111] mb-1">
                        Password
                    </label>
                    <div class="relative">
                        <input id="admin-password"
                               type="password"
                               wire:model="password"
                               autocomplete="current-password"
                               required
                               placeholder="••••••••••••"
                               class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] placeholder:text-[#686868]/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                        <svg class="w-4 h-4 text-[#686868] absolute left-3.5 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    @error('password')
                        <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Remember Me & Security Status -->
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox"
                               wire:model="remember"
                               class="w-4 h-4 rounded text-[#DF2600] focus:ring-[#DF2600] border-[#EEEEEE]">
                        <span class="text-xs font-semibold text-[#686868]">Keep me signed in</span>
                    </label>

                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-[#007D49]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#007D49]"></span>
                        Encrypted SSL
                    </span>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit"
                            wire:loading.attr="disabled"
                            style="background-color: #DF2600;"
                            class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-white font-extrabold text-sm shadow-md hover:opacity-95 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#DF2600] transition-all disabled:opacity-50">
                        <span wire:loading.remove wire:target="login">Sign In to Operations</span>
                        <span wire:loading wire:target="login" class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4 text-white" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Verifying Credentials...
                        </span>
                    </button>
                </div>
            </form>

            @if (app()->environment('local'))
                <!-- Developer & Staff Convenience Hint in Local Environment -->
                <div class="p-3 rounded-xl bg-[#FAFAFA] border border-[#EEEEEE] text-[11px] text-[#686868] flex items-center justify-between gap-2">
                    <div class="truncate">
                        <span class="font-bold text-[#111111]">Dev Credential:</span>
                        <span class="font-mono ml-1">admin@isaleeeko.ca</span> / <span class="font-mono">password123</span>
                    </div>
                    <button type="button"
                            wire:click="$set('email', 'admin@isaleeeko.ca'); $set('password', 'password123');"
                            class="px-2 py-0.5 rounded bg-white border border-[#EEEEEE] font-bold text-[#111111] hover:bg-[#F6F6F6] text-[10px] shrink-0">
                        Auto-Fill
                    </button>
                </div>
            @endif
        </div>

        <!-- Back to Storefront Link -->
        <div class="mt-6 text-center">
            <a href="{{ route('home') }}" class="text-xs font-semibold text-[#686868] hover:text-[#111111] transition-colors inline-flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Customer Storefront
            </a>
        </div>
    </div>
</div>
