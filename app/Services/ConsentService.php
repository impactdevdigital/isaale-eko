<?php

namespace App\Services;

use App\Models\Consent;

class ConsentService
{
    public function grant(string $channel, ?int $userId, ?string $email, string $source): void
    {
        if ($this->has($channel, $userId, $email)) {
            return;
        }

        Consent::create([
            'user_id' => $userId,
            'email' => $email,
            'channel' => $channel,
            'source' => $source,
            'granted_at' => now(),
        ]);
    }

    public function revoke(string $channel, ?int $userId, ?string $email): void
    {
        $this->query($channel, $userId, $email)->update(['revoked_at' => now()]);
    }

    public function has(string $channel, ?int $userId, ?string $email): bool
    {
        return $this->query($channel, $userId, $email)->exists();
    }

    private function query(string $channel, ?int $userId, ?string $email)
    {
        return Consent::where('channel', $channel)
            ->whereNull('revoked_at')
            ->where(function ($q) use ($userId, $email) {
                $q->when($userId, fn ($x) => $x->where('user_id', $userId))
                  ->when($email, fn ($x) => $x->orWhere('email', $email));
            });
    }
}
