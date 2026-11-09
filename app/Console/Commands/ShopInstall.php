<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\Product;
use App\Models\Story;
use App\Models\StoreHour;
use App\Models\User;
use App\Services\Square\CatalogSync;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Database\Seeders\StorySeeder;
use Database\Seeders\StorefrontSeeder;
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

    protected $description = 'Add opening hours, payment methods, menu categories, countries, demo stories and the first Square import if they are missing';

    public function handle(CatalogSync $sync): int
    {
        if (StoreHour::query()->doesntExist()) {
            $this->call('db:seed', ['--class' => StoreHoursSeeder::class, '--force' => true]);
        }

        // firstOrCreate per method: adds new ones, leaves existing settings alone.
        $this->call('db:seed', ['--class' => PaymentMethodSeeder::class, '--force' => true]);

        // Ensure default store manager / administrator exists for operations
        if (User::where('is_admin', true)->where('is_active', true)->doesntExist()) {
            $this->call('db:seed', ['--class' => AdminUserSeeder::class, '--force' => true]);
        }

        // Menu categories, home tiles and countries: ours, not Square's.
        if (Country::query()->doesntExist()) {
            $this->call('db:seed', ['--class' => StorefrontSeeder::class, '--force' => true]);
        }

        // Demo Food Stories and Brand Updates, until staff write their own.
        if (Story::query()->doesntExist()) {
            $this->call('db:seed', ['--class' => StorySeeder::class, '--force' => true]);
        }

        if (filled(config('services.square.token')) && Product::whereNotNull('square_id')->doesntExist()) {
            $this->info('No Square products yet: running the first import.');

            try {
                $this->call('square:sync', ['--full' => true]);
            } catch (\Throwable $e) {
                // The shop still starts; the scheduled sync will try again.
                $this->warn('Square import failed: '.$e->getMessage());
            }
        }

        if ($merged = $sync->mergeIntoStorefrontCategories()) {
            $this->info("Matched {$merged} Square categories onto the menu categories.");
        }

        // The sandbox holds the demo products: give them their countries,
        // badges, recipes and collections once, like the local demo.
        if (config('services.square.env') === 'sandbox'
            && Product::whereNotNull('square_id')->exists()
            && Product::whereNotNull('country_id')->doesntExist()) {
            $this->info('Sandbox: adding demo countries, badges, recipes and collections.');
            app(DemoCatalogSeeder::class)->extras();
        }

        return self::SUCCESS;
    }
}
