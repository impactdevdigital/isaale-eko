<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Loyalty\SquareLoyalty;
use Illuminate\Console\Command;

/**
 * Catch-up for Square Loyalty: paid orders whose points didn't reach
 * Square (it was down, or the request timed out), and rewards held on
 * orders that will never be paid. Safe to run any time.
 */
class LoyaltySync extends Command
{
    protected $signature = 'loyalty:sync';

    protected $description = 'Send missed Square Loyalty points and give back points held on unpaid orders';

    public function handle(SquareLoyalty $loyalty): int
    {
        if (! $loyalty->enabled()) {
            $this->info('Square Loyalty is off (no active programme on the payments account, or LOYALTY_ENABLED=false).');

            return self::SUCCESS;
        }

        $since = now()->subDays((int) config('loyalty.retry_days'));
        $earned = 0;
        $released = 0;

        Order::whereNull('loyalty_synced_at')
            ->where('placed_at', '>=', $since)
            ->where(fn ($q) => $q->where('status', Order::STATUS_PAID)
                ->orWhere(fn ($q) => $q->where('status', Order::STATUS_PLACED)->where('fulfilment_status', Order::FULFILMENT_COMPLETED)))
            ->each(function (Order $order) use ($loyalty, &$earned) {
                $loyalty->recordPurchase($order);
                $earned += (int) ($order->fresh()->loyalty_synced_at !== null);
            });

        Order::where('loyalty_reward_status', SquareLoyalty::REWARD_ISSUED)
            ->whereIn('status', [Order::STATUS_PAYMENT_FAILED, Order::STATUS_CANCELLED, Order::STATUS_UNCOLLECTED])
            ->each(function (Order $order) use ($loyalty, &$released) {
                $loyalty->releaseReward($order);
                $released += (int) ($order->fresh()->loyalty_reward_status === SquareLoyalty::REWARD_RELEASED);
            });

        $this->info("Orders sent to Square Loyalty: {$earned}. Rewards given back: {$released}.");

        return self::SUCCESS;
    }
}
