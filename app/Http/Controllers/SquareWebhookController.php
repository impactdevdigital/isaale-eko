<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Checkout\OrderReceipt;
use App\Services\Checkout\TakePayment;
use App\Services\Square\SquareGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Square payment webhooks. Settles orders whose charge succeeded after our
 * own request gave up (timeouts), so the shopper never needs to pay twice.
 */
class SquareWebhookController extends Controller
{
    public function __invoke(Request $request, SquareGateway $square, TakePayment $payments, OrderReceipt $receipt): Response
    {
        abort_unless($square->isValidWebhook($request), 403);

        $payment = $request->json('data.object.payment');

        if (! in_array($request->json('type'), ['payment.created', 'payment.updated'], true) || ! $payment) {
            return response()->noContent();
        }

        $order = Order::where('number', $payment['reference_id'] ?? null)->first();

        if ($order && ! $order->isConfirmed() && ($payment['status'] ?? null) === 'COMPLETED'
            && ($payment['amount_money']['amount'] ?? null) === $order->total_cents) {
            $payments->markPaid($order, $payment['id']);
            $receipt->send($order);
        }

        return response()->noContent();
    }
}
