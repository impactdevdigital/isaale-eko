<?php

use App\Livewire\Admin\OrdersQueue;
use App\Livewire\Store\Checkout;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Cart;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Database\Seeders\StoreHoursSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([DemoCatalogSeeder::class, PaymentMethodSeeder::class, StoreHoursSeeder::class]);
    $this->travelTo(CarbonImmutable::parse('2026-10-26 08:00', 'America/Moncton'));
    Mail::fake();
    config([
        'shop.currency' => 'CAD',
        'services.square.sync_orders' => true,
        'services.square.token' => 'test-token',
        'services.square.location_id' => 'LOC1',
    ]);

    $this->okra = Product::where('slug', 'fresh-okra')->firstOrFail()->defaultVariation;
    $this->okra->update(['square_id' => 'SQ-OKRA']);
    $this->slot = CarbonImmutable::parse('2026-10-26 13:00', 'America/Moncton')->toIso8601String();
});

/** Square that accepts everything; $orderTotal lets a test make Square disagree about the total. */
function fakeSquareOrders(?int $orderTotal = null, string $fulfilmentState = 'PROPOSED'): void
{
    Http::fake([
        '*/v2/customers/search' => Http::response([]),
        '*/v2/customers' => Http::response(['customer' => ['id' => 'CUST-1']]),
        '*/v2/orders' => fn (Request $r) => Http::response(['order' => [
            'id' => 'SQ-ORDER-1', 'state' => 'OPEN', 'version' => 1,
            'total_money' => ['amount' => $orderTotal ?? Order::firstOrFail()->total_cents, 'currency' => 'CAD'],
        ]]),
        '*/v2/orders/SQ-ORDER-1' => fn (Request $r) => $r->method() === 'GET'
            ? Http::response(['order' => ['id' => 'SQ-ORDER-1', 'state' => 'OPEN', 'version' => 3, 'fulfillments' => [['uid' => 'website-fulfilment', 'state' => $fulfilmentState]]]])
            : Http::response(['order' => ['id' => 'SQ-ORDER-1']]),
        '*/v2/payments' => Http::response(['payment' => ['id' => 'sq-pay-1', 'status' => 'COMPLETED']]),
    ]);
}

function payByCardSynced(): Order
{
    test()->actingAs(User::factory()->create(['name' => 'Amara Okafor', 'email' => 'amara@example.com', 'email_verified_at' => now()]));
    app(Cart::class)->add(test()->okra->id, 2);

    Livewire::test(Checkout::class)->set('phone', '506 555 0123')
        ->set('pickupStart', test()->slot)
        ->set('paymentMethod', 'card')
        ->call('pay', 'cnon:card-nonce-ok', 'attempt-1')
        ->assertRedirect();

    return Order::firstOrFail();
}

test('a card order is created in Square with its items, customer and pickup, then paid against', function () {
    fakeSquareOrders();
    $order = payByCardSynced();

    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/v2/customers')
        && $r['email_address'] === 'amara@example.com' && $r['given_name'] === 'Amara' && $r['family_name'] === 'Okafor');

    Http::assertSent(function (Request $r) use ($order) {
        if (! str_ends_with($r->url(), '/v2/orders')) {
            return false;
        }
        $o = $r['order'];
        $pickup = $o['fulfillments'][0];

        return $o['reference_id'] === $order->number
            && $o['customer_id'] === 'CUST-1'
            && $o['location_id'] === 'LOC1'
            && $o['line_items'][0]['catalog_object_id'] === 'SQ-OKRA'
            && $o['line_items'][0]['quantity'] === '2'
            && $o['line_items'][0]['base_price_money'] === ['amount' => 249, 'currency' => 'CAD']
            && $pickup['type'] === 'PICKUP'
            && $pickup['pickup_details']['recipient']['email_address'] === 'amara@example.com'
            && $pickup['pickup_details']['pickup_at'] === $order->pickup_starts_at->toIso8601ZuluString();
    });

    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/v2/payments')
        && $r['order_id'] === 'SQ-ORDER-1'
        && $r['amount_money'] === ['amount' => 498, 'currency' => 'CAD']
        && $r['reference_id'] === $order->number);

    expect($order->status)->toBe(Order::STATUS_PAID)
        ->and($order->square_order_id)->toBe('SQ-ORDER-1')
        ->and($order->square_synced_at)->not->toBeNull()
        ->and($order->user->square_customer_id)->toBe('CUST-1');
});

test('if Square disagrees about the total, the card is charged without a Square order', function () {
    fakeSquareOrders(orderTotal: 1);
    $order = payByCardSynced();

    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/v2/payments') && ! isset($r['order_id']));

    expect($order->status)->toBe(Order::STATUS_PAID)
        ->and($order->square_order_id)->toBeNull()
        ->and($order->square_synced_at)->toBeNull()
        ->and($order->square_sync_error)->toContain("doesn't match");
});

test('if Square refuses the order, the shopper can still pay', function () {
    Http::fake([
        '*/v2/customers/search' => Http::response([]),
        '*/v2/customers' => Http::response(['customer' => ['id' => 'CUST-1']]),
        '*/v2/orders' => Http::response(['errors' => [['code' => 'INVALID_VALUE', 'detail' => 'bad']]], 400),
        '*/v2/payments' => Http::response(['payment' => ['id' => 'sq-pay-1', 'status' => 'COMPLETED']]),
    ]);

    $order = payByCardSynced();

    expect($order->status)->toBe(Order::STATUS_PAID)
        ->and($order->square_sync_error)->toContain('INVALID_VALUE');
});

test('moving the order along in the staff queue moves the Square fulfilment and completes the order', function () {
    fakeSquareOrders();
    $order = payByCardSynced();

    Livewire::test(OrdersQueue::class)->call('startPreparing', $order->id);
    Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && $r['order']['fulfillments'][0]['state'] === 'RESERVED' && $r['order']['version'] === 3);

    Livewire::test(OrdersQueue::class)->call('markAsCompleted', $order->id);
    Http::assertSent(fn (Request $r) => $r->method() === 'PUT'
        && $r['order']['fulfillments'][0]['state'] === 'COMPLETED'
        && $r['order']['state'] === 'COMPLETED');
});

test('a confirmed e-Transfer goes to Square as an order paid by bank transfer', function () {
    fakeSquareOrders();
    $this->actingAs(User::factory()->create(['email' => 'ada@example.com', 'email_verified_at' => now()]));
    app(Cart::class)->add($this->okra->id, 2);

    Livewire::test(Checkout::class)->set('phone', '506 555 0123')
        ->set('slotStart', CarbonImmutable::parse('2026-10-26 11:00', 'America/Moncton')->toIso8601String())
        ->call('confirmSlot')
        ->set('paymentMethod', 'etransfer')
        ->call('placeETransferOrder');

    // Nothing reaches Square until the money is confirmed.
    Http::assertNothingSent();

    $order = Order::firstOrFail();
    Livewire::test(OrdersQueue::class)->call('confirmETransfer', $order->id);

    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/v2/payments')
        && $r['source_id'] === 'EXTERNAL'
        && $r['external_details'] === ['type' => 'BANK_TRANSFER', 'source' => 'Interac e-Transfer']
        && $r['order_id'] === 'SQ-ORDER-1');

    expect($order->refresh()->square_synced_at)->not->toBeNull()
        ->and($order->payment_id)->toBeNull();
});
