<?php

use App\Http\Controllers\Store\AccountController;
use App\Http\Controllers\Store\HomeController;
use App\Http\Controllers\Store\OrderController;
use App\Http\Controllers\Store\SessionController;
use App\Livewire\Admin\AdminLogin;
use App\Livewire\Admin\CategoriesManager;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\IssuesDesk;
use App\Livewire\Admin\OrdersQueue;

use App\Livewire\Admin\PaymentMethods;
use App\Livewire\Admin\ProductsList;
use App\Livewire\Admin\RecipesManager;
use App\Livewire\Admin\StaffManager;
use App\Livewire\Admin\StoreHoursEditor;
use App\Livewire\Admin\StoreSettings;
use Illuminate\Support\Facades\Auth;
use App\Livewire\Store\Account\AccountManagement;
use App\Livewire\Store\Account\AddressBook;
use App\Livewire\Store\Account\BuyAgain;
use App\Livewire\Store\Account\OrderDetail;
use App\Livewire\Store\Account\OrderHistory;
use App\Livewire\Store\Account\Preferences;
use App\Livewire\Store\Account\Rewards;
use App\Livewire\Store\Account\Wishlist;
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
Route::get('/orders/{number}/substitutions/{id}', \App\Livewire\Store\SubstitutionResponse::class)
    ->name('orders.substitutions.review')
    ->middleware('signed');
Route::get('/orders/{number}/reschedule', \App\Livewire\Store\RescheduleOrder::class)
    ->name('orders.reschedule')
    ->middleware('signed');

// Part G: Ìsalèè Kitchen & Ingredients Engine
Route::get('/kitchen', \App\Livewire\Store\Kitchen\KitchenHome::class)->name('kitchen.index');
Route::get('/kitchen/cook-with-what-i-have', \App\Livewire\Store\Kitchen\CookWithWhatIHave::class)->name('kitchen.pantry');
Route::get('/kitchen/cuisine/{cuisine}', \App\Livewire\Store\Kitchen\KitchenHome::class)->name('kitchen.cuisine');
Route::get('/kitchen/{recipe:slug}', \App\Livewire\Store\Kitchen\RecipeDetail::class)->name('kitchen.recipe');


// Customer accounts: password-free sign-in happens in the drawer.
Route::get('/sign-in', [SessionController::class, 'create'])->middleware('guest')->name('login');
Route::post('/sign-out', [SessionController::class, 'destroy'])->middleware('auth')->name('sign-out');

Route::prefix('account')->middleware('auth')->group(function () {
    Route::get('/', [AccountController::class, 'overview'])->name('account');
    Route::get('/manage', AccountManagement::class)->name('account.manage');
    Route::get('/addresses', AddressBook::class)->name('account.addresses');
    Route::get('/orders', OrderHistory::class)->name('account.orders');
    Route::get('/orders/{order}', OrderDetail::class)->name('account.orders.show');
    Route::get('/rewards', Rewards::class)->name('account.rewards');
    Route::get('/wishlist', Wishlist::class)->name('account.wishlist');
    Route::get('/buy-again', BuyAgain::class)->name('account.buy-again');
    Route::get('/preferences', Preferences::class)->name('account.preferences');
});

// Admin Operations Portal
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', AdminLogin::class)->name('login');
    Route::post('/logout', function () {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'You have been safely signed out of operations.');
    })->name('logout');

    // Protected Operations Portal
    Route::middleware('admin')->group(function () {
        Route::get('/', Dashboard::class)->name('dashboard');
        Route::get('/orders', OrdersQueue::class)->name('orders');
        Route::get('/issues', IssuesDesk::class)->name('issues');
        Route::get('/products', ProductsList::class)->name('products');

        Route::get('/categories', CategoriesManager::class)->name('categories');
        Route::get('/recipes', RecipesManager::class)->name('recipes');
        Route::get('/settings', StoreSettings::class)->name('settings');
        Route::get('/hours', StoreHoursEditor::class)->name('hours');
        Route::get('/payments', PaymentMethods::class)->middleware('admin:manager')->name('payments');
        Route::get('/staff', StaffManager::class)->middleware('admin:manager')->name('staff');
    });
});
