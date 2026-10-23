<?php

use App\Models\PaymentMethod;
use App\Models\StoreHour;
use Illuminate\Support\Facades\Http;

test('shop:install fills in what is missing and leaves staff changes alone', function () {
    config(['services.square.token' => null]);

    $this->artisan('shop:install')->assertSuccessful();

    expect(StoreHour::count())->toBeGreaterThan(0)
        ->and(PaymentMethod::count())->toBeGreaterThan(0);

    // Staff change an opening time; running it again must not undo that.
    $monday = StoreHour::first();
    $monday->update(['opens_at' => '06:30']);

    $this->artisan('shop:install')->assertSuccessful();

    expect($monday->fresh()->opens_at)->toStartWith('06:30');
});

test('shop:install runs the first Square import only when no products are linked', function () {
    config(['services.square.token' => 't', 'services.square.location_id' => 'LOC1']);
    Http::fake(['*' => Http::response(['objects' => [], 'counts' => []])]);

    $this->artisan('shop:install')->assertSuccessful();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'catalog/list'));
});
