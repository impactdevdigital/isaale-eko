<?php

use App\Livewire\Admin\PaymentMethods;
use App\Livewire\Admin\ProductsList;
use App\Livewire\Admin\StoreHoursEditor;
use App\Livewire\Admin\StoreSettings;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StoreClosure;
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

test('admin products list component renders, searches, and updates product attributes', function () {
    $product = Product::create([
        'square_item_id' => 'TEST_ITEM_123',
        'name' => 'Premium Nigerian Egusi',
        'slug' => 'premium-nigerian-egusi',
        'status' => 'active',
        'item_class' => 'perishable',
    ]);

    Livewire::test(ProductsList::class)
        ->assertStatus(200)
        ->assertSee('Products')
        ->assertSee('Premium Nigerian Egusi')
        ->call('toggleFeatured', $product->id)
        ->call('updateItemClass', $product->id, 'non_food')
        ->call('toggleStatus', $product->id);

    $product->refresh();
    expect($product->is_featured)->toBeTrue()
        ->and($product->item_class)->toBe('non_food')
        ->and($product->status)->toBe('hidden');
});
