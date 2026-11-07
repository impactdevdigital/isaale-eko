@php
    use App\Livewire\Store\Checkout;

    $contact = $user?->signInContact();
    $contactLabel = $contact ? $contact['value'].' · Verified '.($contact['channel'] === 'email' ? 'email' : 'mobile') : null;
    $methodIcons = ['card' => 'card', 'wallet' => 'wallet', 'etransfer' => 'mail'];
    $methodHints = ['card' => 'Visa, Mastercard, Amex and more', 'wallet' => 'Apple Pay or Google Pay on supported devices', 'etransfer' => 'Send from your bank app after you place the order'];
    $methodBadges = [
        'card' => [['VISA', 'bg-surface text-[#1a1f71]'], ['MC', 'bg-surface text-[#eb001b]'], ['AMEX', 'bg-[#016fd0] text-white']],
        'wallet' => [['Apple Pay', 'bg-black text-white'], ['G Pay', 'bg-surface text-[#333333]']],
        'etransfer' => [['INTERAC', 'bg-[#fdb913] text-black']],
    ];

    [$feeLabel, $feeValue] = match (true) {
        $fulfilment === 'pickup' => ['Pickup fee', money(0)],
        $totals->deliveryFee !== null => ['Delivery fee', $totals->deliveryFee ? money($totals->deliveryFee) : 'Free'],
        default => ['Delivery fee', 'Calculated after address check'],
    };

    // The coloured note under the costs (Figma: pickup green, delivery yellow until an address is checked).
    [$note, $noteTone] = match (true) {
        $fulfilment === 'pickup' => ['Pickup selected · No delivery address needed', 'ok'],
        ! $address => ['🚚 Enter your address to calculate the delivery fee', 'pending'],
        $addressStatus === 'ok' => ['🚚 Delivering to '.$address['postal_code'], 'ok'],
        default => ['We can’t deliver to this address. Edit it or choose Pickup.', 'error'],
    };

    $subtitle = match (true) {
        (bool) $user => 'Signed in as '.$user->name.' — choose how you’d like to receive your order.',
        $fulfilment === 'delivery' && $address => 'Checkout as a guest — your details are saved for delivery.',
        default => 'Checkout as a guest — enter your details and choose how you’d like to receive your order.',
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
            <p class="mt-4 text-body-sm text-body-muted">Other options: Apple Pay / Google Pay.</p>
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

        <div class="flex flex-col gap-6">
            <div class="flex flex-col gap-2">
                <h1 class="text-h2-mobile md:text-h2">Fulfilment &amp; Order Review</h1>
                <p class="text-[14px] leading-[22px] font-medium text-body-muted">{{ $subtitle }}</p>
            </div>

            @if ($quantityChanged)
                <p class="rounded-sm bg-brand-yellow-10 px-4 py-3 text-body-sm text-brand-yellow-90" role="status">Some quantities changed because stock ran low. Your order summary shows what’s available.</p>
            @endif

            <div class="flex flex-col gap-4">
                {{-- Pickup or Delivery + email --}}
                <div class="flex flex-col gap-5 sm:flex-row">
                    <div class="flex flex-col gap-2 sm:w-[150px] sm:shrink-0">
                        <label for="fulfilment" class="text-[14px] leading-5">Pickup or Delivery</label>
                        <div class="relative">
                            <select id="fulfilment" wire:change="choose($event.target.value)"
                                    class="h-12 w-full appearance-none rounded-sm border border-grayscale-40 bg-surface pr-10 pl-4 text-[14px] font-medium focus:border-ink focus:outline-none">
                                <option value="pickup" @selected($fulfilment === 'pickup')>Pickup</option>
                                <option value="delivery" @selected($fulfilment === 'delivery')>Delivery</option>
                            </select>
                            <x-store.icon name="arrow-down" class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2" />
                        </div>
                    </div>

                    <div class="flex min-w-0 flex-1 flex-col gap-2">
                        @if ($user)
                            <label for="contact" class="text-[14px] leading-5">{{ $contact && $contact['channel'] === 'phone' ? 'Mobile Number' : 'Email Address' }}</label>
                            <div id="contact" class="flex h-12 items-center justify-between gap-3 rounded-sm border border-grayscale-40 bg-grayscale-0 px-4 text-[14px]">
                                <span class="truncate">{{ $contact['value'] ?? $user->email }}</span>
                                @if ($contact)
                                    <span class="flex shrink-0 items-center gap-1 text-[11px] font-bold text-stock-50"><x-store.icon name="check" class="size-3" /> Verified</span>
                                @endif
                            </div>
                        @else
                            <label for="email" class="text-[14px] leading-5">Email Address</label>
                            <input id="email" type="email" wire:model.blur="email" autocomplete="email" placeholder="name@example.com"
                                   @class(['h-12 w-full rounded-sm border bg-surface px-4 text-[14px] placeholder:text-body-muted focus:border-ink focus:outline-none', 'border-price-40' => $errors->has('email'), 'border-grayscale-40' => ! $errors->has('email')])>
                            @error('email')
                                <p class="text-body-xs text-price-40">{{ $message }}</p>
                            @else
                                <p class="text-[12px] leading-[18px] font-medium text-body-muted">We’ll send a code to confirm it and keep your order in your account.</p>
                            @enderror
                        @endif
                    </div>
                </div>

                {{-- Pickup slot or delivery address --}}
                @if ($fulfilment === 'pickup')
                    <div class="flex flex-col gap-2">
                        <span class="text-[14px] leading-5">Pickup day &amp; time</span>
                        <button type="button" wire:click="open('slot')"
                                @class(['flex h-12 items-center gap-2.5 rounded-sm border px-4 text-left text-[14px]', 'border-price-40' => $errors->has('pickupStart'), 'border-grayscale-40 hover:border-ink' => ! $errors->has('pickupStart')])>
                            <x-store.icon name="calendar" class="size-4 shrink-0" />
                            <span @class(['flex-1 truncate', 'text-body-muted' => ! $pickupLabel])>{{ $pickupLabel ?? 'Choose pickup day and time' }}</span>
                            @if ($pickupLabel)
                                <span class="text-[12px] font-bold">Change</span>
                            @endif
                        </button>
                        @error('pickupStart')
                            <p class="text-body-xs text-price-40">{{ $message }}</p>
                        @else
                            <p class="text-[12px] leading-[18px] font-medium text-body-muted">{{ $pickupLabel ? 'Your selected pickup slot. You can change it before checkout.' : 'Open the calendar to select a pickup slot · '.config('shop.pickup.location') }}</p>
                        @enderror
                    </div>
                @elseif ($address)
                    <div class="airmail rounded-lg bg-surface">
                        <dl class="grid gap-4 p-6 text-[14px] sm:grid-cols-[1fr_1fr_1.3fr_1.3fr]">
                            @foreach (['Full Name' => $address['recipient_name'], 'Phone Number' => $address['phone'], 'Delivery Address' => $address['street'].', '.$address['city'].', Canada', 'Postcode' => $address['postal_code']] as $term => $detail)
                                <div class="min-w-0">
                                    <dt class="text-[12px] leading-[18px] font-medium text-body-muted">{{ $term }}</dt>
                                    <dd class="mt-1.5 truncate leading-5" title="{{ $detail }}">{{ $detail }}</dd>
                                </div>
                            @endforeach
                        </dl>
                        <div class="flex items-center justify-between gap-3 border-t border-grayscale-40 px-6 py-3">
                            <span @class(['flex items-center gap-1.5 rounded-sm px-2 py-1 text-[11px] font-bold', 'bg-stock-0 text-stock-50' => $addressStatus === 'ok', 'bg-sale-0 text-price-40' => $addressStatus !== 'ok'])>
                                <x-store.icon :name="$addressStatus === 'ok' ? 'check' : 'close'" class="size-3" />
                                {{ match ($addressStatus) {
                                    'ok' => 'We deliver here · '.($totals->deliveryFee ? money($totals->deliveryFee).' delivery fee' : 'free delivery'),
                                    'invalid_postcode' => 'Check the postal code',
                                    default => 'Outside our delivery area',
                                } }}
                            </span>
                            <div class="flex gap-4 text-[12px] font-bold">
                                <button type="button" wire:click="open('{{ $user && $savedAddresses->isNotEmpty() ? 'addresses' : 'address' }}')" class="hover:underline">Edit address</button>
                                <button type="button" wire:click="removeAddress" class="hover:underline">Remove</button>
                            </div>
                        </div>
                    </div>
                    @error('address') <p class="-mt-2 text-body-xs text-price-40">{{ $message }}</p> @enderror
                @else
                    <div class="flex flex-col items-start gap-2">
                        <button type="button" wire:click="open('{{ $user && $savedAddresses->isNotEmpty() ? 'addresses' : 'address' }}')"
                                class="flex h-[42px] items-center gap-2 rounded-full bg-ink px-5 text-[14px] font-extrabold text-white transition-colors hover:bg-grayscale-90">
                            <x-store.icon name="plus" class="size-3.5" /> Add address
                        </button>
                        @error('address') <p class="text-body-xs text-price-40">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>

            {{-- Payment method --}}
            <fieldset class="flex flex-col gap-4">
                <legend class="mb-4 text-[14px] leading-[18px] font-extrabold">Payment method</legend>
                <div @class(['overflow-hidden rounded-sm border', 'border-price-40' => $errors->has('paymentMethod'), 'border-grayscale-40' => ! $errors->has('paymentMethod')])>
                    @foreach ($paymentMethods as $method)
                        <label @class(['flex cursor-pointer items-center gap-3 border-grayscale-40 px-4 py-3.5', 'border-t' => ! $loop->first])>
                            <input type="radio" wire:model.live="paymentMethod" value="{{ $method->code }}" class="sr-only peer">
                            <x-store.radio :checked="$paymentMethod === $method->code" />
                            <x-store.icon :name="$methodIcons[$method->code] ?? 'card'" class="size-5 shrink-0" />
                            <span class="min-w-0 flex-1">
                                <span class="block text-[14px] leading-[18px] font-bold">{{ $method->label() }}</span>
                                <span class="block text-[12px] leading-[18px] font-medium text-body-muted">{{ $methodHints[$method->code] ?? $method->description() }}</span>
                            </span>
                            <span class="hidden gap-1 sm:flex">
                                @foreach ($methodBadges[$method->code] ?? [] as [$badge, $tone])
                                    <span class="rounded-[3px] border border-grayscale-40 px-1.5 py-0.5 text-[9px] font-extrabold {{ $tone }}">{{ $badge }}</span>
                                @endforeach
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
                        @if ($paymentMethod === $method->code && $method->code === 'etransfer')
                            <div class="bg-grayscale-0 px-4 pb-4 text-body-sm">
                                <p class="font-bold">How it works</p>
                                <ol class="mt-2 list-decimal space-y-1 pl-5 text-body-muted">
                                    <li>Place your order. We’ll show you the e-Transfer details and email them to you.</li>
                                    <li>Send <strong class="text-ink">{{ money($totals->total()) }}</strong> to <strong class="text-ink">{{ $etransfer['email'] }}</strong>, with your order number in the message.</li>
                                    <li>We start preparing your order once the transfer arrives, usually within a few hours.</li>
                                </ol>
                                <p class="mt-2 text-[12px] text-body-muted">Unpaid orders are cancelled after {{ $etransfer['hold_hours'] }} hours.</p>
                            </div>
                        @endif
                    @endforeach
                </div>
                @error('paymentMethod') <p class="text-body-xs text-price-40">{{ $message }}</p> @enderror
                <p x-show="error" x-cloak x-text="error" role="alert" class="rounded-sm bg-sale-0 px-4 py-3 text-body-sm text-price-40"></p>
            </fieldset>

            {{-- Items --}}
            @if ($checkoutLines->isNotEmpty())
                <section class="flex flex-col gap-4" aria-labelledby="items-title">
                    <h2 id="items-title" class="text-[14px] leading-[18px] font-extrabold">Items</h2>
                    <ul class="flex flex-wrap gap-4 bg-grayscale-0 p-3">
                        @foreach ($checkoutLines as $line)
                            @php($variation = $line->variation)
                            <li class="flex w-[98px] flex-col gap-2" wire:key="item-{{ $variation->id }}">
                                <img src="{{ asset($variation->product->image) }}" alt="{{ $variation->product->name }}" class="size-[98px] rounded-sm border border-grayscale-40 bg-surface object-cover">
                                <strong class="text-[16px] leading-[22px] font-extrabold text-price-40">{{ money($variation->price_cents * $line->quantity) }}</strong>
                                <div class="flex h-[34px] items-center justify-between rounded-full border border-grayscale-40 bg-surface">
                                    <button type="button" wire:click="decrement({{ $variation->id }})" @disabled($line->quantity <= 1) class="-m-px flex size-[34px] items-center justify-center rounded-full border border-grayscale-40 bg-surface text-[14px] font-extrabold disabled:opacity-40" aria-label="Decrease quantity of {{ $variation->product->name }}">−</button>
                                    <span class="text-[13px] font-extrabold" aria-live="polite">{{ $line->quantity }}</span>
                                    <button type="button" wire:click="increment({{ $variation->id }})" class="-m-px flex size-[34px] items-center justify-center rounded-full border border-grayscale-40 bg-surface text-[14px] font-extrabold" aria-label="Increase quantity of {{ $variation->product->name }}">+</button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <x-store.order-summary :lines="$checkoutLines" :totals="$totals" :coupon-result="$couponResult"
                               :fee-label="$feeLabel" :fee-value="$feeValue" savings-label="Savings" :note="$note" :note-tone="$noteTone" class="lg:self-start">
            @if ($readyToPay && $paymentMethod === 'etransfer')
                <button type="button" wire:click="placeETransferOrder" wire:loading.attr="disabled" wire:target="placeETransferOrder"
                        class="flex h-[42px] items-center justify-center rounded-full bg-ink text-[14px] font-extrabold text-white transition-colors hover:bg-grayscale-90 disabled:opacity-60">
                    <span wire:loading.remove wire:target="placeETransferOrder">Place order · {{ money($totals->total()) }}</span>
                    <span wire:loading wire:target="placeETransferOrder">Placing order…</span>
                </button>
            @elseif ($readyToPay)
                <button type="button" @click="pay()" :disabled="busy || ! ready"
                        class="flex h-[42px] items-center justify-center rounded-full bg-ink text-[14px] font-extrabold text-white transition-colors hover:bg-grayscale-90 disabled:opacity-60">
                    <span x-show="! busy">Pay {{ money($totals->total()) }}</span>
                    <span x-show="busy" x-cloak>Processing…</span>
                </button>
            @else
                <button type="button" wire:click="proceed"
                        class="flex h-[42px] items-center justify-center gap-2 rounded-full bg-ink text-[14px] font-extrabold text-white transition-colors hover:bg-grayscale-90">
                    Proceed to checkout <x-store.icon name="arrow-right" class="size-4" />
                </button>
            @endif
            @if ($problem)
                <p class="rounded-sm bg-sale-0 px-3 py-2 text-body-sm font-bold text-price-40" role="alert">{{ $problem }}</p>
            @endif
        </x-store.order-summary>
    </div>
    @endif

    {{-- Dialogs --}}
    @if ($modal === 'address')
        <x-store.modal :title="$user ? 'Add new address' : 'Add address'" subtitle="All fields are required unless marked optional. We’ll check delivery availability before payment." width="max-w-[680px]">
            <form wire:submit="saveAddress" class="grid gap-5 sm:grid-cols-2">
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

                @if ($user)
                    <div class="sm:col-span-2"><x-store.check model="saveToAddressBook" label="Save to my address book" /></div>
                @endif

                <div class="flex justify-end gap-3 sm:col-span-2">
                    <button type="button" wire:click="close" class="rounded-full border border-grayscale-40 px-5 py-3 text-[15px] font-bold">Cancel</button>
                    <button type="submit" class="rounded-full bg-ink px-5 py-3 text-[15px] font-bold text-white">{{ $user ? 'Use this address' : 'Add address' }}</button>
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
        <x-store.modal title="Confirm your email" :subtitle="'Enter the 6-digit code we sent to '.$codeDestination.'. This keeps your order in your account.'" width="max-w-[600px]">
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
                <x-store.field model="name" label="Your full name" autocomplete="name" />
                <x-store.check model="marketing" label="Email me offers and news (optional)" />
                <button type="submit" class="h-12 rounded-full bg-ink text-[15px] font-bold text-white">Verify and continue</button>
                <div class="flex items-center justify-between gap-3">
                    <button type="button" wire:click="changeContact" class="rounded-full border border-grayscale-40 px-5 py-3 text-[15px] font-bold">Change email</button>
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
