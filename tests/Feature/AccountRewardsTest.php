<?php

use App\Livewire\Store\Account\BuyAgain;
use App\Livewire\Store\Account\Preferences;
use App\Livewire\Store\Account\Rewards;
use App\Livewire\Store\Account\Wishlist;
use App\Livewire\Store\ProductPage;
use App\Models\Consent;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\Product;
use App\Models\User;
use App\Services\Checkout\TakePayment;
use App\Support\Cart;
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

test('paying online earns 10 points per dollar, once', function () {
    $order = rewardsOrder($this->user);

    app(TakePayment::class)->markPaid($order, 'sq-1');
    app(TakePayment::class)->markPaid($order->fresh(), 'sq-1');

    expect($this->user->pointsBalance())->toBe(242)
        ->and(PointTransaction::count())->toBe(1);
});

test('pay-at-pickup orders earn points when collected, not before', function () {
    $order = rewardsOrder($this->user, ['status' => Order::STATUS_PLACED, 'payment_method' => 'pay_at_pickup']);

    $order->update(['fulfilment_status' => Order::FULFILMENT_READY]);
    expect($this->user->pointsBalance())->toBe(0);

    $order->update(['fulfilment_status' => Order::FULFILMENT_COMPLETED]);
    expect($this->user->pointsBalance())->toBe(242);
});

test('cancelled orders earn nothing', function () {
    rewardsOrder($this->user, ['status' => Order::STATUS_PLACED])->update(['status' => Order::STATUS_CANCELLED]);

    expect($this->user->pointsBalance())->toBe(0);
});

test('redeeming 500 points gives a personal $5 code on the basket', function () {
    $this->user->pointTransactions()->create(['type' => 'earned', 'points' => 582, 'description' => 'Purchase']);

    Livewire::test(Rewards::class)
        ->assertSee('582 pts')
        ->call('redeem')
        ->assertSet('problem', null)
        ->assertSee('Redeemed!');

    $coupon = Coupon::where('user_id', $this->user->id)->sole();
    expect($this->user->pointsBalance())->toBe(82)
        ->and($coupon->value)->toBe(500)
        ->and($coupon->max_uses)->toBe(1)
        ->and(app(Cart::class)->coupon()?->code)->toBe($coupon->code);

    // Not enough points left for another.
    Livewire::test(Rewards::class)->call('redeem')->assertSet('problem', 'You need 500 points for this reward.');
    expect(Coupon::whereNotNull('user_id')->count())->toBe(1);
});

test('reward codes only work for their owner', function () {
    $coupon = Coupon::create(['code' => 'REWARD-ABC123', 'user_id' => $this->user->id, 'type' => 'fixed', 'value' => 500, 'max_uses' => 1, 'is_active' => true]);

    expect(app(Cart::class)->applyCoupon($coupon->code, 1000))->toBe('applied');

    app(Cart::class)->removeCoupon();
    $this->actingAs(User::create(['name' => 'Other', 'email' => 'other@example.com']));

    expect(app(Cart::class)->applyCoupon($coupon->code, 1000))->toBe('invalid');
});

test('the points history filters earned and used', function () {
    $order = rewardsOrder($this->user, ['number' => 'ISA-EARN01']);
    $this->user->pointTransactions()->create(['type' => 'earned', 'order_id' => $order->id, 'points' => 600, 'description' => '$60.00 spent']);
    $this->user->pointTransactions()->create(['type' => 'redeemed', 'points' => -500, 'description' => '$5.00 reward redeemed']);

    Livewire::test(Rewards::class)
        ->assertSee('$60.00 spent')->assertSee('$5.00 reward redeemed')->assertSee('ISA-EARN01')
        ->set('tab', 'earned')
        ->assertSee('$60.00 spent')->assertDontSee('$5.00 reward redeemed')
        ->set('tab', 'used')
        ->assertDontSee('$60.00 spent')->assertSee('$5.00 reward redeemed');
});

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
