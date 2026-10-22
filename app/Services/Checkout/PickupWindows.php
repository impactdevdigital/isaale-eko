<?php

namespace App\Services\Checkout;

use App\Models\Order;
use App\Services\Store\StoreHours;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Pickup windows (two hours by default) built from the store's opening
 * hours. A window can be chosen when it still has a pickup slot after the
 * lead time and isn't at capacity. Choosing one is not a reservation: it
 * is checked again when the order is placed.
 */
class PickupWindows
{
    public function __construct(private StoreHours $hours) {}

    /**
     * The next week, including closed days so the calendar reads naturally.
     *
     * @return Collection<int, array{date: CarbonImmutable, open: bool}>
     */
    public function days(int $count = 7): Collection
    {
        $today = CarbonImmutable::now(config('shop.timezone'))->startOfDay();

        return collect(range(0, $count - 1))->map(function (int $offset) use ($today) {
            $date = $today->addDays($offset);

            return ['date' => $date, 'open' => $this->windowsFor($date)->contains('available', true)];
        });
    }

    /**
     * @return Collection<int, array{start: CarbonImmutable, end: CarbonImmutable, available: bool}>
     */
    public function windowsFor(CarbonInterface $day): Collection
    {
        $opening = $this->hours->hoursFor($day);

        if (! $opening) {
            return collect();
        }

        [$open, $close] = array_map(fn ($time) => CarbonImmutable::instance($time), $opening);
        $slots = $this->hours->slotsFor($day);
        $hours = config('shop.pickup.window_hours');
        $windows = collect();

        for ($start = $open; $start->lessThan($close); $start = $start->addHours($hours)) {
            $end = $start->addHours($hours)->min($close);
            $hasSlot = $slots->contains(fn ($slot) => $slot->greaterThanOrEqualTo($start) && $slot->lessThan($end));

            $windows->push([
                'start' => $start,
                'end' => $end,
                'available' => $hasSlot && ! $this->isFull($start),
            ]);
        }

        return $windows;
    }

    /**
     * The window starting at this moment, if it can still be chosen.
     *
     * @return array{start: CarbonImmutable, end: CarbonImmutable, available: bool}|null
     */
    public function find(CarbonInterface $start): ?array
    {
        return $this->windowsFor($start->copy()->setTimezone(config('shop.timezone')))
            ->first(fn ($window) => $window['available'] && $window['start']->equalTo($start));
    }

    private function isFull(CarbonInterface $start): bool
    {
        return Order::where('fulfilment', 'pickup')
            ->where('pickup_starts_at', $start->copy()->utc())
            ->count() >= config('shop.pickup.window_capacity');
    }
}
