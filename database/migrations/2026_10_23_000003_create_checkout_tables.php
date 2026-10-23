<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Checkout: password-free accounts (verified email or mobile), saved
 * addresses, coupons and orders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Shoppers sign in with a one-time code sent to an email or a mobile, never both required.
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
            $table->string('phone', 20)->nullable()->unique()->after('email');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('recipient_name');
            $table->string('phone', 20);
            $table->string('street');
            $table->string('city');
            $table->string('region')->nullable();
            $table->string('postal_code', 10);
            $table->string('country', 2)->default('CA');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type')->comment('percent or fixed');
            $table->unsignedInteger('value')->comment('percent, or cents when fixed');
            $table->unsignedInteger('min_subtotal_cents')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('times_used')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->index();
            $table->string('fulfilment');
            $table->string('contact_name');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->timestamp('pickup_starts_at')->nullable();
            $table->timestamp('pickup_ends_at')->nullable();
            $table->json('delivery_address')->nullable();
            $table->unsignedInteger('subtotal_cents');
            $table->unsignedInteger('savings_cents')->default(0);
            $table->unsignedInteger('discount_cents')->default(0);
            $table->unsignedInteger('delivery_fee_cents')->default(0);
            $table->unsignedInteger('total_cents');
            $table->string('coupon_code')->nullable();
            $table->string('payment_method');
            $table->timestamp('placed_at')->useCurrent();
            $table->timestamps();

            $table->index(['fulfilment', 'pickup_starts_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('variation_name')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('line_total_cents');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('addresses');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropColumn(['phone', 'phone_verified_at']);
            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }
};
