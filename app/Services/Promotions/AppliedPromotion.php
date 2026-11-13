<?php

namespace App\Services\Promotions;

use App\Models\Promotion;

/**
 * A promotion as it applies to one basket: how many cents it takes off.
 */
final class AppliedPromotion
{
    public function __construct(
        public readonly Promotion $promotion,
        public readonly int $discount,
    ) {}
}
