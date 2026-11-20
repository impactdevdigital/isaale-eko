<?php

use App\Models\StoreClosure;
use App\Models\StoreHour;

it('shows the about page with store photos, hours and directions', function () {
    StoreHour::query()->delete();
    StoreHour::create(['weekday' => 1, 'opens_at' => '10:00', 'closes_at' => '19:00', 'is_closed' => false]);
    StoreHour::create(['weekday' => 0, 'is_closed' => true]);
    StoreClosure::create(['date' => now('America/Moncton')->addDays(3)->toDateString(), 'reason' => 'Staff training']);

    $this->get('/about')->assertOk()
        ->assertSee('More than a grocery store. A little closer to home.')
        ->assertSee('Ìsàlèè Èkó officially opened in March 2024.')
        ->assertSee('Explore Our Store')
        ->assertSee('images/store/about/shopfront.webp', false)
        ->assertSee('images/store/about/own-brand-spices.webp', false)
        ->assertSee('10:00 AM – 7:00 PM')
        ->assertSee('Closed')
        ->assertSee('Staff training')
        ->assertSee('google.com/maps', false);
});

it('links to the about page from the header, footer and help page', function () {
    $about = route('about');

    $this->get('/')->assertOk()
        ->assertSee('href="'.$about.'"', false)
        ->assertDontSee('Visit Us in Saint John');

    $this->get('/help')->assertOk()
        ->assertSee('images/store/about/shopfront-sign.webp', false)
        ->assertSee('href="'.$about.'"', false);
});

it('keeps the store photos in the website files', function (string $file) {
    expect(public_path("images/store/{$file}"))->toBeFile();
})->with([
    'about/shopfront.webp', 'about/shopfront-sign.webp', 'about/building.webp', 'about/aisle.webp',
    'about/grains-aisle.webp', 'about/pantry-corner.webp',
    'about/own-brand-spices.webp', 'about/own-brand-grains.webp', 'banners/visit-the-store.webp',
]);
