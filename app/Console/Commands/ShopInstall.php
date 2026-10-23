<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\StoreHour;
use Database\Seeders\PaymentMethodSeeder;
use Database\Seeders\StoreHoursSeeder;
use Illuminate\Console\Command;

/**
 * First-run setup that is safe to run on every start: fills in only what is
 * missing, so it never overwrites hours or methods staff have changed.
 * Hosts without a shell (e.g. Render's free plan) run it at container start.
 */
class ShopInstall extends Command
{
    protected $signature = 'shop:install';

    protected $description = 'Add opening hours, payment methods and the first Square import if they are missing';

    public function handle(): int
    {
        if (StoreHour::query()->doesntExist()) {
            $this->call('db:seed', ['--class' => StoreHoursSeeder::class, '--force' => true]);
        }

        // firstOrCreate per method: adds new ones, leaves existing settings alone.
        $this->call('db:seed', ['--class' => PaymentMethodSeeder::class, '--force' => true]);

        if (filled(config('services.square.token')) && Product::whereNotNull('square_id')->doesntExist()) {
            $this->info('No Square products yet: running the first import.');

            try {
                $this->call('square:sync', ['--full' => true]);
            } catch (\Throwable $e) {
                // The shop still starts; the scheduled sync will try again.
                $this->warn('Square import failed: '.$e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
