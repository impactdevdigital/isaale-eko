<?php

use App\Livewire\Admin\AnalyticsDashboard;
use App\Livewire\Admin\IssuesDesk;
use App\Livewire\Admin\OrdersQueue;
use App\Livewire\Admin\StoriesManager;
use App\Livewire\Store\HelpCentre;
use App\Livewire\Store\ProductPage;
use App\Mail\BackInStockMail;
use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\OrderIssue;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\StockNotification;
use App\Models\Story;
use App\Models\User;
use App\Services\Square\CatalogSync;
use App\Services\Store\BackInStock;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(DemoCatalogSeeder::class);
});

function deliveryOrder(array $attributes = []): Order
{
    $order = Order::create(array_merge([
        'number' => 'ISA-'.strtoupper(str()->random(6)),
        'status' => Order::STATUS_PAID,
        'fulfilment' => 'pickup',
        'contact_name' => 'Chioma Test',
        'contact_email' => 'chioma@example.com',
        'contact_phone' => '+15065550100',
        'pickup_starts_at' => now()->addHours(2),
        'pickup_ends_at' => now()->addHours(4),
        'subtotal_cents' => 998,
        'savings_cents' => 0,
        'discount_cents' => 0,
        'delivery_fee_cents' => 0,
        'total_cents' => 998,
        'payment_method' => 'card',
        'placed_at' => now(),
    ], $attributes));

    $variation = Product::where('slug', 'fresh-okra')->firstOrFail()->defaultVariation;
    $order->items()->create([
        'product_id' => $variation->product_id,
        'product_variation_id' => $variation->id,
        'name' => 'Fresh Okra',
        'variation_name' => $variation->name,
        'unit_price_cents' => 499,
        'quantity' => 2,
        'line_total_cents' => 998,
    ]);

    return $order;
}

function staff(string $role = User::ROLE_MANAGER): User
{
    return User::factory()->create(['is_admin' => true, 'is_active' => true, 'role' => $role, 'name' => 'Bukola Staff']);
}

// ----- Stock wording ------------------------------------------------------

it('never shows exact stock numbers to customers', function () {
    $product = Product::where('slug', 'fresh-spinach')->firstOrFail();
    $product->defaultVariation->update(['stock_quantity' => 3]);

    $this->get(route('product', $product))->assertOk()
        ->assertSee('Low Stock')
        ->assertDontSee('Only 3 left')
        ->assertDontSee('3 remaining');
});

// ----- Stories ↔ products and recipes ------------------------------------

it('lets staff link a story to products and recipes, and shows the links on the store', function () {
    $okra = Product::where('slug', 'fresh-okra')->firstOrFail();
    $recipe = Recipe::firstOrFail();
    $recipe->update(['status' => Recipe::STATUS_APPROVED]);

    Livewire::actingAs(staff())->test(StoriesManager::class)
        ->call('create', Story::TYPE_FOOD)
        ->set('title', 'Okra, the Slippery Hero')
        ->set('body', 'Why okra matters.')
        ->set('status', Story::STATUS_PUBLISHED)
        ->set('productSearch', 'okra')
        ->assertSee('Fresh Okra')
        ->call('addProduct', $okra->id)
        ->call('addRecipe', $recipe->id)
        ->call('save')
        ->assertHasNoErrors();

    $story = Story::where('title', 'Okra, the Slippery Hero')->firstOrFail();
    expect($story->products->pluck('id')->all())->toBe([$okra->id])
        ->and($story->recipes->pluck('id')->all())->toBe([$recipe->id]);

    $this->get($story->url())->assertOk()
        ->assertSee('Shop this story')
        ->assertSee('Fresh Okra')
        ->assertSee('Cook from this story')
        ->assertSee($recipe->name);

    $this->get(route('product', $okra))->assertOk()
        ->assertSee('The Story Behind It')
        ->assertSee('Okra, the Slippery Hero');
});

