<?php

namespace App\Services\Messaging;

/**
 * Sends a text message. Bind a real provider (Twilio, Vonage, ...) in
 * AppServiceProvider; the default only writes to the log.
 */
interface SmsSender
{
    public function send(string $phone, string $message): void;
}
