<?php

namespace App\Livewire\Admin;

use App\Services\Square\CatalogCheck;
use Livewire\Component;
use Throwable;

/**
 * Managers can check that the site's items, categories and prices match
 * Square, using the Square account this site is connected to. Read-only.
 */
class SquareCatalogCheck extends Component
{
    /** @var array<string, mixed>|null */
    public ?array $report = null;

    public ?string $error = null;

    public ?string $checkedAt = null;

    public function check(CatalogCheck $check): void
    {
        $this->reset('report', 'error');

        if (! config('services.square.token') || ! config('services.square.location_id')) {
            $this->error = 'This site is not connected to Square (SQUARE_ACCESS_TOKEN / SQUARE_LOCATION_ID).';

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

    public function render()
    {
        return view('livewire.admin.square-catalog-check', [
            'squareEnv' => config('services.square.env'),
            'labels' => CatalogCheck::LABELS,
        ])->layout('layouts.admin', ['header' => 'Square Catalogue Check']);
    }
}
