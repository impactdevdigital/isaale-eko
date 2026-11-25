<?php

namespace App\Services;

use App\Models\Consent;
use App\Services\Marketing\Brevo;

class ConsentService
{
    /**
     * Record a marketing consent. Email consents are also copied into Brevo
     * (after the response, so a slow Brevo never holds up the shopper).
     */
    public function grant(string $channel, ?int $userId, ?string $email, string $source, ?string $name = null): void
    {
        if ($this->has($channel, $userId, $email)) {
            return;
        }

        $consent = Consent::create([
            'user_id' => $userId,
            'email' => $email,
            'name' => filled($name) ? trim($name) : null,
            'channel' => $channel,
            'source' => $source,
            'page' => self::currentPage(),
            'granted_at' => now(),
        ]);

        if ($channel === 'email') {
            Analytics::track('newsletter_signup', ['source' => $source]);
        }

        if ($email && app(Brevo::class)->listFor($channel)) {
            dispatch(fn () => app(Brevo::class)->syncConsent($consent->fresh()))->afterResponse();
        }
    }

    public function revoke(string $channel, ?int $userId, ?string $email): void
    {
        $active = $this->query($channel, $userId, $email);
        $emails = (clone $active)->pluck('email')->filter()->unique()->values();

        $active->update(['revoked_at' => now()]);

        if ($emails->isNotEmpty() && ($listId = app(Brevo::class)->listFor($channel))) {
            dispatch(function () use ($emails, $listId) {
                foreach ($emails as $address) {
                    try {
                        app(Brevo::class)->unsubscribe($address, $listId);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            })->afterResponse();
        }
    }

    /** Where each consent source sits on the website, for people reading the list. */
    public const SOURCES = [
        'footer_signup' => 'Newsletter form (page footer)',
        'community_hub' => 'Community Hub launch updates',
        'food_stories' => 'Food Stories sign-up',
        'checkout' => 'Checkout opt-in',
        'account' => 'Account preferences',
        'in_store' => 'In store',
    ];

    public static function sourceLabel(?string $source): string
    {
        return self::SOURCES[$source] ?? str((string) $source)->replace('_', ' ')->ucfirst()->toString();
    }

    /**
     * The storefront page the shopper was on. Livewire actions post to
     * /livewire/update, so the page comes from the Referer header.
     */
    private static function currentPage(): ?string
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return null;
        }

        $url = request()->is('livewire/*') || request()->is('livewire-*/*')
            ? request()->headers->get('referer')
            : request()->fullUrl();

        $host = $url ? parse_url($url, PHP_URL_HOST) : null;

        if (! $host || ! in_array($host, [request()->getHost(), parse_url((string) config('app.url'), PHP_URL_HOST)], true)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '/';

        return mb_substr($path, 0, 255);
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
