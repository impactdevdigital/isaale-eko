<?php

use App\Livewire\Store\CartDrawer;
use App\Models\Product;
use App\Support\Cart;
use Database\Seeders\DemoCatalogSeeder;
use Livewire\Livewire;

beforeEach(fn () => $this->seed(DemoCatalogSeeder::class));

function variationId(string $slug): int
{
    return Product::where('slug', $slug)->firstOrFail()->defaultVariation->id;
}

test('home page renders the storefront from the database', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Fresh This Week')
        ->assertSee('Black Eyed Beans')
        ->assertSee('Jollof Rice')
        ->assertSee('Shop Categories')
        ->assertSee('Your basket is hungry');
});

test('adding a product prices it from the database and opens the drawer', function () {
    Livewire::test(CartDrawer::class)
        ->dispatch('cart-add', variation: variationId('indomie'))
        ->assertDispatched('cart-open')
        ->assertDispatched('cart-updated', count: 1)
        ->assertSee('Indomie')
        ->assertSee('$4.49');

    expect(app(Cart::class)->subtotalCents())->toBe(449);
});

test('price changes in the catalogue reach existing baskets', function () {
    $id = variationId('indomie');
    app(Cart::class)->add($id);

    Product::where('slug', 'indomie')->first()->defaultVariation->update(['price_cents' => 500]);

    expect(app(Cart::class)->subtotalCents())->toBe(500);
});

test('unknown, inactive and out of stock products are not added', function () {
    Product::where('slug', 'indomie')->update(['is_active' => false]);

    Livewire::test(CartDrawer::class)
        ->dispatch('cart-add', variation: 999999)
        ->dispatch('cart-add', variation: variationId('west-african-pepper-sauce'))
        ->dispatch('cart-add', variation: variationId('indomie'))
        ->assertNotDispatched('cart-open');

    expect(app(Cart::class)->count())->toBe(0);
});

test('quantity, selection and removal update the subtotal', function () {
    $okra = variationId('fresh-okra');
    $paste = variationId('groundnut-paste');

    $component = Livewire::test(CartDrawer::class)
        ->dispatch('cart-add', variation: $okra)
        ->dispatch('cart-add', variation: $paste)
        ->call('increment', $okra);

    expect(app(Cart::class)->subtotalCents())->toBe(249 * 2 + 399);

    $component->call('toggle', $paste);
    expect(app(Cart::class)->subtotalCents())->toBe(498);

    $component->call('decrement', $okra)->call('decrement', $okra);
    expect(app(Cart::class)->quantity($okra))->toBe(0);

    $component->call('remove', $paste)->assertSee('Your basket is hungry');
});

test('adding a whole recipe adds each ingredient once', function () {
    $oil = variationId('red-palm-oil');

    Livewire::test(CartDrawer::class)
        ->dispatch('cart-add-many', variations: [$oil, variationId('fresh-beef'), $oil])
        ->assertDispatched('cart-updated', count: 2);
});

test('the demo seeder can run twice without duplicating rows', function () {
    $before = Product::count();

    $this->seed(DemoCatalogSeeder::class);

    expect(Product::count())->toBe($before)
        ->and(Product::where('slug', 'indomie')->first()->variations()->count())->toBe(1);
});
