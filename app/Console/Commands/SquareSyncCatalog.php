<?php

namespace App\Console\Commands;

use App\Actions\Catalog\ImportCatalog;
use App\Actions\Catalog\ImportInventory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SquareSyncCatalog extends Command
{
    protected $signature = 'square:sync-catalog {--full : Re-import everything from Square} {--initial : The very first import (skips New Arrival tags)}';
    protected $description = 'Synchronize Square catalogue and inventory counts into our database';

    public function handle(ImportCatalog $catalog, ImportInventory $inventory): int
    {
        $this->info('Starting Square catalogue sync (' . config('services.square.env') . ')...');

        $everything = $this->option('full') || $this->option('initial');
        $since = $everything ? null : Cache::get('catalog_synced_at');
        $started = now()->toIso8601ZuluString();

        $stats = $catalog->handle((bool) $this->option('initial'), $since);
        Cache::forever('catalog_synced_at', $started);

        $this->info('Fetching live inventory snapshot counts...');
        $stockRows = $inventory->handle();

        $this->info("Completed: {$stats['items']} items, {$stats['categories']} categories, and {$stockRows} inventory rows updated.");

        return self::SUCCESS;
    }
}
