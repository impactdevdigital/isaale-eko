<?php

namespace App\Services\Loyalty;

/**
 * A Square Loyalty reward tier a shopper has enough points for, e.g.
 * "100 points = $3 off". It becomes a Square reward (points held) when the
 * order is placed.
 */
final class LoyaltyReward
{
    public function __construct(
        public readonly string $accountId,
        public readonly string $tierId,
        public readonly string $name,
        public readonly int $points,
        public readonly ?int $fixedCents,
        public readonly ?float $percentage = null,
        public readonly ?int $maxCents = null,
    ) {}

    public function discountFor(int $subtotalCents): int
    {
        $discount = $this->fixedCents ?? (int) round($subtotalCents * ($this->percentage ?? 0) / 100);

        if ($this->maxCents !== null) {
            $discount = min($discount, $this->maxCents);
        }

        return max(0, min($discount, $subtotalCents));
    }
}
