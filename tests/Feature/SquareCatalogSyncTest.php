<?php

use App\Jobs\SyncSquareCatalog;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Services\Square\CatalogSync;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config([
        'shop.currency' => 'CAD',
        'services.square.token' => 'test-token',
        'services.square.location_id' => 'LOC1',
        'services.square.webhook_key' => 'whkey',
        'services.square.webhook_url' => 'https://shop.test/api/webhooks/square',
    ]);
    Cache::forget(CatalogSync::STATE_KEY);
    unset($GLOBALS['square']);
});

function squareItem(string $id, string $name, array $variations, array $extra = []): array
{
    $itemData = [
        'name' => $name,
        'description_plaintext' => $name.' from Square',
        'categories' => [['id' => 'CAT1']],
        'image_ids' => ['IMG-'.$id],
        'variations' => $variations,
        ...($extra['item_data'] ?? []),
    ];
    unset($extra['item_data']);

    return ['type' => 'ITEM', 'id' => $id, 'present_at_all_locations' => true, 'item_data' => $itemData, ...$extra];
}

function squareVariation(string $id, ?string $name, ?int $cents, bool $tracked = true, array $extra = []): array
{
    return array_replace_recursive([
        'type' => 'ITEM_VARIATION',
        'id' => $id,
        'present_at_all_locations' => true,
        'item_variation_data' => array_filter([
            'name' => $name,
            'pricing_type' => $cents === null ? 'VARIABLE_PRICING' : 'FIXED_PRICING',
            'price_money' => $cents === null ? null : ['amount' => $cents, 'currency' => 'CAD'],
            'track_inventory' => $tracked,
        ], fn ($v) => $v !== null),
    ], $extra);
}

/**
 * Fake Square's API. Can be called again in a test to change what it returns.
 */
function fakeSquare(array $objects, array $counts = [], array $changed = []): void
{
    $first = ! isset($GLOBALS['square']);
    $GLOBALS['square'] = compact('objects', 'counts', 'changed');

    if ($first) {
        Http::fake([
            '*/v2/catalog/list*' => fn () => Http::response(['objects' => $GLOBALS['square']['objects']]),
            '*/v2/catalog/search' => fn () => Http::response(['objects' => $GLOBALS['square']['changed']]),
            '*/v2/catalog/batch-retrieve' => fn ($request) => Http::response(['objects' => collect($GLOBALS['square']['objects'])->whereIn('id', $request['object_ids'])->values()->all()]),
            '*/v2/inventory/counts/batch-retrieve' => fn () => Http::response(['counts' => $GLOBALS['square']['counts']]),
        ]);
    }
}

function catalogue(): array
{
    return [
        ['type' => 'CATEGORY', 'id' => 'CAT1', 'category_data' => ['name' => 'Rice & Grains']],
        ['type' => 'IMAGE', 'id' => 'IMG-ITEM1', 'image_data' => ['url' => 'https://images.square.test/rice.jpg']],
        squareItem('ITEM1', 'Jollof Rice Mix', [
            squareVariation('VAR1', '500g', 499),
            squareVariation('VAR2', '1kg', 899),
        ]),
        squareItem('ITEM2', 'Plantain Chips', [squareVariation('VAR3', 'Regular', 299, tracked: false)], ['item_data' => ['image_ids' => []]]),
    ];
}

test('a full sync copies categories, products, prices, photos and stock', function () {
    fakeSquare(catalogue(), [
        ['catalog_object_id' => 'VAR1', 'location_id' => 'LOC1', 'state' => 'IN_STOCK', 'quantity' => '12'],
        ['catalog_object_id' => 'VAR1', 'location_id' => 'LOC1', 'state' => 'WASTE', 'quantity' => '3'],
    ]);

    $stats = app(CatalogSync::class)->full();

    expect($stats)->toMatchArray(['items' => 2, 'categories' => 1]);

    $rice = Product::where('square_id', 'ITEM1')->with('variations')->sole();
    expect($rice)
        ->name->toBe('Jollof Rice Mix')
        ->slug->toBe('jollof-rice-mix')
        ->description->toBe('Jollof Rice Mix from Square')
        ->image->toBe('https://images.square.test/rice.jpg')
        ->is_active->toBeTrue()
        ->and($rice->category->name)->toBe('Rice & Grains')
        ->and($rice->variations->pluck('price_cents', 'name')->all())->toBe(['500g' => 499, '1kg' => 899])
        // Tracked: 12 from Square, and 0 where Square has no count.
        ->and($rice->variations->pluck('stock_quantity', 'square_id')->all())->toBe(['VAR1' => 12, 'VAR2' => 0]);

    $chips = Product::where('square_id', 'ITEM2')->sole();
    expect($chips->image)->toBe(CatalogSync::NO_IMAGE)
        ->and($chips->defaultVariation->stock_quantity)->toBeNull();

    expect(Cache::get(CatalogSync::STATE_KEY))->not->toBeNull();
});

