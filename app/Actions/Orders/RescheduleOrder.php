<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\Setting;
use App\Services\Store\StoreHours;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class RescheduleOrder
{
    public function __construct(private StoreHours $storeHours) {}

    /**
     * Reschedules the pickup window for an order.
     *
     * @param Order $order
     * @param CarbonInterface $newStartsAt
     * @param string $rescheduledBy 'customer' | 'staff'
     * @return Order
     *
     * @throws InvalidArgumentException
     */
    public function handle(Order $order, CarbonInterface $newStartsAt, string $rescheduledBy = 'customer'): Order
    {
        if (in_array($order->status, [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED], true)) {
            throw new InvalidArgumentException(__('Cancelled or refunded orders cannot be rescheduled.'));
        }

        if ($order->fulfilment_status === Order::FULFILMENT_COMPLETED) {
            throw new InvalidArgumentException(__('Completed orders cannot be rescheduled.'));
        }

        // Validate the slot against store opening hours
        if (! $this->storeHours->isValidPickup($newStartsAt)) {
            throw new InvalidArgumentException(__('The selected pickup time is outside store hours or too soon.'));
        }

        $stepMinutes = (int) Setting::get('pickup_step_minutes', 15);
        $newEndsAt = Carbon::instance($newStartsAt)->addMinutes($stepMinutes);

        // If the order was marked uncollected, restore its operational status
        $newStatus = $order->status;
        if ($order->status === Order::STATUS_UNCOLLECTED) {
            $isPaid = $order->payment_id || $order->paid_at || ($order->payment_method && $order->payment_method !== 'pay_at_pickup');
            $newStatus = $isPaid ? Order::STATUS_PAID : Order::STATUS_PLACED;
        }

        $order->update([
            'pickup_starts_at' => Carbon::instance($newStartsAt)->utc(),
            'pickup_ends_at' => $newEndsAt->utc(),
            'uncollected_at' => null,
            'pickup_reminder_sent_at' => null,
            'pickup_overdue_reminder_sent_at' => null,
            'status' => $newStatus,
        ]);

        return $order->fresh();
    }
}
