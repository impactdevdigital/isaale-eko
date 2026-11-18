<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Square Loyalty: which loyalty account an order earned points on, how
 * many, and the reward (if any) the shopper spent on it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('loyalty_account_id')->nullable()->after('promotion_id');
            $table->unsignedInteger('loyalty_points')->nullable()->after('loyalty_account_id');
            $table->unsignedInteger('loyalty_points_reversed')->default(0)->after('loyalty_points');
            $table->timestamp('loyalty_synced_at')->nullable()->after('loyalty_points_reversed');
            $table->string('loyalty_reward_id')->nullable()->after('loyalty_synced_at');
            $table->unsignedInteger('loyalty_reward_points')->nullable()->after('loyalty_reward_id');
            // issued (points held), redeemed (used), released (points given back)
            $table->string('loyalty_reward_status')->nullable()->after('loyalty_reward_points');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'loyalty_account_id', 'loyalty_points', 'loyalty_points_reversed', 'loyalty_synced_at',
                'loyalty_reward_id', 'loyalty_reward_points', 'loyalty_reward_status',
            ]);
        });
    }
};
