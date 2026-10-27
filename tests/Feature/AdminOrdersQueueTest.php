<?php

namespace Tests\Feature;

use App\Livewire\Admin\OrdersQueue;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Livewire\Livewire;

function createTestOrder(array $attributes = []): Order
{
    return Order::create(array_merge([
        'number' => 'ISA-'.strtoupper(str()->random(6)),
        'status' => Order::STATUS_PAID,
        'fulfilment' => 'pickup',
        'contact_name' => 'Chioma Test',
        'contact_email' => 'chioma@example.com',
        'contact_phone' => '+15065550100',
        'pickup_starts_at' => now()->addHours(2),
        'pickup_ends_at' => now()->addHours(4),
        'subtotal_cents' => 3000,
        'savings_cents' => 0,
        'discount_cents' => 0,
        'delivery_fee_cents' => 0,
        'total_cents' => 3000,
        'payment_method' => 'card',
        'placed_at' => now(),
        'fulfilment_status' => null,
    ], $attributes));
}

test('admin orders queue renders and shows active orders', function () {
    $order = createTestOrder(['contact_name' => 'Amara Kalu']);

    Livewire::test(OrdersQueue::class)
        ->assertStatus(200)
        ->assertSee('Fulfillment Queue')
        ->assertSee('#'.$order->number)
        ->assertSee('Amara Kalu');
});

test('staff can search orders by number or customer name', function () {
    $order1 = createTestOrder(['contact_name' => 'Fatima Yusuf']);
    $order2 = createTestOrder(['contact_name' => 'David Okafor']);

    Livewire::test(OrdersQueue::class)
        ->set('search', 'Fatima')
        ->assertSee('Fatima Yusuf')
        ->assertDontSee('David Okafor');
});

test('staff can filter by fulfilment type', function () {
    $pickupOrder = createTestOrder(['fulfilment' => 'pickup', 'contact_name' => 'Pickup Customer']);
    $deliveryOrder = createTestOrder(['fulfilment' => 'delivery', 'contact_name' => 'Delivery Customer']);

    Livewire::test(OrdersQueue::class)
        ->set('fulfilmentFilter', 'delivery')
        ->assertSee('Delivery Customer')
        ->assertDontSee('Pickup Customer');
});

test('staff can advance order stages from new to preparing, ready and completed', function () {
    $order = createTestOrder([
        'status' => Order::STATUS_PLACED,
        'payment_method' => 'pay_at_pickup',
        'fulfilment_status' => null,
    ]);

    // 1. Start preparing
    Livewire::test(OrdersQueue::class)
        ->call('startPreparing', $order->id)
        ->assertHasNoErrors();

    expect($order->fresh()->fulfilment_status)->toBe(Order::FULFILMENT_PREPARING);

    // 2. Mark ready
    Livewire::test(OrdersQueue::class)
        ->call('markAsReady', $order->id)
        ->assertHasNoErrors();

    expect($order->fresh()->fulfilment_status)->toBe(Order::FULFILMENT_READY);

    // 3. Complete order (and confirms payment for pay_at_pickup)
    Livewire::test(OrdersQueue::class)
        ->call('markAsCompleted', $order->id)
        ->assertHasNoErrors();

    $freshOrder = $order->fresh();
    expect($freshOrder->fulfilment_status)->toBe(Order::FULFILMENT_COMPLETED);
    expect($freshOrder->status)->toBe(Order::STATUS_PAID);
    expect($freshOrder->paid_at)->not->toBeNull();
});

test('staff can open and close the order detail drawer', function () {
    $order = createTestOrder();

    Livewire::test(OrdersQueue::class)
        ->call('viewOrder', $order->id)
        ->assertSet('selectedOrderId', $order->id)
        ->assertSee('Ordered Items')
        ->call('closeDrawer')
        ->assertSet('selectedOrderId', null);
});

test('staff can cancel an active order', function () {
    $order = createTestOrder(['status' => Order::STATUS_PLACED]);

    Livewire::test(OrdersQueue::class)
        ->call('openCancelModal', $order->id)
        ->assertSet('showCancelModal', true)
        ->call('confirmCancel')
        ->assertSet('showCancelModal', false);

    $freshOrder = $order->fresh();
    expect($freshOrder->status)->toBe(Order::STATUS_CANCELLED);
    expect($freshOrder->cancelled_at)->not->toBeNull();
});

test('staff can mark ready pickup order as uncollected and subsequently complete or reschedule it', function () {
    $order = createTestOrder([
        'status' => Order::STATUS_PAID,
        'fulfilment' => 'pickup',
        'fulfilment_status' => Order::FULFILMENT_READY,
    ]);

    // 1. Staff marks order as Uncollected
    Livewire::test(OrdersQueue::class)
        ->call('markAsUncollected', $order->id)
        ->assertHasNoErrors()
        ->assertSee('marked as Uncollected');

    $uncollectedOrder = $order->fresh();
    expect($uncollectedOrder->status)->toBe(Order::STATUS_UNCOLLECTED)
        ->and($uncollectedOrder->uncollected_at)->not->toBeNull();

    // 2. Customer shows up late; staff completes the order
    Livewire::test(OrdersQueue::class)
        ->call('markAsCompleted', $order->id)
        ->assertHasNoErrors();

    $completedOrder = $order->fresh();
    expect($completedOrder->fulfilment_status)->toBe(Order::FULFILMENT_COMPLETED)
        ->and($completedOrder->status)->toBe(Order::STATUS_PAID);
});
