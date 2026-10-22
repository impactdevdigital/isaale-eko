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
     * "Shop by category" tiles on the home page.
     */
    private const FEATURED = ['Rice & beans', 'Sauces', 'Seasonings', 'Fresh produce', 'Meat & fish', 'Snacks', 'Drinks', 'Beauty & home'];

    private const COUNTRIES = [
        ['Nigeria', 'Naija, your favourites are here.', 'green'],
        ['Ghana', 'Chale, home is closer than you think.', 'yellow'],
        ['Jamaica', 'Straight from yard to your kitchen.', 'green'],
        ['Cameroon', 'Chale, home is closer than you think.', 'yellow'],
        ['Trinidad & Tobago', 'Chale, home is closer than you think.', 'green'],
        ['Ethiopia', 'Chale, home is closer than you think.', 'green'],
    ];

    /**
     * name => [size, price, previous price, stock, category, country, badges]
     */
    private const PRODUCTS = [
        'Black Eyed Beans' => ['1kg pouch', 399, 499, 40, 'Rice & beans', 'Nigeria'],
        'West African Pepper Sauce' => ['250ml bottle', 379, 469, 0, 'Sauces', 'Ghana'],
        'Ripe Plantain' => ['4 pieces', 329, 419, 60, 'Fresh produce', 'Jamaica', ['trending' => true, 'discount' => 24]],
        'Indomie' => ['70g pack', 449, 599, 120, 'Snacks', 'Nigeria', ['bestseller' => 6]],
        'Zobo Hibiscus Drink' => ['500ml bottle', 289, 359, 30, 'Drinks', 'Nigeria', ['hot' => true]],
        'Dried Hibiscus Petals' => ['200g pouch', 349, 439, 25, 'Seasonings', 'Nigeria', ['bestseller' => 3, 'discount' => 24]],
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
        'Dried Cryfish' => [null, 299, null, 22, 'Meat & fish', 'Nigeria'],
    ];

    private const RECIPES = [
        'Jollof Rice' => ['Smoky, rich and always a good idea.', 50, 'Nigeria', ['Premium Parboiled Rice', 'Gino Tomato Paste', 'Scotch Bonnet Peppers', 'Mamador Pure Oil', 'Maggi Seasoning Cubes']],
        'Egusi Soup' => ['Nutty, hearty and full of flavour.', 45, 'Nigeria', ['Fresh Beef', 'Gino Tomato Paste', 'Red Palm Oil', 'Vegetable', 'Maggi Seasoning Cubes', 'Dried Stockfish']],
        'Banga Soup' => ['A true taste of the Niger Delta.', 70, 'Nigeria', ['Dried Cryfish', 'Red Palm Oil', 'Fresh Beef', 'Dried Stockfish', 'Vegetable']],
        'Curry Goat' => ['Slow-cooked, fragrant and made for sharing.', 90, 'Jamaica', []],
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

        // Home page tiles sit outside the menu tree until the Square taxonomy replaces both.
        foreach (self::FEATURED as $i => $name) {
            $this->category($name, [
                'is_featured' => true,
                'image' => 'images/store/categories/'.$this->slug($name).'.webp',
                'sort_order' => 100 + $i,
            ]);
        }

        foreach (self::COUNTRIES as $i => [$name, $tagline, $theme]) {
            Country::updateOrCreate(['slug' => $this->slug($name)], [
                'name' => $name,
                'tagline' => $tagline,
                'theme' => $theme,
                'image' => 'images/store/countries/'.$this->slug($name).'.webp',
                'sort_order' => $i,
            ]);
        }

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
                'thumbnail' => "images/store/recipes/thumbs/{$slug}.webp",
                'is_featured' => (bool) $ingredients,
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
