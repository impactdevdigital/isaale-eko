<?php

namespace App\Livewire\Admin;

use App\Services\Square\CatalogCheck;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Throwable;

/**
 * Managers can check that the site's items, categories and prices match
 * Square (read-only), and pull the whole catalogue from Square on demand:
 * on Render's free plan there's no Shell or worker to run square:sync.
 */
class SquareCatalogCheck extends Component
{
    /** Progress of the last "Pull everything from Square": state, at, stats or error. */
    public const PULL_KEY = 'square.full_pull';

    /** @var array<string, mixed>|null */
    public ?array $report = null;

    public ?string $error = null;

    public ?string $checkedAt = null;

    public function check(CatalogCheck $check): void
    {
        $this->reset('report', 'error');

        if (! $this->connected()) {
            return;
        }

        try {
            $this->report = $check->run();
            $this->checkedAt = now()->timezone('America/Moncton')->format('j M Y, g:i a');
        } catch (Throwable $e) {
            report($e);
            $this->error = 'Square could not be reached or refused the request. Check the Square keys and try again.';
        }
    }

    /**
     * Full sync plus hiding demo products, like `square:sync --full
     * --retire-demo`. Runs after the response, so a big catalogue can't
     * time out the page; the page polls for the result.
     */
    public function pullFromSquare(): void
    {
        $this->reset('report', 'error');

        if (! $this->connected() || ($this->pullStatus()['state'] ?? null) === 'running') {
            return;
        }

        Cache::put(self::PULL_KEY, ['state' => 'running', 'at' => now()->toIso8601String()], now()->addMinutes(15));

        defer(function () {
            @set_time_limit(900);

            try {
                Artisan::call('square:sync', ['--full' => true, '--retire-demo' => true]);
                Cache::forever(self::PULL_KEY, ['state' => 'done', 'at' => now()->toIso8601String(), 'output' => trim(Artisan::output())]);
            } catch (Throwable $e) {
                report($e);
                Cache::forever(self::PULL_KEY, ['state' => 'failed', 'at' => now()->toIso8601String(), 'output' => $e->getMessage()]);
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function pullStatus(): array
    {
        return Cache::get(self::PULL_KEY, []);
    }

    private function connected(): bool
    {
        if (config('services.square.token') && config('services.square.location_id')) {
            return true;
        }

        $this->error = 'This site is not connected to Square (SQUARE_ACCESS_TOKEN / SQUARE_LOCATION_ID).';

        return false;
    }

    public function render()
    {
        return view('livewire.admin.square-catalog-check', [
            'squareEnv' => config('services.square.env'),
            'labels' => CatalogCheck::LABELS,
            'pull' => $this->pullStatus(),
        ])->layout('layouts.admin', ['header' => 'Square Catalogue Check']);
    }
}
