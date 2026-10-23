<?php

namespace App\Support;

/**
 * Editorial content for the storefront (promo banners) until it moves to the admin.
 */
class StorefrontContent
{
    /**
     * @return array<int, array<string, string>>
     */
    public static function heroSlides(): array
    {
        return [
            [
                'eyebrow' => 'Good food, on its way',
                'title' => 'Free Delivery Over $40.',
                'body' => 'From jollof to jerk, plantain to egusi — authentic favourites for your favourite dishes.',
                'cta' => 'Fill your box',
                'image' => 'images/store/banners/free-delivery.webp',
                'tint' => 'bg-trend-60',
            ],
            [
                'eyebrow' => 'This week only',
                'title' => '20% Off Pantry Staples',
                'body' => 'Stock up on everyday essentials.',
                'cta' => 'Shop deals',
                'image' => 'images/store/banners/pantry-staples.webp',
                'tint' => 'bg-sale-50',
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function promoSlides(): array
    {
        return array_reverse(self::heroSlides());
    }
}
