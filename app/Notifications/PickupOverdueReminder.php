<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class PickupOverdueReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $n = $this->order->number;

        $rescheduleUrl = URL::temporarySignedRoute(
            'orders.reschedule',
            now()->addDays(2),
            ['number' => $n]
        );

        return (new MailMessage)
            ->subject(__('Friendly reminder: Order #:number is waiting for collection', ['number' => $n]))
            ->greeting(__('Hello :name,', ['name' => $this->order->contact_name]))
            ->line(__('Your pickup order #:number was scheduled for collection earlier today and is ready for you at Ìsalèè Èkó.', [
                'number' => $n,
            ]))
            ->line(__('If you are running late or need to pick it up at a later time or date, you can easily reschedule your collection time using the link below:'))
            ->action(__('Reschedule My Collection Time'), $rescheduleUrl)
            ->line(__('Our team will keep your order safe and fresh. If you need any assistance, feel free to call our store.'));
    }
}
