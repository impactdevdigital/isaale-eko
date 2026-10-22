<?php

namespace App\Services\Checkout;

use App\Mail\VerificationCodeMail;
use App\Services\Messaging\SmsSender;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Six-digit sign-in codes sent by email or SMS. Only a hash is kept, in the
 * cache, with an expiry, a resend cooldown and a cap on wrong guesses.
 */
class OneTimeCode
{
    public function __construct(private SmsSender $sms) {}

    /**
     * Seconds until another code may be sent to this destination (0 = now).
     */
    public function cooldown(string $channel, string $destination): int
    {
        $until = Cache::get($this->key($channel, $destination).':resend');

        return $until ? max(0, $until - now()->timestamp) : 0;
    }

    /**
     * Send a fresh code. Returns false while the resend cooldown runs.
     */
    public function send(string $channel, string $destination): bool
    {
        if ($this->cooldown($channel, $destination) > 0) {
            return false;
        }

        $config = config('shop.verification');
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $key = $this->key($channel, $destination);

        Cache::put($key, ['hash' => Hash::make($code), 'attempts' => 0], now()->addMinutes($config['code_ttl_minutes']));
        Cache::put($key.':resend', now()->addSeconds($config['resend_after_seconds'])->timestamp, now()->addSeconds($config['resend_after_seconds']));

        if ($channel === 'email') {
            Mail::to($destination)->send(new VerificationCodeMail($code, $config['code_ttl_minutes']));
        } else {
            $this->sms->send($destination, "Your Ìsàlèè Èkó code is {$code}. It expires in {$config['code_ttl_minutes']} minutes.");
        }

        return true;
    }

    /**
     * Check a code. A correct code can only be used once; too many wrong
     * guesses burn it.
     */
    public function verify(string $channel, string $destination, string $code): bool
    {
        $key = $this->key($channel, $destination);
        $entry = Cache::get($key);

        if (! $entry || $entry['attempts'] >= config('shop.verification.max_attempts')) {
            return false;
        }

        if (! Hash::check($code, $entry['hash'])) {
            Cache::put($key, [...$entry, 'attempts' => $entry['attempts'] + 1], now()->addMinutes(config('shop.verification.code_ttl_minutes')));

            return false;
        }

        Cache::forget($key);

        return true;
    }

    private function key(string $channel, string $destination): string
    {
        return 'otp:'.$channel.':'.sha1(strtolower($destination));
    }
}
