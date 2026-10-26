<?php

namespace App\Notifications;

use App\Models\SubstitutionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class SubstitutionQuestion extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SubstitutionRequest $substitutionRequest) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $order = $this->substitutionRequest->order;
        $item = $this->substitutionRequest->item;
        $substitute = $this->substitutionRequest->substitute;
        $diff = $this->substitutionRequest->price_difference_cents;

        $reviewUrl = URL::temporarySignedRoute(
            'orders.substitutions.review',
            now()->addHours(24),
            ['number' => $order->number, 'id' => $this->substitutionRequest->id]
        );

        $mail = (new MailMessage)
            ->subject(__('Regarding your Ìsàlèè Èkó order #:number – Item update', ['number' => $order->number]))
            ->greeting(__('Hello :name,', ['name' => $order->contact_name]))
            ->line(__('While preparing your order #:number, our team noticed that :item is currently unavailable.', [
                'number' => $order->number,
                'item' => $item->name . ($item->variation_name ? " ({$item->variation_name})" : ''),
            ]));

        if ($substitute) {
            $diffText = match (true) {
                $diff > 0 => __('additional :amount', ['amount' => money($diff)]),
                $diff < 0 => __('you will be refunded :amount', ['amount' => money(abs($diff))]),
                default => __('no additional cost'),
            };

            $mail->line(__('We would love to offer **:substitute** as a replacement (:diff).', [
                'substitute' => $substitute->product->name . ($substitute->name ? " ({$substitute->name})" : ''),
                'diff' => $diffText,
            ]));
        } else {
            $mail->line(__('Unfortunately, we do not have a suitable substitute in stock right now.'));
        }

        $mail->line(__('Please let us know your decision within 15 minutes. To avoid delaying fulfillment, if we do not hear from you within 15 minutes, the unavailable item will be removed from your order and refunded automatically.'))
            ->action(__('Review & Confirm Substitution', [], $this->locale), $reviewUrl)
            ->line(__('Thank you for shopping with Ìsàlèè Èkó!'));

        return $mail;
    }
}
