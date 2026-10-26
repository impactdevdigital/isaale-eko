<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Refund;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RefundNotice extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order, public Refund $refund) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $n = $this->order->number;
        $amount = money($this->refund->amount_cents);

        $mail = (new MailMessage)
            ->greeting(__('Hello :name,', ['name' => $this->order->contact_name]));

        if ($this->refund->isProcessed()) {
            $mail->subject(__('Refund processed for order #:number', ['number' => $n]))
                ->line(__('Your refund of :amount for order #:number has been completed by our payment provider.', [
                    'amount' => $amount,
                    'number' => $n,
                ]))
                ->line(__('The funds were refunded to your original payment method. Depending on your bank or credit card issuer, it typically takes 2 to 7 business days to appear on your statement.'));
        } else {
            $mail->subject(__('Refund initiated for order #:number', ['number' => $n]))
                ->line(__('We have initiated a refund of :amount for order #:number.', [
                    'amount' => $amount,
                    'number' => $n,
                ]))
                ->line(__('The request has been sent to our payment processor and will be returned directly to your original payment method. How quickly the balance appears depends on your bank, which is outside our direct control.'))
                ->line(__('Reason: :reason', ['reason' => $this->refund->reason ?? 'Store adjustment']));
        }

        $mail->line(__('Please note that our store does not issue store credits; all returns and adjustments are refunded to your original payment method.'))
            ->line(__('Thank you for your patience and for shopping with Ìsàlèè Èkó.'));

        return $mail;
    }
}
