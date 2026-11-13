<?php

namespace App\Services\Checkout;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Promotion;
use App\Models\PromotionRedemption;
use App\Models\User;
use App\Services\Analytics;
use App\Support\Cart;
use App\Support\CartTotals;
use App\Support\Delivery;
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
     * @param  string|null  $guestEmail  set for a guest (not signed in) checkout: the email
     *                                   they typed, unverified; it's where the receipt goes
     */
    public function handle(
        User $user,
        string $contactName,
        string $fulfilment,
        string $paymentMethod,
        ?CarbonInterface $pickupStart = null,
        ?array $address = null,
        bool $clearCart = true,
        ?string $guestEmail = null,
    ): Order {
        $lines = $this->cart->lines();
        $checkoutLines = $this->cart->checkoutLines($lines);

        if ($checkoutLines->isEmpty()) {
            throw new CheckoutProblem('Your basket has nothing ready to order.');
        }

        if ($lines->contains(fn ($line) => $line->notice)) {
            throw new CheckoutProblem('Some quantities changed because stock ran low. Check your order summary, then pay.');
        }

        if ($fulfilment === 'delivery' && ! Delivery::enabled()) {
            throw new CheckoutProblem('Delivery isn’t available right now. Choose Pickup to place your order.');
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

        // A guest only typed an email, so they can't use that account's personal reward codes.
        if ($coupon && ! $coupon->usableBy($guestEmail === null ? $user : null)) {
            $this->cart->removeCoupon();

            throw new CheckoutProblem('That reward code belongs to another account, so we removed it. Check your order summary, then pay.');
        }
        $subtotal = $this->cart->subtotalCents($lines);
        $totals = CartTotals::for($checkoutLines, $coupon, $fulfilment === 'delivery' ? CartTotals::deliveryFeeFor($subtotal) : 0, $user, $guestEmail);
        $contact = $guestEmail !== null
            ? ['channel' => 'email', 'value' => $guestEmail]
            : ($user->signInContact() ?? ['channel' => 'email', 'value' => $user->email]);

        $order = DB::transaction(function () use ($user, $contactName, $fulfilment, $method, $window, $address, $totals, $checkoutLines, $contact) {
            $order = Order::create([
                'number' => $this->newNumber(),
                'user_id' => $user->id,
                'status' => Order::STATUS_AWAITING_PAYMENT,
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
                'promotion_id' => $totals->promotion?->promotion->id,
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

            if ($applied = $totals->promotion) {
                // Re-check the cap under a lock so two last-minute orders can't both take the final redemption.
                $promotion = Promotion::whereKey($applied->promotion->id)->lockForUpdate()->first();

                if (! $promotion?->hasRoom()) {
                    throw new CheckoutProblem('The '.$applied->promotion->name.' offer has just run out. Check your order summary, then pay.');
                }

                PromotionRedemption::create([
                    'promotion_id' => $promotion->id,
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'discount_cents' => $applied->discount,
                ]);

                Analytics::track('promotion_redeemed', ['promotion_id' => $promotion->id, 'order_id' => $order->id, 'discount_cents' => $applied->discount]);
            }

            return $order;
        });

        // Online payments keep the basket until the charge succeeds.
        if ($clearCart) {
            $this->cart->clear(...$checkoutLines->keys()->all());
        }

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
