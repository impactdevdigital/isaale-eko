<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

/**
 * Promo codes for trying the basket: one that works, one expired, one with a minimum spend.
 */
class DemoCouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            'WELCOME10' => ['type' => 'percent', 'value' => 10, 'expires_at' => null],
            'SPRING5' => ['type' => 'fixed', 'value' => 500, 'expires_at' => now()->subMonth()],
            'BIGSHOP15' => ['type' => 'percent', 'value' => 15, 'min_subtotal_cents' => 6000, 'expires_at' => null],
        ];

        foreach ($coupons as $code => $attributes) {
            Coupon::updateOrCreate(['code' => $code], $attributes + ['is_active' => true]);
        }
    }
}
