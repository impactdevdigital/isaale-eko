<?php

namespace Tests\Feature;

use App\Actions\Orders\CancelUncollectedOrder;
use App\Actions\Orders\RescheduleOrder;
use App\Console\Commands\HandleUncollectedOrders;
use App\Livewire\Store\RescheduleOrder as RescheduleOrderComponent;
use App\Models\Order;
use App\Models\StoreHour;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();

    // Ensure store hours exist for everyday 09:00 - 18:00
    for ($i = 0; $i < 7; $i++) {
        StoreHour::updateOrCreate(['weekday' => $i], [
            'opens_at' => '09:00:00',
            'closes_at' => '18:00:00',
            'is_closed' => false,
        ]);
    }
});

function createUncollectedTestOrder(array $attributes = []): Order
{
    return Order::create(array_merge([
        'number' => 'ISA-'.strtoupper(str()->random(6)),
        'status' => Order::STATUS_PAID,
        'fulfilment' => 'pickup',
        'contact_name' => 'Bolanle Balogun',
        'contact_email' => 'bolanle@example.com',
        'contact_phone' => '+15065550199',
        'pickup_starts_at' => now()->subDays(4), // overdue past 3-day grace
        'pickup_ends_at' => now()->subDays(4)->addMinutes(15),
        'subtotal_cents' => 4500,
        'savings_cents' => 0,
        'discount_cents' => 0,
        'delivery_fee_cents' => 0,
        'total_cents' => 4500,
        'payment_method' => 'card',
        'placed_at' => now()->subDays(5),
        'fulfilment_status' => Order::FULFILMENT_READY,
    ], $attributes));
}

test('handle uncollected orders command marks overdue pickup orders as uncollected', function () {
    $order = createUncollectedTestOrder();

    $this->artisan('orders:handle-uncollected')
        ->assertSuccessful();

    $fresh = $order->fresh();
    expect($fresh->status)->toBe(Order::STATUS_UNCOLLECTED);
    expect($fresh->uncollected_at)->not->toBeNull();
});

test('staff can reschedule an uncollected order to a valid future slot', function () {
    $order = createUncollectedTestOrder(['status' => Order::STATUS_UNCOLLECTED]);

    $futureSlot = now()->addDays(2)->setTimezone(config('shop.timezone', 'America/Moncton'))->setHour(14)->setMinute(0)->setSecond(0);

    $action = app(RescheduleOrder::class);
    $rescheduled = $action->handle($order, $futureSlot, 'staff');

    expect($rescheduled->status)->toBe(Order::STATUS_PAID);
    expect($rescheduled->uncollected_at)->toBeNull();
    expect($rescheduled->pickup_starts_at->timestamp)->toBe($futureSlot->utc()->timestamp);
});

test('customer can view reschedule portal with signed url and pick a new collection slot', function () {
    $order = createUncollectedTestOrder(['status' => Order::STATUS_UNCOLLECTED]);

    $url = URL::temporarySignedRoute(
        'orders.reschedule',
        now()->addDays(2),
        ['number' => $order->number]
    );

    $response = $this->get($url);
    $response->assertOk();
    $response->assertSee('Reschedule Order #' . $order->number);

    Livewire::test(RescheduleOrderComponent::class, ['number' => $order->number])
        ->assertSet('orderNumber', $order->number)
        ->assertSee('Confirm New Collection Time');
});
