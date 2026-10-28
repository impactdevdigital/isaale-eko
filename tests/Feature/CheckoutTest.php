<?php

use App\Livewire\Store\CartPage;
use App\Livewire\Store\Checkout;
use App\Livewire\Store\SignIn;
use App\Mail\VerificationCodeMail;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Cart;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Database\Seeders\StoreHoursSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([DemoCatalogSeeder::class, PaymentMethodSeeder::class, StoreHoursSeeder::class]);

    // A Monday morning before opening, so every window that day is open.
    $this->travelTo(CarbonImmutable::parse('2026-10-26 08:00', 'America/Moncton'));
    Mail::fake();
    config(['shop.currency' => 'CAD']);

    // Square's payments API: approve every charge unless a test says otherwise.
    Http::fake(['*/v2/payments' => Http::response(['payment' => ['id' => 'sq-pay-1', 'status' => 'COMPLETED']])]);
});

function addToCart(string $slug, int $quantity = 1): int
{
    $id = Product::where('slug', $slug)->firstOrFail()->defaultVariation->id;
    app(Cart::class)->add($id, $quantity);

    return $id;
}

function sentCode(): string
{
    $code = null;
    Mail::assertSent(VerificationCodeMail::class, function (VerificationCodeMail $mail) use (&$code) {
        $code = $mail->code;

        return true;
    });

    return $code;
}

test('basket page shows the empty state and the filled basket', function () {
    $this->get('/cart')->assertOk()->assertSee('Your Basket Is Hungry');

    addToCart('fresh-okra', 2);

    $this->get('/cart')->assertOk()->assertSee('Fresh Okra')->assertSee('Proceed to checkout')->assertSee(money(498));
});

test('items that sold out move to unavailable and quantities above stock are lowered', function () {
    addToCart('fresh-okra');
    addToCart('ground-egusi-seeds', 5);

    Product::where('slug', 'fresh-okra')->first()->defaultVariation->update(['stock_quantity' => 0]);
    Product::where('slug', 'ground-egusi-seeds')->first()->defaultVariation->update(['stock_quantity' => 2]);

    Livewire::test(CartPage::class)
        ->assertSee('Unavailable items (1)')
        ->assertSee('Only 2 left — we updated your quantity.')
        ->assertViewHas('totals', fn ($totals) => $totals->subtotal === 499 * 2);
});

test('promo codes show applied, invalid, expired and minimum spend states', function () {
    addToCart('fresh-okra', 4); // $9.96

    Livewire::test(CartPage::class)
        ->set('couponCode', 'NOPE')->call('applyCoupon')->assertSee('This code is not valid.')
        ->set('couponCode', 'spring5')->call('applyCoupon')->assertSee('This code has expired.')
        ->set('couponCode', 'BIGSHOP15')->call('applyCoupon')->assertSee('minimum spend')
        ->set('couponCode', 'welcome10')->call('applyCoupon')
        ->assertSee('WELCOME10 applied — you saved '.money(100).'.')
        ->assertViewHas('totals', fn ($totals) => $totals->total() === 996 - 100);
});

test('checkout sends shoppers with an empty basket back to the basket', function () {
    Livewire::test(Checkout::class)->assertRedirect(route('cart'));
});

test('guest pickup: choose a slot, confirm the email on proceed and pay by card', function () {
    addToCart('fresh-okra', 2);
    $start = CarbonImmutable::parse('2026-10-26 11:00', 'America/Moncton')->toIso8601String();

    $component = Livewire::test(Checkout::class)
        ->assertSee('Fulfilment &amp; Order Review', false)
        ->assertSee('Pickup selected · No delivery address needed')
        ->assertSee('Proceed to checkout')
        ->call('proceed')
        ->assertHasErrors(['email', 'pickupStart', 'paymentMethod'])
        ->assertSet('modal', null)
        ->call('pickDay', '2026-10-26')
        ->set('slotStart', $start)
        ->call('confirmSlot')
        ->assertSet('pickupStart', $start)
        ->set('email', 'Valentine@Example.com')
        ->set('paymentMethod', 'card')
        ->call('proceed')
        ->assertHasNoErrors()
        ->assertSet('modal', 'code')
        ->set('name', 'Valentine Akin')
        ->set('code', '000000')
        ->call('verifyCode')
        ->assertHasErrors('code');

    $component->set('code', sentCode())
        ->call('verifyCode')
        ->assertHasNoErrors()
        ->assertSet('modal', null)
        ->assertSee('Pay '.money(498));

    $user = User::where('email', 'valentine@example.com')->firstOrFail();
    expect(Auth::id())->toBe($user->id)->and($user->email_verified_at)->not->toBeNull()->and($user->name)->toBe('Valentine Akin');

    $component->call('pay', 'cnon:card-nonce-ok', 'attempt-1');

    $order = Order::firstOrFail();
    $component->assertRedirect(route('orders.show', $order));

    expect($order->status)->toBe(Order::STATUS_PAID)
        ->and($order->user_id)->toBe($user->id)
        ->and($order->contact_email)->toBe('valentine@example.com')
        ->and($order->total_cents)->toBe(498)
        ->and($order->items)->toHaveCount(1)
        ->and(app(Cart::class)->count())->toBe(0);

    $this->get(route('orders.show', $order))->assertOk()->assertSee($order->number);
});

