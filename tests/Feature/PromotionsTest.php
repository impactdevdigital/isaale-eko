<?php

use App\Livewire\Admin\PromotionsManager;
use App\Livewire\Store\CartPage;
use App\Livewire\Store\Checkout;
use App\Models\AnalyticsEvent;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Promotion;
use App\Models\PromotionRedemption;
use App\Models\User;
use App\Services\Checkout\PlaceOrder;
use App\Services\Promotions\PromotionEngine;
use App\Support\Cart;
use App\Support\CartTotals;
use Carbon\CarbonImmutable;
use Database\Seeders\PaymentMethodSeeder;
use Database\Seeders\StoreHoursSeeder;
use Illuminate\Support\Collection;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([PaymentMethodSeeder::class, StoreHoursSeeder::class]);
    // A Monday morning before opening, so every pickup window that day is open.
    $this->travelTo(CarbonImmutable::parse('2026-10-26 08:00', 'America/Moncton'));

    $this->rice = promoVariation('Golden Sella Rice', 2000);
    $this->okra = promoVariation('Fresh Okra', 500);
});

function promoVariation(string $name, int $priceCents): ProductVariation
{
    $product = Product::create(['name' => $name, 'slug' => str($name)->slug(), 'item_class' => 'grocery']);

    return $product->variations()->create(['name' => '1kg', 'price_cents' => $priceCents, 'stock_quantity' => 50]);
}

function promotion(array $attributes = [], array $productIds = []): Promotion
{
    $promotion = Promotion::create([
        'name' => 'Rice week',
        'template' => Promotion::TEMPLATE_ITEM_DISCOUNT,
        'objective' => 'discovery',
        'audience' => 'everyone',
        'discount_type' => 'percent',
        'discount_value' => 10,
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addWeek(),
        ...$attributes,
    ]);

    $promotion->products()->sync($productIds);

    return $promotion;
}

function promoOrder(User $user, string $email = 'valentine@example.com'): Order
{
    $start = CarbonImmutable::parse('2026-10-26 11:00', 'America/Moncton');

    return app(PlaceOrder::class)->handle(
        user: $user,
        contactName: $user->name,
        fulfilment: 'pickup',
        paymentMethod: 'card',
        pickupStart: $start,
        guestEmail: $email,
    );
}

function lines(): Collection
{
    return app(Cart::class)->checkoutLines();
}

test('an item discount takes money off the chosen products in the basket and shows on the product page', function () {
    promotion([], [$this->rice->product_id]);
    app(Cart::class)->add($this->rice->id, 2);
    app(Cart::class)->add($this->okra->id);

    $totals = CartTotals::for(lines());
    expect($totals->discount)->toBe(400)        // 10% of 2 × $20, okra untouched
        ->and($totals->total())->toBe(4500 - 400)
        ->and($totals->promotion->promotion->name)->toBe('Rice week');

    Livewire::test(CartPage::class)->assertSee('Rice week')->assertSeeHtml('&minus;'.money(400));

    $this->get(route('product', $this->rice->product))->assertSee('10% off')->assertSee('Taken off in your basket');
    $this->get(route('product', $this->okra->product))->assertDontSee('Taken off in your basket');

    expect(AnalyticsEvent::where('name', 'promotion_exposed')->count())->toBe(1);
});

test('drafts, paused, scheduled and ended promotions do nothing', function (array $attributes) {
    promotion($attributes, [$this->rice->product_id]);
    app(Cart::class)->add($this->rice->id);

    expect(CartTotals::for(lines())->discount)->toBe(0);
})->with([
    'draft' => fn () => ['status' => 'draft'],
    'paused' => fn () => ['status' => 'paused'],
    'scheduled' => fn () => ['starts_at' => now()->addDay()],
    'ended' => fn () => ['ends_at' => now()->subMinute()],
]);

