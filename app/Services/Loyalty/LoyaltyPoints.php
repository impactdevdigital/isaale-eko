<?php

namespace App\Services\Loyalty;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Earning points on orders and trading them for a reward code.
 */
class LoyaltyPoints
{
    public function pointsFor(int $cents): int
    {
        return intdiv($cents * config('loyalty.points_per_dollar'), 100);
    }

    /**
     * Award points once an order has been paid for: online at payment, or
     * at the counter when a pay-at-pickup order is collected. Safe to call
     * more than once.
     */
    public function earnFor(Order $order): void
    {
        $paid = $order->status === Order::STATUS_PAID
            || ($order->status === Order::STATUS_PLACED && $order->fulfilment_status === Order::FULFILMENT_COMPLETED);

        if (! $paid || ! $order->user_id || ($points = $this->pointsFor($order->total_cents)) <= 0) {
            return;
        }

        PointTransaction::firstOrCreate(
            ['order_id' => $order->id, 'type' => PointTransaction::TYPE_EARNED],
            ['user_id' => $order->user_id, 'points' => $points, 'description' => money($order->total_cents).' spent'],
        );
    }

    /**
     * Spend points on a single-use code for this customer. Returns null when
     * the balance is too low.
     */
    public function redeem(User $user): ?Coupon
    {
        return DB::transaction(function () use ($user) {
            // Serialise redemptions per customer so a double-click can't overspend.
            User::whereKey($user->id)->lockForUpdate()->first();

            $cost = config('loyalty.reward_points');

            if ($user->pointsBalance() < $cost) {
                return null;
            }

            $value = config('loyalty.reward_value_cents');

            $coupon = Coupon::create([
                'code' => $this->newCode(),
                'user_id' => $user->id,
                'type' => 'fixed',
                'value' => $value,
                'max_uses' => 1,
                'expires_at' => now()->addDays(config('loyalty.reward_valid_days')),
                'is_active' => true,
            ]);

            $user->pointTransactions()->create([
                'type' => PointTransaction::TYPE_REDEEMED,
                'points' => -$cost,
                'coupon_id' => $coupon->id,
                'description' => money($value).' reward redeemed',
            ]);

            return $coupon;
        });
    }

    private function newCode(): string
    {
        do {
            $code = 'REWARD-'.strtoupper(Str::random(6));
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }
}
