<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Loyalty moved from 10 points per $1 (500 points = $5) to 1 hidden point
 * per $1 (every 100 = $3 off). Rescale existing history so balances keep
 * their dollar meaning; any full $100 unlocks on the customer's next paid
 * order.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('point_transactions')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('point_transactions')->where('id', $row->id)->update(['points' => intdiv((int) $row->points, 10)]);
            }
        });
    }

    public function down(): void
    {
        DB::table('point_transactions')->update(['points' => DB::raw('points * 10')]);
    }
};
