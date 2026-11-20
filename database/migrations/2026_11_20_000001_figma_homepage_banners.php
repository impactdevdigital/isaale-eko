<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Swaps the starter homepage banners for the new designs in Figma
 * (Nov 2026) and adds a text colour choice for light photos.
 * The "Visit Us in Saint John" slide is hidden, not deleted, so staff can
 * switch it back on in Admin → Homepage Banners.
 */
return new class extends Migration
{
    private const OLD_IMAGES = [
        'images/store/banners/free-delivery.webp',
        'images/store/banners/pantry-staples.webp',
    ];

    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('style', 20)->default('light')->after('tint');
        });

        DB::table('banners')->whereIn('image', self::OLD_IMAGES)->delete();
        DB::table('banners')->where('image', 'images/store/banners/visit-the-store.webp')
            ->update(['is_active' => false, 'sort_order' => 3]);

        $now = now();
        $rows = [];

        foreach ($this->slides() as $placement => $slides) {
            foreach ($slides as $i => $slide) {
                $rows[] = $slide + [
                    'placement' => $placement,
                    'eyebrow' => null,
                    'eyebrow_fr' => null,
                    'image' => 'images/store/banners/'.$slide['file'].'.webp',
                    'is_active' => true,
                    'sort_order' => $i,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('banners')->insert(array_map(function (array $row) {
            unset($row['file']);

            return $row;
        }, $rows));
    }

    public function down(): void
    {
        $files = collect($this->slides())->flatten(1)->pluck('file')
            ->map(fn ($f) => 'images/store/banners/'.$f.'.webp')->all();

        DB::table('banners')->whereIn('image', $files)->delete();

        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('style');
        });
    }

    /**
     * @return array<string, array<int, array<string, ?string>>>
     */
    private function slides(): array
    {
        return [
            'hero' => [
                [
                    'file' => 'rewards',
                    'title' => 'Earn $3 in Rewards for Every $100 You Spend.',
                    'title_fr' => 'Gagnez 3 $ en récompenses pour chaque tranche de 100 $.',
                    'body' => 'Shop your favourites and earn rewards towards your next purchase.',
                    'body_fr' => 'Achetez vos favoris et cumulez des récompenses pour votre prochain achat.',
                    'cta_label' => 'Start Earning Rewards',
                    'cta_label_fr' => 'Commencer à gagner',
                    'cta_url' => '/account/rewards',
                    'tint' => 'bg-stock-50',
                    'style' => 'light-yellow',
                ],
                [
                    'file' => 'pantry-reset',
                    'title' => 'Time for a Pantry Reset?',
                    'title_fr' => 'Besoin de regarnir le garde-manger ?',
                    'body' => 'Get all your kitchen essentials in one go.',
                    'body_fr' => 'Tous vos essentiels de cuisine en une seule fois.',
                    'cta_label' => 'Shop',
                    'cta_label_fr' => 'Magasiner',
                    'cta_url' => '/shop',
                    'tint' => 'bg-[#F3E3CC]',
                    'style' => 'dark',
                ],
                [
                    'file' => 'whats-cooking',
                    'title' => 'What’s Cooking This Week?',
                    'title_fr' => 'Qu’est-ce qu’on cuisine cette semaine ?',
                    'body' => 'Let’s get your ingredients sorted.',
                    'body_fr' => 'Trouvons tous vos ingrédients.',
                    'cta_label' => 'Shop Your Meal Essentials',
                    'cta_label_fr' => 'Vos essentiels repas',
                    'cta_url' => '/kitchen',
                    'tint' => 'bg-stock-50',
                    'style' => 'light-yellow',
                ],
            ],
            'promo' => [
                [
                    'file' => 'pantry-festival',
                    'eyebrow' => 'The Ìsàlèè Èkó Pantry Festival',
                    'eyebrow_fr' => 'Le festival du garde-manger Ìsàlèè Èkó',
                    'title' => 'Good Groceries Don’t Have to Cost a Fortune.',
                    'title_fr' => 'De bonnes épiceries sans se ruiner.',
                    'body' => null,
                    'body_fr' => null,
                    'cta_label' => 'Shop Under $10',
                    'cta_label_fr' => 'Moins de 10 $',
                    'cta_url' => '/shop?price=under-10',
                    'tint' => 'bg-sale-50',
                    'style' => 'light',
                ],
                [
                    'file' => 'ask-asha',
                    'eyebrow' => 'Real People. Real Answers',
                    'eyebrow_fr' => 'De vraies personnes. De vraies réponses',
                    'title' => 'Ask Asha',
                    'title_fr' => 'Demandez à Asha',
                    'body' => 'Your personal grocery concierge.',
                    'body_fr' => 'Votre concierge épicerie personnelle.',
                    'cta_label' => 'Get answers',
                    'cta_label_fr' => 'Obtenir des réponses',
                    'cta_url' => '/help',
                    'tint' => 'bg-trend-60',
                    'style' => 'light',
                ],
                [
                    'file' => 'busy-day',
                    'eyebrow' => 'Cook It Tonight',
                    'eyebrow_fr' => 'À cuisiner ce soir',
                    'title' => 'Busy Day? No Problem.',
                    'title_fr' => 'Journée chargée ? Pas de problème.',
                    'body' => 'Get your grocery shopping sorted without the extra trip.',
                    'body_fr' => 'Faites vos courses sans déplacement de plus.',
                    'cta_label' => 'Shop now',
                    'cta_label_fr' => 'Magasiner',
                    'cta_url' => '/shop',
                    'tint' => 'bg-stock-50',
                    'style' => 'light',
                ],
                [
                    'file' => 'missing-home',
                    'eyebrow' => 'Cook It This Weekend',
                    'eyebrow_fr' => 'À cuisiner ce week-end',
                    'title' => 'Missing Home?',
                    'title_fr' => 'Le pays vous manque ?',
                    'body' => 'Your favourite flavours are closer than you think.',
                    'body_fr' => 'Vos saveurs préférées sont plus proches que vous ne le pensez.',
                    'cta_label' => 'Find Your Favourites',
                    'cta_label_fr' => 'Trouver vos favoris',
                    'cta_url' => '/shop?sort=bestsellers',
                    'tint' => 'bg-brand-yellow-90',
                    'style' => 'light',
                ],
            ],
        ];
    }
};
