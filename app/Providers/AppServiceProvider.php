<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Country;
use App\Models\Product;
use App\Services\Messaging\LogSmsSender;
use App\Services\Messaging\SmsSender;
use App\Support\Cart;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        // Short names for translatable models in the translations table. Not enforced,
        // so other polymorphic relations (e.g. API tokens) keep working.
        Relation::morphMap([
            'product' => Product::class,
            'category' => Category::class,
            'country' => Country::class,
            'ingredient' => \App\Models\Ingredient::class,
            'recipe' => \App\Models\Recipe::class,
            'meal_combo' => \App\Models\MealCombo::class,
        ]);
    }
}
