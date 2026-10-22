<?php

namespace App\Services\Checkout;

use App\Mail\OrderConfirmedMail;
use App\Models\Order;
use App\Services\Messaging\SmsSender;
use Illuminate\Support\Facades\Mail;

/**
 * Tells the shopper their order is confirmed, on the channel they verified.
 */
class OrderReceipt
{
    public function __construct(private SmsSender $sms) {}

    public function send(Order $order): void
    {
        if ($order->contact_email) {
            Mail::to($order->contact_email)->send(new OrderConfirmedMail($order));

            return;
        }

        if ($order->contact_phone) {
            $this->sms->send($order->contact_phone, "Ìsàlèè Èkó: order {$order->number} is confirmed. Total ".money($order->total_cents).'. '.route('orders.show', $order));
        }
    }
}
