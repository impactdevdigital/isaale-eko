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
use Database\Seeders\DemoCatalogSeeder;
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

function promotion(array $attributes = []): Promotion
{
    return Promotion::create([
        'name' => 'Welcome 10% off',
        'template' => Promotion::TEMPLATE_FIRST_ORDER,
        'objective' => 'acquisition',
        'audience' => 'first_time',
        'discount_type' => 'percent',
        'discount_value' => 10,
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addWeek(),
        ...$attributes,
    ]);
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

test('item discounts are retired: old ones never apply and nothing shows on the product page', function () {
    $old = Promotion::create([
        'name' => 'Rice week',
        'template' => Promotion::TEMPLATE_ITEM_DISCOUNT,
        'objective' => 'discovery',
        'audience' => 'everyone',
        'discount_type' => 'percent',
        'discount_value' => 10,
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addWeek(),
    ]);
    $old->products()->sync([$this->rice->product_id]);
    app(Cart::class)->add($this->rice->id, 2);

    $totals = CartTotals::for(lines());
    expect($totals->discount)->toBe(0)->and($totals->promotion)->toBeNull();

    Livewire::test(CartPage::class)->assertDontSee('Rice week');
    $this->get(route('product', $this->rice->product))->assertOk()->assertDontSee('10% off')->assertDontSee('Taken off in your basket');

    // Still listed in the admin history, marked retired, and can't be edited.
    Livewire::test(PromotionsManager::class)->assertSee('Item discount (retired)')->assertDontSee('wire:click="edit('.$old->id.')"', false);
});

test('drafts, paused, scheduled and ended promotions do nothing', function (array $attributes) {
    promotion($attributes);
    app(Cart::class)->add($this->rice->id);

    expect(CartTotals::for(lines(), customer: User::factory()->create())->discount)->toBe(0);
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

    Livewire::test(Checkout::class)->set('phone', '506 555 0123')
        ->assertDontSee('Welcome $5 off')
        ->set('email', 'brand-new@example.com')
        ->assertSee('Welcome $5 off')
        ->assertSee(money(2000 - 500));
});

test('one discount per order: the better of a promotion and a code wins, and the shopper is told', function () {
    $newcomer = User::factory()->create();
    promotion(['name' => 'Welcome 10% off']);
    Coupon::create(['code' => 'TENOFF', 'type' => 'fixed', 'value' => 400, 'is_active' => true]);
    $cart = app(Cart::class);
    $cart->add($this->rice->id);
    $cart->add($this->okra->id, 4); // subtotal $40

    $cart->applyCoupon('TENOFF', $cart->subtotalCents());

    // Code: $4 off; promotion: 10% = $4. A tie keeps the code.
    $totals = CartTotals::for(lines(), $cart->coupon(), customer: $newcomer);
    expect($totals->discount)->toBe(400)->and($totals->coupon?->code)->toBe('TENOFF')->and($totals->promotion)->toBeNull();

    // At $60 the promotion ($6) beats the code ($4): no stacking, the code is set aside.
    $cart->add($this->rice->id);
    $totals = CartTotals::for(lines(), $cart->coupon(), customer: $newcomer);
    expect($totals->discount)->toBe(600)->and($totals->promotion)->not->toBeNull()->and($totals->setAsideCoupon?->code)->toBe('TENOFF');

    $this->actingAs($newcomer);
    Livewire::test(CartPage::class)->assertSee('Your code TENOFF isn’t used: Welcome 10% off saves you more. One discount per order.');
});

test('a budget cap stops the promotion once reached, and the last redemption is re-checked when the order is placed', function () {
    $promotion = promotion(['max_redemptions' => 1]);

    app(Cart::class)->add($this->rice->id);
    promoOrder(User::factory()->create(), 'first@example.com');

    expect($promotion->fresh()->state())->toBe('capped');

    app(Cart::class)->add($this->rice->id);
    expect(CartTotals::for(lines(), customer: User::factory()->create())->discount)->toBe(0);
});

test('audience sizes for the admin preview: lapsed customers and loyalty members', function () {
    $engine = new PromotionEngine;

    $lapsed = User::factory()->create();
    Order::create(['number' => 'ISA-OLD001', 'user_id' => $lapsed->id, 'status' => Order::STATUS_PAID, 'fulfilment' => 'pickup', 'contact_name' => 'A', 'subtotal_cents' => 100, 'total_cents' => 100, 'payment_method' => 'card', 'placed_at' => now()->subDays(120)]);
    $recent = User::factory()->create();
    Order::create(['number' => 'ISA-NEW001', 'user_id' => $recent->id, 'status' => Order::STATUS_PAID, 'fulfilment' => 'pickup', 'contact_name' => 'B', 'subtotal_cents' => 100, 'total_cents' => 100, 'payment_method' => 'card', 'placed_at' => now()->subDays(10)]);
    PointTransaction::create(['user_id' => $recent->id, 'type' => PointTransaction::TYPE_EARNED, 'points' => 10, 'description' => 'test']);

    expect($engine->audienceSize('lapsed'))->toBe(1)
        ->and($engine->audienceSize('loyalty_members'))->toBe(1)
        ->and($engine->audienceSize('first_time'))->toBe(0);
});

test('admin: item discount is no longer offered; a first order offer is filled in, previewed, activated, paused and ended', function () {
    User::factory()->count(2)->create(); // customers with no orders yet

    Livewire::test(PromotionsManager::class)
        ->call('create')
        ->assertSee('First online order offer')
        ->assertDontSee('Item discount')
        ->call('chooseTemplate', Promotion::TEMPLATE_ITEM_DISCOUNT)
        ->assertNotFound();

    Livewire::test(PromotionsManager::class)
        ->call('create')
        ->call('chooseTemplate', Promotion::TEMPLATE_FIRST_ORDER)
        ->set('name', 'Welcome $5 off')
        ->set('discountValue', '5')
        ->set('endsAt', '')
        ->call('preview')
        ->assertHasErrors(['endsAt'])
        ->set('endsAt', now()->addDays(7)->timezone(config('shop.timezone'))->format('Y-m-d\TH:i'))
        ->call('preview')
        ->assertHasNoErrors()
        ->assertSet('step', 'preview')
        ->assertSee('Who is eligible')
        ->assertSee(money(5000))
        ->assertSee(money(4500))
        ->call('save', true)
        ->assertSee('is active');

    $promotion = Promotion::sole();
    expect($promotion->state())->toBe('live')
        ->and($promotion->template)->toBe(Promotion::TEMPLATE_FIRST_ORDER)
        ->and($promotion->discount_value)->toBe(500);

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

test('products show no discount markers: no crossed-out price, no estimate tag, no deals links or sort', function () {
    $this->seed(DemoCatalogSeeder::class);
    $this->rice->update(['previous_price_cents' => 2500]);
    $this->rice->product->update(['discount_estimate' => 20]);
    app(Cart::class)->add($this->rice->id);

    $this->get(route('product', $this->rice->product))->assertOk()
        ->assertSee(money(2000))
        ->assertDontSee(money(2500))
        ->assertDontSee('Estimate');

    $this->get(route('shop'))->assertOk()->assertDontSee('Special deals')->assertDontSee('Discounted');

    Livewire::test(CartPage::class)->assertDontSee(money(2500))->assertDontSee('Product savings');
    expect(CartTotals::for(lines())->savings)->toBe(0);
});