test('nothing is written to Square', function () {
    fakeSquare(catalogue());

    app(CatalogSync::class)->full();

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'upsert')
        || str_contains($request->url(), 'changes/batch-create')
        || in_array($request->method(), ['PUT', 'DELETE'], true));
});

test('the first sync takes over the demo product with the same name, keeping our extras', function () {
    $this->seed(DemoCatalogSeeder::class);
    $demo = Product::where('slug', 'fresh-okra')->firstOrFail();
    $demo->update(['is_trending' => true]);
    $demoVariation = $demo->defaultVariation;

    fakeSquare([squareItem('OKRA', 'Fresh Okra', [squareVariation('OKRA-V', 'Regular', 279)], ['item_data' => ['image_ids' => [], 'categories' => [], 'description_plaintext' => '']])]);

    app(CatalogSync::class)->full();

    $okra = $demo->fresh();
    expect($okra)
        ->square_id->toBe('OKRA')
        ->is_trending->toBeTrue()
        ->image->toBe($demo->image)             // no Square photo yet, so the demo one stays
        ->description->toBe($demo->description)
        ->category_id->toBe($demo->category_id)
        ->and(Product::where('slug', 'like', 'fresh-okra%')->count())->toBe(1)
        // The demo variation became the Square one: same row, Square price, our size label kept.
        ->and($demoVariation->fresh())->square_id->toBe('OKRA-V')->price_cents->toBe(279)->name->toBe($demoVariation->name);
});

test('items gone from Square, archived or without a fixed price are hidden', function () {
    fakeSquare(catalogue());
    app(CatalogSync::class)->full();

    fakeSquare([
        ['type' => 'CATEGORY', 'id' => 'CAT1', 'category_data' => ['name' => 'Rice & Grains']],
        squareItem('ITEM1', 'Jollof Rice Mix', [squareVariation('VAR1', '500g', null)]),
        squareItem('ITEM3', 'Old Stock', [squareVariation('VAR9', null, 100)], ['item_data' => ['is_archived' => true]]),
    ]);
    app(CatalogSync::class)->full();

    expect(Product::where('square_id', 'ITEM1')->value('is_active'))->toBeFalse()   // variable price only
        ->and(Product::where('square_id', 'ITEM2')->value('is_active'))->toBeFalse() // removed from Square
        ->and(Product::where('square_id', 'ITEM3')->exists())->toBeFalse();          // archived, never shown
});

test('items not sold at our location, or priced in another currency, are skipped', function () {
    fakeSquare([
        squareItem('ELSEWHERE', 'Other Shop Item', [squareVariation('V1', null, 100)], ['present_at_all_locations' => false, 'present_at_location_ids' => ['LOC2']]),
        squareItem('USD', 'Dollar Item', [squareVariation('V2', null, 100, extra: ['item_variation_data' => ['price_money' => ['currency' => 'USD']]])]),
    ]);

    app(CatalogSync::class)->full();

    expect(Product::where('is_active', true)->count())->toBe(0);
});

test('an incremental sync applies only the changes', function () {
    fakeSquare(catalogue());
    app(CatalogSync::class)->full();

    fakeSquare(catalogue(), [
        ['catalog_object_id' => 'VAR2', 'location_id' => 'LOC1', 'state' => 'IN_STOCK', 'quantity' => '7'],
    ], changed: [
        squareItem('ITEM1', 'Jollof Rice Mix', [squareVariation('VAR1', '500g', 549)]),
        ['type' => 'ITEM', 'id' => 'ITEM2', 'is_deleted' => true],
    ]);

    app(CatalogSync::class)->changes();

    $rice = Product::where('square_id', 'ITEM1')->with('variations')->sole();
    expect($rice->variations->pluck('price_cents', 'square_id')->all())->toBe(['VAR1' => 549])  // 1kg removed in Square
        ->and($rice->image)->toBe('https://images.square.test/rice.jpg')                        // image fetched separately
        ->and(Product::where('square_id', 'ITEM2')->value('is_active'))->toBeFalse();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'catalog/search') && $request['begin_time'] !== null);
});

