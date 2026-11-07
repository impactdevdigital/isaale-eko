<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

test('the Apple Pay domain verification file is published', function () {
    $file = public_path('.well-known/apple-developer-merchantid-domain-association');

    expect($file)->toBeFile()
        ->and(hex2bin(trim(file_get_contents($file))))->toContain('"pspId"');
});

test('square:apple-pay-domain registers the APP_URL host with Square', function () {
    config(['app.url' => 'https://isaale-eko.onrender.com', 'services.square.env' => 'sandbox']);
    Http::fake(['*/v2/apple-pay/domains' => Http::response(['status' => 'VERIFIED'])]);

    $this->artisan('square:apple-pay-domain')
        ->expectsOutputToContain('isaale-eko.onrender.com registered with Square (sandbox): VERIFIED')
        ->assertSuccessful();

    Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://connect.squareupsandbox.com/v2/apple-pay/domains')
        && $request['domain_name'] === 'isaale-eko.onrender.com');
});

test('square:apple-pay-domain explains when Square refuses the domain', function () {
    Http::fake(['*/v2/apple-pay/domains' => Http::response(['errors' => [['detail' => 'Domain verification failed']]], 400)]);

    $this->artisan('square:apple-pay-domain', ['domain' => 'example.com'])
        ->expectsOutputToContain('Domain verification failed')
        ->assertFailed();

    $this->artisan('square:apple-pay-domain', ['domain' => 'localhost'])->assertFailed();
});