test('a first online order offer applies only to customers with no previous order, checked by account and email', function () {
    promotion([
        'name' => 'Welcome $5 off',
        'template' => Promotion::TEMPLATE_FIRST_ORDER,
        'objective' => 'acquisition',
        'audience' => 'first_time',
        'discount_type' => 'fixed',
        'discount_value' => 500,
        'min_subtotal_cents' => 2000,
        'per_customer_limit' => 1,
    ]);
    app(Cart::class)->add($this->rice->id);

    // An anonymous shopper: we can't tell yet, so it waits for their email at checkout.
    expect(CartTotals::for(lines())->discount)->toBe(0);

    $newcomer = User::factory()->create(['email' => 'new@example.com']);
    expect(CartTotals::for(lines(), customer: $newcomer)->discount)->toBe(500)
        ->and(CartTotals::for(lines(), email: 'someone-new@example.com')->discount)->toBe(500);

    // Under the minimum basket it doesn't apply.
    app(Cart::class)->setQuantity($this->rice->id, 1);
    app(Cart::class)->remove($this->rice->id);
    app(Cart::class)->add($this->okra->id);
    expect(CartTotals::for(lines(), customer: $newcomer)->discount)->toBe(0);
    app(Cart::class)->remove($this->okra->id);

    // Their first order uses it…
    app(Cart::class)->add($this->rice->id);
    $order = promoOrder($newcomer, 'new@example.com');
    expect($order->discount_cents)->toBe(500)
        ->and($order->promotion_id)->not->toBeNull()
        ->and(PromotionRedemption::where('order_id', $order->id)->value('discount_cents'))->toBe(500)
        ->and(AnalyticsEvent::where('name', 'promotion_redeemed')->count())->toBe(1);

    // …so their next one doesn't, by account or by email.
    app(Cart::class)->add($this->rice->id);
    expect(CartTotals::for(lines(), customer: $newcomer)->discount)->toBe(0)
        ->and(CartTotals::for(lines(), email: 'NEW@example.com')->discount)->toBe(0);

    // A payment that failed doesn't use the offer up.
    $order->update(['status' => Order::STATUS_PAYMENT_FAILED]);
    expect(CartTotals::for(lines(), customer: $newcomer)->discount)->toBe(500);
});

test('guests get the first order offer at checkout once they type an email with no past orders', function () {
    promotion(['name' => 'Welcome $5 off', 'template' => Promotion::TEMPLATE_FIRST_ORDER, 'audience' => 'first_time', 'discount_type' => 'fixed', 'discount_value' => 500]);
    app(Cart::class)->add($this->rice->id);

    Livewire::test(Checkout::class)
        ->assertDontSee('Welcome $5 off')
        ->set('email', 'brand-new@example.com')
        ->assertSee('Welcome $5 off')
        ->assertSee(money(2000 - 500));
});

test('one discount per order: the better of a promotion and a code wins, and the shopper is told', function () {
    promotion(['discount_type' => 'fixed', 'discount_value' => 300], [$this->rice->product_id]); // $3 off each rice
    Coupon::create(['code' => 'TENOFF', 'type' => 'fixed', 'value' => 400, 'is_active' => true]);
    $cart = app(Cart::class);
    $cart->add($this->rice->id);
    $cart->add($this->okra->id, 4); // subtotal $40

    $cart->applyCoupon('TENOFF', $cart->subtotalCents());

    // Code: $4 off; promotion: $3. The code wins.
    $totals = CartTotals::for(lines(), $cart->coupon());
    expect($totals->discount)->toBe(400)->and($totals->coupon?->code)->toBe('TENOFF')->and($totals->promotion)->toBeNull();

    // With two rice the promotion ($6) beats the code ($4): no stacking, the code is set aside.
    $cart->add($this->rice->id);
    $totals = CartTotals::for(lines(), $cart->coupon());
    expect($totals->discount)->toBe(600)->and($totals->promotion)->not->toBeNull()->and($totals->setAsideCoupon?->code)->toBe('TENOFF');

    Livewire::test(CartPage::class)->assertSee('Your code TENOFF isn’t used: Rice week saves you more. One discount per order.');
});

test('a budget cap stops the promotion once reached, and the last redemption is re-checked when the order is placed', function () {
    $promotion = promotion(['max_redemptions' => 1], [$this->rice->product_id]);

    app(Cart::class)->add($this->rice->id);
    promoOrder(User::factory()->create());

    expect($promotion->fresh()->state())->toBe('capped');

    app(Cart::class)->add($this->rice->id);
    expect(CartTotals::for(lines())->discount)->toBe(0);
});

