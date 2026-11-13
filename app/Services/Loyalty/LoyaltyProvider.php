<?php

namespace App\Services\Loyalty;

use App\Models\Order;
use App\Models\User;

/**
 * The loyalty engine behind the website. The website only displays
 * progress and enforces the rules Square can't (minimum order, no
 * stacking, 30-day expiry); the engine itself can be the website
 * (WebsiteLoyaltyProvider) or, later, Square Loyalty, without the rest of
 * the code changing.
 */
interface LoyaltyProvider
{
    /**
     * Where the customer stands: spend counted towards the next reward, and
     * the rewards they have unlocked and not used yet.
     */
    public function progress(User $user): LoyaltyProgress;

    /**
     * Count a paid order towards the customer's next reward (safe to call
     * more than once for the same order).
     */
    public function recordPurchase(Order $order): void;

    /**
     * Take back the progress a refunded order earned. $refundedCents is the
     * order's total refunded so far.
     */
    public function reversePurchase(Order $order, int $refundedCents): void;
}