test('the command can hide the demo products once Square products are live', function () {
    $this->seed(DemoCatalogSeeder::class);
    $demoCount = Product::count();
    fakeSquare(catalogue());

    $this->artisan('square:sync --full --retire-demo')->assertSuccessful();

    expect(Product::where('is_active', true)->pluck('square_id')->sort()->values()->all())->toBe(['ITEM1', 'ITEM2'])
        ->and(Product::count())->toBe($demoCount + 2);   // hidden, not deleted
});

test('the seed command refuses to touch a live Square account', function () {
    config(['services.square.env' => 'production']);
    Http::fake();

    $this->artisan('square:seed-sandbox')->assertFailed();

    Http::assertNothingSent();
});

function signedWebhook(array $payload): array
{
    $body = json_encode($payload);
    $signature = base64_encode(hash_hmac('sha256', config('services.square.webhook_url').$body, 'whkey', true));

    return [$body, ['X-Square-Hmacsha256-Signature' => $signature, 'Content-Type' => 'application/json']];
}

test('stock webhooks update counts and catalogue webhooks queue a sync', function () {
    fakeSquare(catalogue(), [['catalog_object_id' => 'VAR1', 'location_id' => 'LOC1', 'state' => 'IN_STOCK', 'quantity' => '12']]);
    app(CatalogSync::class)->full();
    Queue::fake();

    [$body, $headers] = signedWebhook(['type' => 'inventory.count.updated', 'data' => ['object' => ['inventory_counts' => [
        ['catalog_object_id' => 'VAR1', 'location_id' => 'LOC1', 'state' => 'IN_STOCK', 'quantity' => '4'],
        ['catalog_object_id' => 'VAR1', 'location_id' => 'OTHER', 'state' => 'IN_STOCK', 'quantity' => '99'],
    ]]]]);
    $this->call('POST', '/api/webhooks/square', [], [], [], collect($headers)->mapWithKeys(fn ($v, $k) => ['HTTP_'.strtoupper(str_replace('-', '_', $k)) => $v])->all(), $body)->assertNoContent();

    expect(ProductVariation::where('square_id', 'VAR1')->value('stock_quantity'))->toBe(4);

    [$body, $headers] = signedWebhook(['type' => 'catalog.version.updated']);
    $this->call('POST', '/api/webhooks/square', [], [], [], collect($headers)->mapWithKeys(fn ($v, $k) => ['HTTP_'.strtoupper(str_replace('-', '_', $k)) => $v])->all(), $body)->assertNoContent();

    Queue::assertPushed(SyncSquareCatalog::class);
});

test('webhooks without a valid signature are rejected', function () {
    $this->postJson('/api/webhooks/square', ['type' => 'catalog.version.updated'])->assertForbidden();
});

test('staff hiding a product survives later syncs', function () {
    fakeSquare(catalogue());
    app(CatalogSync::class)->full();

    Product::where('square_id', 'ITEM1')->update(['is_hidden' => true]);
    app(CatalogSync::class)->full();

    $rice = Product::where('square_id', 'ITEM1')->sole();
    expect($rice->is_active)->toBeTrue()->and($rice->is_hidden)->toBeTrue()
        ->and(Product::active()->pluck('square_id')->all())->toBe(['ITEM2']);
});

test('SKUs and Square sub-categories come across', function () {
    fakeSquare([
        ['type' => 'CATEGORY', 'id' => 'TOP', 'category_data' => ['name' => 'Pantry']],
        ['type' => 'CATEGORY', 'id' => 'SUB', 'category_data' => ['name' => 'Spices', 'parent_category' => ['id' => 'TOP']]],
        squareItem('ITEM1', 'Suya Spice', [squareVariation('V1', null, 499, extra: ['item_variation_data' => ['sku' => 'SUYA-1']])], ['item_data' => ['categories' => [['id' => 'SUB']], 'image_ids' => []]]),
    ]);

    app(CatalogSync::class)->full();

    $suya = Product::where('square_id', 'ITEM1')->sole();
    expect($suya->category->name)->toBe('Spices')
        ->and($suya->category->parent->name)->toBe('Pantry')
        ->and($suya->defaultVariation->sku)->toBe('SUYA-1');
});

