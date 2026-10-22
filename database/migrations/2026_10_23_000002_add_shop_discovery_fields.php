<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fields for the listing, country and product detail pages, plus
 * back-in-stock sign-ups.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('badge_label')->nullable()->after('is_hot')->comment('Custom purple tag, e.g. Popular, Kids fave');
            $table->json('gallery')->nullable()->after('image');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('emoji', 16)->nullable()->after('image');
            $table->unsignedSmallInteger('featured_order')->nullable()->after('is_featured');
        });

        Schema::table('countries', function (Blueprint $table) {
            $table->string('code', 2)->nullable()->after('slug')->comment('ISO 3166-1 alpha-2, for the flag');
            $table->string('demonym')->nullable()->after('name');
            $table->string('banner_image')->nullable()->after('image');
            $table->string('banner_description')->nullable()->after('banner_image');
        });

        Schema::create('stock_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_notifications');

        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn(['code', 'demonym', 'banner_image', 'banner_description']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['emoji', 'featured_order']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['badge_label', 'gallery']);
        });
    }
};
