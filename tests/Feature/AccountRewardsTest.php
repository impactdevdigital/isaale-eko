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
use App\Services\Loyalty\LoyaltyProvider;
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

test('paying online counts the order towards the next reward, once', function () {
    $order = rewardsOrder($this->user);

    app(TakePayment::class)->markPaid($order, 'sq-1');
    app(TakePayment::class)->markPaid($order->fresh(), 'sq-1');

    // $24.20 spent: 24 hidden points, shown as $24 / $100.
    expect($this->user->pointsBalance())->toBe(24)
        ->and(PointTransaction::count())->toBe(1)
        ->and(app(LoyaltyProvider::class)->progress($this->user))
        ->countedCents->toBe(2400)
        ->blockCents->toBe(10000);
});

test('only the items after discounts count: no delivery fee', function () {
    $order = rewardsOrder($this->user, ['fulfilment' => 'delivery', 'subtotal_cents' => 5000, 'discount_cents' => 500, 'delivery_fee_cents' => 799, 'total_cents' => 5299]);

    app(TakePayment::class)->markPaid($order, 'sq-1');

    expect($this->user->pointsBalance())->toBe(45);
});

test('pay-at-pickup orders count when collected, not before', function () {
    $order = rewardsOrder($this->user, ['status' => Order::STATUS_PLACED, 'payment_method' => 'pay_at_pickup']);

    $order->update(['fulfilment_status' => Order::FULFILMENT_READY]);
    expect($this->user->pointsBalance())->toBe(0);

    $order->update(['fulfilment_status' => Order::FULFILMENT_COMPLETED]);
    expect($this->user->pointsBalance())->toBe(24);
});

test('cancelled orders count for nothing', function () {
    rewardsOrder($this->user, ['status' => Order::STATUS_PLACED])->update(['status' => Order::STATUS_CANCELLED]);

    expect($this->user->pointsBalance())->toBe(0);
});

test('every $100 unlocks $3 off automatically: minimum order $25, valid 30 days, remainder carries over', function () {
    $this->user->pointTransactions()->create(['type' => 'earned', 'points' => 76, 'description' => '$76.00 spent']);

    Livewire::test(Rewards::class)->assertSee('$76.00')->assertSee('/ $100.00')->assertSee('$24.00 more to unlock $3.00 off');

    // A $230.00 order takes $76 to $306: three rewards, $6 carries over.
    app(TakePayment::class)->markPaid(rewardsOrder($this->user, ['subtotal_cents' => 23000, 'total_cents' => 23000]), 'sq-2');

    $rewards = Coupon::where('user_id', $this->user->id)->get();
    expect($rewards)->toHaveCount(3)
        ->and($rewards->first()->value)->toBe(300)
        ->and($rewards->first()->min_subtotal_cents)->toBe(2500)
        ->and($rewards->first()->max_uses)->toBe(1)
        ->and($rewards->first()->expires_at->isSameDay(now()->addDays(30)))->toBeTrue()
        ->and($this->user->pointsBalance())->toBe(6);

    Livewire::test(Rewards::class)
        ->assertSee('$6.00')
        ->assertSee('$3.00 off an order of $25.00+')
        ->assertSee('can’t be combined with other offers');
});

test('a reward goes on the basket only once it reaches the $25 minimum', function () {
    $this->user->pointTransactions()->create(['type' => 'earned', 'points' => 99, 'description' => 'Purchase']);
    app(TakePayment::class)->markPaid(rewardsOrder($this->user, ['subtotal_cents' => 100, 'total_cents' => 100]), 'sq-3'); // the 100th dollar
    $reward = Coupon::where('user_id', $this->user->id)->sole();

    $product = Product::whereHas('defaultVariation', fn ($q) => $q->where('price_cents', '<', 2500))->firstOrFail();
    app(Cart::class)->add($product->defaultVariation->id);

    Livewire::test(Rewards::class)->call('useReward', $reward->id)->assertSee('Your basket needs to reach $25.00 first');
    expect(app(Cart::class)->coupon())->toBeNull();

    app(Cart::class)->setQuantity($product->defaultVariation->id, (int) ceil(2500 / $product->defaultVariation->price_cents));

    Livewire::test(Rewards::class)->call('useReward', $reward->id)->assertSee('On your basket');
    expect(app(Cart::class)->coupon()?->code)->toBe($reward->code);
});

test('a refund takes back the progress its order earned, never the delivery fee', function () {
    $order = rewardsOrder($this->user, ['fulfilment' => 'delivery', 'subtotal_cents' => 6000, 'delivery_fee_cents' => 799, 'total_cents' => 6799]);
    app(TakePayment::class)->markPaid($order, 'sq-4');
    expect($this->user->pointsBalance())->toBe(60);

    $loyalty = app(LoyaltyProvider::class);
    $loyalty->reversePurchase($order->fresh(), 2000);
    expect($this->user->pointsBalance())->toBe(40);

    // Refunding everything (including the fee) takes back no more than the order earned.
    $loyalty->reversePurchase($order->fresh(), 6799);
    $loyalty->reversePurchase($order->fresh(), 6799);
    expect($this->user->pointsBalance())->toBe(0)
        ->and(PointTransaction::where('type', PointTransaction::TYPE_REVERSED)->sum('points'))->toBe(-60);

    Livewire::test(Rewards::class)->set('tab', 'used')->assertSee('Refund')->assertSee('$20.00 refunded');
});

test('reward codes only work for their owner', function () {
    $coupon = Coupon::create(['code' => 'REWARD-ABC123', 'user_id' => $this->user->id, 'type' => 'fixed', 'value' => 500, 'max_uses' => 1, 'is_active' => true]);

    expect(app(Cart::class)->applyCoupon($coupon->code, 1000))->toBe('applied');

    app(Cart::class)->removeCoupon();
    $this->actingAs(User::create(['name' => 'Other', 'email' => 'other@example.com']));

    expect(app(Cart::class)->applyCoupon($coupon->code, 1000))->toBe('invalid');
});

test('the history filters what counted from what was unlocked or refunded', function () {
    $order = rewardsOrder($this->user, ['number' => 'ISA-EARN01']);
    $this->user->pointTransactions()->create(['type' => 'earned', 'order_id' => $order->id, 'points' => 160, 'description' => '$160.00 spent']);
    $this->user->pointTransactions()->create(['type' => 'redeemed', 'points' => -100, 'description' => '$3.00 off unlocked']);

    Livewire::test(Rewards::class)
        ->assertSee('$160.00 spent')->assertSee('$3.00 off unlocked')->assertSee('ISA-EARN01')
        ->set('tab', 'earned')
        ->assertSee('$160.00 spent')->assertDontSee('$3.00 off unlocked')
        ->set('tab', 'used')
        ->assertDontSee('$160.00 spent')->assertSee('$3.00 off unlocked');
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
