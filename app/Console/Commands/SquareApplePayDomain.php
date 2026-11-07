<?php

namespace App\Console\Commands;

use App\Services\Square\SquareGateway;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;

/**
 * Turns on Apple Pay for the site's domain with Square. The verification
 * file in public/.well-known must already be live on that domain.
 */
class SquareApplePayDomain extends Command
{
    protected $signature = 'square:apple-pay-domain {domain? : Defaults to the host of APP_URL}';

    protected $description = 'Register a domain with Square for Apple Pay on the Web';

    public function handle(SquareGateway $square): int
    {
        $domain = $this->argument('domain') ?: parse_url(config('app.url'), PHP_URL_HOST);

        if (! $domain || in_array($domain, ['localhost', '127.0.0.1'], true)) {
            $this->error('Give a public domain, e.g. php artisan square:apple-pay-domain isaale-eko.onrender.com');

            return self::FAILURE;
        }

        try {
            $status = $square->registerApplePayDomain($domain);
        } catch (RequestException $e) {
            $this->error('Square refused '.$domain.': '.($e->response->json('errors.0.detail') ?? $e->getMessage()));

            return self::FAILURE;
        }

        $this->info("Apple Pay domain {$domain} registered with Square (".config('services.square.env')."): {$status}");

        return self::SUCCESS;
    }
}
