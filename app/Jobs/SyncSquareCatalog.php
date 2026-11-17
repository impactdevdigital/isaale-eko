<?php

namespace App\Jobs;

use App\Services\Square\CatalogSync;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

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
        // Same lock as square:sync, so a webhook never runs alongside a full pull.
        $lock = Cache::lock('square-sync', 600);

        if (! $lock->get()) {
            return;
        }

        try {
            $sync->changes();
        } finally {
            $lock->release();
        }
    }
}
