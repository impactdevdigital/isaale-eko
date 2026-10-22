<?php

use App\Http\Controllers\Store\HomeController;
use App\Livewire\Admin\PaymentMethods;
use App\Livewire\Admin\StoreHoursEditor;
use App\Livewire\Admin\StoreSettings;
use App\Livewire\Store\ProductListing;
use App\Livewire\Store\ProductPage;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/shop', ProductListing::class)->name('shop');
Route::get('/shop/country/{countryPage:slug}', ProductListing::class)->name('shop.country');
Route::get('/products/{product:slug}', ProductPage::class)->name('product');

// Admin Operations Portal
Route::prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/settings');
    Route::get('/settings', StoreSettings::class)->name('settings');
    Route::get('/hours', StoreHoursEditor::class)->name('hours');
    Route::get('/payments', PaymentMethods::class)->name('payments');
});