it('does not show draft stories on product pages', function () {
    $okra = Product::where('slug', 'fresh-okra')->firstOrFail();
    $draft = Story::create(['type' => Story::TYPE_FOOD, 'title' => 'Secret Draft', 'slug' => 'secret-draft', 'status' => Story::STATUS_DRAFT]);
    $draft->products()->attach($okra);

    $this->get(route('product', $okra))->assertOk()->assertDontSee('Secret Draft');
});

// ----- Guest invitation ---------------------------------------------------

it('invites guests to sign in on the order confirmation page', function () {
    $order = deliveryOrder();

    $this->withSession(['guest_orders' => [$order->id]])
        ->get(route('orders.show', $order))->assertOk()
        ->assertSee('Keep your order history and earn rewards. Sign in or create an account')
        ->assertSee('chioma@example.com');
});

it('does not invite signed-in customers', function () {
    $user = User::factory()->create(['email' => 'chioma@example.com']);
    $order = deliveryOrder(['user_id' => $user->id]);

    $this->actingAs($user)->get(route('orders.show', $order))->assertOk()
        ->assertDontSee('Keep your order history and earn rewards');
});

// ----- Order timeline -----------------------------------------------------

it('records a timestamped timeline of every status change, in Atlantic time', function () {
    $this->travelTo(now()->setTimezone('UTC')->setTime(14, 5));
    $order = deliveryOrder();
    $manager = staff();

    Livewire::actingAs($manager)->test(OrdersQueue::class)
        ->call('startPreparing', $order->id)
        ->call('markAsReady', $order->id)
        ->call('viewOrder', $order->id)
        ->assertSee('Order Timeline')
        ->assertSee('Accepted · preparing')
        ->assertSee('Ready · awaiting pickup')
        ->assertSee('by Bukola Staff');

    $events = $order->statusEvents()->get();
    expect($events->pluck('label')->all())->toBe(['Paid · new order', 'Accepted · preparing', 'Ready · awaiting pickup'])
        ->and($events[1]->user_id)->toBe($manager->id)
        ->and($events[0]->timeLabel())->toContain('10:05 AM A'); // 14:05 UTC = 10:05 Atlantic (ADT/AST)
});

it('tells the customer the order was accepted when preparation starts', function () {
    $order = deliveryOrder(['fulfilment_status' => Order::FULFILMENT_PREPARING]);

    expect($order->statusLabel())->toBe('Accepted · preparing for pickup');
});

// ----- Back in stock ------------------------------------------------------

it('emails shoppers once when a product they asked about is back in stock', function () {
    Mail::fake();
    config(['services.square.location_id' => 'LOC1']);
    $product = Product::where('slug', 'mama-gold-garri')->firstOrFail();
    $variation = $product->defaultVariation;
    $variation->update(['square_id' => 'VAR-GARRI', 'stock_quantity' => 0]);

    Livewire::test(ProductPage::class, ['product' => $product])
        ->set('email', 'Ada@Example.com')
        ->call('notify')
        ->assertSet('subscribed', true);

    // Still sold out: nothing to send.
    expect(app(BackInStock::class)->send())->toBe(0);

    app(CatalogSync::class)->applyCounts([['catalog_object_id' => 'VAR-GARRI', 'state' => 'IN_STOCK', 'location_id' => 'LOC1', 'quantity' => '12']]);

    Mail::assertSent(BackInStockMail::class, fn ($mail) => $mail->hasTo('ada@example.com') && $mail->product->is($product));
    expect(StockNotification::sole()->notified_at)->not->toBeNull()
        ->and(app(BackInStock::class)->send())->toBe(0);
    Mail::assertSentCount(1);
});

// ----- Report a problem ---------------------------------------------------

