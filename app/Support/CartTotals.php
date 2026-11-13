<?php

namespace App\Support;

use App\Models\Coupon;
use App\Models\User;
use App\Services\Promotions\AppliedPromotion;
use App\Services\Promotions\PromotionEngine;
use Illuminate\Support\Collection;

/**
 * Money breakdown shown in every Order Summary. All amounts are cents.
 * A null delivery fee means "not known yet" (no address checked).
 *
 * One discount per order: a promo/reward code or the best live promotion,
 * whichever saves the shopper more. They never stack.
 */
final class CartTotals
{
    public function __construct(
        public readonly int $itemCount,
        public readonly int $subtotal,
        public readonly int $savings,
        public readonly int $discount,
        public readonly ?int $deliveryFee,
        public readonly ?Coupon $coupon,
        public readonly ?AppliedPromotion $promotion = null,
        // A valid code the shopper entered that lost out to a better promotion.
        public readonly ?Coupon $setAsideCoupon = null,
    ) {}

    /**
     * @param  Collection<int, object>  $checkoutLines  ticked, available lines
     * @param  ?User  $customer  the shopper, when known, for promotion eligibility
     * @param  ?string  $email  a guest's email at checkout, for promotion eligibility
     */
    public static function for(Collection $checkoutLines, ?Coupon $coupon = null, ?int $deliveryFee = 0, ?User $customer = null, ?string $email = null): self
    {
        $subtotal = $checkoutLines->sum(fn ($line) => $line->variation->price_cents * $line->quantity);

        $savings = $checkoutLines->sum(fn ($line) => max(0, ($line->variation->previous_price_cents ?? 0) - $line->variation->price_cents) * $line->quantity);

        // A code that has lapsed since it was applied simply stops counting.
        $usable = $coupon && $coupon->isLive() && ! $coupon->isExpired()
            && (! $coupon->min_subtotal_cents || $subtotal >= $coupon->min_subtotal_cents);
        $couponDiscount = $usable ? $coupon->discountFor($subtotal) : 0;

        $promotion = app(PromotionEngine::class)->best($checkoutLines, $customer, $email);
        $promotionWins = $promotion && $promotion->discount > $couponDiscount;

        return new self(
            itemCount: (int) $checkoutLines->sum('quantity'),
            subtotal: $subtotal,
            savings: $savings,
            discount: $promotionWins ? $promotion->discount : $couponDiscount,
            deliveryFee: $deliveryFee,
            coupon: $usable && ! $promotionWins ? $coupon : null,
            promotion: $promotionWins ? $promotion : null,
            setAsideCoupon: $usable && $promotionWins ? $coupon : null,
        );
    }

    /**
     * Delivery fee for a subtotal: free over the threshold.
     */
    public static function deliveryFeeFor(int $subtotal): int
    {
        return $subtotal >= config('shop.delivery.free_over_cents') ? 0 : config('shop.delivery.fee_cents');
    }

    public function total(): int
    {
        return $this->subtotal - $this->discount + ($this->deliveryFee ?? 0);
    }

    /**
     * Cents still needed for free delivery, or 0 when already free.
     */
    public function untilFreeDelivery(): int
    {
        return max(0, config('shop.delivery.free_over_cents') - $this->subtotal);
    }
}
