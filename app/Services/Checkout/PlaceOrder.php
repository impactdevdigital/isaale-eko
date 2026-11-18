<?php

namespace App\Services\Checkout;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Promotion;
use App\Models\PromotionRedemption;
use App\Models\User;
use App\Services\Analytics;
use App\Services\Loyalty\SquareLoyalty;
use App\Support\Cart;
use App\Support\CartTotals;
use App\Support\Delivery;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

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
        private SquareLoyalty $loyalty,
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
        ?string $contactPhone = null,
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

        // A Square Loyalty reward is only for a signed-in shopper who still has the points.
        $reward = null;

        if ($this->cart->usingReward()) {
            $reward = $guestEmail === null ? $this->loyalty->availableReward($user) : null;

            if (! $reward) {
                $this->cart->dropReward();

                throw new CheckoutProblem('Your loyalty reward isn’t available any more, so we took it off. Check your order summary, then pay.');
            }
        }

        $subtotal = $this->cart->subtotalCents($lines);
        $totals = CartTotals::for($checkoutLines, $reward, $fulfilment === 'delivery' ? CartTotals::deliveryFeeFor($subtotal) : 0, $user, $guestEmail);

        // Hold the reward's points in Square now; they're used once the order is paid (see SquareLoyalty).
        $heldReward = null;

        if ($totals->reward) {
            try {
                $heldReward = $this->loyalty->reserve($totals->reward, 'loyalty-reward-'.Str::uuid());
            } catch (Throwable $e) {
                report($e);

                throw new CheckoutProblem('We couldn’t reach our rewards programme just now. Try again, or remove the reward to pay without it.');
            }
        }

        $contact = $guestEmail !== null
            ? ['channel' => 'email', 'value' => $guestEmail]
            : ($user->signInContact() ?? ['channel' => 'email', 'value' => $user->email]);

        try {
            $order = $this->createOrder($user, $contactName, $fulfilment, $method, $window, $address, $totals, $checkoutLines, $contact, $contactPhone, $heldReward);
        } catch (Throwable $e) {
            if ($heldReward) {
                $this->loyalty->cancelReservation($heldReward, $totals->reward->accountId);
            }

            throw $e;
        }

        // Online payments keep the basket until the charge succeeds.
        if ($clearCart) {
            $this->cart->clear(...$checkoutLines->keys()->all());
        }

        return $order;
    }

    private function createOrder(User $user, string $contactName, string $fulfilment, PaymentMethod $method, ?array $window, ?array $address, CartTotals $totals, $checkoutLines, array $contact, ?string $contactPhone, ?string $heldReward): Order
    {
        return DB::transaction(function () use ($user, $contactName, $fulfilment, $method, $window, $address, $totals, $checkoutLines, $contact, $contactPhone, $heldReward) {
            $order = Order::create([
                'number' => $this->newNumber(),
                'user_id' => $user->id,
                'status' => Order::STATUS_AWAITING_PAYMENT,
                'fulfilment' => $fulfilment,
                'contact_name' => $contactName,
                'contact_email' => $contact['channel'] === 'email' ? $contact['value'] : null,
                'contact_phone' => $contactPhone ?? ($contact['channel'] === 'phone' ? $contact['value'] : ($address['phone'] ?? null)),
                'pickup_starts_at' => $window ? $window['start']->utc() : null,
                'pickup_ends_at' => $window ? $window['end']->utc() : null,
                'delivery_address' => $fulfilment === 'delivery' ? $address : null,
                'subtotal_cents' => $totals->subtotal,
                'savings_cents' => $totals->savings,
                'discount_cents' => $totals->discount,
                'delivery_fee_cents' => $totals->deliveryFee ?? 0,
                'total_cents' => $totals->total(),
                'promotion_id' => $totals->promotion?->promotion->id,
                'loyalty_account_id' => $totals->reward?->accountId,
                'loyalty_reward_id' => $heldReward,
                'loyalty_reward_points' => $heldReward ? $totals->reward->points : null,
                'loyalty_reward_status' => $heldReward ? SquareLoyalty::REWARD_ISSUED : null,
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
    }

    private function newNumber(): string
    {
        do {
            $number = 'ISA-'.strtoupper(Str::random(6));
        } while (Order::where('number', $number)->exists());

        return $number;
    }
}
