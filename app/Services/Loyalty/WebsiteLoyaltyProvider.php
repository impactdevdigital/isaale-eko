<?php

namespace App\Services\Loyalty;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Loyalty run by the website itself: 1 hidden point per whole dollar of
 * eligible spend; every 100 points automatically unlock a $3-off reward
 * (a personal single-use code, minimum order $25, valid 30 days) and the
 * remainder carries over. Refunds take the progress back.
 *
 * No stacking: a reward code is one discount, and CartTotals never
 * combines it with a promotion.
 */
class WebsiteLoyaltyProvider implements LoyaltyProvider
{
    public function progress(User $user): LoyaltyProgress
    {
        $block = (int) config('loyalty.block_points');
        $perDollar = max(1, (int) config('loyalty.points_per_dollar'));

        return new LoyaltyProgress(
            // A refund after a reward unlocked can leave the balance below zero; show nothing counted.
            countedCents: intdiv(max(0, $user->pointsBalance()) * 100, $perDollar),
            blockCents: intdiv($block * 100, $perDollar),
            rewardValueCents: (int) config('loyalty.reward_value_cents'),
            rewardMinOrderCents: (int) config('loyalty.reward_min_order_cents'),
            rewards: Coupon::where('user_id', $user->id)
                ->where('code', 'like', 'REWARD-%')
                ->where('is_active', true)
                ->where('times_used', 0)
                ->where('expires_at', '>', now())
                ->orderBy('expires_at')
                ->get(),
        );
    }

    /**
     * Count an order once it is paid for: online at payment, or at the
     * counter when a pay-at-pickup order is collected.
     */
    public function recordPurchase(Order $order): void
    {
        $paid = $order->status === Order::STATUS_PAID
            || ($order->status === Order::STATUS_PLACED && $order->fulfilment_status === Order::FULFILMENT_COMPLETED);

        if (! $paid || ! $order->user_id || ($points = $this->pointsFor($this->eligibleCents($order))) <= 0) {
            return;
        }

        $earned = PointTransaction::firstOrCreate(
            ['order_id' => $order->id, 'type' => PointTransaction::TYPE_EARNED],
            ['user_id' => $order->user_id, 'points' => $points, 'description' => money($this->eligibleCents($order)).' spent'],
        );

        if ($earned->wasRecentlyCreated) {
            $this->unlockRewards($order->user);
        }
    }

    public function reversePurchase(Order $order, int $refundedCents): void
    {
        if (! $order->user_id) {
            return;
        }

        DB::transaction(function () use ($order, $refundedCents) {
            User::whereKey($order->user_id)->lockForUpdate()->first();

            $earned = (int) PointTransaction::where('order_id', $order->id)->where('type', PointTransaction::TYPE_EARNED)->sum('points');
            $reversed = (int) -PointTransaction::where('order_id', $order->id)->where('type', PointTransaction::TYPE_REVERSED)->sum('points');

            // Refunds can include the delivery fee, which never counted; only refunded goods come off.
            $target = min($earned, $this->pointsFor(min($refundedCents, $this->eligibleCents($order))));

            if (($delta = $target - $reversed) > 0) {
                PointTransaction::create([
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'type' => PointTransaction::TYPE_REVERSED,
                    'points' => -$delta,
                    'description' => money($delta * 100 / max(1, (int) config('loyalty.points_per_dollar'))).' refunded',
                ]);
            }
        });
    }

    /**
     * Turn every full block of points into a reward; the rest carries over.
     */
    private function unlockRewards(User $user): void
    {
        DB::transaction(function () use ($user) {
            // Serialise per customer so two payments landing together can't double-unlock.
            User::whereKey($user->id)->lockForUpdate()->first();

            $block = (int) config('loyalty.block_points');
            $value = (int) config('loyalty.reward_value_cents');

            while ($user->pointsBalance() >= $block) {
                $coupon = Coupon::create([
                    'code' => $this->newCode(),
                    'user_id' => $user->id,
                    'type' => 'fixed',
                    'value' => $value,
                    'min_subtotal_cents' => (int) config('loyalty.reward_min_order_cents'),
                    'max_uses' => 1,
                    'expires_at' => now()->addDays((int) config('loyalty.reward_valid_days')),
                    'is_active' => true,
                ]);

                $user->pointTransactions()->create([
                    'type' => PointTransaction::TYPE_REDEEMED,
                    'points' => -$block,
                    'coupon_id' => $coupon->id,
                    'description' => money($value).' off unlocked',
                ]);
            }
        });
    }

    /**
     * What counts: the items after any discount. No delivery fee, no tax.
     */
    private function eligibleCents(Order $order): int
    {
        return max(0, $order->subtotal_cents - $order->discount_cents);
    }

    private function pointsFor(int $cents): int
    {
        return intdiv($cents * (int) config('loyalty.points_per_dollar'), 100);
    }

    private function newCode(): string
    {
        do {
            $code = 'REWARD-'.strtoupper(Str::random(6));
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }
}
