<?php

namespace App\Services\Checkout;

use App\Models\Order;
use App\Services\Square\OrderSync;
use App\Services\Square\SquareGateway;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Settles card orders whose outcome we never heard (the request to Square
 * timed out, the network dropped, or the server stopped mid-payment) by
 * asking Square what actually happened:
 *
 *  - paid      → the order is marked paid and the receipt goes out
 *  - failed    → the order is closed as payment failed (nothing was charged)
 *  - pending   → Square doesn't know yet; ask again later
 *
 * Used by checkout (so a shopper can't pay twice while one payment is still
 * open), by the payments:reconcile command and when staff open the orders queue.
 */
class PaymentReconciler
{
    public const PAID = 'paid';

    public const FAILED = 'failed';

    public const PENDING = 'pending';

    /** With no payment in Square after this long, the charge never happened. */
    public const GIVE_UP_AFTER_MINUTES = 10;

    public function __construct(
        private SquareGateway $square,
        private TakePayment $payments,
        private OrderReceipt $receipt,
        private OrderSync $orders,
    ) {}

    /**
     * Card and wallet orders still waiting to hear whether they were paid.
     */
    public static function unresolved(): Builder
    {
        return Order::query()
            ->where('status', Order::STATUS_AWAITING_PAYMENT)
            ->whereIn('payment_method', ['card', 'wallet'])
            ->where('placed_at', '>=', now()->subDays(3));
    }

    public function resolve(Order $order, bool $sendReceipt = true): string
    {
        if ($order->status !== Order::STATUS_AWAITING_PAYMENT) {
            return $order->isConfirmed() ? self::PAID : self::FAILED;
        }

        try {
            $payment = $this->findPayment($order);
        } catch (Throwable $e) {
            Log::warning('Could not check Square for order '.$order->number, ['error' => $e->getMessage()]);

            return self::PENDING;
        }

        $status = $payment['status'] ?? null;

        if ($payment && in_array($status, ['COMPLETED', 'APPROVED'], true)
            && ($payment['amount_money']['amount'] ?? null) === $order->total_cents) {
            $this->payments->markPaid($order, $payment['id']);

            if ($sendReceipt) {
                try {
                    $this->receipt->send($order);
                } catch (Throwable $e) {
                    report($e);
                }
            }

            return self::PAID;
        }

        if ($payment && in_array($status, ['FAILED', 'CANCELED'], true)) {
            return $this->fail($order, $status);
        }

        if (! $payment && $order->placed_at->lt(now()->subMinutes(self::GIVE_UP_AFTER_MINUTES))) {
            return $this->fail($order, 'NO_PAYMENT');
        }

        return self::PENDING;
    }

    /**
     * Resolve every open card order older than a minute (newer ones may still
     * be mid-request). Returns counts per outcome.
     *
     * @return array<string, int>
     */
    public function resolveAll(): array
    {
        $counts = [self::PAID => 0, self::FAILED => 0, self::PENDING => 0];

        self::unresolved()
            ->where('placed_at', '<=', now()->subMinute())
            ->orderBy('id')
            ->each(function (Order $order) use (&$counts) {
                $counts[$this->resolve($order)]++;
            });

        return $counts;
    }

    private function fail(Order $order, string $reason): string
    {
        $this->orders->abandon($order);
        $order->update(['status' => Order::STATUS_PAYMENT_FAILED, 'payment_error' => $order->payment_error === 'TIMEOUT' ? 'TIMEOUT_'.$reason : $reason]);

        return self::FAILED;
    }

    /**
     * The Square payment for this order, if there is one: via its Square
     * order's tenders, otherwise by our order number (reference_id) among the
     * payments taken around the time it was placed.
     */
    private function findPayment(Order $order): ?array
    {
        if ($order->payment_id) {
            return $this->square->getPayment($order->payment_id) ?: null;
        }

        if ($order->square_order_id) {
            $tender = collect($this->square->getOrder($order->square_order_id)['tenders'] ?? [])->first(fn ($t) => filled($t['payment_id'] ?? null));

            if ($tender) {
                return $this->square->getPayment($tender['payment_id']) ?: null;
            }
        }

        $begin = $order->placed_at->copy()->subMinutes(2)->utc()->format('Y-m-d\TH:i:s\Z');
        $end = $order->placed_at->copy()->addHour()->utc()->format('Y-m-d\TH:i:s\Z');
        $cursor = null;

        for ($page = 0; $page < 10; $page++) {
            $result = $this->square->listPayments($begin, $end, $cursor);
            $match = collect($result['payments'])->firstWhere('reference_id', $order->number);

            if ($match) {
                return $match;
            }

            if (! $cursor = $result['cursor'] ?? null) {
                break;
            }
        }

        return null;
    }
}
