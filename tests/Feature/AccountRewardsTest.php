<?php

use App\Livewire\Store\Account\BuyAgain;
use App\Livewire\Store\Account\Preferences;
use App\Livewire\Store\Account\Wishlist;
use App\Livewire\Store\ProductPage;
use App\Models\Consent;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoCatalogSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(DemoCatalogSeeder::class);

    $this->user = User::create(['name' => 'Valentine Akin', 'email' => 'val@example.com', 'email_verified_at' => now()]);
    $this->actingAs($this->user);
});

function rewardsOrder(User $user, array $attributes = []): Order
{
    return Order::create([
        'number' => 'ISA-'.strtoupper(str()->random(6)),
        'user_id' => $user->id,
        'status' => Order::STATUS_AWAITING_PAYMENT,
        'fulfilment' => 'pickup',
        'contact_name' => $user->name,
        'subtotal_cents' => 2420,
        'total_cents' => 2420,
        'payment_method' => 'card',
        'placed_at' => now(),
        ...$attributes,
    ]);
}

test('preferences start off and save as consent records', function () {
    $component = Livewire::test(Preferences::class)
        ->assertSet('choices.recipes', false)
        ->set('choices.recipes', true)
        ->set('choices.offers', true)
        ->set('choices.whatsappOrders', true)
        ->call('save')
        ->assertSet('saved', true)
        // No verified mobile, so WhatsApp stays off.
        ->assertSet('choices.whatsappOrders', false);

    expect(Consent::whereNull('revoked_at')->pluck('channel')->sort()->values()->all())->toBe(['email', 'email_offers']);

    $component->set('choices.offers', false)->call('save');

    expect(Consent::whereNull('revoked_at')->pluck('channel')->all())->toBe(['email']);

    Livewire::test(Preferences::class)->assertSet('choices.recipes', true)->assertSet('choices.offers', false);
});

test('products can be saved to the wishlist and removed', function () {
    $product = Product::where('slug', 'fresh-okra')->firstOrFail();

    Livewire::test(ProductPage::class, ['product' => $product])->call('toggleWishlist');
    expect($this->user->wishlist()->pluck('products.id')->all())->toBe([$product->id]);

    Livewire::test(Wishlist::class)
        ->assertSee('Fresh Okra')
        ->call('remove', $product->id)
        ->assertSee('Nothing saved yet');
});

test('guests are asked to sign in before saving to the wishlist', function () {
    auth()->logout();

    Livewire::test(ProductPage::class, ['product' => Product::where('slug', 'fresh-okra')->firstOrFail()])
        ->call('toggleWishlist')
        ->assertDispatched('sign-in-open');
});

test('buy again lists products from confirmed orders only', function () {
    $okra = Product::where('slug', 'fresh-okra')->firstOrFail();
    $other = Product::where('id', '!=', $okra->id)->firstOrFail();

    rewardsOrder($this->user, ['status' => Order::STATUS_PAID])->items()->create([
        'product_id' => $okra->id, 'name' => $okra->name, 'unit_price_cents' => 249, 'quantity' => 1, 'line_total_cents' => 249,
    ]);
    rewardsOrder($this->user, ['status' => Order::STATUS_PAYMENT_FAILED])->items()->create([
        'product_id' => $other->id, 'name' => $other->name, 'unit_price_cents' => 100, 'quantity' => 1, 'line_total_cents' => 100,
    ]);

    Livewire::test(BuyAgain::class)->assertSee($okra->name)->assertDontSee($other->name);
});

test('every account page loads', function (string $path) {
    $this->get($path)->assertOk();
})->with(['/account/rewards', '/account/wishlist', '/account/buy-again', '/account/preferences']);
