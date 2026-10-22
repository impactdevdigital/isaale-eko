<?php

namespace App\Livewire\Store;

use App\Models\Address;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Checkout\CheckoutProblem;
use App\Services\Checkout\DeliveryCheck;
use App\Services\Checkout\OneTimeCode;
use App\Services\Checkout\OrderReceipt;
use App\Services\Checkout\PickupWindows;
use App\Services\Checkout\PlaceOrder;
use App\Services\Checkout\TakePayment;
use App\Services\ConsentService;
use App\Support\Cart;
use App\Support\CartTotals;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * One-page checkout (Figma "Revised guest & returning customer proposal"):
 * Pickup or Delivery, verified contact, slot or address, payment method and
 * a single order summary. Entered details survive switching methods.
 */
#[Layout('layouts::store')]
#[Title('Checkout')]
class Checkout extends Component
{
    public string $fulfilment = 'pickup';

    /** Open dialog: contact, code, slot, address or addresses. */
    public ?string $modal = null;

    // Contact (guest)
    public string $name = '';

    public string $channel = 'email';

    public string $email = '';

    public string $phone = '';

    public bool $marketing = false;

    // One-time code: where the last code went. Locked so the browser can't redirect verification.
    #[Locked]
    public ?string $codeDestination = null;

    #[Locked]
    public ?string $codeChannel = null;

    public string $code = '';

    public int $resendAt = 0;

    // Pickup
    public ?string $slotDay = null;

    public ?string $slotStart = null;

    public ?string $pickupStart = null;

    // Delivery address form
    public string $country = 'CA';

    public string $recipient = '';

    public string $recipientPhone = '';

    public string $street = '';

    public string $city = '';

    public string $postalCode = '';

    public bool $saveToAddressBook = false;

    /** With SMS on delivery, the shopper confirms the recipient phone is their own. */
    public bool $recipientPhoneIsMine = true;

    /** @var array<string, string|null>|null */
    public ?array $address = null;

    #[Locked]
    public ?string $addressStatus = null;

    public ?int $selectedAddressId = null;

    // Payment
    public ?string $paymentMethod = null;

    public string $couponCode = '';

    public ?string $couponResult = null;

    public ?string $problem = null;

    /** Set when an online payment is declined: shows the Payment Declined screen. */
    public ?string $declined = null;

    #[Locked]
    public ?int $declinedAmount = null;

    public function mount(): void
    {
        if (app(Cart::class)->checkoutLines()->isEmpty()) {
            $this->redirectRoute('cart');

            return;
        }

        $this->couponCode = app(Cart::class)->coupon()?->code ?? '';

        if ($user = Auth::user()) {
            $this->name = $user->name;

            if ($default = $user->addresses()->first()) {
                $this->useAddress($default->id);
            }
        }
    }

    public function choose(string $fulfilment): void
    {
        $this->fulfilment = $fulfilment === 'delivery' ? 'delivery' : 'pickup';
        $this->problem = null;

        if ($this->paymentMethod && ! $this->paymentMethods()->contains('code', $this->paymentMethod)) {
            $this->paymentMethod = null;
        }
    }

    public function open(string $modal): void
    {
        $this->resetErrorBag();
        $this->modal = $modal;

        if ($modal === 'slot') {
            $firstOpen = app(PickupWindows::class)->days()->firstWhere('open', true);
            $this->slotDay ??= $firstOpen ? $firstOpen['date']->toDateString() : null;
            $this->slotStart = $this->pickupStart;
        }

        if ($modal === 'address' && Auth::guest() && $this->recipient === '') {
            $this->recipient = $this->name;
        }
    }

    public function close(): void
    {
        // Closing keeps whatever was typed.
        $this->modal = null;
    }

    // ----- Contact verification -------------------------------------------------

