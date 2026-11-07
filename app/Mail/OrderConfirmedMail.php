<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->order->isAwaitingETransfer()
            ? "Your Ìsàlèè Èkó order {$this->order->number}: send your Interac e-Transfer"
            : "Your Ìsàlèè Èkó order {$this->order->number} is confirmed");
    }

    public function content(): Content
    {
        return new Content(text: 'mail.order-confirmed');
    }
}
