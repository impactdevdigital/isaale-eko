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

it('starts with the banners from the Figma designs', function () {
    expect(Banner::live()->where('placement', 'hero')->orderBy('sort_order')->pluck('title')->all())
        ->toBe(['Earn $3 in Rewards for Every $100 You Spend.', 'Time for a Pantry Reset?', 'What’s Cooking This Week?'])
        ->and(Banner::live()->where('placement', 'promo')->orderBy('sort_order')->pluck('title')->all())
        ->toBe(['Good Groceries Don’t Have to Cost a Fortune.', 'Ask Asha', 'Busy Day? No Problem.', 'Missing Home?'])
        ->and(Banner::where('title', 'Visit Us in Saint John')->sole()->is_active)->toBeFalse();

    $this->get('/')->assertOk()
        ->assertSee('Earn $3 in Rewards for Every $100 You Spend.')
        ->assertSee('The Ìsàlèè Èkó Pantry Festival')
        ->assertSee('images/store/banners/pantry-reset.webp', false)
        ->assertSee('href="'.url('/shop?price=under-10').'"', false)
        ->assertDontSee('Free Delivery Over $40.');

    foreach (Banner::all() as $banner) {
        expect(public_path($banner->image))->toBeFile();
    }
});

it('shows dark text on light banners', function () {
    $slide = Banner::where('title', 'Time for a Pantry Reset?')->sole()->toSlide();

    expect($slide['text'])->toBe('text-ink')
        ->and($slide['button'])->toContain('bg-ink');

    Livewire::actingAs(bannerStaff())->test(BannersManager::class)
        ->call('edit', Banner::where('title', 'Ask Asha')->sole()->id)
        ->set('style', 'dark')
        ->call('save')
        ->assertHasNoErrors();

    expect(Banner::where('title', 'Ask Asha')->sole()->style)->toBe('dark');
});

it('links the under $10 banner to a working price filter', function () {
    $this->get('/shop?price=under-10')->assertOk();
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
    expect($banner->sort_order)->toBe(4)
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
    $pantry = Banner::where('placement', 'hero')->where('title', 'Time for a Pantry Reset?')->sole();
    $delivery = Banner::where('placement', 'hero')->where('title', 'Earn $3 in Rewards for Every $100 You Spend.')->sole();

    Livewire::actingAs(bannerStaff())->test(BannersManager::class)->call('toggleActive', $pantry->id);
    Banner::where('placement', 'promo')->delete();
    $delivery->update(['ends_at' => now()->subMinute()]);

    $this->get('/')->assertOk()->assertDontSee('Time for a Pantry Reset?')->assertDontSee('Earn $3 in Rewards for Every $100 You Spend.');

    $delivery->update(['ends_at' => null, 'starts_at' => now()->addDay()]);
    expect($delivery->fresh()->stateLabel())->toBe('Scheduled');
    $this->get('/')->assertDontSee('Earn $3 in Rewards for Every $100 You Spend.');
});

it('reorders slides within a slideshow', function () {
    $pantry = Banner::where('placement', 'hero')->where('title', 'Time for a Pantry Reset?')->sole();

    Livewire::actingAs(bannerStaff())->test(BannersManager::class)->call('move', $pantry->id, -1);

    expect(Banner::where('placement', 'hero')->orderBy('sort_order')->pluck('title')->first())->toBe('Time for a Pantry Reset?');
});

it('is open to all staff but not to customers', function () {
    $this->actingAs(bannerStaff())->get('/admin/banners')->assertOk()->assertSee('Homepage Banners');
    $this->actingAs(User::factory()->create())->get('/admin/banners')->assertForbidden();
});
