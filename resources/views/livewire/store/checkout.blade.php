@php
    use App\Livewire\Store\Checkout;

    $contact = $user?->signInContact();
    $contactLabel = $contact ? $contact['value'].' · Verified '.($contact['channel'] === 'email' ? 'email' : 'mobile') : null;
    $methodIcons = ['card' => 'card', 'wallet' => 'wallet', 'pay_at_pickup' => 'store'];
    $methodHints = ['card' => 'Visa, Mastercard, Amex and more', 'wallet' => 'Apple Pay or Google Pay on supported devices', 'pay_at_pickup' => 'Cash, card or Interac at the counter'];

    [$feeLabel, $feeValue, $totalLabel] = match (true) {
        $fulfilment === 'pickup' => ['Pickup fee', money(0), null],
        $totals->deliveryFee !== null => ['Delivery fee', $totals->deliveryFee ? money($totals->deliveryFee) : 'Free', null],
        default => ['Delivery fee', 'Calculated after address check', 'Pending address check'],
    };

    $notReadyHelp = match (true) {
        ! $user => $fulfilment === 'pickup'
            ? 'Add and verify pickup details, choose a slot and payment method to enable payment.'
            : 'Add a delivery address and verify your contact, then choose a payment method.',
        ! $fulfilmentReady => $fulfilment === 'pickup'
            ? 'Choose a pickup slot to enable payment.'
            : 'Confirm a delivery address we can reach, then choose a payment method.',
        ! $paymentMethod => 'Choose a payment method to enable payment.',
        default => null,
    };
@endphp

