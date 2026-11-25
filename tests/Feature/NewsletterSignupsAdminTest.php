<?php

use App\Livewire\Admin\NewsletterSignups;
use App\Models\Consent;
use App\Models\User;
use App\Services\ConsentService;
use Illuminate\Http\Request;
use Livewire\Livewire;

function staffUser(string $role): User
{
    return User::factory()->create(['is_admin' => true, 'is_active' => true, 'role' => $role]);
}

it('is only open to store managers', function () {
    $this->actingAs(staffUser(User::ROLE_MANAGER))->get('/admin/newsletter')->assertOk()->assertSee('Newsletter sign-ups');
    $this->actingAs(staffUser(User::ROLE_ASSOCIATE))->get('/admin/newsletter')->assertForbidden();
});

it('lists sign-ups with their form and page, and filters by source', function () {
    Consent::create(['email' => 'ada@example.com', 'name' => 'Ada Obi', 'channel' => 'email', 'source' => 'footer_signup', 'page' => '/shop', 'granted_at' => now()]);
    Consent::create(['email' => 'kemi@example.com', 'name' => 'Kemi Ade', 'channel' => 'email', 'source' => 'community_hub', 'page' => '/community', 'granted_at' => now()]);
    Consent::create(['email' => 'wa@example.com', 'channel' => 'whatsapp', 'source' => 'account', 'granted_at' => now()]);

    Livewire::actingAs(staffUser(User::ROLE_MANAGER))->test(NewsletterSignups::class)
        ->assertSee('ada@example.com')->assertSee('Newsletter form (page footer)')->assertSee('/shop')
        ->assertSee('kemi@example.com')->assertSee('Community Hub launch updates')
        ->assertDontSee('wa@example.com')
        ->set('source', 'community_hub')
        ->assertSee('kemi@example.com')->assertDontSee('ada@example.com');
});

it('records the page a sign-up came from', function () {
    // A Livewire action: the page comes from the Referer header.
    $request = Request::create('http://localhost/livewire/update', 'POST', server: ['HTTP_REFERER' => 'http://localhost/community?utm=x']);
    app()->instance('request', $request);

    app(ConsentService::class)->grant('email', null, 'page@example.com', 'community_hub', 'Page Test');

    expect(Consent::sole()->page)->toBe('/community');
});