test('audiences: lapsed customers and loyalty members', function () {
    $engine = new PromotionEngine;
    $lapsedPromo = promotion(['audience' => 'lapsed']);
    $loyaltyPromo = promotion(['audience' => 'loyalty_members']);

    $lapsed = User::factory()->create();
    Order::create(['number' => 'ISA-OLD001', 'user_id' => $lapsed->id, 'status' => Order::STATUS_PAID, 'fulfilment' => 'pickup', 'contact_name' => 'A', 'subtotal_cents' => 100, 'total_cents' => 100, 'payment_method' => 'card', 'placed_at' => now()->subDays(120)]);
    $recent = User::factory()->create();
    Order::create(['number' => 'ISA-NEW001', 'user_id' => $recent->id, 'status' => Order::STATUS_PAID, 'fulfilment' => 'pickup', 'contact_name' => 'B', 'subtotal_cents' => 100, 'total_cents' => 100, 'payment_method' => 'card', 'placed_at' => now()->subDays(10)]);
    PointTransaction::create(['user_id' => $recent->id, 'type' => PointTransaction::TYPE_EARNED, 'points' => 10, 'description' => 'test']);

    expect($engine->eligible($lapsedPromo, $lapsed))->toBeTrue()
        ->and($engine->eligible($lapsedPromo, $recent))->toBeFalse()
        ->and($engine->eligible($loyaltyPromo, $recent))->toBeTrue()
        ->and($engine->eligible($loyaltyPromo, $lapsed))->toBeFalse()
        ->and($engine->audienceSize('lapsed'))->toBe(1)
        ->and($engine->audienceSize('loyalty_members'))->toBe(1);
});

test('admin: choose a template, fill the required fields, preview who is eligible and the price, then activate', function () {
    User::factory()->count(2)->create(); // customers with no orders yet

    Livewire::test(PromotionsManager::class)
        ->call('create')
        ->assertSee('First online order offer')
        ->call('chooseTemplate', Promotion::TEMPLATE_ITEM_DISCOUNT)
        ->set('name', 'Rice week')
        ->set('discountValue', '15')
        ->set('endsAt', '')
        ->call('preview')
        ->assertHasErrors(['endsAt', 'productIds'])
        ->set('productSearch', 'golden')
        ->assertSee('+ Golden Sella Rice')
        ->call('addProduct', $this->rice->product_id)
        ->set('endsAt', now()->addDays(7)->timezone(config('shop.timezone'))->format('Y-m-d\TH:i'))
        ->call('preview')
        ->assertHasNoErrors()
        ->assertSet('step', 'preview')
        ->assertSee('Who is eligible')
        ->assertSee(money(2000))
        ->assertSee(money(1700))
        ->call('save', true)
        ->assertSee('is active');

    $promotion = Promotion::sole();
    expect($promotion->state())->toBe('live')
        ->and($promotion->discount_value)->toBe(15)
        ->and($promotion->products->pluck('id')->all())->toBe([$this->rice->product_id]);

    Livewire::test(PromotionsManager::class)->call('pause', $promotion->id);
    expect($promotion->fresh()->state())->toBe('paused');

    Livewire::test(PromotionsManager::class)->call('resume', $promotion->id)->call('endNow', $promotion->id);
    expect($promotion->fresh()->state())->toBe('ended');

    // Only drafts can be deleted.
    Livewire::test(PromotionsManager::class)->call('delete', $promotion->id);
    expect(Promotion::count())->toBe(1);
});

test('admin: the first order template is locked to first-time customers and shows an example basket', function () {
    Livewire::test(PromotionsManager::class)
        ->call('create')
        ->call('chooseTemplate', Promotion::TEMPLATE_FIRST_ORDER)
        ->assertSet('audience', 'first_time')
        ->set('name', 'Welcome $5 off')
        ->set('discountValue', '5')
        ->call('preview')
        ->assertSee('A first order of '.money(5000))
        ->assertSee(money(4500))
        ->call('save', false);

    expect(Promotion::sole())->status->toBe('draft')->audience->toBe('first_time')->discount_value->toBe(500)->per_customer_limit->toBe(1);
});

test('only managers can open the promotions page', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true, 'role' => User::ROLE_ASSOCIATE]))
        ->get(route('admin.promotions'))->assertForbidden();

    $this->actingAs(User::factory()->create(['is_admin' => true, 'role' => User::ROLE_MANAGER]))
        ->get(route('admin.promotions'))->assertOk()->assertSee('Promotions');
});