<div class="page-container pt-6 pb-16 md:pt-10">
    <nav aria-label="Breadcrumb" class="text-[11px] leading-[14px] font-semibold text-body-muted">
        <ol class="flex items-center gap-1.5">
            <li><a href="{{ route('home') }}" class="hover:text-ink">Home</a></li>
            <li aria-hidden="true">/</li>
            <li><a href="{{ route('cart') }}" class="hover:text-ink">Your Cart</a></li>
            <li aria-hidden="true">/</li>
            <li class="font-bold text-ink" aria-current="page">Checkout</li>
        </ol>
    </nav>

    @if ($declined)
        <section class="mx-auto flex max-w-[560px] flex-col items-center py-10 text-center" aria-labelledby="declined-title">
            <span class="flex size-16 items-center justify-center rounded-full bg-sale-10 text-price-40"><x-store.icon name="card" class="size-7" /></span>
            <h1 id="declined-title" class="mt-5 text-h2-mobile md:text-h2">Payment Declined</h1>
            <p class="mt-3 text-lead-mobile font-normal text-body-muted md:text-lead">We couldn’t complete your {{ money($declinedAmount ?? $totals->total()) }} payment. Your order hasn’t been placed.</p>
            <div class="mt-6 w-full rounded-sm bg-grayscale-0 px-6 py-6 text-left">
                <p class="text-[15px] font-bold">Check your card before trying again</p>
                <p class="mt-3 text-body text-body-muted">{{ $declined }}</p>
                <p class="mt-3 text-body-sm">Your {{ $totals->itemCount }} {{ str('item')->plural($totals->itemCount) }} {{ $totals->itemCount === 1 ? 'is' : 'are' }} still in your cart.</p>
            </div>
            <div class="mt-6 grid w-full gap-3 sm:grid-cols-2">
                <button type="button" wire:click="retryPayment" class="h-[42px] rounded-full bg-ink text-[15px] font-bold text-white">Retry payment</button>
                <button type="button" wire:click="changePaymentMethod" class="h-[42px] rounded-full border border-ink text-[15px] font-bold">Change payment method</button>
            </div>
            <p class="mt-4 text-body-sm text-body-muted">Other options: Apple Pay / Google Pay, or Pay at Pickup.</p>
            <p class="mt-4 text-body-sm text-body-muted">Need a hand? <a href="mailto:{{ \App\Models\Setting::get('support_email') }}" class="font-bold text-ink">Contact us</a></p>
        </section>
    @else
    <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_400px]"
         wire:key="pay-{{ $paymentMethod ?? 'none' }}-{{ $totals->total() }}"
         x-data="squarePayments(@js($square))">
        {{-- Payment processing --}}
        <div x-data="{ show: false }" x-show="show" x-cloak @payment-processing.window="show = $event.detail"
             class="fixed inset-0 z-[60] flex items-center justify-center bg-surface/95 px-4" role="status" aria-live="assertive">
            <div class="flex max-w-[560px] flex-col items-center text-center">
                <span class="flex size-[72px] items-center justify-center rounded-full bg-brand-yellow-10">
                    <span class="size-10 animate-spin rounded-full border-[3px] border-grayscale-30 border-t-ink"></span>
                </span>
                <p class="mt-5 text-h2-mobile md:text-h2">Processing Your Payment</p>
                <p class="mt-3 text-lead-mobile font-normal text-body-muted md:text-lead">We’re checking your {{ money($totals->total()) }} payment securely. This may take a moment.</p>
                <p class="mt-4 text-body-sm">Please keep this page open. Don’t refresh or pay again while we check.</p>
            </div>
        </div>

        <div class="flex flex-col gap-8">
            <div>
                <h1 class="text-h2-mobile md:text-h2">Checkout</h1>
                <p class="mt-2 text-lead-mobile font-normal text-body-muted md:text-lead">
                    {{ $user ? 'Signed in as '.$user->name.'.' : 'Checkout as a guest — add your details without leaving this page.' }}
                </p>
            </div>

            @if ($problem)
                <p class="rounded-sm border border-price-40 bg-sale-0 px-4 py-3 text-body-sm font-bold text-price-40" role="alert">{{ $problem }}</p>
            @endif
            @if ($quantityChanged)
                <p class="rounded-sm bg-brand-yellow-10 px-4 py-3 text-body-sm text-brand-yellow-90" role="status">Some quantities changed because stock ran low. Your order summary shows what’s available.</p>
            @endif

            {{-- Pickup or Delivery --}}
            <fieldset>
                <legend class="mb-3 text-[15px]">Pickup or Delivery</legend>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach (['pickup' => 'Pickup', 'delivery' => 'Delivery'] as $value => $label)
                        <button type="button" wire:click="choose('{{ $value }}')" role="radio" aria-checked="{{ $fulfilment === $value ? 'true' : 'false' }}"
                                @class(['flex h-12 items-center gap-3 rounded-sm border px-4 text-[16px] font-bold', 'border-brand-yellow bg-brand-yellow-0 border-2' => $fulfilment === $value, 'border-grayscale-40' => $fulfilment !== $value])>
                            <x-store.radio :checked="$fulfilment === $value" />
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </fieldset>

            {{-- Details --}}
            <section aria-labelledby="details-title">
                <h2 id="details-title" class="mb-3 text-[15px] font-bold">{{ $fulfilment === 'pickup' ? 'Pickup details' : 'Delivery details' }}</h2>

                @if ($user)
                    <div class="mb-4 rounded-sm bg-grayscale-0 px-4 py-4">
                        <p class="text-[16px] font-bold">{{ $user->name }}</p>
                        @if ($contactLabel)
                            <p class="text-[15px]">{{ $contactLabel }}</p>
                        @endif
                        <p class="mt-1 text-body-xs text-body-muted">Your order will be linked to this verified account.</p>
                    </div>
                @endif

                @if ($fulfilment === 'pickup')
                    @if (! $user)
                        <div class="rounded-sm border border-grayscale-40 p-6">
                            <p class="text-[16px] font-bold">Who will collect your order?</p>
                            <p class="mt-2 text-body text-body-muted">Add your name and verify an email or mobile number, then choose your pickup slot. No delivery address needed.</p>
                            <button type="button" wire:click="open('contact')" class="mt-5 rounded-full bg-ink px-5 py-3 text-[14px] font-extrabold text-white">Add pickup details</button>
                        </div>
                    @else
                        <div class="rounded-sm border border-grayscale-40 p-5">
                            <p class="text-[15px] font-bold">Store collection · {{ config('shop.pickup.location') }}</p>
                            <p class="mt-1 text-body text-body-muted">Pickup is free · {{ money(0) }}</p>
                            @if ($pickupLabel)
                                <p class="mt-3 text-[15px] font-bold">{{ $pickupLabel }}</p>
                            @else
                                <p class="mt-3 text-[15px] font-bold">Choose a pickup slot for this order</p>
                            @endif
                            <div class="mt-4 flex flex-wrap gap-2">
                                <button type="button" wire:click="open('slot')" class="rounded-full border border-grayscale-40 px-5 py-2.5 text-[14px] font-bold hover:border-ink">{{ $pickupLabel ? 'Change slot' : 'Choose pickup slot' }}</button>
                            </div>
                            <p class="mt-3 text-body-xs text-body-muted">Your slot is not reserved until checkout succeeds.</p>
                        </div>
                    @endif
                @else
                    @if ($address)
                        <div class="overflow-hidden rounded-sm border border-grayscale-40">
                            <div class="grid gap-4 p-5 sm:grid-cols-[1fr_auto]">
                                <div class="text-[14px]">
                                    <p class="font-bold">{{ $address['recipient_name'] }}</p>
                                    <p class="mt-1">{{ $address['street'] }}, {{ $address['city'] }}, Canada</p>
                                    <p class="mt-1 text-body-muted">Recipient phone · {{ $address['phone'] }}</p>
                                    <p class="mt-1 text-body-muted">Courier contact only · does not change your sign-in</p>
                                </div>
                                <div class="text-[14px] sm:text-right">
                                    <p class="font-bold">Postal code</p>
                                    <p class="mt-1">{{ $address['postal_code'] }}</p>
                                </div>
                            </div>
                            <div @class([
                                'flex items-center justify-between gap-3 px-5 py-3 text-body-sm',
                                'bg-stock-0 text-stock-50' => $addressStatus === 'ok',
                                'bg-sale-0 text-price-40' => $addressStatus !== 'ok',
                            ])>
                                <span>
                                    {{ match ($addressStatus) {
                                        'ok' => 'We deliver here · '.($totals->deliveryFee ? money($totals->deliveryFee).' delivery fee' : 'free delivery'),
                                        'invalid_postcode' => 'Check the postal code — Canadian codes look like E2L 4L1.',
                                        default => 'We can’t deliver to this address. Edit it or choose Pickup.',
                                    } }}
                                </span>
                                <button type="button" wire:click="open('{{ $user && $savedAddresses->isNotEmpty() ? 'addresses' : 'address' }}')" class="shrink-0 rounded-full border border-current bg-surface px-4 py-2 text-[13px] font-bold text-ink">Edit address</button>
                            </div>
                        </div>
                    @else
                        <div class="rounded-sm border border-grayscale-40 p-6">
                            <p class="text-[16px] font-bold">Where should we deliver your order?</p>
                            <p class="mt-2 text-body text-body-muted">{{ $user ? 'Choose a saved address or add a new one.' : 'Add a delivery address and verify one contact. We’ll check delivery availability before payment.' }}</p>
                            <button type="button" wire:click="open('{{ $user && $savedAddresses->isNotEmpty() ? 'addresses' : 'address' }}')" class="mt-5 rounded-full bg-ink px-5 py-3 text-[14px] font-extrabold text-white">Add address</button>
                        </div>
                    @endif
                @endif
            </section>

            {{-- Payment method --}}
            <fieldset>
                <legend class="mb-3 text-[15px] font-bold">Payment method</legend>
                <div class="divide-y divide-grayscale-40 rounded-sm border border-grayscale-40">
                    @foreach ($paymentMethods as $method)
                        <label @class(['flex cursor-pointer items-center gap-4 px-4 py-4', 'bg-grayscale-0' => $paymentMethod === $method->code])>
                            <input type="radio" wire:model.live="paymentMethod" value="{{ $method->code }}" class="sr-only peer">
                            <x-store.radio :checked="$paymentMethod === $method->code" />
                            <x-store.icon :name="$methodIcons[$method->code] ?? 'card'" class="size-5 shrink-0" />
                            <span>
                                <span class="block text-[15px] font-bold">{{ $method->label() }}</span>
                                <span class="block text-body-sm font-normal text-body-muted">{{ $methodHints[$method->code] ?? $method->description() }}</span>
                            </span>
                        </label>

                        @if ($paymentMethod === $method->code && $method->code === 'card')
                            <div class="bg-grayscale-0 px-4 pb-4">
                                <div x-ref="card" wire:ignore class="min-h-[90px]"></div>
                                <p x-show="! ready && ! error" class="text-body-sm text-body-muted">Loading secure card form…</p>
                                <p class="mt-1 flex items-center gap-1.5 text-[11px] text-stock-50"><x-store.icon name="shield" class="size-3.5" /> Card details go straight to Square and never touch our servers.</p>
                            </div>
                        @endif

                        @if ($paymentMethod === $method->code && $method->code === 'wallet')
                            <div class="flex flex-col gap-2 bg-grayscale-0 px-4 pb-4">
                                <div x-ref="googlePay" wire:ignore x-show="walletAvailable.googlePay" class="h-11"></div>
                                <button type="button" x-show="walletAvailable.applePay" x-cloak @click="pay('applePay')"
                                        class="h-11 rounded-full bg-black text-[15px] font-bold text-white">Pay with Apple Pay</button>
                                <p x-show="! ready && ! error" class="text-body-sm text-body-muted">Checking Apple Pay and Google Pay…</p>
                            </div>
                        @endif
                    @endforeach
                </div>
                <p x-show="error" x-cloak x-text="error" role="alert" class="mt-3 rounded-sm bg-sale-0 px-4 py-3 text-body-sm text-price-40"></p>
            </fieldset>
        </div>

        <x-store.order-summary :lines="$checkoutLines" :totals="$totals" :coupon-result="$couponResult"
                               :fee-label="$feeLabel" :fee-value="$feeValue" :total-label="$totalLabel" class="lg:self-start">
            @if ($readyToPay && $totalLabel === null)
                <button type="button" @click="pay()" :disabled="busy || (method !== 'pay_at_pickup' && ! ready)"
                        class="flex h-[50px] items-center justify-center rounded-full bg-ink text-[16px] font-extrabold text-white transition-colors hover:bg-grayscale-90 disabled:opacity-60">
                    <span x-show="! busy">{{ $paymentMethod === 'pay_at_pickup' ? 'Place order' : 'Pay '.money($totals->total()) }}</span>
                    <span x-show="busy" x-cloak>{{ $paymentMethod === 'pay_at_pickup' ? 'Placing your order…' : 'Processing…' }}</span>
                </button>
                <p class="text-center text-body-sm text-body-muted">Your details, {{ $fulfilment === 'pickup' ? 'pickup slot' : 'delivery address' }} and payment method are complete.</p>
            @else
                <span aria-disabled="true" class="flex h-[50px] cursor-not-allowed items-center justify-center rounded-full bg-grayscale-20 text-[16px] font-extrabold text-grayscale-60">Payment not ready</span>
                @if ($notReadyHelp)
                    <p class="text-body-sm text-body-muted">{{ $notReadyHelp }}</p>
                @endif
            @endif
        </x-store.order-summary>
    </div>
    @endif

    {{-- Dialogs --}}
    @if ($modal === 'contact')
        <x-store.modal title="Add pickup details" subtitle="Add your name, then choose one way to verify your contact.">
            <form wire:submit="sendCode" class="flex flex-col gap-5">
                <x-store.field model="name" label="Full name" autocomplete="name" />
                <x-store.channel-tabs :channel="$channel" />
                @if ($channel === 'email')
                    <x-store.field model="email" label="Email address" type="email" autocomplete="email" />
                @else
                    <x-store.field model="phone" label="Mobile number" type="tel" autocomplete="tel" placeholder="+1 506 555 0123" />
                @endif
                <p class="text-body-sm text-body-muted">We’ll verify your contact and create or sign you into a password-free account so you can track this order and find it later.</p>
                <x-store.check model="marketing" :label="$channel === 'email' ? 'Email me offers and news (optional)' : 'Text me offers and news (optional)'" />
                <div class="flex justify-end gap-3">
                    <button type="button" wire:click="close" class="rounded-full border border-grayscale-40 px-5 py-3 text-[15px] font-bold">Cancel</button>
                    <button type="submit" class="rounded-full bg-ink px-5 py-3 text-[15px] font-bold text-white">Send code</button>
                </div>
            </form>
        </x-store.modal>
    @endif

    @if ($modal === 'address')
        <x-store.modal :title="$user ? 'Add new address' : 'Add address'" subtitle="All fields are required unless marked optional. We’ll check delivery availability before payment." width="max-w-[680px]">
            <form wire:submit="{{ $user ? 'saveAddress' : 'sendCode' }}" class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="field-country" class="mb-2 block text-[11px] font-medium uppercase tracking-[0.3px] text-grayscale-80">Country / Region</label>
                    <select id="field-country" wire:model="country" class="h-11 w-full rounded-sm border border-grayscale-30 bg-surface px-4 text-[16px]">
                        <option value="CA">Canada</option>
                    </select>
                </div>
                <x-store.field model="recipient" label="Recipient / contact name" autocomplete="name" />
                <x-store.field model="recipientPhone" label="Phone number · courier contact" type="tel" autocomplete="tel" class="sm:col-span-2" />
                <x-store.field model="street" label="Street address" autocomplete="street-address" class="sm:col-span-2" />
                <x-store.field model="city" label="City" autocomplete="address-level2" />
                <x-store.field model="postalCode" label="Postal code" autocomplete="postal-code" placeholder="E2L 4L1" />

                @unless ($user)
                    <div class="flex flex-col gap-4 sm:col-span-2">
                        <x-store.field model="name" label="Your full name" autocomplete="name" hint="For your account. The recipient above can be someone else." />
                        <x-store.channel-tabs :channel="$channel" />
                        @if ($channel === 'email')
                            <x-store.field model="email" label="Email address" type="email" autocomplete="email" />
                        @else
                            <x-store.check model="recipientPhoneIsMine" label="The phone number above is my own mobile — send my code there" live />
                            @unless ($recipientPhoneIsMine)
                                <x-store.field model="phone" label="Your mobile number" type="tel" autocomplete="tel" hint="We’ll sign you in with this number. The courier keeps using the recipient phone." />
                            @endunless
                        @endif
                        <p class="text-body-sm text-body-muted">We’ll verify your contact and create or sign you into a password-free account so you can track this order and find it later.</p>
                    </div>
                @endunless

                <div class="flex flex-col gap-3 sm:col-span-2">
                    @if ($user)
                        <x-store.check model="saveToAddressBook" label="Save to my address book" />
                    @endif
                    <x-store.check model="marketing" label="Email me offers and news (optional)" />
                </div>

                <div class="flex justify-end gap-3 sm:col-span-2">
                    <button type="button" wire:click="close" class="rounded-full border border-grayscale-40 px-5 py-3 text-[15px] font-bold">Cancel</button>
                    <button type="submit" class="rounded-full bg-ink px-5 py-3 text-[15px] font-bold text-white">{{ $user ? 'Use this address' : 'Continue / Send code' }}</button>
                </div>
            </form>
        </x-store.modal>
    @endif

    @if ($modal === 'addresses')
        <x-store.modal title="Choose a delivery address" :subtitle="'Signed in as '.$user->name.($contactLabel ? ' · '.$contactLabel : '')" width="max-w-[880px]">
            <div class="flex flex-col gap-3">
                @foreach ($savedAddresses as $saved)
                    <button type="button" wire:click="useAddress({{ $saved->id }})" wire:key="address-{{ $saved->id }}"
                            @class(['flex items-start gap-4 rounded-sm border p-5 text-left text-[14px]', 'border-ink' => $selectedAddressId === $saved->id, 'border-grayscale-40 hover:border-ink' => $selectedAddressId !== $saved->id])>
                        <x-store.radio :checked="$selectedAddressId === $saved->id" />
                        <span class="flex-1">
                            <span class="block font-bold">{{ $saved->recipient_name }}</span>
                            <span class="mt-1 block">{{ $saved->street }}, {{ $saved->city }} {{ $saved->postal_code }}</span>
                            <span class="mt-1 block text-body-muted">Recipient phone · {{ $saved->phone }}</span>
                        </span>
                    </button>
                @endforeach
            </div>
            <button type="button" wire:click="open('address')" class="mt-4 rounded-full border border-grayscale-40 px-5 py-3 text-[15px] font-bold hover:border-ink">Add new address</button>
            <p class="mt-4 text-body-sm text-body-muted">A different recipient or courier phone won’t change your verified sign-in contact.</p>
        </x-store.modal>
    @endif

    @if ($modal === 'code')
        <x-store.modal title="Enter the 6-digit code" :subtitle="'Use the code sent to '.$codeDestination.'.'" width="max-w-[600px]">
            <form wire:submit="verifyCode" class="flex flex-col gap-5"
                  x-data="{ left: Math.max(0, {{ $resendAt }} - Math.floor(Date.now() / 1000)), tick() { this.left = Math.max(0, {{ $resendAt }} - Math.floor(Date.now() / 1000)) } }"
                  x-init="setInterval(() => tick(), 1000)">
                <p class="flex items-center gap-2 text-[15px] font-bold">
                    <x-store.icon :name="$codeChannel === 'email' ? 'mail' : 'phone'" class="size-5" />
                    {{ $codeDestination }}
                </p>
                <div>
                    <label for="otp" class="sr-only">Six-digit code</label>
                    <input id="otp" wire:model="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]*" autofocus
                           @class(['h-14 w-full rounded-sm border text-center font-mono text-[28px] tracking-[0.6em] focus:outline-none', 'border-price-40' => $errors->has('code'), 'border-grayscale-40 focus:border-ink' => ! $errors->has('code')])>
                    @error('code')
                        <p class="mt-3 rounded-sm bg-sale-0 px-4 py-3 text-body-sm text-price-40" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="h-12 rounded-full bg-ink text-[15px] font-bold text-white">Verify and continue</button>
                <div class="flex items-center justify-between gap-3">
                    <button type="button" wire:click="changeContact" class="rounded-full border border-grayscale-40 px-5 py-3 text-[15px] font-bold">Change contact</button>
                    <span x-show="left > 0" class="text-body-sm text-body-muted">Resend code in <span x-text="`00:${String(left).padStart(2, '0')}`"></span></span>
                    <button type="button" x-show="left === 0" x-cloak wire:click="resendCode" class="text-body-sm font-bold underline">Resend code</button>
                </div>
                <p class="text-body-sm text-body-muted">Contact isn’t verified yet. Closing keeps your details but won’t enable payment.</p>
            </form>
        </x-store.modal>
    @endif

    @if ($modal === 'slot')
        <x-store.modal title="Choose your pickup slot" subtitle="Select a day, then a two-hour window to collect your order." width="max-w-[800px]">
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <p class="text-[15px] font-bold">1. Choose a day</p>
                    <div class="mt-3 flex flex-col gap-2">
                        @foreach ($days as $day)
                            @php($value = $day['date']->toDateString())
                            <button type="button" wire:click="pickDay('{{ $value }}')" @disabled(! $day['open']) wire:key="day-{{ $value }}"
                                    @class([
                                        'flex h-11 items-center gap-3 rounded-sm border px-4 text-[15px]',
                                        'border-brand-yellow border-2 bg-brand-yellow-0' => $slotDay === $value,
                                        'border-grayscale-40 hover:border-ink' => $slotDay !== $value && $day['open'],
                                        'cursor-not-allowed border-grayscale-30 bg-grayscale-20 text-grayscale-60' => ! $day['open'],
                                    ])>
                                <x-store.radio :checked="$slotDay === $value" :disabled="! $day['open']" />
                                <span class="flex-1 text-left">{{ $day['date']->format('D j M') }}</span>
                                <span @class(['text-[12px]', 'text-stock-50' => $day['open'] && $slotDay !== $value])>{{ ! $day['open'] ? 'Closed' : ($slotDay === $value ? 'Selected' : 'Available') }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <p class="text-[15px] font-bold">2. Choose a time</p>
                    <div class="mt-3 flex flex-col gap-2">
                        @forelse ($windows as $window)
                            @php($value = $window['start']->toIso8601String())
                            <button type="button" wire:click="$set('slotStart', '{{ $value }}')" @disabled(! $window['available']) wire:key="window-{{ $value }}"
                                    @class([
                                        'flex h-11 items-center gap-3 rounded-sm border px-4 text-[15px]',
                                        'border-brand-yellow border-2 bg-brand-yellow-0' => $slotStart === $value,
                                        'border-grayscale-40 hover:border-ink' => $slotStart !== $value && $window['available'],
                                        'cursor-not-allowed border-grayscale-30 bg-grayscale-20 text-grayscale-60' => ! $window['available'],
                                    ])>
                                <x-store.radio :checked="$slotStart === $value" :disabled="! $window['available']" />
                                <span class="flex-1 text-left">{{ $window['start']->format('H:i') }}–{{ $window['end']->format('H:i') }}</span>
                                <span @class(['text-[12px]', 'text-stock-50' => $window['available'] && $slotStart !== $value])>{{ ! $window['available'] ? 'Unavailable' : ($slotStart === $value ? 'Selected' : 'Available') }}</span>
                            </button>
                        @empty
                            <p class="text-body-sm text-body-muted">Choose a day to see pickup times.</p>
                        @endforelse
                    </div>
                    <p class="mt-3 text-body-sm text-body-muted">Full windows and closed days cannot be selected.</p>
                </div>
            </div>

            @error('slotStart')
                <p class="mt-4 rounded-sm bg-sale-0 px-4 py-3 text-body-sm text-price-40" role="alert">{{ $message }}</p>
            @enderror

            @if ($slotStart)
                <div class="mt-6 rounded-sm bg-grayscale-0 px-4 py-4">
                    <p class="text-[15px] font-bold">{{ Checkout::windowLabel(\Carbon\CarbonImmutable::parse($slotStart)) }}</p>
                    <p class="mt-1 text-body text-body-muted">Your choice is not a reservation. Your slot is reserved only when checkout succeeds.</p>
                </div>
            @endif

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" wire:click="close" class="rounded-full border border-grayscale-40 px-5 py-3 text-[15px] font-bold">Back</button>
                <button type="button" wire:click="confirmSlot" class="rounded-full bg-ink px-5 py-3 text-[15px] font-bold text-white">Confirm pickup slot</button>
            </div>
        </x-store.modal>
    @endif
</div>
