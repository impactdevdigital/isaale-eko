<?php

use App\Actions\Orders\IssueRefund;
use App\Livewire\Admin\LoyaltyTracker;
use App\Livewire\Store\Account\Rewards;
use App\Livewire\Store\CartPage;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use App\Services\Checkout\CheckoutProblem;
use App\Services\Checkout\PlaceOrder;
use App\Services\Checkout\TakePayment;
use App\Services\Loyalty\SquareLoyalty;
use App\Support\Cart;
use Carbon\CarbonImmutable;
use Database\Seeders\PaymentMethodSeeder;
use Database\Seeders\StoreHoursSeeder;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([PaymentMethodSeeder::class, StoreHoursSeeder::class]);
    $this->travelTo(CarbonImmutable::parse('2026-10-26 08:00', 'America/Moncton'));

    // Run the after-response loyalty work straight away, as it would at the end of a request.
    app(Dispatcher::class)->withoutDispatchingAfterResponses();

    config([
        'loyalty.enabled' => true,
        'services.square.env' => 'sandbox',
        'services.square.token' => 'test-token',
        'services.square.location_id' => 'LOC1',
    ]);

    $product = Product::create(['name' => 'Golden Sella Rice', 'slug' => 'golden-sella-rice', 'item_class' => 'grocery']);
    $this->rice = ProductVariation::create(['product_id' => $product->id, 'name' => '5kg', 'price_cents' => 2000, 'stock_quantity' => 50]);
});

/**
 * A pretend Square Loyalty: one programme (1 point per $1, 100 points =
 * $3 off) and accounts keyed by phone number.
 *
 * @param  array<string, int>  $balances  phone => points already on the account
 */
function fakeLoyalty(array $balances = [], bool $program = true, bool $down = false): object
{
    // Flip ->program and ->down during a test: Http::fake can't be swapped once set.
    $square = (object) ['accounts' => [], 'rewards' => [], 'program' => $program, 'down' => $down];

    foreach ($balances as $phone => $points) {
        $square->accounts['acct-'.count($square->accounts)] = ['phone' => $phone, 'balance' => $points];
    }

    $account = fn (string $id) => ['id' => $id, 'balance' => $square->accounts[$id]['balance'], 'lifetime_points' => $square->accounts[$id]['balance']];

    Http::fake(function (Request $request) use ($square, $account) {
        $path = parse_url($request->url(), PHP_URL_PATH);
        $body = $request->data();

        if ($square->down && $path !== '/v2/loyalty/programs/main') {
            return Http::response(['errors' => [['code' => 'INTERNAL_SERVER_ERROR']]], 500);
        }

        return match (true) {
            $path === '/v2/loyalty/programs/main' => $square->program
                ? Http::response(['program' => [
                    'id' => 'prog-1', 'status' => 'ACTIVE',
                    'reward_tiers' => [['id' => 'tier-1', 'points' => 100, 'name' => '$3 off your order', 'definition' => [
                        'scope' => 'ORDER', 'discount_type' => 'FIXED_AMOUNT', 'fixed_discount_money' => ['amount' => 300, 'currency' => 'CAD'],
                    ]]],
                ]])
                : Http::response(['errors' => [['code' => 'NOT_FOUND']]], 404),
            $path === '/v2/loyalty/accounts/search' => Http::response(['loyalty_accounts' => collect($square->accounts)
                ->filter(fn ($a) => $a['phone'] === $body['query']['mappings'][0]['phone_number'])
                ->keys()->map($account)->values()->all()]),
            $path === '/v2/loyalty/accounts' => (function () use ($square, $account, $body) {
                $id = 'acct-'.count($square->accounts);
                $square->accounts[$id] = ['phone' => $body['loyalty_account']['mapping']['phone_number'], 'balance' => 0];

                return Http::response(['loyalty_account' => $account($id)]);
            })(),
            $path === '/v2/loyalty/programs/prog-1/calculate' => Http::response(['points' => intdiv($body['transaction_amount_money']['amount'], 100)]),
            (bool) preg_match('#^/v2/loyalty/accounts/([^/]+)/(accumulate|adjust)$#', $path, $m) => (function () use ($square, $m, $body) {
                $square->accounts[$m[1]]['balance'] += $body['accumulate_points']['points'] ?? $body['adjust_points']['points'];

                return Http::response(['events' => [], 'event' => []]);
            })(),
            (bool) preg_match('#^/v2/loyalty/accounts/([^/]+)$#', $path, $m) => Http::response(['loyalty_account' => $account($m[1])]),
            $path === '/v2/loyalty/rewards' => (function () use ($square, $body) {
                $id = 'reward-'.(count($square->rewards) + 1);
                $square->accounts[$body['reward']['loyalty_account_id']]['balance'] -= 100;
                $square->rewards[$id] = ['account' => $body['reward']['loyalty_account_id'], 'status' => 'ISSUED'];

                return Http::response(['reward' => ['id' => $id, 'status' => 'ISSUED']]);
            })(),
            (bool) preg_match('#^/v2/loyalty/rewards/([^/]+)/redeem$#', $path, $m) => (function () use ($square, $m) {
                $square->rewards[$m[1]]['status'] = 'REDEEMED';

                return Http::response(['event' => ['type' => 'REDEEM_REWARD']]);
            })(),
            $request->method() === 'DELETE' && (bool) preg_match('#^/v2/loyalty/rewards/([^/]+)$#', $path, $m) => (function () use ($square, $m) {
                $square->accounts[$square->rewards[$m[1]]['account']]['balance'] += 100;
                $square->rewards[$m[1]]['status'] = 'DELETED';

                return Http::response([]);
            })(),
            $path === '/v2/loyalty/events/search' => Http::response(['events' => [
                ['type' => 'ACCUMULATE_POINTS', 'created_at' => '2026-10-20T15:00:00Z', 'source' => 'SQUARE', 'accumulate_points' => ['points' => 42]],
                ['type' => 'CREATE_REWARD', 'created_at' => '2026-10-21T15:00:00Z', 'source' => 'LOYALTY_API', 'create_reward' => ['points' => -100]],
            ]]),
            $path === '/v2/refunds' => Http::response(['refund' => ['id' => 'refund-1', 'status' => 'COMPLETED']]),
            default => Http::response(['errors' => [['code' => 'UNEXPECTED', 'detail' => $path]]], 400),
        };
    });

    return $square;
}

