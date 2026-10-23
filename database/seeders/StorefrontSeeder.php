<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The shop's own structure from Figma, which Square doesn't have: the mega
 * menu category tree, the "Shop by category" tiles and the countries.
 * Used on the live site too. Square categories with the same names are
 * matched onto these by the sync, so products land in the right place.
 */
class StorefrontSeeder extends Seeder
{
    /**
     * Mega menu groups and their subcategories. Only the first group has artwork in Figma.
     */
    private const MENU = [
        'Food & pastry' => ['Flours & baking', 'Grains & cereals', 'Spices & seasonings', 'Cooking oils', 'Vegetables'],
        'Frozen foods' => ['Meat & poultry', 'Seafood', 'Frozen vegetables', 'Frozen prepared foods', 'Other frozen foods'],
        'Snacks & treats' => ['Snacks', 'Sweets & confectionary'],
        'Drinks' => ['Soft drinks', 'Beverages'],
        'Beauty & personal care' => ['Skincare', 'Haircare', 'Bath & body', 'Personal care', 'Beauty accessories'],
        'Home & household' => ['Cleaning', 'Laundry', 'Kitchen & household', 'Other home essentials'],
    ];

    /**
     * "Shop by category" tiles on the home page: name => [emoji, menu group it belongs to].
     */
    private const FEATURED = [
        'Rice & beans' => ['🌾', 'Food & pastry'],
        'Sauces' => ['🥫', 'Food & pastry'],
        'Seasonings' => ['🌶️', 'Food & pastry'],
        'Fresh produce' => ['🥬', 'Food & pastry'],
        'Meat & fish' => ['🐟', 'Frozen foods'],
        'Snacks' => ['🍪', 'Snacks & treats'],
        'Drinks' => ['🧃', null],
        'Beauty & home' => ['🧴', 'Home & household'],
    ];

    /**
     * [name, ISO code, demonym, tile tagline, tile theme, has tile artwork]
     */
    private const COUNTRIES = [
        ['Nigeria', 'NG', 'Nigerian', 'Naija, your favourites are here.', 'green', true],
        ['Ghana', 'GH', 'Ghanaian', 'Chale, home is closer than you think.', 'yellow', true],
        ['Jamaica', 'JM', 'Jamaican', 'Straight from yard to your kitchen.', 'green', true],
        ['Cameroon', 'CM', 'Cameroonian', 'Chale, home is closer than you think.', 'yellow', true],
        ['Trinidad & Tobago', 'TT', 'Trinidadian', 'Chale, home is closer than you think.', 'green', true],
        ['Ethiopia', 'ET', 'Ethiopian', 'Chale, home is closer than you think.', 'green', true],
        ['Kenya', 'KE', 'Kenyan', 'Karibu, a taste of home.', 'green', false],
    ];

    /**
     * Country page banners that exist in Figma.
     */
    private const COUNTRY_BANNERS = [
        'Nigeria' => 'images/store/countries/banners/nigeria.webp',
    ];

    public function run(): void
    {
        foreach (array_keys(self::MENU) as $i => $group) {
            $parent = $this->category($group, ['sort_order' => $i]);

            foreach (self::MENU[$group] as $j => $name) {
                $image = $i === 0 ? 'images/store/subcategories/'.$this->slug($name).'.webp' : null;
                $this->category($name, ['parent_id' => $parent->id, 'image' => $image, 'sort_order' => $j]);
            }
        }

        // Home page tiles are part of the tree so the shop filters find their products.
        // "Drinks" is itself a menu group, so it stays a root.
        foreach (array_keys(self::FEATURED) as $i => $name) {
            [$emoji, $group] = self::FEATURED[$name];

            $this->category($name, array_filter([
                'parent_id' => $group ? Category::where('slug', $this->slug($group))->value('id') : null,
                'is_featured' => true,
                'featured_order' => $i,
                'emoji' => $emoji,
                'image' => 'images/store/categories/'.$this->slug($name).'.webp',
                // Keep a menu group's own menu position; tiles go after the designed subcategories.
                'sort_order' => $group ? 100 + $i : null,
            ], fn ($value) => $value !== null));
        }

        foreach (self::COUNTRIES as $i => [$name, $code, $demonym, $tagline, $theme, $hasTile]) {
            Country::updateOrCreate(['slug' => $this->slug($name)], [
                'name' => $name,
                'code' => $code,
                'demonym' => $demonym,
                'tagline' => $tagline,
                'theme' => $theme,
                'image' => $hasTile ? 'images/store/countries/'.$this->slug($name).'.webp' : null,
                'banner_image' => self::COUNTRY_BANNERS[$name] ?? null,
                'banner_description' => "Find familiar {$demonym} staples, pantry favourites and flavours from home — all in one place.",
                'sort_order' => $i,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function category(string $name, array $attributes): Category
    {
        return Category::updateOrCreate(['slug' => $this->slug($name)], ['name' => $name, ...$attributes]);
    }

    private function slug(string $name): string
    {
        return Str::slug(str_replace('&', 'and', $name));
    }
}
