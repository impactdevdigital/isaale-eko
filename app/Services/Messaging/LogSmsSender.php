<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Log;

/**
 * Development stand-in until an SMS provider is chosen.
 */
class LogSmsSender implements SmsSender
{
    public function send(string $phone, string $message): void
    {
        Log::info('SMS to '.$phone.': '.$message);
    }
}
