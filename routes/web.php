<?php

use App\Http\Controllers\Store\HomeController;
use App\Livewire\Admin\PaymentMethods;
use App\Livewire\Admin\StoreHoursEditor;
use App\Livewire\Admin\StoreSettings;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Admin Operations Portal
Route::prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/settings');
    Route::get('/settings', StoreSettings::class)->name('settings');
    Route::get('/hours', StoreHoursEditor::class)->name('hours');
    Route::get('/payments', PaymentMethods::class)->name('payments');
});
