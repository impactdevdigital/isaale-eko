<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BackInStockMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Product $product) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Back in stock: {$this->product->name}");
    }

    public function content(): Content
    {
        return new Content(text: 'mail.back-in-stock', with: [
            'url' => route('product', $this->product),
        ]);
    }
}
