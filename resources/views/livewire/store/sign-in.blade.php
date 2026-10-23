<div x-data="{ open: $wire.entangle('open') }" @keydown.escape.window="open && $wire.close()">
    <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-black/30" @click="$wire.close()"></div>

    <aside
        x-show="open"
        x-cloak
        x-transition:enter="transition duration-300 ease-out"
        x-transition:enter-start="translate-x-full"
        x-transition:leave="transition duration-200 ease-in"
        x-transition:leave-end="translate-x-full"
        x-trap.noscroll="open"
        role="dialog"
        aria-modal="true"
        aria-labelledby="sign-in-title"
        class="fixed inset-y-0 right-0 z-50 flex w-full max-w-[433px] flex-col overflow-y-auto bg-surface px-6 pb-10 pt-[52px] md:px-8"
    >
        <button type="button" wire:click="close" class="absolute right-4 top-4 flex size-10 items-center justify-center rounded-full bg-surface shadow-[0_0_16px_rgb(0_0_0/0.15)]" aria-label="Close sign in">
            <x-store.icon name="close" />
        </button>

        <img src="{{ asset('images/logo-small.svg') }}" alt="Ìsàlèè Èkó" class="mx-auto h-[66px] w-[107px]">

        @if ($step === 'contact')
            <h2 id="sign-in-title" class="mt-14 text-center text-[28px] leading-9 font-bold">Welcome To Ìsàlèè Èkó</h2>
            <p class="mt-2 text-center text-body text-body-muted">
                {{ $channel === 'email' ? 'Enter your email — we’ll send you a confirmation code.' : 'Enter your mobile number — we’ll text you a confirmation code.' }}
            </p>

            <div class="mt-8 grid grid-cols-2 gap-1 rounded-sm bg-grayscale-0 p-1" role="tablist" aria-label="Sign in with">
                @foreach (['phone' => 'Phone', 'email' => 'Email'] as $value => $label)
                    <button type="button" role="tab" aria-selected="{{ $channel === $value ? 'true' : 'false' }}" wire:click="useChannel('{{ $value }}')"
                            @class(['flex h-9 items-center justify-center gap-2 rounded-sm text-[14px]', 'bg-surface font-bold text-ink shadow-sm' => $channel === $value, 'text-body-muted' => $channel !== $value])>
                        <x-store.icon :name="$value === 'phone' ? 'phone' : 'mail'" class="size-4" />
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <form wire:submit="sendCode" class="mt-6 flex flex-col gap-6">
                @if ($channel === 'email')
                    <div>
                        <label for="sign-in-email" class="mb-2 block text-body-sm text-grayscale-80">Email address</label>
                        <input id="sign-in-email" type="email" wire:model="email" autocomplete="email" placeholder="name@example.com"
                               @class(['h-11 w-full rounded-sm border bg-grayscale-0 px-4 text-[16px] focus:border-ink focus:outline-none', 'border-price-40' => $errors->has('email'), 'border-grayscale-30' => ! $errors->has('email')])>
                        @error('email') <p class="mt-1.5 text-body-xs text-price-40">{{ $message }}</p> @enderror
                    </div>
                @else
                    <div>
                        <label for="sign-in-phone" class="mb-2 block text-body-sm text-grayscale-80">Mobile number</label>
                        <input id="sign-in-phone" type="tel" wire:model="phone" autocomplete="tel" placeholder="+1 (555) 000-0000"
                               @class(['h-11 w-full rounded-sm border bg-grayscale-0 px-4 text-[16px] focus:border-ink focus:outline-none', 'border-price-40' => $errors->has('phone'), 'border-grayscale-30' => ! $errors->has('phone')])>
                        @error('phone') <p class="mt-1.5 text-body-xs text-price-40">{{ $message }}</p> @enderror
                    </div>
                @endif

                <button type="submit" wire:loading.attr="disabled" class="h-12 rounded-full bg-ink text-[15px] font-bold text-white disabled:opacity-60">Get Confirmation Code</button>
            </form>

            <ul class="mt-10 grid grid-cols-3 gap-3 text-[11px] leading-[14px] text-body-muted">
                <li class="flex items-start gap-1.5"><x-store.icon name="box" class="size-4 shrink-0 text-brand-purple" /> Trusted Product Brand</li>
                <li class="flex items-start gap-1.5"><x-store.icon name="shield" class="size-4 shrink-0 text-brand-purple" /> Guaranteed Authenticity</li>
                <li class="flex items-start gap-1.5"><x-store.icon name="bus" class="size-4 shrink-0 text-brand-purple" /> Delivered with care</li>
            </ul>
        @elseif ($step === 'code')
            <h2 id="sign-in-title" class="mt-14 text-center text-[28px] leading-9 font-bold">Enter The 6-Digit Code</h2>
            <p class="mt-2 text-center text-body text-body-muted">Sent to <strong class="text-ink">{{ $codeDestination }}</strong></p>

            <span class="mx-auto mt-6 flex size-11 items-center justify-center rounded-sm bg-grayscale-0">
                <x-store.icon :name="$codeChannel === 'email' ? 'mail' : 'phone'" class="size-5" />
            </span>

            <form wire:submit="verifyCode" class="mt-6 flex flex-col gap-6"
                  x-data="{ left: 0, tick() { this.left = Math.max(0, {{ $resendAt }} - Math.floor(Date.now() / 1000)) } }"
                  x-init="tick(); setInterval(() => tick(), 1000)">
                <x-store.otp-input />

                <button type="submit" wire:loading.attr="disabled" class="h-12 rounded-full bg-ink text-[15px] font-bold text-white disabled:opacity-60">Continue</button>

                <p class="text-center text-body-sm text-body-muted">
                    Didn’t receive it?
                    <span x-show="left > 0">Resend in <span x-text="`00:${String(left).padStart(2, '0')}`"></span></span>
                    <button type="button" x-show="left === 0" x-cloak wire:click="resendCode" @click="$dispatch('otp-clear')" class="font-bold text-brand-purple">Resend OTP</button>
                </p>
                <button type="button" wire:click="back" class="text-center text-body-sm font-bold underline">Use a different {{ $codeChannel === 'email' ? 'email' : 'number' }}</button>
            </form>
        @else
            <h2 id="sign-in-title" class="mt-14 text-center text-[28px] leading-9 font-bold">Welcome to the family</h2>
            <p class="mt-2 text-center text-body text-body-muted">Just a couple of details to make your Ìsàlèè Èkó experience more personal.</p>

            <p class="mt-8 flex items-center gap-3 rounded-sm bg-grayscale-0 px-4 py-3 text-body-sm">
                <span class="flex size-5 items-center justify-center rounded-full bg-ink text-white"><x-store.icon name="check" class="size-3" /></span>
                Contact verified. You’re nearly there!
            </p>

            <form wire:submit="completeAccount" class="mt-6 flex flex-col gap-6">
                <div>
                    <label for="sign-in-name" class="mb-2 block text-body-sm text-grayscale-80">Full name <span aria-hidden="true">*</span></label>
                    <input id="sign-in-name" type="text" wire:model="name" autocomplete="name" placeholder="Enter your full name" autofocus
                           @class(['h-11 w-full rounded-sm border bg-grayscale-0 px-4 text-[16px] focus:border-ink focus:outline-none', 'border-price-40' => $errors->has('name'), 'border-grayscale-30' => ! $errors->has('name')])>
                    @error('name') <p class="mt-1.5 text-body-xs text-price-40">{{ $message }}</p> @enderror
                </div>
                <button type="submit" wire:loading.attr="disabled" class="h-12 rounded-full bg-ink text-[15px] font-bold text-white disabled:opacity-60">Complete my account</button>
            </form>
        @endif
    </aside>
</div>
