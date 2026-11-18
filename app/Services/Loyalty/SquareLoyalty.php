<?php

namespace App\Services\Loyalty;

use App\Models\Order;
use App\Models\User;
use App\Services\Square\SquareGateway;
use Carbon\Carbon;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The website side of the shop's Square Loyalty programme. Shoppers are
 * found (or signed up) by phone number. A paid order earns points by the
 * programme's own rules; a refund takes them back. A signed-in shopper
 * with enough points can spend a reward online: the points are held when
 * the order is placed, used once it is paid, and given back if it never is.
 *
 * Square stays the source of truth: balances and rewards are the same
 * ones the till sees. Nothing here ever blocks a sale; when Square can't
 * be reached the order goes through and loyalty:sync catches up.
 */
class SquareLoyalty
{
    public const REWARD_ISSUED = 'issued';

    public const REWARD_REDEEMED = 'redeemed';

    public const REWARD_RELEASED = 'released';

    public function __construct(private SquareGateway $square) {}

    /**
     * The website side is switched on and the payments account has an
     * active loyalty programme.
     */
    public function enabled(): bool
    {
        return config('loyalty.enabled')
            && filled(SquareGateway::payments()['token'])
            && $this->program() !== null;
    }

    /**
     * The active programme, cached for an hour (a missing one for 10 minutes).
     *
     * @return ?array{id: string, tier: array}
     */
    public function program(): ?array
    {
        $key = 'loyalty:program:'.sha1((string) SquareGateway::payments()['token']);

        if (($cached = Cache::get($key)) !== null) {
            return $cached ?: null;
        }

        try {
            $program = $this->square->loyaltyProgram();
        } catch (Throwable $e) {
            report($e);
            Cache::put($key, false, now()->addMinutes(5));

            return null;
        }

        $tier = collect($program['reward_tiers'] ?? [])->sortBy('points')->first();
        $found = ($program['status'] ?? null) === 'ACTIVE' && $tier
            ? ['id' => $program['id'], 'tier' => $tier]
            : false;

        Cache::put($key, $found, $found ? now()->addHour() : now()->addMinutes(10));

        return $found ?: null;
    }

