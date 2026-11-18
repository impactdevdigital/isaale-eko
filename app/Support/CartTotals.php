<?php

namespace App\Support;

use App\Models\User;
use App\Services\Loyalty\LoyaltyReward;
use App\Services\Promotions\AppliedPromotion;
use App\Services\Promotions\PromotionEngine;
use Illuminate\Support\Collection;

/**
 * Money breakdown shown in every Order Summary. All amounts are cents.
 * A null delivery fee means "not known yet" (no address checked).
 *
 * One discount per order: a Square Loyalty reward or the best live
 * promotion, whichever saves the shopper more. They never stack.
 */
final class CartTotals
{
    public function __construct(
        public readonly int $itemCount,
        public readonly int $subtotal,
        public readonly int $savings,
        public readonly int $discount,
        public readonly ?int $deliveryFee,
        public readonly ?LoyaltyReward $reward = null,
        public readonly ?AppliedPromotion $promotion = null,
        // A reward the shopper chose that lost out to a better promotion (no points are spent).
        public readonly ?LoyaltyReward $setAsideReward = null,
    ) {}

    /**
     * @param  Collection<int, object>  $checkoutLines  ticked, available lines
     * @param  ?User  $customer  the shopper, when known, for promotion eligibility
     * @param  ?string  $email  a guest's email at checkout, for promotion eligibility
     */
    public static function for(Collection $checkoutLines, ?LoyaltyReward $reward = null, ?int $deliveryFee = 0, ?User $customer = null, ?string $email = null): self
    {
        $subtotal = $checkoutLines->sum(fn ($line) => $line->variation->price_cents * $line->quantity);

        // No "was" prices or product savings: the shop doesn't run product discounts.
        $savings = 0;

        $rewardDiscount = $reward ? $reward->discountFor($subtotal) : 0;
        $usable = $rewardDiscount > 0;

        $promotion = app(PromotionEngine::class)->best($checkoutLines, $customer, $email);
        $promotionWins = $promotion && $promotion->discount > $rewardDiscount;

        return new self(
            itemCount: (int) $checkoutLines->sum('quantity'),
            subtotal: $subtotal,
            savings: $savings,
            discount: $promotionWins ? $promotion->discount : $rewardDiscount,
            deliveryFee: $deliveryFee,
            reward: $usable && ! $promotionWins ? $reward : null,
            promotion: $promotionWins ? $promotion : null,
            setAsideReward: $usable && $promotionWins ? $reward : null,
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
