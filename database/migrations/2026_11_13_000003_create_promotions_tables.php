<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Promotions built from a small set of templates (item discount, first
 * online order offer), each with an objective, audience, dates, a limit
 * per customer and an optional cap on redemptions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('template', 30);       // item_discount | first_order
            $table->string('objective', 30);      // acquisition | reactivation | basket_growth | discovery
            $table->string('audience', 30);       // everyone | first_time | lapsed | loyalty_members
            $table->string('discount_type', 10);  // percent | fixed
            $table->unsignedInteger('discount_value'); // percent (1-90) or cents off
            $table->foreignId('collection_id')->nullable()->constrained('collections')->nullOnDelete();
            $table->unsignedInteger('min_subtotal_cents')->nullable();
            $table->unsignedInteger('per_customer_limit')->nullable(); // null = no limit
            $table->unsignedInteger('max_redemptions')->nullable();    // budget cap
            $table->string('status', 20)->default('draft'); // draft | active | paused
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'starts_at', 'ends_at']);
        });

        Schema::create('promotion_product', function (Blueprint $table) {
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['promotion_id', 'product_id']);
        });

        Schema::create('promotion_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('discount_cents');
            $table->timestamps();

            $table->unique(['promotion_id', 'order_id']);
            $table->index(['promotion_id', 'user_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('promotion_id')->nullable()->after('coupon_code')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promotion_id');
        });

        Schema::dropIfExists('promotion_redemptions');
        Schema::dropIfExists('promotion_product');
        Schema::dropIfExists('promotions');
    }
};