function loyaltyOrder(array $attributes = []): Order
{
    return Order::create([
        'number' => 'ISA-'.strtoupper(str()->random(6)),
        'status' => Order::STATUS_AWAITING_PAYMENT,
        'fulfilment' => 'pickup',
        'contact_name' => 'Valentine Akin',
        'contact_phone' => '+15065550123',
        'subtotal_cents' => 2420,
        'total_cents' => 2420,
        'payment_method' => 'card',
        'placed_at' => now(),
        ...$attributes,
    ]);
}

function placeLoyaltyOrder(User $user, string $payment = 'card'): Order
{
    return app(PlaceOrder::class)->handle(
        user: $user,
        contactName: $user->name,
        fulfilment: 'pickup',
        paymentMethod: $payment,
        pickupStart: CarbonImmutable::parse('2026-10-26 11:00', 'America/Moncton'),
        clearCart: false,
        contactPhone: '+15065550123',
    );
}

function accumulateCalls(): int
{
    return Http::recorded(fn (Request $r) => str_ends_with($r->url(), '/accumulate'))->count();
}

test('a paid online order signs the shopper up by phone and earns points, once', function () {
    $square = fakeLoyalty();
    $order = loyaltyOrder();

    app(TakePayment::class)->markPaid($order, 'pay-1');
    app(TakePayment::class)->markPaid($order->fresh(), 'pay-1');

    $order->refresh();
    expect($order->loyalty_points)->toBe(24)
        ->and($order->loyalty_account_id)->toBe('acct-0')
        ->and($square->accounts['acct-0'])->toBe(['phone' => '+15065550123', 'balance' => 24])
        ->and(accumulateCalls())->toBe(1);

    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/accumulate')
        && $r['idempotency_key'] === 'loyalty-earn-'.$order->id && $r['location_id'] === 'LOC1');
});