    public function sendCode(): void
    {
        $rules = ['name' => ['required', 'string', 'max:120']];

        if ($this->fulfilment === 'delivery') {
            $rules += $this->addressRules();
        }

        $useRecipientPhone = $this->fulfilment === 'delivery' && $this->channel === 'phone' && $this->recipientPhoneIsMine;

        $rules += match (true) {
            $this->channel === 'email' => ['email' => ['required', 'email:rfc', 'max:255']],
            $useRecipientPhone => [],
            default => ['phone' => ['required', 'regex:/^\+?[0-9 ()-]{10,20}$/']],
        };

        $this->validate($rules, [
            'phone.regex' => 'Enter a mobile number with area code.',
            'recipientPhone.regex' => 'Enter a phone number with area code.',
        ]);

        if ($this->fulfilment === 'delivery' && ! $this->checkAddressForm()) {
            return;
        }

        $destination = $this->channel === 'email'
            ? strtolower(trim($this->email))
            : self::normalisePhone($useRecipientPhone ? $this->recipientPhone : $this->phone);

        $key = 'checkout-code:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 6)) {
            $this->addError($this->channel === 'email' ? 'email' : 'phone', 'Too many codes requested. Try again in a few minutes.');

            return;
        }

        RateLimiter::hit($key, 600);

        $otp = app(OneTimeCode::class);
        $otp->send($this->channel, $destination);