test('guest delivery: an out-of-area address blocks proceeding; a Saint John address adds the fee', function () {
    addToCart('fresh-okra');

    $component = Livewire::test(Checkout::class)
        ->call('choose', 'delivery')
        ->assertSee('Enter your address to calculate the delivery fee')
        ->call('open', 'address')
        ->set('recipient', 'Valentine Akin')
        ->set('recipientPhone', '506 555 0123')
        ->set('street', '24 Berkeley Road')
        ->set('city', 'Toronto')
        ->set('postalCode', 'M5V 2T6')
        ->call('saveAddress')
        ->assertSet('addressStatus', 'out_of_area')
        ->assertSee('Outside our delivery area')
        ->set('email', 'valentine@example.com')
        ->set('paymentMethod', 'card')
        ->call('proceed')
        ->assertHasErrors('address')
        ->assertSet('modal', null);

    $component->call('open', 'address')
        ->set('city', 'Saint John')->set('postalCode', 'e2l4l1')->call('saveAddress')
        ->assertSet('addressStatus', 'ok')
        ->assertSee('Delivering to E2L 4L1')
        ->call('proceed')
        ->assertSet('modal', 'code')
        ->assertSet('name', 'Valentine Akin');

    $component->set('code', sentCode())->call('verifyCode')
        ->assertHasNoErrors()
        ->call('pay', 'cnon:card-nonce-ok', 'attempt-1');

    $order = Order::firstOrFail();
    expect($order->status)->toBe(Order::STATUS_PAID)
        ->and($order->payment_id)->toBe('sq-pay-1')
        ->and($order->contact_email)->toBe('valentine@example.com')
        ->and($order->delivery_address['postal_code'])->toBe('E2L 4L1')
        ->and($order->delivery_fee_cents)->toBe(799)
        ->and($order->total_cents)->toBe(249 + 799);
});

test('item quantities can be changed from the checkout page', function () {
    $variation = addToCart('fresh-okra');

    Livewire::test(Checkout::class)
        ->call('increment', $variation)
        ->call('increment', $variation)
        ->call('decrement', $variation)
        ->assertViewHas('totals', fn ($totals) => $totals->subtotal === 249 * 2);

    expect(app(Cart::class)->quantity($variation))->toBe(2);
});

test('signed-in shoppers use a saved address without verifying again', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $address = Address::create([
        'user_id' => $user->id, 'recipient_name' => 'Amara', 'phone' => '+15065550199',
        'street' => '1 King St', 'city' => 'Saint John', 'postal_code' => 'E2L 1G1',
    ]);

    $this->actingAs($user);
    addToCart('nestle-milo', 7); // over the free delivery threshold

    Livewire::test(Checkout::class)
        ->call('choose', 'delivery')
        ->assertSet('selectedAddressId', $address->id)
        ->assertSet('addressStatus', 'ok')
        ->set('paymentMethod', 'card')
        ->call('pay', 'cnon:card-nonce-ok');

    expect(Order::firstOrFail()->delivery_fee_cents)->toBe(0);
});

test('a full pickup window can’t be booked', function () {
    config(['shop.pickup.window_capacity' => 0]);
    $this->actingAs(User::factory()->create(['email_verified_at' => now()]));
    addToCart('fresh-okra');

    Livewire::test(Checkout::class)
        ->set('slotStart', CarbonImmutable::parse('2026-10-26 11:00', 'America/Moncton')->toIso8601String())
        ->call('confirmSlot')
        ->assertHasErrors('slotStart')
        ->assertSet('pickupStart', null);
});

test('pay at pickup is not offered at checkout', function () {
    addToCart('fresh-okra');

    Livewire::test(Checkout::class)
        ->assertViewHas('paymentMethods', fn ($methods) => ! $methods->contains('code', 'pay_at_pickup'))
        ->assertDontSee('Pay at Pickup')
        ->call('choose', 'delivery')
        ->assertDontSee('Pay at Pickup');
});

test('a guest’s orders appear in their order history when they sign in later with the same email', function () {
    addToCart('fresh-okra');

    $component = Livewire::test(Checkout::class)
        ->set('slotStart', CarbonImmutable::parse('2026-10-26 11:00', 'America/Moncton')->toIso8601String())
        ->call('confirmSlot')
        ->set('email', 'valentine@example.com')
        ->set('paymentMethod', 'card')
        ->call('proceed');
    $component->set('name', 'Valentine Akin')->set('code', sentCode())->call('verifyCode')
        ->call('pay', 'cnon:card-nonce-ok', 'attempt-1');

    $order = Order::firstOrFail();
    Auth::logout();
    Mail::fake();
    $this->travel(1)->day();

    // Later, on another visit: sign in with the same email, typed differently.
    Livewire::test(SignIn::class)
        ->set('channel', 'email')
        ->set('email', 'Valentine@Example.COM')
        ->call('sendCode')
        ->assertHasNoErrors()
        ->set('code', sentCode())
        ->call('verifyCode');

    expect(Auth::id())->toBe($order->user_id)
        ->and(User::where('email', 'valentine@example.com')->count())->toBe(1);

    $this->get(route('account.orders'))->assertOk()->assertSee($order->number);
});

test('orders are only visible to their owner', function () {
    $order = Order::create([
        'number' => 'ISA-TEST01', 'user_id' => User::factory()->create()->id, 'status' => Order::STATUS_PLACED,
        'fulfilment' => 'pickup', 'contact_name' => 'A', 'subtotal_cents' => 100, 'total_cents' => 100,
        'payment_method' => 'pay_at_pickup', 'placed_at' => now(), 'pickup_starts_at' => now(),
    ]);

    $this->get(route('orders.show', $order))->assertNotFound();
    $this->actingAs(User::factory()->create())->get(route('orders.show', $order))->assertNotFound();
});
