<?php

namespace App\Services\Orders;

use App\Models\OrderIssue;

class RefundCalculator
{
    /**
     * Calculates the monetary value of affected items in proportion to quantity,
     * including applicable taxes and line discounts.
     *
     * @return array{amount_cents: int, delivery_fee_cents: int, items_cents: int}
     */
    public function forIssue(OrderIssue $issue, bool $includeDeliveryFee = false): array
    {
        $itemsCents = (int) $issue->items->sum(function ($ii) {
            if (! $ii->orderItem) {
                return 0;
            }

            $lineTotal = $ii->orderItem->line_total_cents;
            $orderQty = max(1, $ii->orderItem->quantity);

            return (int) intdiv($lineTotal * $ii->quantity, $orderQty);
        });

        $deliveryFeeCents = $includeDeliveryFee ? (int) $issue->order->delivery_fee_cents : 0;

        return [
            'amount_cents' => $itemsCents + $deliveryFeeCents,
            'delivery_fee_cents' => $deliveryFeeCents,
            'items_cents' => $itemsCents,
        ];
    }
}
