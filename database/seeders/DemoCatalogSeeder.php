<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Country;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\Recipe;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo catalogue taken from the Figma landing page, used until the Square sync
 * fills these tables. Safe to re-run: everything is matched on slug.
 */
class DemoCatalogSeeder extends Seeder
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

    /**
     * name => [size, price, previous price, stock, category, country, badges]
     * badges: trending, hot, bestseller (rank), discount (estimate %), label (custom purple tag), gallery (image count)
     */
    private const PRODUCTS = [
        'Black Eyed Beans' => ['1kg pouch', 399, 499, 40, 'Rice & beans', 'Nigeria'],
        'West African Pepper Sauce' => ['250ml bottle', 379, 469, 0, 'Sauces', 'Ghana'],
        'Ripe Plantain' => ['4 pieces', 329, 419, 60, 'Fresh produce', 'Jamaica', ['trending' => true, 'discount' => 24]],
        'Indomie' => ['70g pack', 449, 599, 120, 'Snacks', 'Nigeria', ['bestseller' => 6]],
        'Zobo Hibiscus Drink' => ['500ml bottle', 289, 359, 30, 'Drinks', 'Nigeria', ['hot' => true]],
        'Dried Hibiscus Petals' => ['200g pouch', 349, 499, 25, 'Drinks', 'Nigeria', ['bestseller' => 3, 'discount' => 24, 'gallery' => 4]],
        'Fresh Okra' => ['300g bunch', 249, 319, 35, 'Fresh produce', 'Nigeria'],
        'White Garri' => ['1kg pouch', 449, 539, 50, 'Rice & beans', 'Nigeria', ['bestseller' => 5]],
        'Groundnut Paste' => ['400g jar', 399, 479, 20, 'Sauces', 'Ghana'],
        'Titus Sardines' => [null, 249, 329, 80, 'Meat & fish', 'Nigeria', ['discount' => 24]],
        'Peak Evaporated Milk' => [null, 189, 249, 90, 'Drinks', 'Nigeria', ['trending' => true]],
        'Pounded Yam' => ['1kg pouch', 199, 279, 45, 'Rice & beans', 'Ghana'],
        'Tomatoes' => [null, 379, 499, 70, 'Fresh produce', null, ['bestseller' => 1]],
        'Nasco Cornflakes' => ['500g', 229, 299, 40, 'Snacks', 'Nigeria'],
        'Onga Seasoning' => ['1 sachet', 599, 749, 100, 'Seasonings', 'Cameroon'],
        'Dangota Sugar' => ['1kg sachet', 349, 429, 60, 'Snacks', 'Nigeria', ['trending' => true, 'bestseller' => 2]],
        'Golden Penny Macaroni' => ['500g', 349, 429, 55, 'Rice & beans', 'Nigeria'],
        'Premium Parboiled Rice' => [null, 1299, null, 40, 'Rice & beans', null, ['trending' => true]],
        'Gino Tomato Paste' => [null, 249, null, 75, 'Sauces', 'Ghana', ['discount' => 24]],
        'Scotch Bonnet Peppers' => [null, 279, null, 30, 'Fresh produce', 'Jamaica', ['bestseller' => 4]],
        'Mamador Pure Oil' => [null, 499, null, 25, 'Seasonings', 'Nigeria'],
        'Maggi Seasoning Cubes' => [null, 99, null, 200, 'Seasonings', null, ['hot' => true]],
        'Fresh Beef' => [null, 599, null, 15, 'Meat & fish', null, ['trending' => true]],
        'Red Palm Oil' => [null, 349, null, 40, 'Sauces', 'Nigeria', ['hot' => true]],
        'Vegetable' => [null, 249, null, 20, 'Fresh produce', null],
        'Dried Stockfish' => [null, 599, null, 18, 'Meat & fish', 'Nigeria'],
        'Dried Crayfish' => ['100g pack', 299, 349, 22, 'Meat & fish', 'Nigeria', ['trending' => true]],
        'Sardines in Tomato Sauce' => ['155g can', 129, 169, 60, 'Meat & fish', 'Ghana', ['discount' => 24]],
        'Pure Red Palm Oil' => ['1L bottle', 499, 799, 30, 'Sauces', 'Nigeria', ['trending' => true]],
        'Fresh Green Plantain' => ['1 bunch (approx 1.5kg)', 349, 399, 40, 'Fresh produce', 'Ghana', ['hot' => true]],
        'Ground Egusi Seeds' => ['250g pouch', 499, 599, 35, 'Seasonings', 'Nigeria', ['bestseller' => 7]],
        'Fresh Spinach' => ['200g bag', 149, 199, 3, 'Fresh produce', null],
        'Seasoning Stock Cubes' => ['60 cubes pack', 249, 299, 80, 'Seasonings', 'Ghana', ['bestseller' => 8]],
        'Rich Tomato Paste' => ['400g can', 199, 249, 45, 'Sauces', 'Ghana', ['discount' => 20]],
        'Pounded Yam Flour' => ['1.5kg bag', 449, 849, 25, 'Rice & beans', 'Nigeria', ['bestseller' => 9]],
        'Indomie Chicken Noodles' => ['70g pack', 79, 99, 150, 'Snacks', 'Nigeria', ['label' => 'Popular']],
        'Capri-Sun Tropical' => ['200ml pouch', 99, 129, 70, 'Drinks', null, ['label' => 'Kids fave']],
        'Golden Penny Semovita' => ['1kg pack', 399, 449, 40, 'Rice & beans', 'Nigeria', ['bestseller' => 10]],
        'Nestlé Milo' => ['400g tin', 599, 699, 30, 'Drinks', 'Nigeria', ['bestseller' => 11]],
        'Supligen Vanilla Drink' => ['290ml can', 179, 229, 48, 'Drinks', 'Nigeria'],
        'Chief Curry Powder' => ['85g pack', 249, 299, 2, 'Seasonings', 'Nigeria'],
        'Mama Gold Garri' => ['1kg pack', 249, 299, 0, 'Rice & beans', 'Nigeria'],
        'Bournvita Chocolate Drink' => ['500g jar', 649, 749, 26, 'Drinks', 'Nigeria'],
        'Knorr Chicken Seasoning' => ['100g jar', 279, 329, 33, 'Seasonings', 'Nigeria'],
        'Ofada Rice' => ['2kg pack', 699, 899, 18, 'Rice & beans', 'Nigeria', ['label' => 'Popular']],
        'Basmati Rice' => ['5kg bag', 899, 1099, 20, 'Rice & beans', 'Nigeria'],
        'Ginger' => [null, 249, null, 50, 'Fresh produce', null],
        'Clove' => [null, 279, null, 40, 'Seasonings', null, ['bestseller' => 12]],
        'Pineapple' => [null, 499, null, 25, 'Fresh produce', 'Ghana'],
    ];

    private const RECIPES = [
        'Jollof Rice' => ['Smoky, rich and always a good idea.', 50, 'Nigeria', ['Premium Parboiled Rice', 'Gino Tomato Paste', 'Scotch Bonnet Peppers', 'Mamador Pure Oil', 'Maggi Seasoning Cubes']],
        'Egusi Soup' => ['Nutty, hearty and full of flavour.', 45, 'Nigeria', ['Fresh Beef', 'Gino Tomato Paste', 'Red Palm Oil', 'Vegetable', 'Maggi Seasoning Cubes', 'Dried Stockfish']],
        'Banga Soup' => ['A true taste of the Niger Delta.', 70, 'Nigeria', ['Dried Crayfish', 'Red Palm Oil', 'Fresh Beef', 'Dried Stockfish', 'Vegetable']],
        'Curry Goat' => ['Slow-cooked, fragrant and made for sharing.', 90, 'Jamaica', []],
        'Zobo Hibiscus Drink' => ['A refreshing, ruby-red Nigerian classic.', 30, 'Nigeria', ['Dried Hibiscus Petals', 'Ginger', 'Clove', 'Pineapple']],
    ];

    private const COLLECTIONS = [
        'Fresh this week' => ['Popular picks. Great prices. Real flavour.', ['Black Eyed Beans', 'West African Pepper Sauce', 'Ripe Plantain', 'Indomie', 'Zobo Hibiscus Drink', 'Dried Hibiscus Petals', 'Fresh Okra', 'White Garri', 'Groundnut Paste']],
        'More from the shelves' => ['Everyday essentials and new things worth trying.', ['Titus Sardines', 'Peak Evaporated Milk', 'Pounded Yam', 'Tomatoes', 'Nasco Cornflakes', 'Indomie', 'Onga Seasoning', 'Ripe Plantain', 'Dangota Sugar', 'Golden Penny Macaroni']],
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

        // Earlier demo data spelt this "Cryfish"; rename in place so existing baskets keep working.
        Product::where('slug', 'dried-cryfish')->update(['slug' => 'dried-crayfish']);

        foreach (self::PRODUCTS as $name => $row) {
            [$size, $price, $previous, $stock, $category, $country] = $row;
            $badges = $row[6] ?? [];

            $product = Product::updateOrCreate(['slug' => $this->slug($name)], [
                'name' => $name,
                'category_id' => Category::where('slug', $this->slug($category))->value('id'),
                'country_id' => $country ? Country::where('slug', $this->slug($country))->value('id') : null,
                'image' => 'images/store/products/'.$this->slug($name).'.webp',
                'is_trending' => $badges['trending'] ?? false,
                'is_hot' => $badges['hot'] ?? false,
                'bestseller_rank' => $badges['bestseller'] ?? null,
                'discount_estimate' => $badges['discount'] ?? null,
                'badge_label' => $badges['label'] ?? null,
                'gallery' => isset($badges['gallery'])
                    ? array_map(fn (int $n) => 'images/store/gallery/'.$this->slug($name)."-{$n}.webp", range(1, $badges['gallery']))
                    : null,
            ]);

            $product->variations()->updateOrCreate(['sort_order' => 0], [
                'name' => $size,
                'price_cents' => $price,
                'previous_price_cents' => $previous,
                'stock_quantity' => $stock,
            ]);
        }

        foreach (array_keys(self::RECIPES) as $i => $name) {
            [$tagline, $minutes, $country, $ingredients] = self::RECIPES[$name];
            $slug = $this->slug($name);

            $recipe = Recipe::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'tagline' => $tagline,
                'duration_minutes' => $minutes,
                'country_id' => Country::where('slug', $this->slug($country))->value('id'),
                'image' => $ingredients ? "images/store/recipes/{$slug}.webp" : null,
                'thumbnail' => file_exists(public_path("images/store/recipes/thumbs/{$slug}.webp"))
                    ? "images/store/recipes/thumbs/{$slug}.webp"
                    : "images/store/recipes/{$slug}.webp",
                'is_featured' => $i < 3,
                'sort_order' => $i,
            ]);

            $recipe->products()->sync($this->positions($ingredients));
        }

        foreach (self::COLLECTIONS as $name => [$subtitle, $products]) {
            $collection = ProductCollection::updateOrCreate(['slug' => $this->slug($name)], [
                'name' => $name,
                'subtitle' => $subtitle,
            ]);

            $collection->products()->sync($this->positions($products));
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function category(string $name, array $attributes): Category
    {
        return Category::updateOrCreate(['slug' => $this->slug($name)], ['name' => $name, ...$attributes]);
    }

    /**
     * Product names to [product_id => ['sort_order' => n]] for a pivot sync.
     *
     * @param  array<int, string>  $names
     * @return array<int, array{sort_order: int}>
     */
    private function positions(array $names): array
    {
        $ids = Product::whereIn('slug', array_map($this->slug(...), $names))->pluck('id', 'slug');

        return collect($names)
            ->mapWithKeys(fn (string $name, int $i) => [$ids[$this->slug($name)] => ['sort_order' => $i]])
            ->all();
    }

    private function slug(string $name): string
    {
        return Str::slug(str_replace('&', 'and', $name));
    }
}
