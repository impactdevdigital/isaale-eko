<?php

namespace App\Services\Loyalty;

use App\Models\Coupon;
use Illuminate\Support\Collection;

/**
 * What a customer sees: "$76 / $100" and their unlocked rewards.
 */
final class LoyaltyProgress
{
    /**
     * @param  Collection<int, Coupon>  $rewards  unlocked, unused, unexpired
     */
    public function __construct(
        public readonly int $countedCents,
        public readonly int $blockCents,
        public readonly int $rewardValueCents,
        public readonly int $rewardMinOrderCents,
        public readonly Collection $rewards,
    ) {}

    public function remainingCents(): int
    {
        return max(0, $this->blockCents - $this->countedCents);
    }

    public function percent(): int
    {
        return (int) min(100, round($this->countedCents / max(1, $this->blockCents) * 100));
    }
}
