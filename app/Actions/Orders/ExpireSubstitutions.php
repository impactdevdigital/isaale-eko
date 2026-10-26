<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\SubstitutionRequest;

class ExpireSubstitutions
{
    public function __construct(private RespondToSubstitution $respond) {}

    /**
     * Periodic check (runs every minute):
     * Any request whose response deadline has expired is auto-refunded.
     */
    public function due(): int
    {
        $expired = SubstitutionRequest::where('status', SubstitutionRequest::STATUS_AWAITING_REPLY)
            ->whereNotNull('response_deadline_at')
            ->where('response_deadline_at', '<=', now())
            ->get();

        foreach ($expired as $r) {
            $this->respond->handle($r, 'auto_refund', 'system');
        }

        return $expired->count();
    }

    /**
     * Stage rule:
     * Called before moving an order to Ready or Completed / Out for Delivery.
     * Anything still unanswered or contacting is auto-refunded immediately.
     */
    public function forOrder(Order $order): int
    {
        $pending = SubstitutionRequest::where('order_id', $order->id)
            ->whereIn('status', [SubstitutionRequest::STATUS_CONTACTING, SubstitutionRequest::STATUS_AWAITING_REPLY])
            ->get();

        foreach ($pending as $r) {
            $this->respond->handle($r, 'auto_refund', 'system');
        }

        return $pending->count();
    }
}
