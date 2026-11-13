<?php

use App\Livewire\Admin\PaymentMethods;
use App\Livewire\Admin\ProductsList;
use App\Livewire\Admin\StoreHoursEditor;
use App\Livewire\Admin\StoreSettings;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Setting;
use App\Models\SettingChange;
use App\Models\StoreClosure;
use App\Support\Delivery;
use Database\Seeders\PaymentMethodSeeder;
use Database\Seeders\StoreHoursSeeder;
use Livewire\Livewire;

test('admin store settings component renders and saves values', function () {
    Livewire::test(StoreSettings::class)
        ->assertStatus(200)
        ->assertSee('Store Settings')
        ->set('values.pickup_lead_minutes', 45)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Store settings updated successfully');

    expect(Setting::get('pickup_lead_minutes'))->toBe(45);
});

test('admin store hours editor saves weekly schedule and closures', function () {
    $this->seed(StoreHoursSeeder::class);

    Livewire::test(StoreHoursEditor::class)
        ->assertStatus(200)
        ->assertSee('Operating Hours')
        ->set('closureDate', now()->addDays(5)->format('Y-m-d'))
        ->set('closureReason', 'Test Holiday Closure')
        ->call('addClosure')
        ->assertHasNoErrors();

    expect(StoreClosure::where('reason', 'Test Holiday Closure')->exists())->toBeTrue();
});

test('admin payment methods component toggles and guards last method', function () {
    $this->seed(PaymentMethodSeeder::class);
    $card = PaymentMethod::where('code', 'card')->first();

    Livewire::test(PaymentMethods::class)
        ->assertStatus(200)
        ->assertSee('Payment Methods Controls')
        ->assertSee('Pay by Card');

    // Toggle card off
    Livewire::test(PaymentMethods::class)
        ->call('toggle', $card->id)
        ->assertHasNoErrors();

    expect($card->fresh()->is_enabled)->toBeFalse();
});

test('staff can switch delivery off and on from the payment methods page', function () {
    $this->seed(PaymentMethodSeeder::class);
    expect(Delivery::enabled())->toBeTrue();

    Livewire::test(PaymentMethods::class)
        ->assertSee('Let customers choose Delivery at checkout')
        ->call('toggleDelivery')
        ->assertSee('Delivery is off. Checkout now offers Pickup only.');

    expect(Delivery::enabled())->toBeFalse()
        ->and(SettingChange::where('key', Delivery::SETTING)->latest('id')->value('new_value'))->toBeFalse();

    Livewire::test(PaymentMethods::class)->call('toggleDelivery');

    expect(Delivery::enabled())->toBeTrue();
});

test('delivery falls back to the SHOP_DELIVERY_ENABLED config until staff set it', function () {
    config(['shop.delivery.enabled' => false]);
    expect(Delivery::enabled())->toBeFalse();

    Delivery::setEnabled(true);
    expect(Delivery::enabled())->toBeTrue();
});

test('admin products list component renders, searches, and updates product attributes', function () {
    $product = Product::create([
        'square_id' => 'TEST_ITEM_123',
        'name' => 'Premium Nigerian Egusi',
        'slug' => 'premium-nigerian-egusi',
        'item_class' => 'perishable',
    ]);
    $product->variations()->create(['name' => '500g', 'sku' => 'EGU-500', 'price_cents' => 799, 'stock_quantity' => 3]);

    Livewire::test(ProductsList::class)
        ->assertStatus(200)
        ->assertSee('Products')
        ->assertSee('Premium Nigerian Egusi')
        ->assertSee('Low Stock')
        ->set('search', 'egu-500')            // SKU search, any case
        ->assertSee('Premium Nigerian Egusi')
        ->call('toggleFeatured', $product->id)
        ->call('updateItemClass', $product->id, 'non_food')
        ->call('updateItemClass', $product->id, 'nonsense')
        ->call('toggleHidden', $product->id);

    $product->refresh();
    expect($product->is_featured)->toBeTrue()
        ->and($product->item_class)->toBe('non_food')
        ->and($product->is_hidden)->toBeTrue()
        ->and(Product::active()->count())->toBe(0);
});

test('admin products list filters by stock state and category', function () {
    $parent = Category::create(['name' => 'Pantry', 'slug' => 'pantry']);
    $child = Category::create(['name' => 'Spices', 'slug' => 'spices', 'parent_id' => $parent->id]);

    $make = function (string $name, ?int $stock, ?int $categoryId = null) {
        $product = Product::create(['name' => $name, 'slug' => str($name)->slug(), 'category_id' => $categoryId]);
        $product->variations()->create(['price_cents' => 100, 'stock_quantity' => $stock]);
    };
    $make('Plenty', 50, $child->id);
    $make('Few Left', 2);
    $make('Sold Out', 0);
    $make('No Tracking', null);

    $list = Livewire::test(ProductsList::class);

    $list->set('stockFilter', 'out_of_stock')->assertSee('Sold Out')->assertDontSee('Plenty')->assertDontSee('No Tracking');
    $list->set('stockFilter', 'low_stock')->assertSee('Few Left')->assertDontSee('Plenty');
    $list->set('stockFilter', 'untracked')->assertSee('No Tracking')->assertDontSee('Sold Out');
    // A parent category includes its sub-categories.
    $list->set('stockFilter', '')->set('categoryFilter', (string) $parent->id)->assertSee('Plenty')->assertDontSee('Few Left');
});

test('admin products list opens inspection drawer and updates French product fields', function () {
    $product = Product::create([
        'square_id' => 'ITEM_FR_TEST',
        'name' => 'African Honey Beans',
        'slug' => 'african-honey-beans',
        'description' => 'Sweet brown honey beans from West Africa.',
        'item_class' => 'perishable',
    ]);
    $product->variations()->create(['name' => '1kg Pack', 'sku' => 'HB-1000', 'price_cents' => 950, 'stock_quantity' => 12]);

    Livewire::test(ProductsList::class)
        ->assertStatus(200)
        ->call('viewProduct', $product->id)
        ->assertSet('selectedProductId', $product->id)
        ->assertSee('Product Inspection')
        ->assertSee('African Honey Beans')
        ->assertSee('HB-1000')
        ->set('editNameFr', 'Haricots Miel Africains')
        ->set('editDescriptionFr', 'Délicieux haricots bruns doux d’Afrique de l’Ouest.')
        ->call('saveProduct')
        ->assertHasNoErrors()
        ->assertSee('Product \'African Honey Beans\' details and French fields updated successfully.');

    $product->refresh();
    expect($product->name_fr)->toBe('Haricots Miel Africains')
        ->and($product->description_fr)->toBe('Délicieux haricots bruns doux d’Afrique de l’Ouest.')
        ->and($product->tr('name', 'fr'))->toBe('Haricots Miel Africains')
        ->and($product->tr('name', 'en'))->toBe('African Honey Beans')
        ->and($product->tr('description', 'fr'))->toBe('Délicieux haricots bruns doux d’Afrique de l’Ouest.')
        ->and($product->tr('description', 'en'))->toBe('Sweet brown honey beans from West Africa.');
});