        $this->codeChannel = $this->channel;
        $this->codeDestination = $destination;
        $this->resendAt = now()->addSeconds($otp->cooldown($this->channel, $destination))->timestamp;
        $this->code = '';
        $this->modal = 'code';
    }

    public function resendCode(): void
    {
        if (! $this->codeDestination) {
            return;
        }

        $otp = app(OneTimeCode::class);
        $otp->send($this->codeChannel, $this->codeDestination);
        $this->resendAt = now()->addSeconds($otp->cooldown($this->codeChannel, $this->codeDestination))->timestamp;
        $this->resetErrorBag('code');
    }

    public function changeContact(): void
    {
        $this->open($this->fulfilment === 'delivery' ? 'address' : 'contact');
    }

    public function verifyCode(): void
    {
        $this->validate(['code' => ['required', 'digits:6']], ['code.digits' => 'Enter all six digits.']);

        if (! app(OneTimeCode::class)->verify($this->codeChannel, $this->codeDestination, $this->code)) {
            $this->addError('code', 'This code is invalid or has expired. Check the latest code, or request a new one when the timer ends.');

            return;
        }

        $user = $this->signIn();

        if ($this->marketing) {
            app(ConsentService::class)->grant($this->codeChannel === 'email' ? 'email' : 'sms', $user->id, $user->email, 'checkout');
        }

        $this->code = '';
        $this->modal = null;

        if ($this->fulfilment === 'delivery') {
            $this->confirmAddress();
        } elseif (! $this->pickupStart) {
            $this->open('slot');
        }
    }

    /**
     * Find or create the account for the verified contact and sign in.
     * Accounts are matched only on the identifier that was just verified:
     * an email account and a phone account are never merged.
     */
    private function signIn(): User
    {
        $column = $this->codeChannel === 'email' ? 'email' : 'phone';

        $user = User::firstOrCreate([$column => $this->codeDestination], ['name' => trim($this->name)]);
        $user->forceFill([$column.'_verified_at' => $user->{$column.'_verified_at'} ?? now()])->save();

        Auth::login($user, remember: true);
        session()->regenerate();

        return $user;
    }

    // ----- Pickup -------------------------------------------------------------

    public function pickDay(string $date): void
    {
        $this->slotDay = CarbonImmutable::parse($date, config('shop.timezone'))->toDateString();
        $this->slotStart = null;
    }

    public function confirmSlot(): void
    {
        $window = $this->slotStart
            ? app(PickupWindows::class)->find(CarbonImmutable::parse($this->slotStart))
            : null;

        if (! $window) {
            $this->addError('slotStart', $this->slotStart ? 'This slot just filled up. Choose another time.' : 'Choose a time first.');

            return;
        }

        $this->pickupStart = $window['start']->toIso8601String();
        $this->modal = null;
        $this->problem = null;
    }

    // ----- Delivery -----------------------------------------------------------

    /**
     * Signed-in shoppers: validate the form, then use it straight away.
     */
    public function saveAddress(): void
    {
        $this->validate($this->addressRules(), ['recipientPhone.regex' => 'Enter a phone number with area code.']);

        if (! $this->checkAddressForm()) {
            return;
        }

        $this->confirmAddress();
        $this->modal = null;
    }

    public function useAddress(int $id): void
    {
        $address = Auth::user()?->addresses()->find($id);

        if (! $address) {
            return;
        }

        $this->selectedAddressId = $address->id;
        $this->fillAddressForm($address);
        $this->address = $address->toSnapshot();
        $this->addressStatus = app(DeliveryCheck::class)->check($this->address)['status'];
        $this->modal = null;
    }

    /**
     * Snapshot the form as the delivery address and check coverage.
     * Saving to the address book is opt-in and only for signed-in shoppers.
     */
    private function confirmAddress(): void
    {
        $this->address = [
            'recipient_name' => trim($this->recipient),
            'phone' => self::normalisePhone($this->recipientPhone),
            'street' => trim($this->street),
            'city' => trim($this->city),
            'region' => 'NB',
            'postal_code' => DeliveryCheck::normalisePostalCode($this->postalCode) ?? strtoupper(trim($this->postalCode)),
            'country' => $this->country,
        ];
        $this->addressStatus = app(DeliveryCheck::class)->check($this->address)['status'];

        if ($this->saveToAddressBook && Auth::check()) {
            $saved = Auth::user()->addresses()->updateOrCreate(
                ['street' => $this->address['street'], 'postal_code' => $this->address['postal_code']],
                $this->address,
            );
            $this->selectedAddressId = $saved->id;
        }
    }

    private function checkAddressForm(): bool
    {
        if (DeliveryCheck::normalisePostalCode($this->postalCode) === null && $this->country === 'CA') {
            $this->addError('postalCode', 'Check the postal code — Canadian codes look like E2L 4L1.');

            return false;
        }

        return true;
    }

    private function fillAddressForm(Address $address): void
    {
        $this->recipient = $address->recipient_name;
        $this->recipientPhone = $address->phone;
        $this->street = $address->street;
        $this->city = $address->city;
        $this->postalCode = $address->postal_code;
        $this->country = $address->country;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function addressRules(): array
    {
        return [
            'country' => ['required', 'in:CA'],
            'recipient' => ['required', 'string', 'max:120'],
            'recipientPhone' => ['required', 'regex:/^\+?[0-9 ()-]{10,20}$/'],
            'street' => ['required', 'string', 'max:200'],
            'city' => ['required', 'string', 'max:100'],
            'postalCode' => ['required', 'string', 'max:10'],
        ];
    }

    // ----- Payment ------------------------------------------------------------

    public function applyCoupon(): void
    {
        $this->validate(['couponCode' => ['required', 'string', 'max:40']], ['couponCode.required' => 'Enter a code first.']);

        $cart = app(Cart::class);
        $this->couponResult = $cart->applyCoupon($this->couponCode, $cart->subtotalCents());
        $this->couponCode = strtoupper(trim($this->couponCode));
    }

    public function removeCoupon(): void
    {
        app(Cart::class)->removeCoupon();
        $this->reset('couponCode', 'couponResult');
    }

    /**
     * Place the order. Card and wallet payments pass the token from Square's
     * Web Payments SDK; Pay at Pickup needs none.
     */
    public function pay(?string $token = null, ?string $attempt = null): void
    {
        $user = Auth::user();
        $online = $this->paymentMethod !== 'pay_at_pickup';

        if (! $user || ! $this->paymentMethod || ($online && ! $token)) {
            return;
        }

        $this->declined = null;

        try {
            $order = app(PlaceOrder::class)->handle(
                user: $user,
                contactName: $this->fulfilment === 'delivery' && $this->address ? $this->address['recipient_name'] : ($this->name ?: $user->name),
                fulfilment: $this->fulfilment,
                paymentMethod: $this->paymentMethod,
                pickupStart: $this->pickupStart ? CarbonImmutable::parse($this->pickupStart) : null,
                address: $this->fulfilment === 'delivery' ? $this->address : null,
                clearCart: ! $online,
            );
        } catch (CheckoutProblem $problem) {
            // Keep the verified identity, address and slot; just explain what to fix.
            $this->problem = $problem->getMessage();

            if (str_contains($problem->getMessage(), 'slot')) {
                $this->pickupStart = null;
            }

            return;
        }

        if ($online) {
            $failure = app(TakePayment::class)->charge($order, $token, $attempt ?: (string) str()->ulid());

            if ($failure) {
                // The order isn't placed; the basket, details and slot stay as they were.
                $this->declined = $failure;
                $this->declinedAmount = $order->total_cents;

                return;
            }

            app(Cart::class)->clear(...$order->items->pluck('product_variation_id')->all());
        }

        app(OrderReceipt::class)->send($order);
        session()->flash('order_placed', $order->number);

        $this->dispatch('cart-updated', count: app(Cart::class)->count());
        $this->redirectRoute('orders.show', $order);
    }

    public function retryPayment(): void
    {
        $this->declined = null;
    }

    public function changePaymentMethod(): void
    {
        $this->declined = null;
        $this->paymentMethod = null;
    }

    // ----- Helpers ------------------------------------------------------------

    public static function normalisePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        // North American numbers without a country code.
        return '+'.(strlen($digits) === 10 ? '1'.$digits : $digits);
    }

    private function paymentMethods()
    {
        return PaymentMethod::available($this->fulfilment)->get();
    }

    public function render()
    {
        $cart = app(Cart::class);
        $lines = $cart->lines();
        $checkoutLines = $cart->checkoutLines($lines);
        $user = Auth::user();
        $windows = app(PickupWindows::class);

        $deliveryReady = $this->fulfilment === 'delivery' && $this->address && $this->addressStatus === 'ok';
        $fee = match (true) {
            $this->fulfilment === 'pickup' => 0,
            $deliveryReady => CartTotals::deliveryFeeFor($cart->subtotalCents($lines)),
            default => null,
        };

        $fulfilmentReady = $this->fulfilment === 'pickup' ? $this->pickupStart !== null : $deliveryReady;

        $totals = CartTotals::for($checkoutLines, $cart->coupon(), $fee);

        return view('livewire.store.checkout', [
            'user' => $user,
            'checkoutLines' => $checkoutLines,
            'quantityChanged' => $lines->contains(fn ($line) => $line->notice),
            'totals' => $totals,
            'paymentMethods' => $this->paymentMethods(),
            'days' => $this->modal === 'slot' ? $windows->days() : collect(),
            'windows' => $this->modal === 'slot' && $this->slotDay ? $windows->windowsFor(CarbonImmutable::parse($this->slotDay, config('shop.timezone'))) : collect(),
            'savedAddresses' => $user ? $user->addresses : collect(),
            'readyToPay' => $user && $fulfilmentReady && $this->paymentMethod && $checkoutLines->isNotEmpty(),
            'fulfilmentReady' => $fulfilmentReady,
            'pickupLabel' => $this->pickupStart ? self::windowLabel(CarbonImmutable::parse($this->pickupStart)) : null,
            'square' => [
                'env' => config('services.square.env'),
                'appId' => config('services.square.app_id'),
                'locationId' => config('services.square.location_id'),
                'method' => $this->paymentMethod,
                'amount' => $totals->total(),
                'currency' => config('shop.currency'),
                'country' => 'CA',
            ],
        ]);
    }

    /**
     * "Wednesday 7 Oct 2026 · 11:00–13:00"
     */
    public static function windowLabel(CarbonImmutable $start): string
    {
        $start = $start->setTimezone(config('shop.timezone'));

        return $start->format('l j M Y').' · '.$start->format('H:i').'–'.$start->addHours(config('shop.pickup.window_hours'))->format('H:i');
    }
}
