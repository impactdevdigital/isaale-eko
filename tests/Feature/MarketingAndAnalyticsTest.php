<?php

use App\Livewire\Store\Account\Preferences;
use App\Livewire\Store\Checkout;
use App\Livewire\Store\NewsletterSignup;
use App\Models\AnalyticsEvent;
use App\Models\Consent;
use App\Models\Product;
use App\Models\User;
use App\Services\Analytics;
use App\Services\ConsentService;
use App\Support\Cart;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Database\Seeders\StoreHoursSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function connectBrevo(): void
{
    config(['services.brevo.key' => 'xkeysib-test', 'services.brevo.list_id' => '7']);
    Http::fake(['api.brevo.com/*' => Http::response(null, 204)]);
}

// ----- Brevo ---------------------------------------------------------------

it('sends the footer newsletter signup to the Brevo list', function () {
    connectBrevo();

    Livewire::test(NewsletterSignup::class)
        ->set('firstName', 'Ada')
        ->set('lastName', 'Lovelace Obi')
        ->set('email', 'Ada@Example.com')
        ->call('subscribe')
        ->assertHasNoErrors()
        ->assertSet('subscribed', true)
        ->assertSee('You’re on the list.');

    app()->terminate(); // Brevo runs after the response

    $consent = Consent::sole();
    expect($consent->email)->toBe('ada@example.com')
        ->and($consent->source)->toBe('footer_signup')
        ->and($consent->name)->toBe('Ada Lovelace Obi')
        ->and($consent->brevo_synced_at)->not->toBeNull();

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.brevo.com/v3/contacts'
        && $request->hasHeader('api-key', 'xkeysib-test')
        && $request['email'] === 'ada@example.com'
        && $request['listIds'] === [7]
        && $request['updateEnabled'] === true
        && $request['attributes'] === ['FIRSTNAME' => 'Ada', 'LASTNAME' => 'Lovelace Obi', 'SIGNUP_SOURCE' => 'Newsletter form (page footer)', 'SIGNUP_PAGE' => '/']);

    // The sign-up source and page fields are created in Brevo first.
    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.brevo.com/v3/contacts/attributes/normal/SIGNUP_SOURCE');
});

it('validates the footer newsletter form', function () {
    Livewire::test(NewsletterSignup::class)
        ->set('email', 'not-an-email')
        ->call('subscribe')
        ->assertHasErrors(['firstName', 'lastName', 'email'])
        ->assertSet('subscribed', false);

    expect(Consent::count())->toBe(0);
});

it('shows the footer newsletter on store pages', function () {
    $this->get('/')->assertOk()->assertSeeLivewire(NewsletterSignup::class);
});

it('records signups without calling Brevo until it is connected', function () {
    Http::fake();

    app(ConsentService::class)->grant('email', null, 'ada@example.com', 'footer_signup', 'Ada');
    app()->terminate();

    expect(Consent::sole()->brevo_synced_at)->toBeNull();
    Http::assertNothingSent();
});

it('backfills older signups with brevo:sync and skips withdrawn ones', function () {
    $user = User::factory()->create(['name' => 'Kemi Adeyemi', 'email' => 'kemi@example.com']);
    Consent::create(['user_id' => $user->id, 'email' => 'kemi@example.com', 'channel' => 'email', 'source' => 'account', 'granted_at' => now()]);
    Consent::create(['email' => 'gone@example.com', 'channel' => 'email', 'source' => 'checkout', 'granted_at' => now(), 'revoked_at' => now()]);
    Consent::create(['email' => 'done@example.com', 'channel' => 'email', 'source' => 'checkout', 'granted_at' => now(), 'brevo_synced_at' => now()]);
    Consent::create(['user_id' => $user->id, 'channel' => 'whatsapp', 'source' => 'account', 'granted_at' => now()]);
    connectBrevo();

    $this->artisan('brevo:sync')->expectsOutputToContain('1 sign-up(s) sent')->assertSuccessful();

    Http::assertSentCount(3); // the two Brevo sign-up fields are set up first, then the contact
    Http::assertSent(fn (Request $request) => $request['email'] === 'kemi@example.com'
        && $request['attributes'] === ['FIRSTNAME' => 'Kemi', 'LASTNAME' => 'Adeyemi', 'SIGNUP_SOURCE' => 'Account preferences']);
});

it('leaves a signup for the next brevo:sync when Brevo fails', function () {
    config(['services.brevo.key' => 'xkeysib-test', 'services.brevo.list_id' => '7']);
    Http::fake(['api.brevo.com/*' => Http::response(['message' => 'down'], 500)]);
    Consent::create(['email' => 'ada@example.com', 'channel' => 'email', 'source' => 'checkout', 'granted_at' => now()]);

    $this->artisan('brevo:sync')->expectsOutputToContain('1 failed')->assertSuccessful();

    expect(Consent::sole()->brevo_synced_at)->toBeNull();
});

