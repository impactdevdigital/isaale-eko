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
                'eyebrow' => __('Good food, on its way'),
                'title' => __('Free Delivery Over $40.'),
                'body' => __('From jollof to jerk, plantain to egusi — authentic favourites for your favourite dishes.'),
                'cta' => __('Fill your box'),
                'image' => 'images/store/banners/free-delivery.webp',
                'tint' => 'bg-trend-60',
            ],
            [
                'eyebrow' => __('This week only'),
                'title' => __('20% Off Pantry Staples'),
                'body' => __('Stock up on everyday essentials.'),
                'cta' => __('Shop deals'),
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
