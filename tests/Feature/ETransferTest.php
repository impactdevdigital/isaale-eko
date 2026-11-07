<?php

use App\Livewire\Admin\OrdersQueue;
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
    Http::fake();
    config(['payments.etransfer.email' => 'pay@shop.test']);
});

function placeETransferPickupOrder(): Order
{
    $user = User::factory()->create(['email' => 'ada@example.com', 'email_verified_at' => now()]);
    test()->actingAs($user);
    app(Cart::class)->add(Product::where('slug', 'fresh-okra')->firstOrFail()->defaultVariation->id, 2);

    Livewire::test(Checkout::class)
        ->set('slotStart', CarbonImmutable::parse('2026-10-26 11:00', 'America/Moncton')->toIso8601String())
        ->call('confirmSlot')
        ->set('paymentMethod', 'etransfer')
        ->assertSee('How it works')
        ->assertSee('pay@shop.test')
        ->assertSee('Place order')
        ->call('placeETransferOrder')
        ->assertRedirect();

    return Order::firstOrFail();
}

test('e-Transfer is offered at checkout for pickup and delivery', function () {
    app(Cart::class)->add(Product::where('slug', 'fresh-okra')->firstOrFail()->defaultVariation->id);

    Livewire::test(Checkout::class)
        ->assertSee('Interac e-Transfer')
        ->call('choose', 'delivery')
        ->assertSee('Interac e-Transfer');
});

test('placing an e-Transfer order reserves it, clears the basket and emails the details without calling Square', function () {
    $order = placeETransferPickupOrder();

    expect($order->payment_method)->toBe('etransfer')
        ->and($order->status)->toBe(Order::STATUS_AWAITING_PAYMENT)
        ->and($order->isAwaitingETransfer())->toBeTrue()
        ->and(app(Cart::class)->count())->toBe(0);

    Http::assertNothingSent();
    Mail::assertSent(OrderConfirmedMail::class, fn ($mail) => str_contains($mail->envelope()->subject, 'send your Interac e-Transfer'));

    $this->withSession(['order_placed' => $order->number])
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertSee('Send Your e-Transfer')
        ->assertSee('pay@shop.test')
        ->assertSee($order->number)
        ->assertSee(money($order->total_cents));
});

test('the card pay action ignores e-Transfer so no charge is attempted', function () {
    $this->actingAs(User::factory()->create(['email_verified_at' => now()]));
    app(Cart::class)->add(Product::where('slug', 'fresh-okra')->firstOrFail()->defaultVariation->id);

    Livewire::test(Checkout::class)
        ->set('slotStart', CarbonImmutable::parse('2026-10-26 11:00', 'America/Moncton')->toIso8601String())
        ->call('confirmSlot')
        ->set('paymentMethod', 'etransfer')
        ->call('pay', 'cnon:card-nonce-ok');

    expect(Order::count())->toBe(0);
    Http::assertNothingSent();
});

test('staff confirm a received e-Transfer: the order is paid and the customer gets the confirmation', function () {
    $order = placeETransferPickupOrder();
    Mail::fake();

    Livewire::test(OrdersQueue::class)
        ->set('statusFilter', 'etransfer')
        ->assertSee('#'.$order->number)
        ->assertSee('Awaiting e-Transfer')
        ->call('startPreparing', $order->id)
        ->call('confirmETransfer', $order->id);

    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_PAID)
        ->and($order->fulfilment_status)->toBeNull()
        ->and($order->paid_at)->not->toBeNull()
        ->and($order->payment_id)->toBeNull(); // refunds must never go to Square for e-Transfer orders

    Mail::assertSent(OrderConfirmedMail::class, fn ($mail) => str_contains($mail->envelope()->subject, 'is confirmed'));
});

test('staff can cancel an e-Transfer order that was never paid, and customers can cancel it themselves', function () {
    $order = placeETransferPickupOrder();

    expect($order->canBeCancelled())->toBeTrue();

    Livewire::test(OrdersQueue::class)->call('cancelUnpaidETransfer', $order->id);

    expect($order->refresh()->status)->toBe(Order::STATUS_CANCELLED)
        ->and($order->canBeCancelled())->toBeFalse();
});
