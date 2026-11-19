<?php

use App\Livewire\Admin\BannersManager;
use App\Models\Banner;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function bannerStaff(string $role = User::ROLE_ASSOCIATE): User
{
    return User::factory()->create(['is_admin' => true, 'is_active' => true, 'role' => $role]);
}

it('starts with the banners that used to be in the code', function () {
    expect(Banner::where('placement', 'hero')->orderBy('sort_order')->pluck('title')->all())
        ->toBe(['Free Delivery Over $40.', '20% Off Pantry Staples'])
        ->and(Banner::where('placement', 'promo')->count())->toBe(2);

    $this->get('/')->assertOk()
        ->assertSee('Free Delivery Over $40.')
        ->assertSee('href="'.url('/shop').'"', false);
});

it('lets staff add a banner with a photo, link and French text', function () {
    Storage::fake('public');

    Livewire::actingAs(bannerStaff())->test(BannersManager::class)
        ->call('create', 'hero')
        ->set('title', 'Fresh Ugu Every Friday')
        ->set('body', 'Straight from the farm to your pot.')
        ->set('ctaLabel', 'Shop greens')
        ->set('ctaUrl', '/shop?sort=new')
        ->set('titleFr', 'Ugu frais chaque vendredi')
        ->set('imageUpload', UploadedFile::fake()->image('ugu.jpg', 2400, 700))
        ->call('save')
        ->assertHasNoErrors();

    $banner = Banner::where('title', 'Fresh Ugu Every Friday')->sole();
    expect($banner->sort_order)->toBe(2)
        ->and($banner->image)->toStartWith('storage/banners/');
    Storage::disk('public')->assertExists(str_replace('storage/', '', $banner->image));

    $this->get('/')->assertSee('Fresh Ugu Every Friday')->assertSee(url('/shop?sort=new'), false);
    $this->withSession(['locale' => 'fr'])->get('/language/fr');
    $this->get('/')->assertSee('Ugu frais chaque vendredi');
});

it('checks banner details before saving', function () {
    Livewire::actingAs(bannerStaff())->test(BannersManager::class)
        ->call('create', 'promo')
        ->set('ctaLabel', 'Go')
        ->set('ctaUrl', 'javascript:alert(1)')
        ->set('startsAt', '2026-12-10T09:00')
        ->set('endsAt', '2026-12-01T09:00')
        ->call('save')
        ->assertHasErrors(['title', 'imageUpload', 'ctaUrl', 'endsAt']);
});

it('hides, schedules and ends banners on the homepage', function () {
    $pantry = Banner::where('placement', 'hero')->where('title', '20% Off Pantry Staples')->sole();
    $delivery = Banner::where('placement', 'hero')->where('title', 'Free Delivery Over $40.')->sole();

    Livewire::actingAs(bannerStaff())->test(BannersManager::class)->call('toggleActive', $pantry->id);
    Banner::where('placement', 'promo')->delete();
    $delivery->update(['ends_at' => now()->subMinute()]);

    $this->get('/')->assertOk()->assertDontSee('20% Off Pantry Staples')->assertDontSee('Free Delivery Over $40.');

    $delivery->update(['ends_at' => null, 'starts_at' => now()->addDay()]);
    expect($delivery->fresh()->stateLabel())->toBe('Scheduled');
    $this->get('/')->assertDontSee('Free Delivery Over $40.');
});

it('reorders slides within a slideshow', function () {
    $pantry = Banner::where('placement', 'hero')->where('title', '20% Off Pantry Staples')->sole();

    Livewire::actingAs(bannerStaff())->test(BannersManager::class)->call('move', $pantry->id, -1);

    expect(Banner::where('placement', 'hero')->orderBy('sort_order')->pluck('title')->first())->toBe('20% Off Pantry Staples');
});

it('is open to all staff but not to customers', function () {
    $this->actingAs(bannerStaff())->get('/admin/banners')->assertOk()->assertSee('Homepage Banners');
    $this->actingAs(User::factory()->create())->get('/admin/banners')->assertForbidden();
});
