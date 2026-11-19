<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Homepage banners, edited by staff in Admin → Homepage Banners. Two
 * carousels: "hero" at the top of the homepage and "promo" lower down.
 * Starts with the banners that used to be written into the code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('placement', 20)->default('hero'); // hero | promo
            $table->string('eyebrow')->nullable();
            $table->string('eyebrow_fr')->nullable();
            $table->string('title');
            $table->string('title_fr')->nullable();
            $table->string('body', 300)->nullable();
            $table->string('body_fr', 300)->nullable();
            $table->string('cta_label', 40)->nullable();
            $table->string('cta_label_fr', 40)->nullable();
            $table->string('cta_url')->nullable();
            $table->string('image');
            $table->string('tint', 40)->default('bg-trend-60');
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['placement', 'is_active', 'sort_order']);
        });

        $now = now();
        $delivery = [
            'eyebrow' => 'Good food, on its way',
            'title' => 'Free Delivery Over $40.',
            'body' => 'From jollof to jerk, plantain to egusi — authentic favourites for your favourite dishes.',
            'cta_label' => 'Fill your box',
            'cta_url' => '/shop',
            'image' => 'images/store/banners/free-delivery.webp',
            'tint' => 'bg-trend-60',
        ];
        $pantry = [
            'eyebrow' => 'This week only',
            'title' => '20% Off Pantry Staples',
            'body' => 'Stock up on everyday essentials.',
            'cta_label' => 'Shop deals',
            'cta_url' => '/shop',
            'image' => 'images/store/banners/pantry-staples.webp',
            'tint' => 'bg-sale-50',
        ];

        DB::table('banners')->insert([
            ['placement' => 'hero', 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now] + $delivery,
            ['placement' => 'hero', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now] + $pantry,
            ['placement' => 'promo', 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now] + $pantry,
            ['placement' => 'promo', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now] + $delivery,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