it('turns a Help form question into an Issues Desk case', function () {
    Livewire::test(HelpCentre::class)
        ->set('topic', 'enquiry')
        ->set('name', 'Kemi Adeyemi')
        ->set('email', 'kemi@example.com')
        ->set('phone', '506 555 0199')
        ->set('description', 'Do you stock fresh ugu leaves on Saturdays?')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSee('Thanks, we’ve got it');

    $issue = OrderIssue::sole();
    expect($issue->kind)->toBe(OrderIssue::KIND_ENQUIRY)
        ->and($issue->order_id)->toBeNull()
        ->and($issue->channel)->toBe('website')
        ->and($issue->contactName())->toBe('Kemi Adeyemi')
        ->and($issue->contactEmail())->toBe('kemi@example.com');

    Livewire::actingAs(staff())->test(IssuesDesk::class)
        ->assertSee($issue->reference)
        ->assertSee('General enquiry')
        ->call('viewIssue', $issue->id)
        ->assertSee('Reply by Email')
        ->call('markEnquiryResolved');

    expect($issue->fresh()->status)->toBe(OrderIssue::STATUS_RESOLVED);
});

it('lets a customer report damaged items on their own order', function () {
    $order = deliveryOrder(['fulfilment_status' => Order::FULFILMENT_COMPLETED]);
    $item = $order->items()->first();

    Livewire::test(HelpCentre::class)
        ->set('name', 'Chioma Test')
        ->set('email', 'CHIOMA@example.com')
        ->set('orderNumber', strtolower($order->number))
        ->call('findOrder')
        ->assertHasNoErrors()
        ->assertSee('Which items are affected?')
        ->set("items.{$item->id}.selected", true)
        ->set("items.{$item->id}.quantity", 1)
        ->set('description', 'The okra arrived squashed and brown.')
        ->call('submit')
        ->assertHasNoErrors();

    $issue = OrderIssue::with('items')->sole();
    expect($issue->order_id)->toBe($order->id)
        ->and($issue->kind)->toBe('problem')
        ->and($issue->items)->toHaveCount(1)
        ->and($issue->items->first()->quantity)->toBe(1);
});

it('does not reveal someone else’s order on the Help form', function () {
    $order = deliveryOrder();

    Livewire::test(HelpCentre::class)
        ->set('email', 'stranger@example.com')
        ->set('orderNumber', $order->number)
        ->call('findOrder')
        ->assertHasErrors('orderNumber')
        ->assertDontSee('Fresh Okra');
});

it('links the Help page from the footer', function () {
    $this->get('/')->assertSee(route('help'));
    $this->get('/contact')->assertRedirect('/help');
    $this->get('/help')->assertOk()->assertSee('Report a Problem');
});

// ----- Analytics & Measurement ---------------------------------------------

it('counts store page views but not admin pages', function () {
    $this->get('/')->assertOk();
    $this->get('/shop')->assertOk();
    $this->actingAs(staff())->get('/admin')->assertOk();

    expect(AnalyticsEvent::where('name', 'page_view')->pluck('properties')->pluck('path')->all())->toBe(['/', '/shop']);
});

it('shows the Analytics & Measurement module to managers only', function () {
    $this->get('/shop?q=okra');
    $this->get('/shop?q=zzznothing');
    AnalyticsEvent::create(['name' => 'select_fulfilment', 'properties' => ['method' => 'pickup']]);
    AnalyticsEvent::create(['name' => 'view_pickup_slots', 'properties' => ['available_slots' => 6]]);

    Livewire::actingAs(staff())->test(AnalyticsDashboard::class)
        ->assertSee('Analytics & Measurement')
        ->assertSee('Searches with no results')
        ->assertSee('zzznothing')
        ->assertSee('Shopping funnel')
        ->assertSee('Google Analytics is ready but not connected yet')
        ->assertViewHas('counts', fn ($counts) => $counts['search'] === 2 && $counts['select_fulfilment'] === 1)
        ->assertViewHas('averageSlots', 6.0);

    $this->actingAs(staff())->get('/admin/analytics')->assertOk();
    $this->actingAs(staff(User::ROLE_ASSOCIATE))->get('/admin/analytics')->assertForbidden();
});
