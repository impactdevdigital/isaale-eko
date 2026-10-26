<?php

namespace App\Services\Orders;

use App\Models\OrderItem;

class IssueEligibility
{
    public function __construct(private ReportingWindow $windows) {}

    /**
     * @return array{ok: bool, reason: ?string, within_window: bool}
     */
    public function check(OrderItem $item, string $type): array
    {
        $order = $item->order;

        if ($type === 'change_of_mind') {
            if (($item->item_class ?? 'perishable') !== 'non_food') {
                return [
                    'ok' => false,
                    'reason' => 'Food and perishable groceries are not eligible for change-of-mind returns under store policy.',
                    'within_window' => false,
                ];
            }

            $d = $this->windows->returnDeadlineFor($item);

            return ($d && now()->lessThanOrEqualTo($d))
                ? ['ok' => true, 'reason' => null, 'within_window' => true]
                : ['ok' => false, 'reason' => 'The 7-day non-food return window has expired.', 'within_window' => false];
        }

        if ($type === 'late_or_undelivered') {
            $late = $order->promised_by && now()->greaterThan($order->promised_by);

            return $late
                ? ['ok' => true, 'reason' => null, 'within_window' => true]
                : ['ok' => false, 'reason' => 'Confirmed order timeframe has not passed yet.', 'within_window' => false];
        }

        $open = $this->windows->isOpen($item);

        return $open
            ? ['ok' => true, 'reason' => null, 'within_window' => true]
            : ['ok' => false, 'reason' => 'The policy claim window (24h) has elapsed. Staff review is required.', 'within_window' => false];
    }
}