    /**
     * A phone number as Square wants it (+15065550123), or null when it
     * isn't one. Ten digits are taken as North American.
     */
    public static function phone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return match (true) {
            strlen($digits) === 10 => '+1'.$digits,
            strlen($digits) >= 11 && strlen($digits) <= 15 => '+'.$digits,
            default => null,
        };
    }

    public function progress(User $user, bool $withHistory = true): LoyaltyProgress
    {
        if (! $this->enabled()) {
            return LoyaltyProgress::off();
        }

        $tier = $this->program()['tier'];
        $base = ['enabled' => true, 'rewardPoints' => (int) $tier['points'], 'rewardName' => $this->tierName($tier)];
        $phone = self::phone($user->phone);

        if (! $phone) {
            return new LoyaltyProgress(...$base, history: collect());
        }

        try {
            $accountId = $this->accountId($phone);

            return new LoyaltyProgress(
                ...$base,
                phone: $phone,
                member: $accountId !== null,
                points: $accountId ? $this->balance($accountId) : 0,
                history: $accountId && $withHistory ? $this->history($accountId) : collect(),
            );
        } catch (Throwable $e) {
            report($e);

            return new LoyaltyProgress(...$base, available: false, phone: $phone, history: collect());
        }
    }

    /**
     * The reward a signed-in shopper could spend on this order, if they
     * have the points for it.
     */
    public function availableReward(?User $user): ?LoyaltyReward
    {
        if (! $user || ! ($phone = self::phone($user->phone)) || ! $this->enabled()) {
            return null;
        }

        try {
            $tier = $this->program()['tier'];
            $accountId = $this->accountId($phone);

            if (! $accountId || $this->balance($accountId) < (int) $tier['points']) {
                return null;
            }
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        $definition = $tier['definition'] ?? [];

        return new LoyaltyReward(
            accountId: $accountId,
            tierId: $tier['id'],
            name: $this->tierName($tier),
            points: (int) $tier['points'],
            fixedCents: ($definition['discount_type'] ?? 'FIXED_AMOUNT') === 'FIXED_PERCENTAGE' ? null : (int) ($definition['fixed_discount_money']['amount'] ?? 0),
            percentage: isset($definition['percentage_discount']) ? (float) $definition['percentage_discount'] : null,
            maxCents: isset($definition['max_discount_money']['amount']) ? (int) $definition['max_discount_money']['amount'] : null,
        );
    }

    /**
     * Hold the reward's points in Square. Returns the Square reward id.
     */
    public function reserve(LoyaltyReward $reward, string $idempotencyKey): string
    {
        $created = $this->square->createLoyaltyReward($reward->accountId, $reward->tierId, $idempotencyKey);
        Cache::forget('loyalty:balance:'.$reward->accountId);

        return $created['id'];
    }

    /**
     * Give a reward's points back (the order didn't go ahead).
     */
    public function cancelReservation(string $rewardId, ?string $accountId = null): void
    {
        try {
            $this->square->deleteLoyaltyReward($rewardId);
        } catch (Throwable $e) {
            report($e);
        }

        if ($accountId) {
            Cache::forget('loyalty:balance:'.$accountId);
        }
    }

    /**
     * Called whenever an order's status changes: earn and use rewards once
     * it's paid, give held points back if it never will be. The Square
     * calls run after the response so checkout isn't kept waiting.
     */
    public function orderChanged(Order $order): void
    {
        if (self::isPaid($order) && $order->loyalty_synced_at === null) {
            if (! $this->enabled()) {
                // Paid while loyalty was off (e.g. test payments): it never earns, even later.
                $order->forceFill(['loyalty_synced_at' => now()])->saveQuietly();

                return;
            }

            $id = $order->id;
            dispatch(fn () => app(self::class)->recordPurchase(Order::find($id)))->afterResponse();
        }

        if ($this->releasable($order)) {
            $id = $order->id;
            dispatch(fn () => app(self::class)->releaseReward(Order::find($id)))->afterResponse();
        }
    }

    /**
     * Use the order's reward and add the points it earned (safe to repeat).
     * Failures are logged and left for loyalty:sync to retry.
     */
    public function recordPurchase(?Order $order): void
    {
        if (! $order || ! self::isPaid($order) || $order->loyalty_synced_at !== null || ! $this->enabled()) {
            return;
        }

        try {
            if ($order->loyalty_reward_id && $order->loyalty_reward_status === self::REWARD_ISSUED) {
                $this->square->redeemLoyaltyReward($order->loyalty_reward_id, 'loyalty-redeem-'.$order->id);
                $order->forceFill(['loyalty_reward_status' => self::REWARD_REDEEMED])->saveQuietly();
            }

            $accountId = $order->loyalty_account_id;
            $phone = self::phone($order->contact_phone ?: $order->user?->phone);

            if (! $accountId && $phone) {
                $accountId = $this->accountId($phone, create: true);
            }

            $points = 0;
            $eligible = self::eligibleCents($order);

            if ($accountId && $eligible > 0) {
                $points = $this->square->calculateLoyaltyPoints($this->program()['id'], $eligible, config('shop.currency'));

                if ($points > 0) {
                    $this->square->accumulateLoyaltyPoints($accountId, $points, 'loyalty-earn-'.$order->id);
                    Cache::forget('loyalty:balance:'.$accountId);
                }
            }

            $order->forceFill([
                'loyalty_account_id' => $accountId,
                'loyalty_points' => $accountId ? $points : null,
                'loyalty_synced_at' => now(),
            ])->saveQuietly();
        } catch (Throwable $e) {
            Log::warning('Loyalty: could not record order '.$order->number.': '.$e->getMessage());
        }
    }

    /**
     * Take back the points a refunded order earned. $refundedCents is the
     * order's total refunded so far; delivery fees and tax never earned
     * points, so only refunded goods count.
     */
    public function reversePurchase(Order $order, int $refundedCents): void
    {
        if (! $order->loyalty_points || ! $order->loyalty_account_id || ! $this->enabled()) {
            return;
        }

        try {
            $refundedGoods = min($refundedCents, self::eligibleCents($order));
            $target = $refundedGoods >= self::eligibleCents($order)
                ? $order->loyalty_points
                : min($order->loyalty_points, $this->square->calculateLoyaltyPoints($this->program()['id'], $refundedGoods, config('shop.currency')));

            if (($delta = $target - $order->loyalty_points_reversed) > 0) {
                $this->square->adjustLoyaltyPoints($order->loyalty_account_id, -$delta, 'Refund on website order '.$order->number, 'loyalty-refund-'.$order->id.'-'.$target);
                $order->forceFill(['loyalty_points_reversed' => $target])->saveQuietly();
                Cache::forget('loyalty:balance:'.$order->loyalty_account_id);
            }
        } catch (Throwable $e) {
            Log::warning('Loyalty: could not take back points for order '.$order->number.': '.$e->getMessage());
        }
    }

    /**
     * Give back the points held for a reward on an order that won't be paid.
     */
    public function releaseReward(?Order $order): void
    {
        if (! $order || ! $this->releasable($order)) {
            return;
        }

        try {
            $this->square->deleteLoyaltyReward($order->loyalty_reward_id);
        } catch (Throwable $e) {
            // Already gone in Square (used or deleted there): nothing to give back.
            if (! ($e instanceof RequestException && in_array($e->response->status(), [400, 404], true))) {
                Log::warning('Loyalty: could not give back reward on order '.$order->number.': '.$e->getMessage());

                return;
            }
        }

        $order->forceFill(['loyalty_reward_status' => self::REWARD_RELEASED])->saveQuietly();
        Cache::forget('loyalty:balance:'.$order->loyalty_account_id);
    }

    /**
     * Paid online, or paid at the counter when collected.
     */
    public static function isPaid(Order $order): bool
    {
        return $order->status === Order::STATUS_PAID
            || ($order->status === Order::STATUS_PLACED && $order->fulfilment_status === Order::FULFILMENT_COMPLETED);
    }

    /**
     * What earns points: the items after any discount. No delivery fee, no tax.
     */
    public static function eligibleCents(Order $order): int
    {
        return max(0, $order->subtotal_cents - $order->discount_cents);
    }

    private function releasable(Order $order): bool
    {
        return $order->loyalty_reward_id
            && $order->loyalty_reward_status === self::REWARD_ISSUED
            && in_array($order->status, [Order::STATUS_PAYMENT_FAILED, Order::STATUS_CANCELLED, Order::STATUS_UNCOLLECTED], true);
    }

    /**
     * The loyalty account for a phone number: looked up once a day, and
     * created (the shopper joins) when $create and there isn't one.
     */
    public function accountId(string $phone, bool $create = false): ?string
    {
        $key = 'loyalty:account:'.sha1(SquareGateway::payments()['token'].$phone);

        $cached = Cache::get($key);

        // false = "not a member" (checked in the last 5 minutes), so basket pages don't ask Square on every refresh.
        if ($cached || ($cached === false && ! $create)) {
            return $cached ?: null;
        }

        $id = $this->square->findLoyaltyAccount($phone)['id'] ?? null;

        if (! $id && $create) {
            $id = $this->square->createLoyaltyAccount($this->program()['id'], $phone, 'loyalty-join-'.sha1($phone))['id'];
        }

        Cache::put($key, $id ?: false, $id ? now()->addDay() : now()->addMinutes(5));

        return $id;
    }

    private function balance(string $accountId): int
    {
        return (int) Cache::remember('loyalty:balance:'.$accountId, 60, fn () => $this->square->loyaltyAccount($accountId)['balance'] ?? 0);
    }

    /**
     * @return Collection<int, object>
     */
    private function history(string $accountId)
    {
        $labels = [
            'ACCUMULATE_POINTS' => 'Points earned',
            'ACCUMULATE_PROMOTION_POINTS' => 'Bonus points',
            'CREATE_REWARD' => 'Reward used',
            'DELETE_REWARD' => 'Reward cancelled, points back',
            'ADJUST_POINTS' => 'Points adjusted',
            'EXPIRE_POINTS' => 'Points expired',
        ];

        return collect($this->square->loyaltyEvents($accountId))
            ->filter(fn ($event) => isset($labels[$event['type'] ?? '']))
            ->map(function ($event) use ($labels) {
                $detail = $event[strtolower($event['type'])] ?? [];

                return (object) [
                    'date' => Carbon::parse($event['created_at']),
                    'label' => $labels[$event['type']],
                    'where' => ($event['source'] ?? '') === 'SQUARE' ? 'In store' : 'Online',
                    'points' => (int) ($detail['points'] ?? 0),
                ];
            })
            ->values();
    }

    private function tierName(array $tier): string
    {
        return (string) ($tier['name'] ?? ($tier['points'].' points reward'));
    }
}
