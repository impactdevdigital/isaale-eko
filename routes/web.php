<?php

use App\Livewire\Admin\PaymentMethods;
use App\Livewire\Admin\ProductsList;
use App\Livewire\Admin\StoreHoursEditor;
use App\Livewire\Admin\StoreSettings;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Admin Operations Portal
Route::prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/settings');
    Route::get('/settings', StoreSettings::class)->name('settings');
    Route::get('/hours', StoreHoursEditor::class)->name('hours');
    Route::get('/payments', PaymentMethods::class)->name('payments');
    Route::get('/products', ProductsList::class)->name('products');
});
