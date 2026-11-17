<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\FrenchNames;
use Database\Seeders\DemoCatalogSeeder;

beforeEach(fn () => $this->seed(DemoCatalogSeeder::class));

test('the language switch turns the menus, product names and categories French, and back', function () {
    $this->get('/')->assertOk()->assertSee('Kitchen &amp; Recipes', escape: false)->assertSee('Français');

    $this->from('/shop')->get(route('language', 'fr'))->assertRedirect('/shop');

    $this->get('/shop')
        ->assertOk()
        ->assertSee('<html lang="fr">', escape: false)
        ->assertSee('Cuisine et recettes')       // main navigation
        ->assertSee('Catégories du magasin')     // shop mega menu
        ->assertSee('Épicerie et pâtisserie')    // category group
        ->assertSee('Tous les produits')         // page title
        ->assertSee('Rechercher des produits')   // search box
        ->assertSee('Politique de confidentialité') // footer
        ->assertDontSee('Kitchen &amp; Recipes', escape: false);

    $this->get('/shop?q=okra')->assertOk()->assertSee('Gombo frais'); // product name on a card
    $this->get('/products/fresh-okra')->assertOk()->assertSee('Gombo frais')->assertSee('Bon à savoir');
    $this->get('/shop/country/nigeria')->assertOk()->assertSee('Nigéria');

    $this->get(route('language', 'en'));
    $this->get('/shop?q=okra')->assertSee('Fresh Okra')->assertSee('Kitchen &amp; Recipes', escape: false);
});

test('a signed-in shopper keeps their language on their account', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->get(route('language', 'fr'));

    expect($user->refresh()->language)->toBe('fr');
});

test('only supported languages can be chosen', function () {
    $this->get('/language/de')->assertNotFound();
});

test('the switch never redirects to another site', function () {
    $this->from('https://evil.example/phish')->get(route('language', 'fr'))->assertRedirect(route('home'));
});

test('searching by the French name finds the product', function () {
    $this->get('/shop?q=gombo')->assertOk()->assertSee('Fresh Okra');
});

test('French names never replace what staff typed, and products without one fall back to English', function () {
    Product::where('slug', 'fresh-okra')->update(['name_fr' => 'Gombos du jardin']);
    Category::where('name', 'Seafood')->update(['name_fr' => null]);
    $new = Product::create(['name' => 'Brand New Square Item', 'slug' => 'brand-new-square-item', 'item_class' => 'grocery']);

    FrenchNames::fill();
    app()->setLocale('fr');

    expect(Product::where('slug', 'fresh-okra')->value('name_fr'))->toBe('Gombos du jardin')
        ->and(Category::where('name', 'Seafood')->first()->localized_name)->toBe('Poissons et fruits de mer')
        ->and($new->refresh()->localized_name)->toBe('Brand New Square Item');
});
