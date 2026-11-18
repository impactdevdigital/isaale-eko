<?php

namespace App\Livewire\Store\Concerns;

use App\Services\Loyalty\LoyaltyReward;
use App\Services\Loyalty\SquareLoyalty;
use App\Support\Cart;
use Illuminate\Support\Facades\Auth;

/**
 * The "Use your reward" choice in the Order Summary: a signed-in shopper
 * with enough Square Loyalty points can spend a reward on this order.
 */
trait SpendsLoyaltyReward
{
    public function useReward(): void
    {
        app(Cart::class)->useReward();
    }

    public function dropReward(): void
    {
        app(Cart::class)->dropReward();
    }

    /**
     * The reward this shopper could spend, if any.
     */
    protected function rewardOffer(): ?LoyaltyReward
    {
        return app(SquareLoyalty::class)->availableReward(Auth::user());
    }

    /**
     * The reward to count in the totals: offered and chosen.
     */
    protected function chosenReward(?LoyaltyReward $offer): ?LoyaltyReward
    {
        return $offer && app(Cart::class)->usingReward() ? $offer : null;
    }
}
