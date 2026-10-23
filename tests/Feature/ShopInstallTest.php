<?php

use App\Models\Category;
use App\Models\Country;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\StoreHour;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('shop:install fills in what is missing and leaves staff changes alone', function () {
    config(['services.square.token' => null]);

    $this->artisan('shop:install')->assertSuccessful();

    expect(StoreHour::count())->toBeGreaterThan(0)
        ->and(PaymentMethod::count())->toBeGreaterThan(0)
        ->and(User::where('is_admin', true)->count())->toBeGreaterThan(0);

    // Staff change an opening time; running it again must not undo that.
    $monday = StoreHour::first();
    $monday->update(['opens_at' => '06:30']);

    $this->artisan('shop:install')->assertSuccessful();

    expect($monday->fresh()->opens_at)->toStartWith('06:30');
});

test('shop:install runs the first Square import only when no products are linked', function () {
    config(['services.square.token' => 't', 'services.square.location_id' => 'LOC1']);
    Http::fake(['*' => Http::response(['objects' => [], 'counts' => []])]);

    $this->artisan('shop:install')->assertSuccessful();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'catalog/list'));
});

/**
 * A Square sandbox like the live one: flat categories, one demo product.
 */
function fakeFlatSquare(): void
{
    config(['services.square.token' => 't', 'services.square.location_id' => 'LOC1', 'services.square.env' => 'sandbox']);

    $objects = [
        ['type' => 'CATEGORY', 'id' => 'CAT1', 'category_data' => ['name' => 'Rice & beans']],
        ['type' => 'ITEM', 'id' => 'ITEM1', 'present_at_all_locations' => true, 'item_data' => [
            'name' => 'Black Eyed Beans',
            'categories' => [['id' => 'CAT1']],
            'variations' => [['type' => 'ITEM_VARIATION', 'id' => 'VAR1', 'present_at_all_locations' => true, 'item_variation_data' => [
                'name' => '1kg pouch', 'pricing_type' => 'FIXED_PRICING', 'price_money' => ['amount' => 425, 'currency' => 'CAD'],
            ]]],
        ]],
    ];

    Http::fake([
        '*/v2/catalog/list*' => Http::response(['objects' => $objects]),
        '*/v2/catalog/batch-retrieve' => Http::response(['objects' => []]),
        '*/v2/inventory/counts/batch-retrieve' => Http::response(['counts' => []]),
    ]);
}

test('a fresh live site gets the menu categories and countries, and Square products land in them', function () {
    fakeFlatSquare();

    $this->artisan('shop:install')->assertSuccessful();

    $product = Product::where('square_id', 'ITEM1')->first();

    expect(Country::count())->toBeGreaterThan(0)
        ->and($product->category->slug)->toBe('rice-and-beans')
        ->and($product->category->square_id)->toBe('CAT1')
        ->and($product->category->parent->slug)->toBe('food-and-pastry')
        ->and(Category::where('name', 'Rice & beans')->count())->toBe(1)
        // Sandbox demo extras.
        ->and($product->country->slug)->toBe('nigeria');

    $this->get('/shop')->assertOk()->assertSee('Rice &amp; beans', false)->assertSee('Nigeria');
});

test('Square categories imported before the menu existed are merged into it', function () {
    $copy = Category::create(['name' => 'Rice & beans', 'slug' => 'rice-beans', 'square_id' => 'CAT1']);
    $product = Product::create(['name' => 'Beans', 'slug' => 'beans', 'square_id' => 'ITEM1', 'category_id' => $copy->id]);
    config(['services.square.token' => null]);

    $this->artisan('shop:install')->assertSuccessful();

    $category = $product->fresh()->category;

    expect($category->slug)->toBe('rice-and-beans')
        ->and($category->square_id)->toBe('CAT1')
        ->and(Category::find($copy->id))->toBeNull();
});

test('the shop filters list flat Square categories and hide countries without products', function () {
    $category = Category::create(['name' => 'Pantry', 'slug' => 'pantry']);
    Country::create(['name' => 'Ghana', 'slug' => 'ghana']);
    $product = Product::create(['name' => 'Gari', 'slug' => 'gari', 'category_id' => $category->id]);
    $product->variations()->create(['price_cents' => 299]);

    $this->get('/shop')->assertOk()->assertSee('Pantry')->assertDontSee('Ghana');
});
