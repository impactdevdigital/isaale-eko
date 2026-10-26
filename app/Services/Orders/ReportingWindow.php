<?php

namespace App\Services\Orders;

use App\Models\OrderItem;
use App\Models\Setting;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;

class ReportingWindow
{
    public function windowFor(string $class): CarbonInterval
    {
        return match ($class) {
            'non_food' => CarbonInterval::days((int) Setting::get('issue_window_nonfood_days', 7)),
            'non_perishable' => CarbonInterval::hours((int) Setting::get('issue_window_grocery_hours', 24)),
            default => CarbonInterval::hours((int) Setting::get('issue_window_perishable_hours', 24)),
        };
    }

    /**
     * The exact moment reporting closes for this line item.
     */
    public function deadlineFor(OrderItem $item): ?CarbonInterface
    {
        $fulfilled = $item->order->fulfilled_at ?? $item->order->created_at;

        return $fulfilled
            ? $fulfilled->copy()->add($this->windowFor($item->item_class ?? 'perishable'))
            : null;
    }

    public function isOpen(OrderItem $item): bool
    {
        $d = $this->deadlineFor($item);

        return $d !== null && now()->lessThanOrEqualTo($d);
    }

    /**
     * Change-of-mind returns (non-food only) have their own 7-day window.
     */
    public function returnDeadlineFor(OrderItem $item): ?CarbonInterface
    {
        $fulfilled = $item->order->fulfilled_at ?? $item->order->created_at;

        return ($fulfilled && ($item->item_class ?? '') === 'non_food')
            ? $fulfilled->copy()->addDays((int) Setting::get('return_window_nonfood_days', 7))
            : null;
    }
}
