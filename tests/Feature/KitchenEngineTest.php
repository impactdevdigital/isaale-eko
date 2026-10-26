<?php

namespace Tests\Feature;

use App\Actions\Kitchen\AddRecipeToCart;
use App\Actions\Kitchen\ApproveRecipe;
use App\Models\Ingredient;
use App\Models\IngredientProduct;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Services\Kitchen\IngredientSearch;
use App\Services\Kitchen\PantryMatcher;
use App\Services\Kitchen\RecipeResolver;
use App\Services\Kitchen\RecipeValidator;
use App\Support\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class KitchenEngineTest extends TestCase
{
    use RefreshDatabase;

    private function createProductWithVariation(string $name, int $priceCents = 500, int $stock = 10): ProductVariation
    {
        $product = Product::create([
            'name' => $name,
            'slug' => str()->slug($name),
            'is_active' => true,
            'is_hidden' => false,
        ]);

        return ProductVariation::create([
            'product_id' => $product->id,
            'name' => 'Standard Pack',
            'price_cents' => $priceCents,
            'stock_quantity' => $stock,
        ]);
    }

    public function test_recipe_resolver_computes_lines_and_pack_factors_correctly(): void
    {
        $variation = $this->createProductWithVariation('Premium Parboiled Rice', 1200, 20);

        $ingredient = Ingredient::create([
            'name' => 'Parboiled Rice',
            'slug' => 'parboiled-rice',
            'is_pantry_staple' => false,
            'status' => 'active',
        ]);

        IngredientProduct::create([
            'ingredient_id' => $ingredient->id,
            'product_variation_id' => $variation->id,
            'rank' => 1,
            'pack_factor' => 1.0,
        ]);

        $recipe = Recipe::create([
            'name' => 'Party Jollof Rice',
            'slug' => 'party-jollof-rice',
            'title' => 'Party Jollof Rice',
            'cuisine' => 'nigerian',
            'steps' => '1. Cook rice. 2. Enjoy.',
            'base_servings' => 4,
            'status' => Recipe::STATUS_APPROVED,
        ]);

        RecipeIngredient::create([
            'recipe_id' => $recipe->id,
            'ingredient_id' => $ingredient->id,
            'role' => 'required',
            'people_per_pack' => 4,
            'sort_order' => 1,
        ]);

        $resolver = app(RecipeResolver::class);

        // 4 people: ceil(4/4) / 1.0 = 1 pack
        $resolved4 = $resolver->resolve($recipe, 4);
        $this->assertCount(1, $resolved4->lines);
        $this->assertEquals(1, $resolved4->lines[0]->quantity);
        $this->assertEquals(1200, $resolved4->totalCents());

        // 5 people: ceil(5/4) / 1.0 = 2 packs
        $resolved5 = $resolver->resolve($recipe, 5);
        $this->assertEquals(2, $resolved5->lines[0]->quantity);
        $this->assertEquals(2400, $resolved5->totalCents());
    }

    public function test_recipe_validator_detects_problems_and_approve_recipe_enforces_them(): void
    {
        $recipe = Recipe::create([
            'name' => 'Incomplete Recipe',
            'slug' => 'incomplete-recipe',
            'title' => 'Incomplete Recipe',
            'cuisine' => 'invalid_cuisine',
            'steps' => '', // missing steps
            'base_servings' => 4,
            'status' => Recipe::STATUS_DRAFT,
        ]);

        $validator = app(RecipeValidator::class);
        $problems = $validator->problems($recipe);

        $this->assertContains('The recipe has no steps.', $problems);
        $this->assertContains('Choose a valid cuisine from the list.', $problems);
        $this->assertContains('Add at least one required ingredient.', $problems);

        $approveAction = app(ApproveRecipe::class);
        $this->expectException(InvalidArgumentException::class);
        $approveAction->handle($recipe);
    }

    public function test_pantry_matcher_finds_recipes_and_scores_coverage(): void
    {
        $var1 = $this->createProductWithVariation('Yam Tubers', 800, 10);
        $var2 = $this->createProductWithVariation('Eggs', 400, 10);

        $ing1 = Ingredient::create(['name' => 'Yam', 'slug' => 'yam', 'status' => 'active']);
        $ing2 = Ingredient::create(['name' => 'Eggs', 'slug' => 'eggs', 'status' => 'active']);

        IngredientProduct::create(['ingredient_id' => $ing1->id, 'product_variation_id' => $var1->id, 'rank' => 1, 'pack_factor' => 1]);
        IngredientProduct::create(['ingredient_id' => $ing2->id, 'product_variation_id' => $var2->id, 'rank' => 1, 'pack_factor' => 1]);

        $recipe = Recipe::create([
            'name' => 'Yam and Egg Sauce',
            'slug' => 'yam-and-egg-sauce',
            'title' => 'Yam and Egg Sauce',
            'cuisine' => 'nigerian',
            'steps' => 'Boil yam, fry eggs.',
            'base_servings' => 2,
            'status' => Recipe::STATUS_APPROVED,
        ]);

        RecipeIngredient::create(['recipe_id' => $recipe->id, 'ingredient_id' => $ing1->id, 'role' => 'required', 'people_per_pack' => 2]);
        RecipeIngredient::create(['recipe_id' => $recipe->id, 'ingredient_id' => $ing2->id, 'role' => 'required', 'people_per_pack' => 2]);

        $matcher = app(PantryMatcher::class);

        // Customer only has Yam ($ing1) -> coverage = 50%
        $matches = $matcher->match([$ing1->id], 2);

        $this->assertCount(1, $matches);
        $this->assertEquals(50, $matches[0]['coverage_percent']);
        $this->assertCount(1, $matches[0]['have']);
        $this->assertEquals('Yam', $matches[0]['have'][0]->name);
        $this->assertCount(1, $matches[0]['need']);
        $this->assertEquals('Eggs', $matches[0]['need'][0]->ingredient->name);
        $this->assertEquals(400, $matches[0]['cost_to_complete_cents']);
    }

    public function test_add_recipe_to_cart_adds_ingredients_to_cart(): void
    {
        $var = $this->createProductWithVariation('Palm Oil', 900, 15);
        $ing = Ingredient::create(['name' => 'Palm Oil', 'slug' => 'palm-oil', 'status' => 'active']);
        IngredientProduct::create(['ingredient_id' => $ing->id, 'product_variation_id' => $var->id, 'rank' => 1, 'pack_factor' => 1]);

        $recipe = Recipe::create([
            'name' => 'Banga Broth',
            'slug' => 'banga-broth',
            'title' => 'Banga Broth',
            'cuisine' => 'west_african',
            'steps' => 'Simmer with spices.',
            'base_servings' => 4,
            'status' => Recipe::STATUS_APPROVED,
        ]);

        RecipeIngredient::create(['recipe_id' => $recipe->id, 'ingredient_id' => $ing->id, 'role' => 'required', 'people_per_pack' => 4]);

        $cart = app(Cart::class);
        $cart->clear();

        $action = app(AddRecipeToCart::class);
        $result = $action->handle($recipe, 4);

        $this->assertCount(1, $result['added']);
        $this->assertCount(0, $result['skipped']);
        $this->assertEquals(1, $cart->count());
        $this->assertEquals(900, $cart->subtotalCents());
    }

    public function test_ingredient_search_matches_names_and_alt_names(): void
    {
        Ingredient::create([
            'name' => 'Egusi Seeds',
            'slug' => 'egusi-seeds',
            'alt_names' => 'melon seed, agushi',
            'status' => 'active',
        ]);

        $searcher = app(IngredientSearch::class);

        $results1 = $searcher->find('egusi');
        $this->assertCount(1, $results1);

        $results2 = $searcher->find('melon');
        $this->assertCount(1, $results2);
    }

    public function test_kitchen_hub_renders_approved_recipes_and_cuisines(): void
    {
        $recipe = Recipe::create([
            'name' => 'Ghanaian Jollof',
            'slug' => 'ghanaian-jollof',
            'title' => 'Ghanaian Jollof',
            'cuisine' => 'ghanaian',
            'steps' => '1. Cook with fragrant jasmine rice.',
            'base_servings' => 4,
            'status' => Recipe::STATUS_APPROVED,
        ]);

        \Livewire\Livewire::test(\App\Livewire\Store\Kitchen\KitchenHome::class)
            ->assertStatus(200)
            ->assertSee('Ghanaian Jollof')
            ->assertSee('Cook With What I Have');
    }

    public function test_recipe_detail_scales_servings_and_adds_ingredients_to_cart(): void
    {
        $var = $this->createProductWithVariation('Jasmine Rice Pack', 850, 20);
        $ing = Ingredient::create(['name' => 'Jasmine Rice', 'slug' => 'jasmine-rice', 'status' => 'active']);
        IngredientProduct::create(['ingredient_id' => $ing->id, 'product_variation_id' => $var->id, 'rank' => 1, 'pack_factor' => 1]);

        $recipe = Recipe::create([
            'name' => 'Jasmine Jollof',
            'slug' => 'jasmine-jollof',
            'title' => 'Jasmine Jollof',
            'cuisine' => 'ghanaian',
            'steps' => '1. Wash rice. 2. Cook in sauce.',
            'base_servings' => 4,
            'status' => Recipe::STATUS_APPROVED,
        ]);

        RecipeIngredient::create([
            'recipe_id' => $recipe->id,
            'ingredient_id' => $ing->id,
            'role' => 'required',
            'people_per_pack' => 4,
        ]);

        \Livewire\Livewire::test(\App\Livewire\Store\Kitchen\RecipeDetail::class, ['recipe' => $recipe])
            ->assertStatus(200)
            ->assertSee('Jasmine Jollof')
            ->assertSee('4 Servings')
            ->call('incrementPeople')
            ->assertSet('people', 5)
            ->call('addIngredientsToCart')
            ->assertDispatched('cart-updated');

        $this->assertEquals(2, app(Cart::class)->count()); // 5 people with 4/pack = 2 packs
    }

    public function test_recipe_detail_returns_404_for_draft_recipes(): void
    {
        $draftRecipe = Recipe::create([
            'name' => 'Secret Test Dish',
            'slug' => 'secret-test-dish',
            'cuisine' => 'nigerian',
            'status' => Recipe::STATUS_DRAFT,
        ]);

        $this->get('/kitchen/secret-test-dish')->assertNotFound();
    }

    public function test_cook_with_what_i_have_interactivity(): void
    {
        $var1 = $this->createProductWithVariation('Palm Oil Bottle', 700, 10);
        $var2 = $this->createProductWithVariation('Yam Tubers', 900, 10);

        $ing1 = Ingredient::create(['name' => 'Palm Oil', 'slug' => 'palm-oil', 'status' => 'active']);
        $ing2 = Ingredient::create(['name' => 'Yam', 'slug' => 'yam', 'status' => 'active']);

        IngredientProduct::create(['ingredient_id' => $ing1->id, 'product_variation_id' => $var1->id, 'rank' => 1, 'pack_factor' => 1]);
        IngredientProduct::create(['ingredient_id' => $ing2->id, 'product_variation_id' => $var2->id, 'rank' => 1, 'pack_factor' => 1]);

        $recipe = Recipe::create([
            'name' => 'Boiled Yam and Palm Oil',
            'slug' => 'boiled-yam-and-palm-oil',
            'title' => 'Boiled Yam and Palm Oil',
            'cuisine' => 'nigerian',
            'steps' => '1. Boil yam. 2. Dip in seasoned palm oil.',
            'base_servings' => 2,
            'status' => Recipe::STATUS_APPROVED,
        ]);

        RecipeIngredient::create(['recipe_id' => $recipe->id, 'ingredient_id' => $ing1->id, 'role' => 'required', 'people_per_pack' => 2]);
        RecipeIngredient::create(['recipe_id' => $recipe->id, 'ingredient_id' => $ing2->id, 'role' => 'required', 'people_per_pack' => 2]);

        \Livewire\Livewire::test(\App\Livewire\Store\Kitchen\CookWithWhatIHave::class)
            ->assertStatus(200)
            ->call('addIngredient', $ing1->id)
            ->assertSee('Boiled Yam and Palm Oil')
            ->assertSee('50% Match')
            ->call('addMissingToCart', $recipe->id)
            ->assertDispatched('cart-updated');

        // Only the missing ingredient (Yam) was added to the cart, scaled for 4 people (2 packs of 2-person portions)
        $this->assertEquals(2, app(Cart::class)->count());
        $this->assertEquals(1800, app(Cart::class)->subtotalCents());
    }
}
