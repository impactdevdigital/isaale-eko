<?php

use App\Http\Controllers\Store\HomeController;
use App\Http\Controllers\Store\OrderController;
use App\Livewire\Admin\PaymentMethods;
use App\Livewire\Admin\StoreHoursEditor;
use App\Livewire\Admin\StoreSettings;
use App\Livewire\Store\CartPage;
use App\Livewire\Store\Checkout;
use App\Livewire\Store\ProductListing;
use App\Livewire\Store\ProductPage;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/shop', ProductListing::class)->name('shop');
Route::get('/shop/country/{countryPage:slug}', ProductListing::class)->name('shop.country');
Route::get('/products/{product:slug}', ProductPage::class)->name('product');
Route::get('/cart', CartPage::class)->name('cart');
Route::get('/checkout', Checkout::class)->name('checkout');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

// Admin Operations Portal
Route::prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/settings');
    Route::get('/settings', StoreSettings::class)->name('settings');
    Route::get('/hours', StoreHoursEditor::class)->name('hours');
    Route::get('/payments', PaymentMethods::class)->name('payments');
});
