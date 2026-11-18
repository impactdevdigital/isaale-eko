<?php

use App\Livewire\Store\Checkout;
use App\Mail\OrderConfirmedMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Checkout\PaymentReconciler;
use App\Support\Cart;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Database\Seeders\StoreHoursSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([DemoCatalogSeeder::class, PaymentMethodSeeder::class, StoreHoursSeeder::class]);
    $this->travelTo(CarbonImmutable::parse('2026-10-26 08:00', 'America/Moncton'));
    Mail::fake();
    config(['shop.currency' => 'CAD', 'services.square.token' => 'test-token', 'services.square.location_id' => 'LOC', 'services.square.sync_orders' => false]);

    $this->user = User::factory()->create(['email' => 'amara@example.com', 'email_verified_at' => now()]);
    $this->okra = Product::where('slug', 'fresh-okra')->firstOrFail()->defaultVariation->id;
    $this->slot = CarbonImmutable::parse('2026-10-26 13:00', 'America/Moncton')->toIso8601String();
});

function openCardOrderFor(User $user, int $minutesAgo, int $total = 498): Order
{
    return Order::create([
        'number' => 'ISA-OPEN'.$minutesAgo, 'user_id' => $user->id, 'status' => Order::STATUS_AWAITING_PAYMENT,
        'fulfilment' => 'pickup', 'contact_name' => 'Amara', 'contact_email' => $user->email, 'payment_method' => 'card',
        'payment_error' => 'TIMEOUT', 'subtotal_cents' => $total, 'total_cents' => $total, 'placed_at' => now()->subMinutes($minutesAgo),
    ]);
}

/** Square's payment list returns $payments; a new charge returns COMPLETED. */
function fakeSquarePayments(array $payments): void
{
    Http::fake(function (Request $request) use ($payments) {
        if ($request->method() === 'GET' && str_contains($request->url(), '/v2/payments')) {
            return Http::response(['payments' => $payments]);
        }

        return Http::response(['payment' => ['id' => 'sq-new', 'status' => 'COMPLETED']]);
    });
}

function squarePaymentFor(Order $order, string $status, int $amount = 498): array
{
    return ['id' => 'sq-'.strtolower($status), 'reference_id' => $order->number, 'status' => $status, 'amount_money' => ['amount' => $amount, 'currency' => 'CAD']];
}

test('a timed-out order that Square did charge is marked paid and the receipt goes out', function () {
    $order = openCardOrderFor($this->user, 3);
    fakeSquarePayments([squarePaymentFor($order, 'COMPLETED')]);

    $this->artisan('payments:reconcile')->assertSuccessful();

    expect($order->fresh())->status->toBe(Order::STATUS_PAID)->payment_id->toBe('sq-completed');
    Mail::assertSent(OrderConfirmedMail::class);
});

test('with no payment in Square after ten minutes the order is closed as failed', function () {
    $order = openCardOrderFor($this->user, 15);
    fakeSquarePayments([]);

    $this->artisan('payments:reconcile')->assertSuccessful();

    expect($order->fresh())->status->toBe(Order::STATUS_PAYMENT_FAILED)->payment_error->toBe('TIMEOUT_NO_PAYMENT');
    Mail::assertNotSent(OrderConfirmedMail::class);
});

test('a recent order with no answer from Square yet is left open', function () {
    $order = openCardOrderFor($this->user, 3);
    fakeSquarePayments([]);

    expect(app(PaymentReconciler::class)->resolve($order))->toBe(PaymentReconciler::PENDING)
        ->and($order->fresh()->status)->toBe(Order::STATUS_AWAITING_PAYMENT);
});

test('a payment for a different amount does not mark the order paid', function () {
    $order = openCardOrderFor($this->user, 3);
    fakeSquarePayments([squarePaymentFor($order, 'COMPLETED', 1)]);

    expect(app(PaymentReconciler::class)->resolve($order))->toBe(PaymentReconciler::PENDING);
});

test('checkout will not charge again while an earlier payment is unresolved', function () {
    $open = openCardOrderFor($this->user, 2);
    fakeSquarePayments([]);
    $this->actingAs($this->user);
    app(Cart::class)->add($this->okra, 2);

    Livewire::test(Checkout::class)
        ->set('phone', '506 555 0123')
        ->set('pickupStart', $this->slot)
        ->set('paymentMethod', 'card')
        ->call('pay', 'cnon:card-nonce-ok', 'attempt-2')
        ->assertNoRedirect()
        ->assertSee('still confirming your earlier payment for order '.$open->number);

    Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    expect(Order::count())->toBe(1);
});

test('if the earlier payment did go through, checkout shows that order instead of charging again', function () {
    $open = openCardOrderFor($this->user, 2);
    fakeSquarePayments([squarePaymentFor($open, 'COMPLETED')]);
    $this->actingAs($this->user);
    app(Cart::class)->add($this->okra, 2);

    Livewire::test(Checkout::class)
        ->set('phone', '506 555 0123')
        ->set('pickupStart', $this->slot)
        ->set('paymentMethod', 'card')
        ->call('pay', 'cnon:card-nonce-ok', 'attempt-2')
        ->assertRedirect(route('orders.show', $open));

    Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    expect($open->fresh()->status)->toBe(Order::STATUS_PAID)
        ->and(Order::count())->toBe(1);
    Mail::assertSent(OrderConfirmedMail::class, 1);
});

test('once the earlier payment is known to have failed, the shopper can pay normally', function () {
    $open = openCardOrderFor($this->user, 2);
    fakeSquarePayments([squarePaymentFor($open, 'FAILED')]);
    $this->actingAs($this->user);
    app(Cart::class)->add($this->okra, 2);

    Livewire::test(Checkout::class)
        ->set('phone', '506 555 0123')
        ->set('pickupStart', $this->slot)
        ->set('paymentMethod', 'card')
        ->call('pay', 'cnon:card-nonce-ok', 'attempt-2')
        ->assertRedirect();

    expect($open->fresh()->status)->toBe(Order::STATUS_PAYMENT_FAILED)
        ->and(Order::latest('id')->first()->status)->toBe(Order::STATUS_PAID);
});

test('a second pay request while one is running is turned away', function () {
    $this->actingAs($this->user);
    app(Cart::class)->add($this->okra, 2);
    fakeSquarePayments([]);
    $lock = Cache::lock('checkout-pay:'.$this->user->id, 60);
    $lock->get();

    Livewire::test(Checkout::class)
        ->set('phone', '506 555 0123')
        ->set('pickupStart', $this->slot)
        ->set('paymentMethod', 'card')
        ->call('pay', 'cnon:card-nonce-ok', 'attempt-3')
        ->assertSee('already being processed');

    $lock->release();
    expect(Order::count())->toBe(0);
});
