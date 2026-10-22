<?php

namespace App\Services\Checkout;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Support\Cart;
use App\Support\CartTotals;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Turns the ticked basket lines into an order after re-checking stock,
 * the pickup window or delivery address, and the payment method.
 * Throws CheckoutProblem with a shopper-facing message when something
 * changed since the page was loaded.
 */
class PlaceOrder
{
    public function __construct(
        private Cart $cart,
        private PickupWindows $windows,
        private DeliveryCheck $delivery,
    ) {}

    /**
     * @param  array<string, string|null>|null  $address  delivery snapshot, for delivery orders
     */
    public function handle(
        User $user,
        string $contactName,
        string $fulfilment,
        string $paymentMethod,
        ?CarbonInterface $pickupStart = null,
        ?array $address = null,
    ): Order {
        $lines = $this->cart->lines();
        $checkoutLines = $this->cart->checkoutLines($lines);

        if ($checkoutLines->isEmpty()) {
            throw new CheckoutProblem('Your basket has nothing ready to order.');
        }

        if ($lines->contains(fn ($line) => $line->notice)) {
            throw new CheckoutProblem('Some quantities changed because stock ran low. Check your order summary, then pay.');
        }

        $method = PaymentMethod::available($fulfilment)->where('code', $paymentMethod)->first();

        if (! $method) {
            throw new CheckoutProblem('Choose a payment method that works for '.$fulfilment.'.');
        }

        $window = null;

        if ($fulfilment === 'pickup') {
            $window = $pickupStart ? $this->windows->find($pickupStart) : null;

            if (! $window) {
                throw new CheckoutProblem('This slot just filled up. Choose another time.');
            }
        } elseif (! $address || $this->delivery->check($address)['status'] !== 'ok') {
            throw new CheckoutProblem('We can’t deliver to this address. Edit it or choose Pickup.');
        }

        $coupon = $this->cart->coupon();
        $subtotal = $this->cart->subtotalCents($lines);
        $totals = CartTotals::for($checkoutLines, $coupon, $fulfilment === 'delivery' ? CartTotals::deliveryFeeFor($subtotal) : 0);
        $contact = $user->signInContact();

        $order = DB::transaction(function () use ($user, $contactName, $fulfilment, $method, $window, $address, $totals, $checkoutLines, $contact) {
            $order = Order::create([
                'number' => $this->newNumber(),
                'user_id' => $user->id,
                'status' => $method->code === 'pay_at_pickup' ? Order::STATUS_PLACED : Order::STATUS_AWAITING_PAYMENT,
                'fulfilment' => $fulfilment,
                'contact_name' => $contactName,
                'contact_email' => $contact['channel'] === 'email' ? $contact['value'] : null,
                'contact_phone' => $contact['channel'] === 'phone' ? $contact['value'] : ($address['phone'] ?? null),
                'pickup_starts_at' => $window ? $window['start']->utc() : null,
                'pickup_ends_at' => $window ? $window['end']->utc() : null,
                'delivery_address' => $fulfilment === 'delivery' ? $address : null,
                'subtotal_cents' => $totals->subtotal,
                'savings_cents' => $totals->savings,
                'discount_cents' => $totals->discount,
                'delivery_fee_cents' => $totals->deliveryFee ?? 0,
                'total_cents' => $totals->total(),
                'coupon_code' => $totals->coupon?->code,
                'payment_method' => $method->code,
                'placed_at' => now(),
            ]);

            foreach ($checkoutLines as $line) {
                $order->items()->create([
                    'product_id' => $line->variation->product_id,
                    'product_variation_id' => $line->variation->id,
                    'name' => $line->variation->product->name,
                    'variation_name' => $line->variation->name,
                    'image' => $line->variation->product->image,
                    'unit_price_cents' => $line->variation->price_cents,
                    'quantity' => $line->quantity,
                    'line_total_cents' => $line->variation->price_cents * $line->quantity,
                ]);
            }

            if ($totals->coupon) {
                Coupon::whereKey($totals->coupon->id)->increment('times_used');
            }

            return $order;
        });

        $this->cart->clear(...$checkoutLines->keys()->all());

        return $order;
    }

    private function newNumber(): string
    {
        do {
            $number = 'ISA-'.strtoupper(Str::random(6));
        } while (Order::where('number', $number)->exists());

        return $number;
    }
}