test('only the items after discounts earn points: no delivery fee', function () {
    fakeLoyalty(['+15065550123' => 10]);
    $order = loyaltyOrder(['fulfilment' => 'delivery', 'subtotal_cents' => 5000, 'discount_cents' => 500, 'delivery_fee_cents' => 799, 'total_cents' => 5299]);

    app(TakePayment::class)->markPaid($order, 'pay-1');

    expect($order->fresh()->loyalty_points)->toBe(45);
    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/calculate') && $r['transaction_amount_money']['amount'] === 4500);
});

test('pay-at-pickup orders earn when collected, not before; cancelled orders never do', function () {
    fakeLoyalty();
    $order = loyaltyOrder(['status' => Order::STATUS_PLACED, 'payment_method' => 'pay_at_pickup']);

    $order->update(['fulfilment_status' => Order::FULFILMENT_READY]);
    expect($order->fresh()->loyalty_points)->toBeNull();

    $order->update(['fulfilment_status' => Order::FULFILMENT_COMPLETED]);
    expect($order->fresh()->loyalty_points)->toBe(24);

    loyaltyOrder(['status' => Order::STATUS_PLACED])->update(['status' => Order::STATUS_CANCELLED]);
    expect(accumulateCalls())->toBe(1);
});

test('orders paid while loyalty is off (e.g. test payments with no programme) never earn, even later', function () {
    $square = fakeLoyalty(program: false);
    $order = loyaltyOrder();

    app(TakePayment::class)->markPaid($order, 'pay-1');

    expect($order->fresh())->loyalty_points->toBeNull()->loyalty_synced_at->not->toBeNull();

    // The programme switches on: loyalty:sync doesn't award the old order.
    cache()->flush();
    $square->program = true;
    $this->artisan('loyalty:sync')->assertSuccessful();

    expect(accumulateCalls())->toBe(0);
});

test('when Square is down the order still goes through, and loyalty:sync sends the points later', function () {
    $square = fakeLoyalty(down: true);
    $order = loyaltyOrder();

    app(TakePayment::class)->markPaid($order, 'pay-1');

    expect($order->fresh())->status->toBe(Order::STATUS_PAID)->loyalty_synced_at->toBeNull();

    $square->down = false;
    $this->artisan('loyalty:sync')->expectsOutputToContain('Orders sent to Square Loyalty: 1')->assertSuccessful();

    expect($order->fresh()->loyalty_points)->toBe(24);
});

test('a refund takes back the points its order earned, never more', function () {
    $square = fakeLoyalty();
    $order = loyaltyOrder(['subtotal_cents' => 5000, 'total_cents' => 5799, 'delivery_fee_cents' => 799, 'fulfilment' => 'delivery']);
    app(TakePayment::class)->markPaid($order, 'pay-1');
    expect($square->accounts['acct-0']['balance'])->toBe(50);

    $loyalty = app(SquareLoyalty::class);
    $loyalty->reversePurchase($order->fresh(), 2000);
    expect($square->accounts['acct-0']['balance'])->toBe(30);

    // Refunding everything, delivery fee included, takes back the rest and no more.
    $loyalty->reversePurchase($order->fresh(), 5799);
    $loyalty->reversePurchase($order->fresh(), 5799);
    expect($square->accounts['acct-0']['balance'])->toBe(0)
        ->and($order->fresh()->loyalty_points_reversed)->toBe(50);
});

test('the Rewards page shows Square points, progress and activity from in store and online', function () {
    fakeLoyalty(['+15065550123' => 230]);
    $this->actingAs(User::factory()->create(['phone' => '+15065550123']));

    Livewire::test(Rewards::class)
        ->assertSee('230')
        ->assertSee('70 more points to your next reward')
        ->assertSee('$3 off your order')
        ->assertSee('In store')->assertSee('+42')
        ->assertSee('Reward used')->assertSee('−100')
        ->call('useReward')
        ->assertSee('Your reward is on your basket');

    expect(app(Cart::class)->usingReward())->toBeTrue();
});

test('the Rewards page asks for a phone number, and says when loyalty is not switched on yet', function () {
    $square = fakeLoyalty();
    $this->actingAs(User::factory()->create(['phone' => null]));
    Livewire::test(Rewards::class)->assertSee('Add your phone number to see your points');

    cache()->flush();
    $square->program = false;
    Livewire::test(Rewards::class)->assertSee('coming to the website soon');
});

