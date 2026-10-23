<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Square\CatalogSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SquareSync extends Command
{
    protected $signature = 'square:sync
        {--full : Re-read the whole catalogue instead of only what changed}
        {--retire-demo : Afterwards, hide the demo products that are not linked to Square}';

    protected $description = 'Copy products, prices, categories, photos and stock from Square (read-only on Square)';

    public function handle(CatalogSync $sync): int
    {
        if (! config('services.square.token') || ! config('services.square.location_id')) {
            $this->error('Square is not configured (SQUARE_ACCESS_TOKEN / SQUARE_LOCATION_ID).');

            return self::FAILURE;
        }

        $lock = Cache::lock('square-sync', 600);

        if (! $lock->get()) {
            $this->warn('Another Square sync is running.');

            return self::SUCCESS;
        }

        try {
            $stats = $this->option('full') ? $sync->full() : $sync->changes();
        } finally {
            $lock->release();
        }

        $this->table(['Updated', 'Count'], collect($stats)->map(fn ($count, $what) => [ucfirst($what), $count])->values()->all());

        if ($this->option('retire-demo')) {
            if (! Product::whereNotNull('square_id')->where('is_active', true)->exists()) {
                $this->warn('No Square products are live yet, so the demo products were left in place.');
            } else {
                $hidden = Product::whereNull('square_id')->where('is_active', true)->update(['is_active' => false]);
                $this->info("Hid {$hidden} demo products that aren't in Square.");
            }
        }

        return self::SUCCESS;
    }
}
