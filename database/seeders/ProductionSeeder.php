<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * What a fresh live database needs: opening hours and payment methods.
 * Products come from Square (square:sync), never from the demo seeder.
 * Run once after the first deploy; running it again resets opening hours.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([StoreHoursSeeder::class, PaymentMethodSeeder::class]);
    }
}