it('takes the shopper off the Brevo list when they turn email off', function () {
    $user = User::factory()->create(['email' => 'ada@example.com', 'email_verified_at' => now()]);
    Consent::create(['user_id' => $user->id, 'email' => 'ada@example.com', 'channel' => 'email', 'source' => 'checkout', 'granted_at' => now(), 'brevo_synced_at' => now()]);
    connectBrevo();

    Livewire::actingAs($user)->test(Preferences::class)
        ->set('choices.recipes', false)
        ->call('save');
    app()->terminate();

    expect(Consent::where('channel', 'email')->whereNull('revoked_at')->exists())->toBeFalse();
    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.brevo.com/v3/contacts/lists/7/contacts/remove'
        && $request['emails'] === ['ada@example.com']);
});

// ----- Google Analytics 4 ------------------------------------------------

it('loads Google Analytics only when a measurement ID is set', function () {
    $this->get('/')->assertOk()->assertDontSee('googletagmanager.com', false);

    config(['services.google_analytics.measurement_id' => 'G-TEST123']);

    $this->get('/')->assertOk()
        ->assertSee('https://www.googletagmanager.com/gtag/js?id=G-TEST123', false)
        ->assertSee("Livewire.on('analytics'", false);
});

it('sends view_item to Google Analytics from the product page', function () {
    $this->seed(DemoCatalogSeeder::class);
    config(['services.google_analytics.measurement_id' => 'G-TEST123']);
    $product = Product::where('slug', 'fresh-okra')->firstOrFail();

    $this->get(route('product', $product))->assertOk()
        ->assertSee("gtag('event', 'view_item'", false)
        ->assertSee('Fresh Okra', false);

    // Printed once, not again on the next page.
    $this->get('/')->assertDontSee("gtag('event', 'view_item'", false);
});

it('tracks add to cart and remove from cart, but not clearing a placed order', function () {
    $this->seed(DemoCatalogSeeder::class);
    $variation = Product::where('slug', 'fresh-okra')->firstOrFail()->defaultVariation;
    $cart = app(Cart::class);

    $cart->add($variation->id, 2);
    $cart->remove($variation->id);
    $cart->add($variation->id);
    $cart->clear($variation->id);

    expect(AnalyticsEvent::where('name', 'add_to_cart')->count())->toBe(2)
        ->and(AnalyticsEvent::where('name', 'remove_from_cart')->count())->toBe(1);

    $added = AnalyticsEvent::where('name', 'add_to_cart')->first()->properties;
    expect($added['currency'])->toBe('CAD')
        ->and($added['items'][0]['item_name'])->toBe('Fresh Okra')
        ->and($added['items'][0]['quantity'])->toBe(2)
        ->and($added['value'])->toEqual(round($variation->price_cents * 2 / 100, 2));
});

it('tracks search and searches with no results once per term', function () {
    $this->seed(DemoCatalogSeeder::class);

    $this->get('/shop?q=okra')->assertOk();
    $this->get('/shop?q=zzzznothing')->assertOk();

    expect(AnalyticsEvent::where('name', 'search')->count())->toBe(2)
        ->and(AnalyticsEvent::where('name', 'search_no_results')->sole()->properties)->toBe(['search_term' => 'zzzznothing']);
});

it('tracks the checkout journey through to purchase on the confirmation page', function () {
    $this->seed([DemoCatalogSeeder::class, PaymentMethodSeeder::class, StoreHoursSeeder::class]);
    $this->travelTo(CarbonImmutable::parse('2026-10-26 08:00', 'America/Moncton'));
    Mail::fake();
    Http::fake();
    config(['services.google_analytics.measurement_id' => 'G-TEST123']);

    $user = User::factory()->create(['email' => 'ada@example.com', 'email_verified_at' => now()]);
    $this->actingAs($user);
    app(Cart::class)->add(Product::where('slug', 'fresh-okra')->firstOrFail()->defaultVariation->id, 2);

    Livewire::test(Checkout::class)->set('phone', '506 555 0123')
        ->call('choose', 'pickup')
        ->set('slotStart', CarbonImmutable::parse('2026-10-26 11:00', 'America/Moncton')->toIso8601String())
        ->call('confirmSlot')
        ->set('paymentMethod', 'etransfer')
        ->call('placeETransferOrder')
        ->assertRedirect();

    foreach (['begin_checkout', 'select_fulfilment', 'select_pickup_slot', 'payment_attempted', 'purchase'] as $event) {
        expect(AnalyticsEvent::where('name', $event)->exists())->toBeTrue("{$event} was not tracked");
    }

    $purchase = AnalyticsEvent::where('name', 'purchase')->sole()->properties;
    expect($purchase['payment_type'])->toBe('etransfer')
        ->and($purchase['fulfilment'])->toBe('pickup')
        ->and($purchase['items'][0]['quantity'])->toBe(2);

    // The purchase waits for the confirmation page, where gtag fires it.
    expect(collect(session(Analytics::QUEUE))->pluck('name'))->toContain('purchase');
});

it('never sends names, emails or phone numbers in events', function () {
    $this->seed(DemoCatalogSeeder::class);
    $user = User::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'phone' => '+15065550123']);
    $this->actingAs($user);

    app(Cart::class)->add(Product::where('slug', 'fresh-okra')->firstOrFail()->defaultVariation->id);
    app(ConsentService::class)->grant('email', $user->id, $user->email, 'account', $user->name);

    $all = AnalyticsEvent::pluck('properties')->toJson();
    expect($all)->not->toContain('ada@example.com')
        ->not->toContain('Ada Lovelace')
        ->not->toContain('5550123');
});
