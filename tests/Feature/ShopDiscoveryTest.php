<?php

use App\Livewire\Store\ProductListing;
use App\Livewire\Store\ProductPage;
use App\Models\Country;
use App\Models\Product;
use App\Models\StockNotification;
use Database\Seeders\DemoCatalogSeeder;
use Livewire\Livewire;

beforeEach(fn () => $this->seed(DemoCatalogSeeder::class));

test('shop, country and product pages render', function (string $url, string $text) {
    $this->get($url)->assertOk()->assertSee($text, escape: false);
})->with([
    'all products' => ['/shop', 'All Products'],
    'category' => ['/shop?category=rice-and-beans', 'Rice &amp; beans'],
    'country' => ['/shop/country/nigeria', 'Shop Nigeria'],
    'product' => ['/products/dried-hibiscus-petals', 'Cook With Dried Hibiscus Petals'],
]);

test('a product with no category still opens (related products fall back to bestsellers)', function () {
    $product = Product::create(['name' => 'Uncategorised Square Item', 'slug' => 'uncategorised-square-item', 'item_class' => 'grocery']);
    $product->variations()->create(['name' => 'Each', 'price_cents' => 199, 'stock_quantity' => 5]);

    $this->get('/products/uncategorised-square-item')->assertOk()->assertSee('Uncategorised Square Item');
});

test('unknown products are a 404', function () {
    $this->get('/products/not-a-real-thing')->assertNotFound();
});

test('listing paginates fifteen products per page', function () {
    Livewire::test(ProductListing::class)
        ->assertViewHas('products', fn ($page) => $page->count() === 15 && $page->total() === Product::active()->count())
        ->call('nextPage')
        ->assertViewHas('products', fn ($page) => $page->currentPage() === 2);
});

test('search matches product names and shows the empty state when nothing matches', function () {
    Livewire::withQueryParams(['q' => 'rice'])
        ->test(ProductListing::class)
        ->assertViewHas('products', fn ($page) => $page->pluck('name')->sort()->values()->all() === ['Basmati Rice', 'Ofada Rice', 'Premium Parboiled Rice']);

    Livewire::withQueryParams(['q' => 'buritos'])
        ->test(ProductListing::class)
        ->assertSee('There Were No Search Results For buritos');
});

test('a parent category includes its subcategories', function () {
    Livewire::test(ProductListing::class)
        ->set('category', 'food-and-pastry')
        ->assertViewHas('products', fn ($page) => $page->total() > 0
            && $page->every(fn ($product) => in_array($product->category->slug, ['rice-and-beans', 'sauces', 'seasonings', 'fresh-produce'])));
});

test('price, availability and country filters narrow the results', function () {
    Livewire::test(ProductListing::class)
        ->set('price', 'under-5')
        ->assertViewHas('products', fn ($page) => $page->every(fn ($product) => $product->defaultVariation->price_cents < 500))
        ->set('price', '')
        ->set('availability', 'out-of-stock')
        ->assertViewHas('products', fn ($page) => $page->pluck('slug')->sort()->values()->all() === ['mama-gold-garri', 'west-african-pepper-sauce'])
        ->set('availability', '')
        ->set('country', 'ghana')
        ->assertViewHas('products', fn ($page) => $page->every(fn ($product) => $product->country_id === Country::where('slug', 'ghana')->value('id')));
});

test('sorting by bestsellers lists ranked products in order', function () {
    Livewire::test(ProductListing::class)
        ->set('sort', 'bestsellers')
        ->assertViewHas('products', fn ($page) => $page->pluck('bestseller_rank')->all() === range(1, 12));
});

test('clearing filters keeps the search term', function () {
    Livewire::withQueryParams(['q' => 'rice'])
        ->test(ProductListing::class)
        ->set('price', 'over-15')
        ->call('clearFilters')
        ->assertSet('price', '')
        ->assertSet('q', 'rice');
});

test('product page shows the right stock state', function (string $slug, string $state, string $text) {
    $product = Product::where('slug', $slug)->firstOrFail();

    expect($product->stockState())->toBe($state);
    $this->get("/products/{$slug}")->assertSee($text, escape: false);
})->with([
    'in stock' => ['indomie', 'in_stock', 'In stock · Ready to deliver'],
    'low stock' => ['fresh-spinach', 'low_stock', 'Only 3 left'],
    'out of stock' => ['mama-gold-garri', 'out_of_stock', 'Temporarily sold out'],
]);

test('inactive products stay reachable but cannot be bought', function () {
    $product = Product::where('slug', 'indomie')->firstOrFail();
    $product->update(['is_active' => false]);

    $this->get('/products/indomie')->assertOk()->assertSee('Product unavailable');

    Livewire::test(ProductPage::class, ['product' => $product])
        ->call('addToBasket')
        ->assertNotDispatched('cart-add');
});

test('adding from the product page sends the chosen quantity to the basket', function () {
    $product = Product::where('slug', 'fresh-spinach')->firstOrFail();

    Livewire::test(ProductPage::class, ['product' => $product])
        ->call('increment')
        ->call('increment')
        ->call('increment') // only 3 in stock
        ->assertSet('quantity', 3)
        ->call('addToBasket')
        ->assertDispatched('cart-add', variation: $product->defaultVariation->id, quantity: 3);
});

test('shoppers can ask to be told when a product is back', function () {
    $product = Product::where('slug', 'mama-gold-garri')->firstOrFail();

    Livewire::test(ProductPage::class, ['product' => $product])
        ->set('email', 'not-an-email')
        ->call('notify')
        ->assertHasErrors('email')
        ->set('email', 'Amara@Example.com')
        ->call('notify')
        ->assertSet('subscribed', true)
        ->call('notify');

    expect(StockNotification::where('product_id', $product->id)->pluck('email')->all())->toBe(['amara@example.com']);
});
