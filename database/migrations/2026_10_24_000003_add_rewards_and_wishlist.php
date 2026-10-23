<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reward coupons that belong to one customer, and wishlists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            // Set for codes bought with loyalty points: only that customer can use them.
            $table->foreignId('user_id')->nullable()->after('code')->constrained()->cascadeOnDelete();
        });

        Schema::table('point_transactions', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
        });

        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');

        Schema::table('point_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
