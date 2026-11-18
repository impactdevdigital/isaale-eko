<?php

namespace App\Services\Loyalty;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * What a customer sees on their account: their Square Loyalty points,
 * progress to the next reward and recent activity (online and in store).
 */
final class LoyaltyProgress
{
    /**
     * @param  bool  $enabled  the shop's loyalty programme is live on the website
     * @param  bool  $available  Square answered (false: show "try again later")
     * @param  ?string  $phone  the number the account is under, if the customer gave one
     * @param  Collection<int, object{date: CarbonInterface, label: string, where: string, points: int}>  $history
     */
    public function __construct(
        public readonly bool $enabled,
        public readonly bool $available = true,
        public readonly ?string $phone = null,
        public readonly bool $member = false,
        public readonly int $points = 0,
        public readonly int $rewardPoints = 100,
        public readonly string $rewardName = '',
        public readonly ?Collection $history = null,
    ) {}

    public static function off(): self
    {
        return new self(enabled: false);
    }

    public function rewardsReady(): int
    {
        return intdiv($this->points, max(1, $this->rewardPoints));
    }

    /** Points collected towards the next reward. */
    public function towardsNext(): int
    {
        return $this->points % max(1, $this->rewardPoints);
    }

    public function remaining(): int
    {
        return $this->rewardPoints - $this->towardsNext();
    }

    public function percent(): int
    {
        return (int) min(100, round($this->towardsNext() / max(1, $this->rewardPoints) * 100));
    }
}
