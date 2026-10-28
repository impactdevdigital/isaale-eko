<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pay at Pickup is no longer offered at checkout. Past orders keep
 * payment_method = 'pay_at_pickup'; only the admin toggle goes.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('payment_methods')->where('code', 'pay_at_pickup')->delete();

        // Checkout needs at least one method switched on.
        if (DB::table('payment_methods')->where('is_enabled', true)->doesntExist()) {
            DB::table('payment_methods')->where('code', 'card')->update(['is_enabled' => true]);
        }
    }

    public function down(): void
    {
        DB::table('payment_methods')->insertOrIgnore([
            'code' => 'pay_at_pickup',
            'is_enabled' => false,
            'sort_order' => DB::table('payment_methods')->max('sort_order') + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
