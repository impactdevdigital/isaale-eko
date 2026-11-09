<?php

use App\Livewire\Store\CommunityHub;
use App\Models\Consent;
use Livewire\Livewire;

test('the community hub page shows the coming soon teaser and is linked from the menu and footer', function () {
    $this->get(route('community'))
        ->assertOk()
        ->assertSee('Launching soon')
        ->assertSee('is coming soon')
        ->assertSee('Get launch updates')
        ->assertSee('Join our WhatsApp community')
        ->assertSee('images/store/community/community-01.webp', false);

    $this->get(route('home'))->assertSee(route('community'), false);
});

test('the WhatsApp button uses the configured invite link, or explains it is coming', function () {
    config(['shop.whatsapp_community_url' => null]);
    $this->get(route('community'))->assertSee('Our WhatsApp community opens very soon');

    config(['shop.whatsapp_community_url' => 'https://chat.whatsapp.com/TestInvite']);
    $this->get(route('community'))
        ->assertSee('href="https://chat.whatsapp.com/TestInvite"', false)
        ->assertDontSee('Our WhatsApp community opens very soon');
});

test('launch updates sign readers up for email', function () {
    Livewire::test(CommunityHub::class)
        ->call('subscribe')
        ->assertHasErrors(['email' => 'required'])
        ->set('email', 'Ngozi@Example.com')
        ->call('subscribe')
        ->assertHasNoErrors()
        ->assertSet('subscribed', true)
        ->assertSee('You’re on the list');

    expect(Consent::where('email', 'ngozi@example.com')->where('source', 'community_hub')->exists())->toBeTrue();
});
