<?php

namespace App\Services\Marketing;

use App\Models\Consent;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Marketing email lives in Brevo (newsletter, new arrivals, promotions);
 * transactional email stays with Resend. The website records every email
 * consent, then copies it into one Brevo list, and takes it off the list
 * when the shopper opts out. Does nothing until BREVO_API_KEY and
 * BREVO_LIST_ID are set.
 */
class Brevo
{
    public const API = 'https://api.brevo.com/v3';

    public function enabled(): bool
    {
        return filled(config('services.brevo.key')) && filled(config('services.brevo.list_id'));
    }

    /**
     * The Brevo list for a consent channel: "email" is the newsletter;
     * "email_offers" / "email_rewards" only if their list is configured.
     */
    public function listFor(string $channel): ?int
    {
        if (! $this->enabled()) {
            return null;
        }

        $id = $channel === 'email'
            ? config('services.brevo.list_id')
            : config("services.brevo.topic_lists.{$channel}");

        return filled($id) ? (int) $id : null;
    }

    /**
     * @return string[] consent channels that are copied into Brevo
     */
    public function channels(): array
    {
        return array_values(array_filter(
            ['email', ...array_keys((array) config('services.brevo.topic_lists', []))],
            fn ($channel) => $this->listFor($channel) !== null,
        ));
    }

    /**
     * Add or update the contact and put it on the list.
     *
     * @throws RequestException
     */
    public function subscribe(string $email, ?string $name = null, ?int $listId = null): void
    {
        [$first, $last] = self::splitName($name);

        $this->http()->post('/contacts', array_filter([
            'email' => $email,
            'attributes' => array_filter(['FIRSTNAME' => $first, 'LASTNAME' => $last]) ?: null,
            'listIds' => [$listId ?? $this->listFor('email')],
            'updateEnabled' => true,
        ], fn ($value) => $value !== null))->throw();
    }

    /**
     * Take the contact off the newsletter list. A contact Brevo doesn't
     * have, or that is already off the list, counts as done.
     *
     * @throws RequestException
     */
    public function unsubscribe(string $email, ?int $listId = null): void
    {
        $listId ??= $this->listFor('email');
        $response = $this->http()->post("/contacts/lists/{$listId}/contacts/remove", ['emails' => [$email]]);

        if ($response->failed() && ! in_array($response->status(), [400, 404], true)) {
            $response->throw();
        }
    }

    /**
     * Copy one active email consent into Brevo and mark it done. Failures
     * are reported and left for brevo:sync to retry.
     */
    public function syncConsent(Consent $consent): bool
    {
        $listId = $this->listFor($consent->channel);

        if (! $listId || ! $consent->email || $consent->revoked_at) {
            return false;
        }

        try {
            $this->subscribe($consent->email, $consent->name ?: $consent->user?->name, $listId);
            $consent->forceFill(['brevo_synced_at' => now()])->save();

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * "Ada Lovelace Obi" → ["Ada", "Lovelace Obi"].
     *
     * @return array{0: ?string, 1: ?string}
     */
    public static function splitName(?string $name): array
    {
        $parts = preg_split('/\s+/', trim((string) $name), 2, PREG_SPLIT_NO_EMPTY);

        return [$parts[0] ?? null, $parts[1] ?? null];
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(self::API)
            ->withHeaders(['api-key' => config('services.brevo.key')])
            ->acceptJson()
            ->asJson()
            ->timeout(10);
    }
}
