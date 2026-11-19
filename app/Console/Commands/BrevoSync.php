<?php

namespace App\Console\Commands;

use App\Models\Consent;
use App\Services\Marketing\Brevo;
use Illuminate\Console\Command;

/**
 * Copy email consents that haven't reached Brevo yet: sign-ups from before
 * Brevo was connected, and any that failed. Safe to run any time.
 */
class BrevoSync extends Command
{
    protected $signature = 'brevo:sync {--limit=500 : Most consents to send in one run}';

    protected $description = 'Copy newsletter sign-ups (email consents) into the Brevo list';

    public function handle(Brevo $brevo): int
    {
        if (! $brevo->enabled()) {
            $this->info('Brevo is not connected (BREVO_API_KEY / BREVO_LIST_ID). Nothing to do.');

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        Consent::with('user')
            ->whereIn('channel', $brevo->channels())
            ->whereNull('revoked_at')
            ->whereNull('brevo_synced_at')
            ->whereNotNull('email')
            ->orderBy('id')
            ->limit((int) $this->option('limit'))
            ->get()
            ->each(function (Consent $consent) use ($brevo, &$sent, &$failed) {
                $brevo->syncConsent($consent) ? $sent++ : $failed++;
            });

        $this->info("Brevo: {$sent} sign-up(s) sent".($failed ? ", {$failed} failed (will retry)." : '.'));

        return self::SUCCESS;
    }
}
