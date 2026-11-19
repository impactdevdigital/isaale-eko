<?php

namespace App\Support;

use App\Models\Banner;

/**
 * Editorial content for the storefront. Homepage banners are edited by
 * staff in Admin → Homepage Banners.
 */
class StorefrontContent
{
    /**
     * @return array<int, array<string, ?string>>
     */
    public static function heroSlides(): array
    {
        return self::slides('hero');
    }

    /**
     * @return array<int, array<string, ?string>>
     */
    public static function promoSlides(): array
    {
        return self::slides('promo');
    }

    /**
     * @return array<int, array<string, ?string>>
     */
    private static function slides(string $placement): array
    {
        return Banner::live()
            ->where('placement', $placement)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Banner $banner) => $banner->toSlide())
            ->all();
    }
}
