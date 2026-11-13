<?php

use App\Livewire\Store\Account\OrderHistory;
use App\Livewire\Store\SignIn;
use App\Mail\VerificationCodeMail;
use App\Models\Order;
use App\Models\User;
use App\Services\Messaging\SmsSender;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
});

function accountOrder(User $user, array $attributes = []): Order
{
    $order = Order::create([
        'number' => 'ISA-'.strtoupper(str()->random(6)),
        'user_id' => $user->id,
        'status' => Order::STATUS_PAID,
        'fulfilment' => 'pickup',
        'contact_name' => $user->name,
        'contact_email' => $user->email,
        'pickup_starts_at' => now()->addDay(),
        'pickup_ends_at' => now()->addDay()->addHours(2),
        'subtotal_cents' => 848,
        'total_cents' => 848,
        'payment_method' => 'card',
        'placed_at' => now(),
        ...$attributes,
    ]);

    $order->items()->create([
        'name' => 'Pineapple', 'unit_price_cents' => 499, 'quantity' => 1, 'line_total_cents' => 499,
    ]);

    return $order;
}

function mailedCode(): string
{
    $code = null;
    Mail::assertSent(VerificationCodeMail::class, function (VerificationCodeMail $mail) use (&$code) {
        $code = $mail->code;

        return true;
    });

    return $code;
}

test('guests are sent to the sign-in drawer and come back to the page they asked for', function () {
    $this->get('/account/orders')->assertRedirect('/sign-in');
    $this->get('/sign-in')->assertRedirect('/?sign-in=1');

    $user = User::create(['name' => 'Valentine Akin', 'email' => 'val@example.com', 'email_verified_at' => now()]);

    Livewire::withQueryParams(['sign-in' => 1])->test(SignIn::class)
        ->assertSet('open', true)
        ->set('email', 'VAL@example.com')
        ->call('sendCode')
        ->assertSet('step', 'code')
        ->set('code', mailedCode())
        ->call('verifyCode')
        ->assertRedirect(url('/account/orders'));

    expect(Auth::id())->toBe($user->id);
});

test('a first-time shopper verifies a phone and gives their name', function () {
    $sms = new class implements SmsSender
    {
        public array $sent = [];

        public function send(string $to, string $message): void
        {
            $this->sent[] = [$to, $message];
        }
    };
    app()->instance(SmsSender::class, $sms);

    $component = Livewire::test(SignIn::class)
        ->call('useChannel', 'phone')
        ->set('phone', '(506) 555-0101')
        ->call('sendCode')
        ->assertSet('codeDestination', '+15065550101');

    preg_match('/\d{6}/', $sms->sent[0][1], $m);

    $component->set('code', '000000')->call('verifyCode')->assertHasErrors('code');

    $component->set('code', $m[0])->call('verifyCode')
        ->assertSet('step', 'details')
        ->call('completeAccount')
        ->assertHasErrors('name')
        ->set('name', 'Ada Obi')
        ->call('completeAccount')
        ->assertRedirect();

    $user = User::where('phone', '+15065550101')->first();
    expect($user->name)->toBe('Ada Obi')
        ->and($user->phone_verified_at)->not->toBeNull()
        ->and(Auth::id())->toBe($user->id);
});

test('the details step cannot be skipped without a verified code', function () {
    Livewire::test(SignIn::class)
        ->set('name', 'Mallory')
        ->call('completeAccount');

    expect(User::count())->toBe(0);
});

test('the overview shows balances and recent orders', function () {
    $user = User::create(['name' => 'Valentine Akin', 'email' => 'val@example.com']);
    $order = accountOrder($user);
    $user->pointTransactions()->create(['type' => 'earned', 'points' => 76, 'description' => 'Purchase']);

    $this->actingAs($user)->get('/account')
        ->assertOk()
        ->assertSee('Hi, Valentine.')
        ->assertSee('$76.00')
        ->assertSee('/ $100.00')
        ->assertSee($order->number);
});

test('order history filters by tab and search, and hides declined attempts', function () {
    $user = User::create(['name' => 'Valentine Akin', 'email' => 'val@example.com']);
    $paid = accountOrder($user);
    $atCounter = accountOrder($user, ['status' => Order::STATUS_PLACED, 'payment_method' => 'pay_at_pickup']);
    $ready = accountOrder($user, ['fulfilment_status' => Order::FULFILMENT_READY]);
    $declined = accountOrder($user, ['status' => Order::STATUS_PAYMENT_FAILED]);
    $someoneElse = accountOrder(User::create(['name' => 'Other', 'email' => 'other@example.com']));

    $this->actingAs($user);

    Livewire::test(OrderHistory::class)
        ->assertSee($paid->number)->assertSee($atCounter->number)->assertSee($ready->number)
        ->assertDontSee($declined->number)->assertDontSee($someoneElse->number)
        ->set('tab', 'unpaid')
        ->assertSee($atCounter->number)->assertDontSee($paid->number)
        ->set('tab', 'processing')
        ->assertSee($paid->number)->assertDontSee($ready->number)
        ->set('tab', 'shipped')
        ->assertSee($ready->number)->assertDontSee($paid->number)
        ->set('tab', 'all')
        ->set('search', substr($ready->number, 4))
        ->assertSee($ready->number)->assertDontSee($paid->number);
});

test('pay-at-pickup orders can be cancelled until the store starts on them', function () {
    $user = User::create(['name' => 'Valentine Akin', 'email' => 'val@example.com']);
    $open = accountOrder($user, ['status' => Order::STATUS_PLACED, 'payment_method' => 'pay_at_pickup']);
    $preparing = accountOrder($user, ['status' => Order::STATUS_PLACED, 'payment_method' => 'pay_at_pickup', 'fulfilment_status' => Order::FULFILMENT_PREPARING]);
    $paid = accountOrder($user);

    $this->actingAs($user);

    Livewire::test(OrderHistory::class)
        ->call('cancel', $open->id)
        ->call('cancel', $preparing->id)
        ->call('cancel', $paid->id);

    expect($open->fresh()->status)->toBe(Order::STATUS_CANCELLED)
        ->and($preparing->fresh()->status)->toBe(Order::STATUS_PLACED)
        ->and($paid->fresh()->status)->toBe(Order::STATUS_PAID);
});

test('order details show the tracker and are private to the owner', function () {
    $user = User::create(['name' => 'Valentine Akin', 'email' => 'val@example.com']);
    $order = accountOrder($user, ['fulfilment_status' => Order::FULFILMENT_PREPARING]);

    $this->actingAs($user)->get('/account/orders/'.$order->number)
        ->assertOk()
        ->assertSee('Order Details')
        ->assertSee('Preparing for pickup')
        ->assertSee('We’re preparing your order now.')
        ->assertSee('Pineapple');

    $this->actingAs(User::create(['name' => 'Other', 'email' => 'other@example.com']))
        ->get('/account/orders/'.$order->number)
        ->assertNotFound();
});

test('signing out ends the session', function () {
    $user = User::create(['name' => 'Valentine Akin', 'email' => 'val@example.com']);

    $this->actingAs($user)
        ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class)
        ->post('/sign-out')
        ->assertRedirect('/');

    $this->assertGuest();
});
