<?php

namespace App\Jobs;

use App\Services\Square\CatalogSync;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Pick up catalogue changes after Square says something changed. Unique,
 * so a burst of webhooks queues one sync.
 */
class SyncSquareCatalog implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 120;

    public function handle(CatalogSync $sync): void
    {
        $sync->changes();
    }
}
