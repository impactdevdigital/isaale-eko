<?php

use App\Livewire\Store\Checkout;
use App\Mail\OrderConfirmedMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Cart;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Database\Seeders\StoreHoursSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([DemoCatalogSeeder::class, PaymentMethodSeeder::class, StoreHoursSeeder::class]);
    $this->travelTo(CarbonImmutable::parse('2026-10-26 08:00', 'America/Moncton'));
    Mail::fake();
    config(['shop.currency' => 'CAD']);

    $this->slot = CarbonImmutable::parse('2026-10-26 13:00', 'America/Moncton')->toIso8601String();
    $this->okra = Product::where('slug', 'fresh-okra')->firstOrFail()->defaultVariation->id;
});

test('a card payment charges the server-side total and confirms the order', function () {
    Http::fake(['*/v2/payments' => Http::response(['payment' => ['id' => 'sq-pay-1', 'status' => 'COMPLETED']])]);
    $this->actingAs(User::factory()->create(['email' => 'amara@example.com', 'email_verified_at' => now()]));
    app(Cart::class)->add($this->okra, 2);

    Livewire::test(Checkout::class)
        ->set('pickupStart', $this->slot)
        ->set('paymentMethod', 'card')
        ->call('pay', 'cnon:card-nonce-ok', 'attempt-1')
        ->assertRedirect();

    $order = Order::firstOrFail();

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/v2/payments')
        && $request['amount_money'] === ['amount' => 498, 'currency' => 'CAD']
        && $request['source_id'] === 'cnon:card-nonce-ok'
        && $request['reference_id'] === $order->number
        && $request['idempotency_key'] === $order->number.'-attempt-1');

    expect($order->status)->toBe(Order::STATUS_PAID)
        ->and($order->paid_at)->not->toBeNull()
        ->and(app(Cart::class)->count())->toBe(0);

    Mail::assertSent(OrderConfirmedMail::class, fn ($mail) => $mail->hasTo('amara@example.com'));

    $this->withSession(['order_placed' => $order->number])
        ->get(route('orders.show', $order))
        ->assertSee('Thank You, Your Order Is Confirmed')
        ->assertSee('Preparing for pickup')
        ->assertSee('Paid online');
});

test('a declined card shows the declined screen and keeps the basket and slot', function () {
    Http::fake(['*/v2/payments' => Http::response(['errors' => [['code' => 'CVV_FAILURE', 'category' => 'PAYMENT_METHOD_ERROR']]], 402)]);
    $this->actingAs(User::factory()->create(['email_verified_at' => now()]));
    app(Cart::class)->add($this->okra);

    Livewire::test(Checkout::class)
        ->set('pickupStart', $this->slot)
        ->set('paymentMethod', 'card')
        ->call('pay', 'cnon:card-nonce-declined')
        ->assertSee('Payment Declined')
        ->assertSee('The security code didn’t match')
        ->assertSet('pickupStart', $this->slot)
        ->call('changePaymentMethod')
        ->assertSet('declined', null)
        ->assertSet('paymentMethod', null);

    expect(Order::firstOrFail()->status)->toBe(Order::STATUS_PAYMENT_FAILED)
        ->and(app(Cart::class)->count())->toBe(1);
    Mail::assertNotSent(OrderConfirmedMail::class);
});

test('online payment methods need a Square token', function () {
    Http::fake();
    $this->actingAs(User::factory()->create(['email_verified_at' => now()]));
    app(Cart::class)->add($this->okra);

    Livewire::test(Checkout::class)
        ->set('pickupStart', $this->slot)
        ->set('paymentMethod', 'card')
        ->call('pay');

    expect(Order::count())->toBe(0);
    Http::assertNothingSent();
});

test('the Square webhook settles an order whose payment completed later', function () {
    config(['services.square.webhook_key' => 'test-key', 'services.square.webhook_url' => 'https://shop.test/api/webhooks/square']);

    $order = Order::create([
        'number' => 'ISA-HOOK01', 'user_id' => User::factory()->create()->id,
        'status' => Order::STATUS_AWAITING_PAYMENT, 'fulfilment' => 'pickup', 'contact_name' => 'A',
        'contact_email' => 'a@example.com', 'subtotal_cents' => 498, 'total_cents' => 498,
        'payment_method' => 'card', 'placed_at' => now(), 'pickup_starts_at' => now(),
    ]);

    $body = json_encode(['type' => 'payment.updated', 'data' => ['object' => ['payment' => [
        'id' => 'sq-pay-late', 'status' => 'COMPLETED', 'reference_id' => 'ISA-HOOK01',
        'amount_money' => ['amount' => 498, 'currency' => 'CAD'],
    ]]]]);
    $signature = base64_encode(hash_hmac('sha256', 'https://shop.test/api/webhooks/square'.$body, 'test-key', true));
    $post = fn (string $sig) => $this->call('POST', '/api/webhooks/square', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_SQUARE_HMACSHA256_SIGNATURE' => $sig,
    ], $body);

    $post('forged')->assertForbidden();
    $post($signature)->assertNoContent();

    expect($order->fresh()->status)->toBe(Order::STATUS_PAID)
        ->and($order->fresh()->payment_id)->toBe('sq-pay-late');
    Mail::assertSent(OrderConfirmedMail::class);
});
