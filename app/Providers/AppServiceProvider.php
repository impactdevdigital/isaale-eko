<?php

namespace App\Providers;

use App\Services\Messaging\LogSmsSender;
use App\Services\Messaging\SmsSender;
use App\Support\Cart;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Swap for a real SMS provider once one is chosen.
        $this->app->bind(SmsSender::class, LogSmsSender::class);

        $this->app->scoped(Cart::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
