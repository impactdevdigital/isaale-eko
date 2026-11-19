<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds a "Visit us in Saint John" slide (store photo) to the top homepage
 * slideshow, linking to the About page. Staff can edit or hide it in
 * Admin → Homepage Banners.
 */
return new class extends Migration
{
    private const TITLE = 'Visit Us in Saint John';

    public function up(): void
    {
        $now = now();

        DB::table('banners')->insert([
            'placement' => 'hero',
            'eyebrow' => 'Our store',
            'eyebrow_fr' => 'Notre magasin',
            'title' => self::TITLE,
            'title_fr' => 'Visitez-nous à Saint John',
            'body' => 'Shelves full of African and Caribbean favourites. Shop online, pick up in store.',
            'body_fr' => 'Des rayons remplis de favoris africains et caribéens. Achetez en ligne, récupérez en magasin.',
            'cta_label' => 'Visit the store',
            'cta_label_fr' => 'Visiter le magasin',
            'cta_url' => '/about',
            'image' => 'images/store/banners/visit-the-store.webp',
            'tint' => 'bg-ink',
            'is_active' => true,
            'sort_order' => (int) DB::table('banners')->where('placement', 'hero')->max('sort_order') + 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('banners')
            ->where('title', self::TITLE)
            ->where('image', 'images/store/banners/visit-the-store.webp')
            ->delete();
    }
};
