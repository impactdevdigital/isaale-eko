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

test('staff can select photo from presets or upload an image file for a recipe', function () {
    \Illuminate\Support\Facades\Storage::fake('public');

    // 1. Preset selection
    Livewire::test(RecipesManager::class)
        ->call('createRecipe')
        ->set('name', 'Preset Test Jollof')
        ->call('selectPreset', 'images/store/recipes/jollof-rice.webp')
        ->assertSet('selectedPreset', 'images/store/recipes/jollof-rice.webp')
        ->assertSet('image', 'images/store/recipes/jollof-rice.webp')
        ->call('save')
        ->assertHasNoErrors();

    $presetRecipe = Recipe::where('name', 'Preset Test Jollof')->first();
    expect($presetRecipe)->not->toBeNull();
    expect($presetRecipe->image)->toBe('images/store/recipes/jollof-rice.webp');

    // 2. File upload
    $jpegBytes = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');
    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('banga-dish.jpg', $jpegBytes);

    Livewire::test(RecipesManager::class)
        ->call('createRecipe')
        ->set('name', 'Upload Test Banga')
        ->set('imageUpload', $file)
        ->call('save')
        ->assertHasNoErrors();

    $uploadedRecipe = Recipe::where('name', 'Upload Test Banga')->first();
    expect($uploadedRecipe)->not->toBeNull();
    expect($uploadedRecipe->image)->toContain('storage/recipes/uploads/recipe-upload-test-banga');
});

test('staff can toggle advanced settings in recipe modal', function () {
    Livewire::test(RecipesManager::class)
        ->call('createRecipe')
        ->assertSet('showAdvanced', false)
        ->call('toggleAdvanced')
        ->assertSet('showAdvanced', true)
        ->call('toggleAdvanced')
        ->assertSet('showAdvanced', false);
});

test('staff can manage culinary ingredients and link shelf variations', function () {
    $product = testProduct('Golden Sella Basmati', 'golden-sella-rice');
    $variation = \App\Models\ProductVariation::create([
        'product_id' => $product->id,
        'name' => '5kg Bag',
        'price_cents' => 1850,
        'stock_quantity' => 15,
    ]);

    Livewire::test(RecipesManager::class)
        ->call('setTab', 'ingredients')
        ->assertSet('tab', 'ingredients')
        ->call('createIngredient')
        ->set('ingredientName', 'Basmati Rice')
        ->set('ingredientAltNames', 'aromatic rice, sella')
        ->set('ingredientIsPantryStaple', false)
        ->call('saveIngredient')
        ->assertHasNoErrors()
        ->assertSet('showIngredientModal', false);

    $ing = \App\Models\Ingredient::where('slug', 'basmati-rice')->first();
    expect($ing)->not->toBeNull();
    expect($ing->alt_names)->toBe('aromatic rice, sella');

    // Link variation
    Livewire::test(RecipesManager::class)
        ->call('openManageOptions', $ing->id)
        ->assertSet('showOptionsModal', true)
        ->set('selectedVariationId', $variation->id)
        ->set('optionRank', 1)
        ->set('optionPackFactor', 2.0)
        ->call('addProductOption')
        ->assertHasNoErrors();

    $opt = \App\Models\IngredientProduct::where('ingredient_id', $ing->id)->first();
    expect($opt)->not->toBeNull();
    expect($opt->product_variation_id)->toBe($variation->id);
    expect((float) $opt->pack_factor)->toBe(2.0);
});

test('staff can define and remove curated substitutions', function () {
    $ing1 = \App\Models\Ingredient::create(['name' => 'Palm Oil', 'slug' => 'test-palm-oil', 'status' => 'active']);
    $ing2 = \App\Models\Ingredient::create(['name' => 'Groundnut Oil', 'slug' => 'test-groundnut-oil', 'status' => 'active']);

    Livewire::test(RecipesManager::class)
        ->call('setTab', 'substitutions')
        ->call('createSubstitution')
        ->set('subBaseIngredientId', $ing1->id)
        ->set('subSubstituteId', $ing2->id)
        ->set('subNote', 'Use equal amounts; imparts golden hue')
        ->call('saveSubstitution')
        ->assertHasNoErrors()
        ->assertSet('showSubstitutionModal', false);

    expect($ing1->substitutes)->toHaveCount(1);
    expect($ing1->substitutes->first()->id)->toBe($ing2->id);

    Livewire::test(RecipesManager::class)
        ->call('removeSubstitution', $ing1->id, $ing2->id)
        ->assertHasNoErrors();

    expect($ing1->fresh()->substitutes)->toHaveCount(0);
});

test('staff can approve a recipe when all validation rules pass', function () {
    $product = testProduct('Scotch Bonnet Peppers', 'fresh-peppers-bundle');
    $variation = \App\Models\ProductVariation::create([
        'product_id' => $product->id,
        'name' => 'Bowl',
        'price_cents' => 300,
        'stock_quantity' => 20,
    ]);
    $ing = \App\Models\Ingredient::create(['name' => 'Scotch Bonnet', 'slug' => 'scotch-bonnet-peppers', 'status' => 'active']);
    \App\Models\IngredientProduct::create(['ingredient_id' => $ing->id, 'product_variation_id' => $variation->id, 'rank' => 1, 'pack_factor' => 1]);

    $recipe = Recipe::create([
        'name' => 'Spicy Pepper Dip',
        'slug' => 'spicy-pepper-dip',
        'title' => 'Spicy Pepper Dip',
        'cuisine' => 'nigerian',
        'steps' => '1. Pound scotch bonnets. 2. Serve with roasted meat.',
        'base_servings' => 4,
        'status' => Recipe::STATUS_DRAFT,
    ]);

    \App\Models\RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ing->id,
        'role' => 'required',
        'people_per_pack' => 4,
    ]);

    Livewire::test(RecipesManager::class)
        ->call('approveRecipeAction', $recipe->id)
        ->assertHasNoErrors();

    expect($recipe->fresh()->status)->toBe(Recipe::STATUS_APPROVED);
    expect($recipe->fresh()->approved_at)->not->toBeNull();
});

