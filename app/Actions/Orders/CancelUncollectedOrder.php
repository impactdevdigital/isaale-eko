<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\Setting;
use App\Notifications\OrderUncollected;
use Illuminate\Support\Facades\Notification;

class CancelUncollectedOrder
{
    public function __construct(private IssueRefund $refunds) {}

    /**
     * @param Order $order
     * @param int|null $refundPercent Custom percentage override or null to use store policy setting
     * @param string $reason
     */
    public function handle(Order $order, ?int $refundPercent = null, string $reason = 'not_collected'): void
    {
        $percent = $refundPercent ?? (int) Setting::get('uncollected_refund_percent', 100);
        $refunded = 0;

        $hasOnlinePayment = filled($order->payment_id) || ($order->payment_method && $order->payment_method !== 'pay_at_pickup');

        if ($hasOnlinePayment && $order->activeRefundableCents() > 0) {
            if ($percent > 0) {
                $refundAmount = (int) intdiv($order->total_cents * $percent, 100);
                $refundAmount = min($refundAmount, $order->activeRefundableCents());

                if ($refundAmount > 0) {
                    $refunded = $this->refunds->handle(
                        $order,
                        $refundAmount,
                        'uncollected',
                        "Uncollected cancellation ({$percent}% policy refund)"
                    )->amount_cents;
                }
            }
        }

        $order->update([
            'status' => Order::STATUS_CANCELLED,
            'cancel_reason' => $reason,
            'cancelled_at' => now(),
        ]);

        if ($order->contact_email) {
            Notification::route('mail', $order->contact_email)
                ->notify(new OrderUncollected($order, 'cancelled', $refunded));
        }
    }
}
