<?php

namespace App\Services\Checkout;

use App\Models\Order;
use App\Services\Square\SquareGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * Charges an order with a Square Web Payments token. The amount always
 * comes from the order, never from the browser. One idempotency key per
 * attempt means a double-click or retry can't charge twice.
 */
class TakePayment
{
    public function __construct(private SquareGateway $square) {}

    /**
     * Returns null on success, or a shopper-facing reason it failed.
     */
    public function charge(Order $order, string $token, string $attemptKey): ?string
    {
        try {
            $payment = $this->square->chargeToken(
                sourceToken: $token,
                cents: $order->total_cents,
                currency: config('shop.currency'),
                idempotencyKey: substr($order->number.'-'.$attemptKey, 0, 45),
                referenceId: $order->number,
                note: 'Ìsàlèè Èkó order '.$order->number,
                buyerEmail: $order->contact_email,
            );
        } catch (RequestException $e) {
            $reason = $this->reasonFrom($e->response?->json('errors.0.code'));
            $order->update(['status' => Order::STATUS_PAYMENT_FAILED, 'payment_error' => $e->response?->json('errors.0.code')]);

            return $reason;
        } catch (ConnectionException $e) {
            // We don't know whether Square charged the card; the webhook settles it.
            Log::warning('Square payment timed out for order '.$order->number, ['error' => $e->getMessage()]);
            $order->update(['payment_error' => 'TIMEOUT']);

            return 'We couldn’t reach the payment service. Please don’t pay again yet — check your email or your orders in a few minutes.';
        }

        if (! in_array($payment['status'] ?? null, ['COMPLETED', 'APPROVED'], true)) {
            $order->update(['status' => Order::STATUS_PAYMENT_FAILED, 'payment_error' => $payment['status'] ?? 'UNKNOWN']);

            return $this->reasonFrom(null);
        }

        $this->markPaid($order, $payment['id']);

        return null;
    }

    public function markPaid(Order $order, string $paymentId): void
    {
        $order->update([
            'status' => Order::STATUS_PAID,
            'payment_id' => $paymentId,
            'payment_error' => null,
            'paid_at' => now(),
        ]);
    }

    private function reasonFrom(?string $code): string
    {
        return match ($code) {
            'CVV_FAILURE', 'VERIFY_CVV_FAILURE' => 'The security code didn’t match. Check the three digits on the back of your card.',
            'ADDRESS_VERIFICATION_FAILURE', 'VERIFY_AVS_FAILURE', 'INVALID_POSTAL_CODE' => 'The postal code didn’t match your card. Check it and try again.',
            'INVALID_EXPIRATION', 'CARD_EXPIRED' => 'This card has expired. Try another card.',
            'INSUFFICIENT_FUNDS' => 'Your bank declined the payment for insufficient funds.',
            default => 'Check the card number, expiry date and security code. Your bank may also need you to approve the payment or check your available balance.',
        };
    }
}
