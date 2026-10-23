@php
    use App\Livewire\Store\Account\AccountManagement;

    $name = str($user->name)->squish();
    $label = $changing === 'phone' ? 'phone' : 'email';
    $current = $changing ? $user->{$changing} : null;
@endphp

<div>
    <x-store.account-shell active="manage" :crumbs="['Your account' => route('account'), 'Account management' => null]">
        <h1 class="text-h2-mobile md:text-h2">Account Management</h1>
        <p class="mt-2 text-lead-mobile font-normal text-body-muted md:text-lead">Manage the details you use to sign in and keep your account secure.</p>

        @if ($notice)
            <p class="mt-6 flex items-center gap-3 rounded-sm bg-stock-0 px-4 py-3 text-body-sm text-stock-50" role="status">
                <x-store.icon name="check" /> {{ $notice }}
            </p>
        @endif

        <section class="mt-8 rounded-sm border border-grayscale-40" aria-labelledby="name-title">
            <div class="flex items-center justify-between border-b border-grayscale-30 px-6 py-4">
                <h2 id="name-title" class="text-[18px] font-bold">Your name</h2>
                <button type="button" wire:click="editName" class="rounded-full border border-grayscale-40 px-4 py-2 text-[13px] font-bold hover:border-ink">Edit</button>
            </div>
            <dl class="grid gap-4 px-6 py-6 sm:grid-cols-2">
                <div>
                    <dt class="text-[11px] font-medium uppercase tracking-[0.3px] text-grayscale-80">First name</dt>
                    <dd class="mt-1.5 text-[16px]">{{ $name->before(' ') }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-medium uppercase tracking-[0.3px] text-grayscale-80">Last name</dt>
                    <dd class="mt-1.5 text-[16px]">{{ $name->contains(' ') ? $name->after(' ') : '—' }}</dd>
                </div>
            </dl>
        </section>

        <section class="mt-8 rounded-sm border border-grayscale-40" aria-labelledby="sign-in-title">
            <div class="border-b border-grayscale-30 px-6 py-5">
                <h2 id="sign-in-title" class="text-[18px] font-bold">Sign-in details</h2>
                <p class="mt-1.5 text-body text-body-muted">For your security, changes to your email or phone require verification of both your current and new details.</p>
            </div>
            @foreach (['email' => 'Email address', 'phone' => 'Phone number'] as $field => $title)
                <div @class(['flex items-center justify-between gap-4 px-6 py-5', 'border-t border-grayscale-30' => ! $loop->first])>
                    <div class="min-w-0">
                        <p class="text-[16px] font-bold">{{ $title }}</p>
                        <p class="mt-1.5 flex flex-wrap items-center gap-3 text-[15px] text-grayscale-80">
                            @if ($user->{$field})
                                <span class="truncate">{{ $user->{$field} }}</span>
                                @if ($user->{$field.'_verified_at'})
                                    <span class="rounded-[4px] bg-grayscale-10 px-2 py-0.5 text-[12px] font-bold text-ink">Verified</span>
                                @endif
                            @else
                                <span class="text-body-muted">Not added</span>
                            @endif
                        </p>
                    </div>
                    <button type="button" wire:click="change('{{ $field }}')" class="shrink-0 rounded-full border border-grayscale-40 px-4 py-2 text-[13px] font-bold hover:border-ink">
                        {{ $user->{$field} ? 'Edit' : 'Add' }}
                    </button>
                </div>
            @endforeach
        </section>

        <section class="mt-8 flex flex-col gap-4 border-t border-grayscale-40 pt-8 sm:flex-row sm:items-center sm:justify-between" aria-labelledby="close-title">
            <div>
                <h2 id="close-title" class="text-[18px] font-bold">Close account</h2>
                <p class="mt-1.5 text-body text-body-muted">Permanently close your account. You’ll lose access to your saved addresses, order history and rewards. This cannot be undone.</p>
            </div>
            <button type="button" wire:click="confirmClose" class="shrink-0 rounded-full border border-grayscale-40 px-5 py-3 text-[15px] font-bold hover:border-ink">Close account</button>
        </section>
    </x-store.account-shell>

    @if ($modal === 'name')
        <x-store.modal title="Edit your name" subtitle="This is how we’ll greet you and label your orders." width="max-w-[520px]">
            <form wire:submit="saveName" class="grid gap-5 sm:grid-cols-2">
                <x-store.field model="firstName" label="First name" autocomplete="given-name" />
                <x-store.field model="lastName" label="Last name (optional)" autocomplete="family-name" />
                <div class="flex justify-end gap-3 sm:col-span-2">
                    <button type="button" wire:click="close" class="rounded-full border border-grayscale-40 px-5 py-3 text-[15px] font-bold">Cancel</button>
                    <button type="submit" class="rounded-full bg-ink px-5 py-3 text-[15px] font-bold text-white">Save name</button>
                </div>
            </form>
        </x-store.modal>
    @endif

    @if (in_array($modal, ['verify-current', 'verify-new'], true))
        @php
            $isCurrent = $modal === 'verify-current';
            $sentTo = $codeDestination ? AccountManagement::mask($changing, $codeDestination) : '';
        @endphp
        <x-store.modal :title="($isCurrent ? 'Verify your current ' : 'Verify your new ').$label"
                       :subtitle="($changing === 'phone' ? 'We sent an SMS code to ' : 'We sent a code to ').$sentTo.'. Enter it below to continue.'"
                       width="max-w-[420px]">
            <form wire:submit="{{ $isCurrent ? 'verifyCurrent' : 'verifyNew' }}" class="flex flex-col gap-5"
                  x-data="{ left: 0, tick() { this.left = Math.max(0, {{ $resendAt }} - Math.floor(Date.now() / 1000)) } }"
                  x-init="tick(); setInterval(() => tick(), 1000)">
                <x-store.otp-input wire:key="otp-{{ $modal }}" />
                <div>
                    <p x-show="left > 0" class="text-body text-grayscale-80">Resend code in <span x-text="`00:${String(left).padStart(2, '0')}`"></span></p>
                    <button type="button" x-show="left === 0" x-cloak wire:click="resendCode" @click="$dispatch('otp-clear')" class="text-body font-bold underline">Resend code</button>
                    <p class="mt-2 text-body-xs text-body-muted">Didn’t receive it? You can request another code when the timer ends.</p>
                </div>
                <div class="flex justify-end gap-3 border-t border-grayscale-30 pt-5">
                    <button type="button" wire:click="close" class="rounded-full border border-grayscale-40 px-5 py-3 text-[15px] font-bold">Cancel</button>
                    <button type="submit" class="rounded-full bg-ink px-5 py-3 text-[15px] font-bold text-white">Confirm</button>
                </div>
            </form>
        </x-store.modal>
    @endif

    @if ($modal === 'enter-new')
        <x-store.modal :title="'Enter your new '.($changing === 'phone' ? 'phone number' : 'email')"
                       :subtitle="($current ? 'Your current '.$label.' has been verified. ' : '').'We’ll send a code to your new '.($changing === 'phone' ? 'phone number' : 'email').' to confirm it belongs to you.'"
                       width="max-w-[420px]">
            <form wire:submit="sendNewCode" class="flex flex-col gap-5">
                <x-store.field model="newValue" :label="$changing === 'phone' ? 'Mobile number' : 'Email address'" :type="$changing === 'phone' ? 'tel' : 'email'"
                               :autocomplete="$changing === 'phone' ? 'tel' : 'email'" :placeholder="$changing === 'phone' ? '+1 (555) 000-0000' : 'name@example.com'" />
                @if ($current)
                    <p class="-mt-2 text-body-xs text-body-muted">Current {{ $label }}: {{ $current }}<br>Your current {{ $label }} stays in use until the new one is verified.</p>
                @endif
                <div class="flex justify-end gap-3 border-t border-grayscale-30 pt-5">
                    <button type="button" wire:click="close" class="rounded-full border border-grayscale-40 px-5 py-3 text-[15px] font-bold">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" class="rounded-full bg-ink px-5 py-3 text-[15px] font-bold text-white disabled:opacity-60">Send verification code</button>
                </div>
            </form>
        </x-store.modal>
    @endif

    @if ($modal === 'close')
        <x-store.modal title="Close your account?" subtitle="Your saved addresses, rewards points and order history will be removed from your account. This cannot be undone." width="max-w-[480px]">
            @error('close')
                <p class="mb-5 rounded-sm bg-sale-0 px-4 py-3 text-body-sm text-price-40" role="alert">{{ $message }}</p>
            @enderror
            <div class="flex justify-end gap-3">
                <button type="button" wire:click="close" class="rounded-full border border-grayscale-40 px-5 py-3 text-[15px] font-bold">Keep my account</button>
                <button type="button" wire:click="closeAccount" class="rounded-full bg-price-40 px-5 py-3 text-[15px] font-bold text-white">Close account</button>
            </div>
        </x-store.modal>
    @endif
</div>
