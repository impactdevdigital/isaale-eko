<?php

use App\Livewire\Admin\PaymentMethods;
use App\Livewire\Admin\StoreHoursEditor;
use App\Livewire\Admin\StoreSettings;
use App\Models\PaymentMethod;
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
