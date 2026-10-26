<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class OrderUncollected extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param Order $order
     * @param string $outcome 'held' | 'cancelled'
     * @param int $refundedCents
     */
    public function __construct(
        public Order $order,
        public string $outcome = 'held',
        public int $refundedCents = 0
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $n = $this->order->number;
        $days = (int) Setting::get('uncollected_grace_days', 3);

        $mail = (new MailMessage)
            ->greeting(__('Hello :name,', ['name' => $this->order->contact_name]));

        if ($this->outcome === 'cancelled') {
            $mail->subject(__('Order #:number was not collected – Cancellation & Refund', ['number' => $n]))
                ->line(__('Your pickup order #:number was not collected within our :days-day grace period and has been cancelled under our store policy.', [
                    'number' => $n,
                    'days' => $days,
                ]));

            if ($this->refundedCents > 0) {
                $mail->line(__('We have issued a refund of :amount back to your original payment method.', [
                    'amount' => money($this->refundedCents),
                ]));
            } else {
                $mail->line(__('No payment was charged for this order.'));
            }

            $mail->line(__('If you have any questions or would like to place a new order, please feel free to reach out to our team.'));
        } else {
            $rescheduleUrl = URL::temporarySignedRoute(
                'orders.reschedule',
                now()->addDays(2),
                ['number' => $n]
            );

            $mail->subject(__('Your order #:number is still waiting for collection', ['number' => $n]))
                ->line(__('We have held your order #:number for :days days past your scheduled pickup date, but it has not been collected.', [
                    'number' => $n,
                    'days' => $days,
                ]))
                ->line(__('Please choose a new pickup time using the link below so our store staff know when to have your order ready.'))
                ->action(__('Reschedule My Pickup Time'), $rescheduleUrl)
                ->line(__('If you are unable to collect the order, please reply to this email or call our team to request a cancellation and policy refund.'));
        }

        return $mail;
    }
}
