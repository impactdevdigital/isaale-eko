<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\Refund;
use App\Notifications\RefundNotice;
use App\Services\Loyalty\LoyaltyProvider;
use App\Services\Square\SquareGateway;
use Illuminate\Support\Facades\Notification;

class IssueRefund
{
    public function __construct(private ?SquareGateway $square = null) {}

    /**
     * The ONLY door for refunds across the entire system.
     * Every action (issues, substitutions, uncollected orders, exchange) routes through this.
     */
    public function handle(
        Order $order,
        int $amountCents,
        string $source,
        string $reason,
        ?int $issueId = null,
        int $deliveryFeeCents = 0
    ): Refund {
        $refundable = $order->activeRefundableCents();
        abort_if(
            $amountCents < 1 || $amountCents > $refundable,
            422,
            'Requested refund amount ('.money($amountCents).') exceeds maximum refundable amount ('.money($refundable).') on this order.'
        );

        // 1. Write ledger entry BEFORE calling payment gateway
        $refund = Refund::create([
            'order_id' => $order->id,
            'order_issue_id' => $issueId,
            'source' => $source,
            'reason' => $reason,
            'amount_cents' => $amountCents,
            'delivery_fee_cents' => $deliveryFeeCents,
            'status' => Refund::STATUS_INITIATING,
            'created_by' => auth()->id(),
        ]);

        // 2. Attempt refund against online payment method or record in-store cash refund
        $isOnlinePayment = filled($order->payment_id) || ($order->payment_method && $order->payment_method !== 'pay_at_pickup');
        $done = false;
        $squareRefundId = null;

        if ($isOnlinePayment && filled($order->payment_id) && $this->square && filled(config('services.square.token'))) {
            try {
                $sq = $this->square->refundPayment(
                    $order->payment_id,
                    $amountCents,
                    $order->currency ?? config('shop.currency', 'CAD'),
                    $reason,
                    "refund-{$refund->id}"
                );
                $squareRefundId = $sq['id'] ?? null;
                $done = ($sq['status'] ?? '') === 'COMPLETED';
            } catch (\Throwable $e) {
                if (app()->environment('local')) {
                    $done = true;
                    $squareRefundId = 'local_sandbox_ref_'.uniqid();
                } else {
                    $refund->update([
                        'status' => Refund::STATUS_FAILED,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }
        } else {
            // Pay at pickup / in-store cash till refund
            $done = true;
            $squareRefundId = 'store_register_ref_'.uniqid();
        }

        // 3. Update ledger entry
        $refund->update([
            'square_refund_id' => $squareRefundId,
            'status' => $done ? Refund::STATUS_PROCESSED : Refund::STATUS_INITIATED,
            'initiated_at' => now(),
            'processed_at' => $done ? now() : null,
        ]);

        // 4. Update order cumulative totals
        $order->increment('refunded_cents', $amountCents);
        if ($order->refunded_cents >= $order->total_cents) {
            $order->update(['status' => 'refunded']);
        }

        // A refund takes back the loyalty progress the order earned.
        app(LoyaltyProvider::class)->reversePurchase($order, $order->refunded_cents);

        // 5. Send transaction notice to customer
        if ($order->contact_email) {
            Notification::route('mail', $order->contact_email)
                ->notify(new RefundNotice($order, $refund));
        }

        return $refund;
    }
}
