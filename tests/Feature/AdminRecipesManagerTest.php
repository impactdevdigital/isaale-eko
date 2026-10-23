<?php

namespace Tests\Feature;

use App\Livewire\Admin\RecipesManager;
use App\Models\Country;
use App\Models\Product;
use App\Models\Recipe;
use Livewire\Livewire;

function testCountry(string $name, string $code): Country
{
    return Country::firstOrCreate(
        ['slug' => str($name)->slug()->toString()],
        ['name' => $name, 'code' => $code]
    );
}

function testProduct(string $name, string $slug): Product
{
    return Product::firstOrCreate(
        ['slug' => $slug],
        ['name' => $name]
    );
}

test('admin recipes manager renders and lists recipes with ingredients', function () {
    $country = testCountry('Nigeria', 'NG');
    $recipe = Recipe::create([
        'name' => 'Suya Spiced Beef Skewers',
        'slug' => 'suya-beef-skewers',
        'tagline' => 'Fiery street food favourite',
        'country_id' => $country->id,
        'duration_minutes' => 35,
        'is_featured' => true,
    ]);

    Livewire::test(RecipesManager::class)
        ->assertStatus(200)
        ->assertSee('Kitchen Recipes')
        ->assertSee('Suya Spiced Beef Skewers')
        ->assertSee('Fiery street food favourite');
});

test('staff can search recipes and filter by country', function () {
    $country1 = testCountry('Nigeria', 'NG');
    $country2 = testCountry('Jamaica', 'JM');

    $recipe1 = Recipe::firstOrCreate(['slug' => 'fried-plantain-dodo'], [
        'name' => 'Dodo Fried Plantain',
        'country_id' => $country1->id,
        'duration_minutes' => 20,
    ]);

    $recipe2 = Recipe::firstOrCreate(['slug' => 'jerk-chicken-platter'], [
        'name' => 'Jerk Chicken Platter',
        'country_id' => $country2->id,
        'duration_minutes' => 60,
    ]);

    // Search by name
    Livewire::test(RecipesManager::class)
        ->set('search', 'Dodo')
        ->assertSee('Dodo Fried Plantain')
        ->assertDontSee('Jerk Chicken Platter');

    // Filter by country
    Livewire::test(RecipesManager::class)
        ->set('countryFilter', (string) $country2->id)
        ->assertSee('Jerk Chicken Platter')
        ->assertDontSee('Dodo Fried Plantain');
});

test('staff can toggle featured status on a recipe', function () {
    $recipe = Recipe::firstOrCreate(['slug' => 'test-waakye'], [
        'name' => 'Waakye Rice and Beans',
        'is_featured' => false,
    ]);

    Livewire::test(RecipesManager::class)
        ->call('toggleFeatured', $recipe->id)
        ->assertHasNoErrors();

    expect($recipe->fresh()->is_featured)->toBeTrue();

    Livewire::test(RecipesManager::class)
        ->call('toggleFeatured', $recipe->id)
        ->assertHasNoErrors();

    expect($recipe->fresh()->is_featured)->toBeFalse();
});

test('staff can create a recipe and bundle shelf products as ingredients', function () {
    $country = testCountry('Ghana', 'GH');
    $product1 = testProduct('Black Eyed Beans', 'beans-test-bundle');
    $product2 = testProduct('Palm Oil', 'oil-test-bundle');

    Livewire::test(RecipesManager::class)
        ->call('createRecipe')
        ->set('name', 'Red Red Bean Stew')
        ->set('slug', 'red-red-bean-stew')
        ->set('tagline', 'Sweet plantains and rich black-eyed peas')
        ->set('country_id', $country->id)
        ->set('duration_minutes', 40)
        ->set('is_featured', true)
        ->call('attachProduct', $product1->id)
        ->call('attachProduct', $product2->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false);

    $created = Recipe::where('slug', 'red-red-bean-stew')->first();
    expect($created)->not->toBeNull();
    expect($created->name)->toBe('Red Red Bean Stew');
    expect($created->is_featured)->toBeTrue();
    expect($created->products)->toHaveCount(2);
    expect($created->products->pluck('id'))->toContain($product1->id, $product2->id);
});

test('staff can edit an existing recipe and remove an ingredient', function () {
    $product1 = testProduct('Pepper Soup Spice', 'pepper-soup-spice');
    $product2 = testProduct('Dried Fish', 'dried-fish-pkg');

    $recipe = Recipe::create([
        'name' => 'Pepper Soup Starter',
        'slug' => 'pepper-soup-starter',
        'duration_minutes' => 30,
    ]);
    $recipe->products()->attach([$product1->id, $product2->id]);

    Livewire::test(RecipesManager::class)
        ->call('editRecipe', $recipe->id)
        ->assertSet('name', 'Pepper Soup Starter')
        ->set('tagline', 'Updated spicy broth recipe')
        ->call('removeProduct', $product1->id)
        ->call('save')
        ->assertHasNoErrors();

    $fresh = $recipe->fresh();
    expect($fresh->tagline)->toBe('Updated spicy broth recipe');
    expect($fresh->products->pluck('id'))->not->toContain($product1->id);
    expect($fresh->products->pluck('id'))->toContain($product2->id);
});

test('staff can delete a recipe', function () {
    $recipe = Recipe::create([
        'name' => 'Recipe to Delete',
        'slug' => 'recipe-to-delete',
    ]);

    Livewire::test(RecipesManager::class)
        ->call('openDeleteModal', $recipe->id)
        ->assertSet('showDeleteModal', true)
        ->call('confirmDelete')
        ->assertSet('showDeleteModal', false);

    expect(Recipe::where('slug', 'recipe-to-delete')->exists())->toBeFalse();
});