test('the first import is not new arrivals, but later new items are', function () {
    fakeSquare(catalogue());
    app(CatalogSync::class)->full();

    expect(Product::whereNotNull('first_seen_at')->count())->toBe(0);

    fakeSquare([...catalogue(), squareItem('ITEM9', 'Fresh Yam', [squareVariation('V9', null, 599)])]);
    app(CatalogSync::class)->full();

    expect(Product::whereNotNull('first_seen_at')->pluck('square_id')->all())->toBe(['ITEM9']);
});

test('the catalogue check finds nothing to fix right after a sync', function () {
    fakeSquare(catalogue());
    app(CatalogSync::class)->full();

    $report = app(\App\Services\Square\CatalogCheck::class)->run();

    expect($report['summary'])->toMatchArray(['square_categories' => 1, 'square_items_for_sale' => 2])
        ->and(collect($report['items'])->except('not_linked_to_square')->flatten()->all())->toBe([])
        ->and(collect($report['categories'])->flatten()->all())->toBe([]);
});

test('the catalogue check lists what differs between Square and the site, and changes nothing', function () {
    fakeSquare(catalogue());
    app(CatalogSync::class)->full();

    // Square moves on: a new sub-category and item, a rename, a new price; one item is deleted.
    $objects = catalogue();
    $objects[] = ['type' => 'CATEGORY', 'id' => 'CAT2', 'category_data' => ['name' => 'Basmati', 'parent_category' => ['id' => 'CAT1']]];
    $objects[2]['item_data']['name'] = 'Jollof Rice Seasoning';
    $objects[2]['item_data']['variations'][0]['item_variation_data']['price_money']['amount'] = 549;
    unset($objects[3]);
    $objects[] = squareItem('ITEM3', 'Ofada Rice', [squareVariation('VAR9', '2kg', 1299)]);
    fakeSquare(array_values($objects));

    $before = Product::orderBy('id')->get(['id', 'name', 'is_active'])->toArray();
    $report = app(\App\Services\Square\CatalogCheck::class)->run();

    expect($report['summary']['square_subcategories'])->toBe(1)
        ->and($report['categories']['missing_on_site'])->toBe(['Rice & Grains › Basmati'])
        ->and($report['items']['missing_on_site'])->toBe(['Ofada Rice'])
        ->and($report['items']['renamed'])->toBe(['Square “Jollof Rice Seasoning” is “Jollof Rice Mix” on the site'])
        ->and($report['items']['price_differs'][0])->toContain('Jollof Rice Seasoning / 500g')
        ->and($report['items']['still_for_sale_on_site'])->toBe(['Plantain Chips (deleted from Square)'])
        ->and(Product::orderBy('id')->get(['id', 'name', 'is_active'])->toArray())->toBe($before);

    Http::assertNotSent(fn ($request) => $request->method() !== 'GET' && ! str_contains($request->url(), 'batch-retrieve'));
});

test('the catalogue check command can use a separate credentials file without printing the token', function () {
    fakeSquare(catalogue());
    $file = tempnam(sys_get_temp_dir(), 'sq');
    file_put_contents($file, "SQUARE_ENV=production\nSQUARE_ACCESS_TOKEN=live-secret-token\nSQUARE_LOCATION_ID=LOC1\n");

    $this->artisan('square:check-catalog', ['--credentials' => $file])
        ->expectsOutputToContain('Checking Square production')
        ->doesntExpectOutputToContain('live-secret-token')
        ->assertSuccessful();

    Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://connect.squareup.com/')
        && $request->header('Authorization')[0] === 'Bearer live-secret-token');
    unlink($file);
});

test('managers can run the catalogue check from the staff portal; associates cannot open it', function () {
    fakeSquare(catalogue());
    app(CatalogSync::class)->full();

    $this->actingAs(\App\Models\User::factory()->create(['is_admin' => true, 'role' => \App\Models\User::ROLE_ASSOCIATE, 'is_active' => true]))
        ->get(route('admin.square-check'))->assertForbidden();

    $this->actingAs(\App\Models\User::factory()->create(['is_admin' => true, 'role' => \App\Models\User::ROLE_MANAGER, 'is_active' => true]))
        ->get(route('admin.square-check'))->assertOk()->assertSee('Run check');

    \Livewire\Livewire::test(\App\Livewire\Admin\SquareCatalogCheck::class)
        ->call('check')
        ->assertSee('Square and the website are in step.');
});
