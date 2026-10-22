<?php

namespace App\Services\Store;

use App\Models\Setting;
use App\Models\StoreClosure;
use App\Models\StoreHour;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class StoreHours
{
    private function tz(): string
    {
        return config('shop.timezone', 'America/Moncton');
    }

    /**
     * [opens, closes] for that day in shop timezone, or null if closed.
     */
    public function hoursFor(CarbonInterface $day): ?array
    {
        $date = Carbon::instance($day)->setTimezone($this->tz())->startOfDay();

        if (StoreClosure::whereDate('date', $date->toDateString())->exists()) {
            return null;
        }

        $h = StoreHour::where('weekday', $date->dayOfWeek)->first();

        if (!$h || $h->is_closed || !$h->opens_at || !$h->closes_at) {
            return null;
        }

        return [
            Carbon::parse($date->toDateString() . ' ' . $h->opens_at, $this->tz()),
            Carbon::parse($date->toDateString() . ' ' . $h->closes_at, $this->tz()),
        ];
    }

    public function isOpenAt(CarbonInterface $moment): bool
    {
        $hours = $this->hoursFor($moment);
        return $hours && $moment->betweenIncluded($hours[0], $hours[1]);
    }

    /**
     * Allowed pickup times on a day (15-min intervals, open hours, not too soon).
     */
    public function slotsFor(CarbonInterface $day): Collection
    {
        $hours = $this->hoursFor($day);
        if (!$hours) {
            return collect();
        }

        [$open, $close] = $hours;

        $leadMinutes = (int) Setting::get('pickup_lead_minutes', 30);
        $stepMinutes = (int) Setting::get('pickup_step_minutes', 15);
        $bufferMinutes = (int) Setting::get('pickup_last_slot_buffer_minutes', 15);

        $earliest = now()->setTimezone($this->tz())->addMinutes($leadMinutes);
        $last = $close->copy()->subMinutes($bufferMinutes);

        $slots = collect();
        for ($t = $open->copy(); $t->lessThanOrEqualTo($last); $t->addMinutes($stepMinutes)) {
            if ($t->greaterThanOrEqualTo($earliest)) {
                $slots->push($t->copy());
            }
        }

        return $slots;
    }

    public function isValidPickup(CarbonInterface $at): bool
    {
        return $this->slotsFor($at)->contains(fn ($s) => $s->equalTo($at));
    }

    public function pickableDays(): Collection
    {
        $daysAhead = (int) Setting::get('pickup_days_ahead', 7);

        return collect(range(0, $daysAhead - 1))
            ->map(fn ($i) => now()->setTimezone($this->tz())->startOfDay()->addDays($i))
            ->filter(fn ($d) => $this->slotsFor($d)->isNotEmpty())
            ->values();
    }

    public function summary(): string
    {
        return StoreHour::orderBy('weekday')->get()->map(function ($h) {
            $dayName = date('D', strtotime("Sunday +{$h->weekday} days"));
            if ($h->is_closed || !$h->opens_at) {
                return "{$dayName}: closed";
            }
            return "{$dayName}: " . substr((string) $h->opens_at, 0, 5) . '-' . substr((string) $h->closes_at, 0, 5);
        })->join('; ');
    }

    public function nextPickupText(): ?string
    {
        for ($i = 1; $i <= 14; $i++) {
            $day = now()->setTimezone($this->tz())->startOfDay()->addDays($i);
            $first = $this->slotsFor($day)->first();
            if ($first) {
                return ($i === 1 ? 'tomorrow' : $day->format('l')) . ' from ' . $first->format('g:i A');
            }
        }

        return null;
    }
}