test('a signed-in shopper spends a reward online: points held at the order, used once paid', function () {
    $square = fakeLoyalty(['+15065550123' => 230]);
    $user = User::factory()->create(['phone' => '+15065550123']);
    $this->actingAs($user);
    app(Cart::class)->add($this->rice->id, 2); // $40

    Livewire::test(CartPage::class)
        ->assertSee('You have a loyalty reward')
        ->call('useReward')
        ->assertSee('Loyalty reward applied')
        ->assertViewHas('totals', fn ($totals) => $totals->discount === 300 && $totals->reward !== null);

    $order = placeLoyaltyOrder($user);

    expect($order)->discount_cents->toBe(300)->total_cents->toBe(3700)
        ->loyalty_reward_id->toBe('reward-1')->loyalty_reward_status->toBe(SquareLoyalty::REWARD_ISSUED)
        ->and($order->discountLabel())->toBe('Loyalty reward')
        ->and($square->accounts['acct-0']['balance'])->toBe(130);

    app(TakePayment::class)->markPaid($order, 'pay-1');

    // Redeemed, and the $37 of goods after the reward earn 37 points.
    expect($order->fresh())->loyalty_reward_status->toBe(SquareLoyalty::REWARD_REDEEMED)->loyalty_points->toBe(37)
        ->and($square->rewards['reward-1']['status'])->toBe('REDEEMED')
        ->and($square->accounts['acct-0']['balance'])->toBe(167);
});

test('a declined payment gives the held reward points back', function () {
    $square = fakeLoyalty(['+15065550123' => 120]);
    $user = User::factory()->create(['phone' => '+15065550123']);
    $this->actingAs($user);
    app(Cart::class)->add($this->rice->id);
    app(Cart::class)->useReward();

    $order = placeLoyaltyOrder($user);
    expect($square->accounts['acct-0']['balance'])->toBe(20);

    $order->update(['status' => Order::STATUS_PAYMENT_FAILED]);

    expect($order->fresh()->loyalty_reward_status)->toBe(SquareLoyalty::REWARD_RELEASED)
        ->and($square->rewards['reward-1']['status'])->toBe('DELETED')
        ->and($square->accounts['acct-0']['balance'])->toBe(120);
});

test('no reward offer without enough points, and a reward that ran out is taken off at checkout', function () {
    fakeLoyalty(['+15065550123' => 99]);
    $user = User::factory()->create(['phone' => '+15065550123']);
    $this->actingAs($user);
    app(Cart::class)->add($this->rice->id);

    Livewire::test(CartPage::class)->assertDontSee('You have a loyalty reward');

    app(Cart::class)->useReward();
    expect(fn () => placeLoyaltyOrder($user))->toThrow(CheckoutProblem::class, 'loyalty reward isn’t available');
    expect(app(Cart::class)->usingReward())->toBeFalse();
});

test('refunding an order through the admin takes its points back', function () {
    $square = fakeLoyalty();
    config(['services.square.token' => 'test-token']);
    $order = loyaltyOrder(['payment_id' => 'pay-1']);
    app(TakePayment::class)->markPaid($order, 'pay-1');
    $this->actingAs(User::factory()->create(['is_admin' => true, 'role' => User::ROLE_MANAGER, 'is_active' => true]));

    app(IssueRefund::class)->handle($order->fresh(), $order->total_cents, 'manual', 'Customer request');

    expect($square->accounts['acct-0']['balance'])->toBe(0);
});

test('admin loyalty tracker shows the connection, website orders and a phone lookup', function () {
    fakeLoyalty(['+15065550123' => 230]);
    loyaltyOrder(['number' => 'ISA-LOYAL1', 'status' => Order::STATUS_PAID, 'loyalty_account_id' => 'acct-0', 'loyalty_points' => 24, 'loyalty_synced_at' => now()]);
    $this->actingAs(User::factory()->create(['is_admin' => true, 'role' => User::ROLE_MANAGER, 'is_active' => true]));

    $this->get(route('admin.loyalty'))->assertOk()->assertSee('Connected to Square Loyalty')->assertSee('ISA-LOYAL1');

    Livewire::test(LoyaltyTracker::class)
        ->set('lookupPhone', '506 555 0123')
        ->call('lookUp')
        ->assertSee('230 points');
});
